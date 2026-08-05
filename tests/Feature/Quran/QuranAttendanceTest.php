<?php

namespace Tests\Feature\Quran;

use App\Models\AttendanceReason;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Employee;
use App\Models\QuranAttendance;
use App\Models\QuranClass;
use App\Models\Teacher;
use App\Models\User;
use Tests\TestCase;

/**
 * Quran Attendance — recording, viewing, company isolation.
 */
class QuranAttendanceTest extends TestCase
{
    private function admin(): User
    {
        return $this->createUserWithCompany([
            'quran.attendance.view', 'quran.attendance.create',
            'quran.attendance.update', 'quran.attendance.delete',
        ]);
    }

    private function makeClass(User $user): QuranClass
    {
        $branch = Branch::factory()->create(['company_id' => $user->company_id]);
        $teacher = Teacher::factory()->create(['company_id' => $user->company_id]);

        return QuranClass::factory()->create([
            'company_id' => $user->company_id,
            'branch_id' => $branch->id,
            'teacher_id' => $teacher->id,
        ]);
    }

    private function addActiveMember(QuranClass $class, User $user): Employee
    {
        $employee = Employee::factory()->create(['company_id' => $user->company_id]);

        $class->members()->attach($employee->id, [
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
        $this->get(route('quran-attendance.index'))->assertRedirect(route('login'));
    }

    public function test_attendance_index_requires_view_permission(): void
    {
        $user = $this->createUserWithCompany();
        $this->actingAs($user)->get(route('quran-attendance.index'))->assertForbidden();
    }

    public function test_attendance_index_returns_ok(): void
    {
        $user = $this->createUserWithCompany(['quran.attendance.view']);
        $this->actingAs($user)->get(route('quran-attendance.index'))->assertOk();
    }

    // ── Create / Store ────────────────────────────────────────────────────

    public function test_create_page_accessible_with_permission(): void
    {
        $user = $this->admin();
        $this->actingAs($user)->get(route('quran-attendance.create'))->assertOk();
    }

    public function test_store_records_attendance(): void
    {
        $user = $this->admin();
        $class = $this->makeClass($user);
        $employee = $this->addActiveMember($class, $user);
        $date = $this->attendanceDate();

        $this->actingAs($user)
            ->post(route('quran-attendance.store'), [
                'date' => $date,
                'class_id' => $class->id,
                'attendance' => [$employee->id => null],
            ])
            ->assertRedirect();

        $this->assertTrue(QuranAttendance::query()
            ->where('company_id', $user->company_id)
            ->whereDate('attendance_date', $date)
            ->where('class_id', $class->id)
            ->where('employee_id', $employee->id)
            ->whereNull('attendance_reason_id')
            ->exists());
    }

    public function test_store_requires_create_permission(): void
    {
        $user = $this->createUserWithCompany(['quran.attendance.view']);
        $class = $this->makeClass($user);
        $employee = $this->addActiveMember($class, $user);

        $this->actingAs($user)
            ->post(route('quran-attendance.store'), [
                'date' => $this->attendanceDate(),
                'class_id' => $class->id,
                'attendance' => [$employee->id => null],
            ])
            ->assertForbidden();
    }

    public function test_store_fails_with_missing_required_fields(): void
    {
        $user = $this->admin();
        $this->actingAs($user)
            ->post(route('quran-attendance.store'), [])
            ->assertSessionHasErrors(['date', 'class_id', 'attendance']);
    }

    public function test_store_rejects_class_from_other_company(): void
    {
        $user = $this->admin();
        $companyB = Company::factory()->create();
        $classB = QuranClass::factory()->create(['company_id' => $companyB->id]);
        $employee = Employee::factory()->create(['company_id' => $user->company_id]);

        $this->actingAs($user)
            ->post(route('quran-attendance.store'), [
                'date' => $this->attendanceDate(),
                'class_id' => $classB->id,
                'attendance' => [$employee->id => null],
            ])
            ->assertSessionHasErrors('class_id');
    }

    public function test_attendance_company_isolation_on_list(): void
    {
        $userA = $this->createUserWithCompany(['quran.attendance.view']);
        QuranAttendance::factory()->create(['company_id' => $userA->company_id]);

        $companyB = Company::factory()->create();
        QuranAttendance::factory()->create(['company_id' => $companyB->id]);

        $this->actingAs($userA);
        $this->assertSame(1, QuranAttendance::count());
    }

    public function test_present_attendance_has_null_reason(): void
    {
        $user = $this->admin();
        $class = $this->makeClass($user);
        $employee = Employee::factory()->create(['company_id' => $user->company_id]);

        $attendance = QuranAttendance::factory()->create([
            'company_id' => $user->company_id,
            'class_id' => $class->id,
            'employee_id' => $employee->id,
            'attendance_reason_id' => null,
        ]);

        $this->assertTrue($attendance->isPresent());
    }

    public function test_absent_attendance_has_reason_id(): void
    {
        $user = $this->admin();
        $class = $this->makeClass($user);
        $employee = Employee::factory()->create(['company_id' => $user->company_id]);
        $reason = AttendanceReason::factory()->create(['company_id' => $user->company_id]);

        $attendance = QuranAttendance::factory()->create([
            'company_id' => $user->company_id,
            'class_id' => $class->id,
            'employee_id' => $employee->id,
            'attendance_reason_id' => $reason->id,
        ]);

        $this->assertFalse($attendance->isPresent());
    }
}
