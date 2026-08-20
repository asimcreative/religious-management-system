<?php

namespace Tests\Feature\Salah;

use App\Models\AttendanceReason;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Jamaat;
use App\Models\JamaatTaleem;
use App\Models\Prayer;
use App\Models\SalahAttendance;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Jamaat Taleem — whether the day's teaching session was held, recorded
 * alongside (but independently of) prayer attendance.
 */
class JamaatTaleemTest extends TestCase
{
    private function admin(): User
    {
        return $this->createUserWithCompany([
            'salah.attendance.view', 'salah.attendance.create', 'salah.attendance.update',
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

    private function createRoleUser(Company $company, string $roleName, array $permissions): User
    {
        $user = User::factory()->create(['company_id' => $company->id]);
        app(PermissionRegistrar::class)->setPermissionsTeamId($company->id);

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $role = Role::findOrCreate($roleName, 'web');
        $role->syncPermissions($permissions);
        $user->assignRole($role);

        return $user;
    }

    private function storePayload(Jamaat $jamaat, string $date, Employee $employee, int $prayerId): array
    {
        return [
            'date' => $date,
            'jamaat_id' => $jamaat->id,
            'attendance' => [$employee->id => [$prayerId => null]],
        ];
    }

    // ── Marking Taleem ────────────────────────────────────────────────────

    public function test_saving_attendance_defaults_taleem_to_held_with_no_reason(): void
    {
        $user = $this->admin();
        $jamaat = $this->makeJamaat($user);
        $prayer = Prayer::factory()->create();
        $employee = $this->addActiveMember($jamaat, $user);
        $date = $this->attendanceDate();

        $this->actingAs($user)
            ->post(route('salah-attendance.store'), $this->storePayload($jamaat, $date, $employee, $prayer->id))
            ->assertRedirect();

        $this->assertDatabaseHas('jamaat_taleem', [
            'company_id' => $user->company_id,
            'jamaat_id' => $jamaat->id,
            'leader_id' => $jamaat->leader_id,
            'held' => 1,
            'attendance_reason_id' => null,
        ]);
    }

    public function test_marking_taleem_not_held_requires_a_reason(): void
    {
        $user = $this->admin();
        $jamaat = $this->makeJamaat($user);
        $prayer = Prayer::factory()->create();
        $employee = $this->addActiveMember($jamaat, $user);

        $payload = $this->storePayload($jamaat, $this->attendanceDate(), $employee, $prayer->id);
        $payload['taleem_held'] = '0';

        $this->actingAs($user)
            ->post(route('salah-attendance.store'), $payload)
            ->assertSessionHasErrors(['taleem_reason_id']);

        $this->assertDatabaseCount('jamaat_taleem', 0);
    }

    public function test_marking_taleem_not_held_with_a_reason_is_recorded(): void
    {
        $user = $this->admin();
        $jamaat = $this->makeJamaat($user);
        $prayer = Prayer::factory()->create();
        $employee = $this->addActiveMember($jamaat, $user);
        $reason = AttendanceReason::factory()->create(['company_id' => $user->company_id]);
        $date = $this->attendanceDate();

        $payload = $this->storePayload($jamaat, $date, $employee, $prayer->id);
        $payload['taleem_held'] = '0';
        $payload['taleem_reason_id'] = $reason->id;
        $payload['taleem_remarks'] = 'Qari sahab out of town';

        $this->actingAs($user)
            ->post(route('salah-attendance.store'), $payload)
            ->assertRedirect();

        $this->assertDatabaseHas('jamaat_taleem', [
            'company_id' => $user->company_id,
            'jamaat_id' => $jamaat->id,
            'held' => 0,
            'attendance_reason_id' => $reason->id,
            'remarks' => 'Qari sahab out of town',
        ]);
    }

    public function test_taleem_does_not_affect_prayer_attendance(): void
    {
        $user = $this->admin();
        $jamaat = $this->makeJamaat($user);
        $prayer = Prayer::factory()->create();
        $employee = $this->addActiveMember($jamaat, $user);
        $reason = AttendanceReason::factory()->create(['company_id' => $user->company_id]);
        $date = $this->attendanceDate();

        $payload = $this->storePayload($jamaat, $date, $employee, $prayer->id);
        $payload['taleem_held'] = '0';
        $payload['taleem_reason_id'] = $reason->id;

        $this->actingAs($user)
            ->post(route('salah-attendance.store'), $payload)
            ->assertRedirect();

        $this->assertDatabaseHas('salah_attendance', [
            'jamaat_id' => $jamaat->id,
            'employee_id' => $employee->id,
            'prayer_id' => $prayer->id,
            'attendance_reason_id' => null,
        ]);
    }

    public function test_resaving_updates_the_same_taleem_row_rather_than_duplicating(): void
    {
        $user = $this->admin();
        $jamaat = $this->makeJamaat($user);
        $prayer = Prayer::factory()->create();
        $employee = $this->addActiveMember($jamaat, $user);
        $reason = AttendanceReason::factory()->create(['company_id' => $user->company_id]);
        $date = $this->attendanceDate();

        $this->actingAs($user)->post(route('salah-attendance.store'), $this->storePayload($jamaat, $date, $employee, $prayer->id));
        $this->assertDatabaseCount('jamaat_taleem', 1);

        $payload = $this->storePayload($jamaat, $date, $employee, $prayer->id);
        $payload['taleem_held'] = '0';
        $payload['taleem_reason_id'] = $reason->id;

        $this->actingAs($user)->post(route('salah-attendance.store'), $payload)->assertRedirect();

        $this->assertDatabaseCount('jamaat_taleem', 1);
        $this->assertDatabaseHas('jamaat_taleem', [
            'jamaat_id' => $jamaat->id,
            'held' => 0,
            'attendance_reason_id' => $reason->id,
        ]);
    }

    public function test_store_requires_create_or_update_permission_for_taleem(): void
    {
        $user = $this->createUserWithCompany(['salah.attendance.view']);
        $jamaat = $this->makeJamaat($user);
        $prayer = Prayer::factory()->create();
        $employee = $this->addActiveMember($jamaat, $user);

        $this->actingAs($user)
            ->post(route('salah-attendance.store'), $this->storePayload($jamaat, $this->attendanceDate(), $employee, $prayer->id))
            ->assertForbidden();

        $this->assertDatabaseCount('jamaat_taleem', 0);
    }

    // ── The attendance history listing shows Taleem status ─────────────────

    public function test_the_attendance_history_listing_shows_taleem_was_held(): void
    {
        $user = $this->admin();
        $jamaat = $this->makeJamaat($user);
        $prayer = Prayer::factory()->create();
        $employee = $this->addActiveMember($jamaat, $user);

        $this->actingAs($user)
            ->post(route('salah-attendance.store'), $this->storePayload($jamaat, $this->attendanceDate(), $employee, $prayer->id))
            ->assertRedirect();

        $this->actingAs($user)
            ->get(route('salah-attendance.index'))
            ->assertOk()
            ->assertSee(__('salah_attendance.taleem_held_short'));
    }

    public function test_the_attendance_history_listing_shows_the_taleem_reason_when_not_held(): void
    {
        $user = $this->admin();
        $jamaat = $this->makeJamaat($user);
        $prayer = Prayer::factory()->create();
        $employee = $this->addActiveMember($jamaat, $user);
        $reason = AttendanceReason::factory()->create([
            'company_id' => $user->company_id,
            'reason_name' => 'Qari on leave',
        ]);

        $payload = $this->storePayload($jamaat, $this->attendanceDate(), $employee, $prayer->id);
        $payload['taleem_held'] = '0';
        $payload['taleem_reason_id'] = $reason->id;

        $this->actingAs($user)
            ->post(route('salah-attendance.store'), $payload)
            ->assertRedirect();

        $this->actingAs($user)
            ->get(route('salah-attendance.index'))
            ->assertOk()
            ->assertSee('Qari on leave')
            ->assertDontSee(__('salah_attendance.taleem_held_short'));
    }

    public function test_the_attendance_history_listing_shows_a_dash_when_taleem_was_never_recorded(): void
    {
        $user = $this->admin();
        $jamaat = $this->makeJamaat($user);
        $employee = $this->addActiveMember($jamaat, $user);
        $prayer = Prayer::factory()->create();

        SalahAttendance::factory()->create([
            'company_id' => $user->company_id,
            'jamaat_id' => $jamaat->id,
            'leader_id' => $jamaat->leader_id,
            'employee_id' => $employee->id,
            'prayer_id' => $prayer->id,
            'attendance_date' => $this->attendanceDate(),
            'attendance_reason_id' => null,
        ]);

        $this->actingAs($user)
            ->get(route('salah-attendance.index'))
            ->assertOk()
            ->assertSee(__('salah_attendance.not_recorded'));
    }

    // ── Company isolation & access control ───────────────────────────────

    public function test_taleem_company_isolation(): void
    {
        $userA = $this->admin();
        $jamaatA = $this->makeJamaat($userA);
        JamaatTaleem::factory()->create([
            'company_id' => $userA->company_id,
            'jamaat_id' => $jamaatA->id,
        ]);

        $companyB = Company::factory()->create();
        $jamaatB = Jamaat::factory()->create(['company_id' => $companyB->id]);
        JamaatTaleem::factory()->create([
            'company_id' => $companyB->id,
            'jamaat_id' => $jamaatB->id,
        ]);

        $this->actingAs($userA);
        $this->assertSame(1, JamaatTaleem::count());
    }

    public function test_jamaat_leader_role_only_sees_own_jamaats_taleem(): void
    {
        $company = Company::factory()->create();
        $branch = Branch::factory()->create(['company_id' => $company->id]);

        $user = $this->createRoleUser($company, 'Jamaat Leader', [
            'salah.attendance.view', 'salah.attendance.create', 'salah.attendance.update',
        ]);
        $leaderEmployee = Employee::factory()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'branch_id' => $branch->id,
        ]);

        $ownJamaat = Jamaat::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'leader_id' => $leaderEmployee->id,
        ]);
        $otherJamaat = Jamaat::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'leader_id' => Employee::factory()->create(['company_id' => $company->id, 'branch_id' => $branch->id])->id,
        ]);

        JamaatTaleem::factory()->create(['company_id' => $company->id, 'jamaat_id' => $ownJamaat->id]);
        JamaatTaleem::factory()->create(['company_id' => $company->id, 'jamaat_id' => $otherJamaat->id]);

        $this->actingAs($user);
        $this->assertSame(1, JamaatTaleem::count());
        $this->assertSame($ownJamaat->id, JamaatTaleem::first()->jamaat_id);
    }
}
