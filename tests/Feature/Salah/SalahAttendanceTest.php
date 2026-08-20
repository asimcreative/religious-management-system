<?php

namespace Tests\Feature\Salah;

use App\Http\Resources\Api\SalahAttendanceResource;
use App\Models\AttendanceReason;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Jamaat;
use App\Models\Prayer;
use App\Models\SalahAttendance;
use App\Models\User;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * Salah Attendance — recording per-prayer, company isolation.
 */
class SalahAttendanceTest extends TestCase
{
    private function admin(): User
    {
        return $this->createUserWithCompany([
            'salah.attendance.view', 'salah.attendance.create',
            'salah.attendance.update', 'salah.attendance.delete',
        ]);
    }

    private function makeJamaat(User $user): Jamaat
    {
        $branch = Branch::factory()->create(['company_id' => $user->company_id]);
        $leader = Employee::factory()->create(['company_id' => $user->company_id]);

        return Jamaat::factory()->create([
            'company_id' => $user->company_id,
            'branch_id' => $branch->id,
            'leader_id' => $leader->id,
        ]);
    }

    private function addActiveMember(Jamaat $jamaat, User $user): Employee
    {
        $employee = Employee::factory()->create(['company_id' => $user->company_id]);

        $jamaat->members()->attach($employee->id, [
            'is_active' => true,
            'joined_at' => now('Asia/Karachi')->toDateString(),
        ]);

        return $employee;
    }

    private function attendanceDate(): string
    {
        return now('Asia/Karachi')->toDateString();
    }

    // ── Index ──────────────────────────────────────────────────────────────

    public function test_attendance_index_requires_auth(): void
    {
        $this->get(route('salah-attendance.index'))->assertRedirect(route('login'));
    }

    public function test_attendance_index_requires_permission(): void
    {
        $user = $this->createUserWithCompany();
        $this->actingAs($user)->get(route('salah-attendance.index'))->assertForbidden();
    }

    public function test_attendance_index_returns_ok(): void
    {
        $user = $this->createUserWithCompany(['salah.attendance.view']);
        $this->actingAs($user)->get(route('salah-attendance.index'))->assertOk();
    }

    /**
     * The mosque's own vocabulary for prayer attendance is "Prayed"/"Not
     * Prayed", not "Present"/"Absent" — the blank/default option on this
     * screen is the one place that wording is hardcoded (every reason name is
     * the tenant's own free text), so it has to say what they mean.
     */
    public function test_mark_attendance_uses_prayed_wording_not_present(): void
    {
        $user = $this->admin();
        $jamaat = $this->makeJamaat($user);
        $this->addActiveMember($jamaat, $user);
        Prayer::factory()->create();

        $this->actingAs($user)
            ->get(route('salah-attendance.create', ['jamaat_id' => $jamaat->id, 'date' => $this->attendanceDate()]))
            ->assertOk()
            ->assertSee('Prayed');
    }

    // ── Store ─────────────────────────────────────────────────────────────

    public function test_store_records_salah_attendance(): void
    {
        $user = $this->admin();
        $jamaat = $this->makeJamaat($user);
        $prayer = Prayer::factory()->create();
        $employee = $this->addActiveMember($jamaat, $user);
        $date = $this->attendanceDate();

        $this->actingAs($user)
            ->post(route('salah-attendance.store'), [
                'date' => $date,
                'jamaat_id' => $jamaat->id,
                'attendance' => [$employee->id => [$prayer->id => null]],
            ])
            ->assertRedirect();

        $this->assertTrue(SalahAttendance::query()
            ->where('company_id', $user->company_id)
            ->whereDate('attendance_date', $date)
            ->where('prayer_id', $prayer->id)
            ->where('jamaat_id', $jamaat->id)
            ->where('employee_id', $employee->id)
            ->whereNull('attendance_reason_id')
            ->exists());
    }

    public function test_store_requires_create_permission(): void
    {
        $user = $this->createUserWithCompany(['salah.attendance.view']);
        $jamaat = $this->makeJamaat($user);
        $prayer = Prayer::factory()->create();
        $employee = $this->addActiveMember($jamaat, $user);

        $this->actingAs($user)
            ->post(route('salah-attendance.store'), [
                'date' => $this->attendanceDate(),
                'jamaat_id' => $jamaat->id,
                'attendance' => [$employee->id => [$prayer->id => null]],
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('salah_attendance', 0);
    }

    public function test_store_fails_without_required_fields(): void
    {
        $user = $this->admin();
        $this->actingAs($user)
            ->post(route('salah-attendance.store'), [])
            ->assertSessionHasErrors(['date', 'jamaat_id', 'attendance']);
    }

    public function test_store_rejects_jamaat_from_other_company(): void
    {
        $user = $this->admin();
        $companyB = Company::factory()->create();
        $jamaatB = Jamaat::factory()->create(['company_id' => $companyB->id]);

        $this->actingAs($user)
            ->post(route('salah-attendance.store'), [
                'date' => $this->attendanceDate(),
                'jamaat_id' => $jamaatB->id,
                'attendance' => [],
            ])
            ->assertSessionHasErrors('jamaat_id');
    }

    // ── Company Isolation ─────────────────────────────────────────────────

    public function test_attendance_query_scoped_to_company(): void
    {
        $userA = $this->createUserWithCompany(['salah.attendance.view']);
        SalahAttendance::factory()->create(['company_id' => $userA->company_id]);

        $companyB = Company::factory()->create();
        SalahAttendance::factory()->create(['company_id' => $companyB->id]);

        $this->actingAs($userA);
        $this->assertSame(1, SalahAttendance::count());
    }

    // ── Business rules ────────────────────────────────────────────────────

    public function test_attendance_with_null_reason_is_present(): void
    {
        $user = $this->admin();
        $jamaat = $this->makeJamaat($user);
        $prayer = Prayer::factory()->create();
        $employee = Employee::factory()->create(['company_id' => $user->company_id]);

        $attendance = SalahAttendance::factory()->create([
            'company_id' => $user->company_id,
            'jamaat_id' => $jamaat->id,
            'prayer_id' => $prayer->id,
            'employee_id' => $employee->id,
            'leader_id' => $jamaat->leader_id,
            'attendance_reason_id' => null,
        ]);

        $this->assertTrue($attendance->isPresent());
    }

    public function test_attendance_with_reason_is_absent(): void
    {
        $user = $this->admin();
        $jamaat = $this->makeJamaat($user);
        $prayer = Prayer::factory()->create();
        $employee = Employee::factory()->create(['company_id' => $user->company_id]);
        $reason = AttendanceReason::factory()->create(['company_id' => $user->company_id]);

        $attendance = SalahAttendance::factory()->create([
            'company_id' => $user->company_id,
            'jamaat_id' => $jamaat->id,
            'prayer_id' => $prayer->id,
            'employee_id' => $employee->id,
            'leader_id' => $jamaat->leader_id,
            'attendance_reason_id' => $reason->id,
        ]);

        $this->assertFalse($attendance->isPresent());
    }

    public function test_is_absent_is_false_for_a_null_reason(): void
    {
        $attendance = SalahAttendance::factory()->make(['attendance_reason_id' => null]);

        $this->assertFalse($attendance->isAbsent());
    }

    public function test_is_absent_is_false_for_a_reason_that_does_not_count_as_absent(): void
    {
        $user = $this->admin();
        $reason = AttendanceReason::factory()->leave()->create(['company_id' => $user->company_id]);
        $attendance = SalahAttendance::factory()->create([
            'company_id' => $user->company_id,
            'attendance_reason_id' => $reason->id,
        ]);

        $this->assertFalse($attendance->isAbsent());
    }

    public function test_is_absent_is_true_for_a_reason_that_counts_as_absent(): void
    {
        $user = $this->admin();
        $reason = AttendanceReason::factory()->create(['company_id' => $user->company_id]);
        $attendance = SalahAttendance::factory()->create([
            'company_id' => $user->company_id,
            'attendance_reason_id' => $reason->id,
        ]);

        $this->assertTrue($attendance->isAbsent());
    }

    /**
     * is_present must never be repurposed — mobile apps already consume it —
     * so a corrected classification is added as a new field, is_absent,
     * rather than changing what is_present means.
     */
    public function test_api_resource_keeps_is_present_unchanged_and_adds_is_absent(): void
    {
        $user = $this->admin();
        $reason = AttendanceReason::factory()->leave()->create(['company_id' => $user->company_id]);
        $attendance = SalahAttendance::factory()->create([
            'company_id' => $user->company_id,
            'attendance_reason_id' => $reason->id,
        ]);

        $data = (new SalahAttendanceResource($attendance))->toArray(Request::create('/'));

        $this->assertFalse($data['is_present']);
        $this->assertFalse($data['is_absent']);
    }

    /** Five prayers can be recorded for the same date and jamaat. */
    public function test_five_prayers_can_be_recorded_same_date(): void
    {
        $user = $this->admin();
        $jamaat = $this->makeJamaat($user);
        $employee = Employee::factory()->create(['company_id' => $user->company_id]);

        $prayerNames = ['Fajr', 'Zuhr', 'Asr', 'Maghrib', 'Isha'];

        foreach ($prayerNames as $name) {
            $prayer = Prayer::factory()->create(['prayer_name' => $name]);
            SalahAttendance::factory()->create([
                'company_id' => $user->company_id,
                'jamaat_id' => $jamaat->id,
                'prayer_id' => $prayer->id,
                'employee_id' => $employee->id,
                'leader_id' => $jamaat->leader_id,
                'attendance_date' => '2026-08-01',
            ]);
        }

        $this->actingAs($user);
        $this->assertSame(5, SalahAttendance::whereDate('attendance_date', '2026-08-01')->count());
    }
}
