<?php

namespace Tests\Feature\Quran;

use App\Models\AttendanceReason;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Employee;
use App\Models\QuranAttendance;
use App\Models\QuranClass;
use App\Models\QuranTeacherAttendance;
use App\Models\Teacher;
use App\Models\User;
use App\Services\ReportService;
use Illuminate\Support\Facades\App;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Teacher/qari absence tracking — the "class not held" flow off the Mark
 * Attendance screen: no student is marked absent, but the teacher's own
 * absence is recorded in its own table for reporting.
 */
class QuranTeacherAttendanceTest extends TestCase
{
    private function admin(): User
    {
        return $this->createUserWithCompany([
            'quran.attendance.view', 'quran.attendance.create',
            'quran.attendance.update', 'quran.attendance.delete',
        ]);
    }

    private function makeClass(User $user, bool $withTeacher = true): QuranClass
    {
        $branch = Branch::factory()->create(['company_id' => $user->company_id]);
        $teacher = $withTeacher ? Teacher::factory()->create(['company_id' => $user->company_id]) : null;

        return QuranClass::factory()->create([
            'company_id' => $user->company_id,
            'branch_id' => $branch->id,
            'teacher_id' => $teacher?->id,
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

    // ── Marking teacher absent ───────────────────────────────────────────

    public function test_marking_teacher_absent_creates_teacher_attendance_row(): void
    {
        $user = $this->admin();
        $class = $this->makeClass($user);
        $employee = $this->addActiveMember($class, $user);
        $reason = AttendanceReason::factory()->create(['company_id' => $user->company_id]);
        $date = $this->attendanceDate();

        $this->actingAs($user)
            ->post(route('quran-attendance.store'), [
                'date' => $date,
                'class_id' => $class->id,
                'attendance' => [$employee->id => null],
                'teacher_absent' => '1',
                'teacher_absence_reason_id' => $reason->id,
                'teacher_absence_remarks' => 'Sick leave',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('quran_teacher_attendance', [
            'company_id' => $user->company_id,
            'class_id' => $class->id,
            'teacher_id' => $class->teacher_id,
            'attendance_reason_id' => $reason->id,
            'remarks' => 'Sick leave',
        ]);
    }

    /**
     * Regression guard: markAbsent() used to search via a raw
     * where($attributes) that never passed attendance_date through the date
     * cast, while its create half did — re-marking the same class+date
     * absent a second time with a different reason hit the unique
     * constraint trying to insert a duplicate row instead of updating.
     */
    public function test_remarking_teacher_absent_same_date_updates_the_same_row_rather_than_duplicating(): void
    {
        $user = $this->admin();
        $class = $this->makeClass($user);
        $employee = $this->addActiveMember($class, $user);
        $firstReason = AttendanceReason::factory()->create(['company_id' => $user->company_id]);
        $secondReason = AttendanceReason::factory()->create(['company_id' => $user->company_id]);
        $date = $this->attendanceDate();

        $this->actingAs($user)->post(route('quran-attendance.store'), [
            'date' => $date,
            'class_id' => $class->id,
            'attendance' => [$employee->id => null],
            'teacher_absent' => '1',
            'teacher_absence_reason_id' => $firstReason->id,
        ])->assertRedirect();

        $this->actingAs($user)->post(route('quran-attendance.store'), [
            'date' => $date,
            'class_id' => $class->id,
            'attendance' => [$employee->id => null],
            'teacher_absent' => '1',
            'teacher_absence_reason_id' => $secondReason->id,
        ])->assertRedirect();

        $this->assertDatabaseCount('quran_teacher_attendance', 1);
        $this->assertDatabaseHas('quran_teacher_attendance', [
            'class_id' => $class->id,
            'attendance_reason_id' => $secondReason->id,
        ]);
    }

    public function test_marking_teacher_absent_sets_class_held_false_and_null_reason_on_every_student_row(): void
    {
        $user = $this->admin();
        $class = $this->makeClass($user);
        $employee = $this->addActiveMember($class, $user);
        $reason = AttendanceReason::factory()->create(['company_id' => $user->company_id]);
        $studentReason = AttendanceReason::factory()->create(['company_id' => $user->company_id]);
        $date = $this->attendanceDate();

        $this->actingAs($user)
            ->post(route('quran-attendance.store'), [
                'date' => $date,
                'class_id' => $class->id,
                // Even though a per-student reason is submitted, the server
                // must ignore it because the class did not happen.
                'attendance' => [$employee->id => $studentReason->id],
                'teacher_absent' => '1',
                'teacher_absence_reason_id' => $reason->id,
            ])
            ->assertRedirect();

        $attendance = QuranAttendance::query()
            ->where('class_id', $class->id)
            ->where('employee_id', $employee->id)
            ->whereDate('attendance_date', $date)
            ->firstOrFail();

        $this->assertFalse($attendance->isClassHeld());
        $this->assertNull($attendance->attendance_reason_id);
        $this->assertFalse($attendance->isPresent());
    }

    public function test_teacher_absence_reason_is_required_when_teacher_absent(): void
    {
        $user = $this->admin();
        $class = $this->makeClass($user);
        $employee = $this->addActiveMember($class, $user);

        $this->actingAs($user)
            ->post(route('quran-attendance.store'), [
                'date' => $this->attendanceDate(),
                'class_id' => $class->id,
                'attendance' => [$employee->id => null],
                'teacher_absent' => '1',
            ])
            ->assertSessionHasErrors(['teacher_absence_reason_id']);

        $this->assertDatabaseCount('quran_teacher_attendance', 0);
    }

    public function test_teacher_absent_rejected_when_class_has_no_teacher(): void
    {
        $user = $this->admin();
        $class = $this->makeClass($user, withTeacher: false);
        $employee = $this->addActiveMember($class, $user);
        $reason = AttendanceReason::factory()->create(['company_id' => $user->company_id]);

        $this->actingAs($user)
            ->post(route('quran-attendance.store'), [
                'date' => $this->attendanceDate(),
                'class_id' => $class->id,
                'attendance' => [$employee->id => null],
                'teacher_absent' => '1',
                'teacher_absence_reason_id' => $reason->id,
            ])
            ->assertSessionHasErrors(['teacher_absent']);

        $this->assertDatabaseCount('quran_teacher_attendance', 0);
    }

    public function test_unmarking_teacher_absent_on_resave_deletes_teacher_attendance_and_restores_normal_marking(): void
    {
        $user = $this->admin();
        $class = $this->makeClass($user);
        $employee = $this->addActiveMember($class, $user);
        $reason = AttendanceReason::factory()->create(['company_id' => $user->company_id]);
        $studentReason = AttendanceReason::factory()->create(['company_id' => $user->company_id]);
        $date = $this->attendanceDate();

        $this->actingAs($user)->post(route('quran-attendance.store'), [
            'date' => $date,
            'class_id' => $class->id,
            'attendance' => [$employee->id => null],
            'teacher_absent' => '1',
            'teacher_absence_reason_id' => $reason->id,
        ])->assertRedirect();

        $this->assertDatabaseCount('quran_teacher_attendance', 1);

        $this->actingAs($user)->post(route('quran-attendance.store'), [
            'date' => $date,
            'class_id' => $class->id,
            'attendance' => [$employee->id => $studentReason->id],
            'teacher_absent' => '0',
        ])->assertRedirect();

        $this->assertDatabaseCount('quran_teacher_attendance', 0);

        $attendance = QuranAttendance::query()
            ->where('class_id', $class->id)
            ->where('employee_id', $employee->id)
            ->whereDate('attendance_date', $date)
            ->firstOrFail();

        $this->assertTrue($attendance->isClassHeld());
        $this->assertSame($studentReason->id, $attendance->attendance_reason_id);
    }

    // ── Reports ───────────────────────────────────────────────────────────

    public function test_quran_attendance_summary_excludes_class_not_held_from_report_service(): void
    {
        $user = $this->admin();
        $class = $this->makeClass($user);
        $presentEmployee = $this->addActiveMember($class, $user);
        $notHeldEmployee = $this->addActiveMember($class, $user);
        $reason = AttendanceReason::factory()->create(['company_id' => $user->company_id]);

        QuranAttendance::factory()->create([
            'company_id' => $user->company_id,
            'class_id' => $class->id,
            'employee_id' => $presentEmployee->id,
            'attendance_reason_id' => null,
            'class_held' => true,
        ]);

        QuranAttendance::factory()->create([
            'company_id' => $user->company_id,
            'class_id' => $class->id,
            'employee_id' => $notHeldEmployee->id,
            'attendance_reason_id' => null,
            'class_held' => false,
        ]);

        $this->actingAs($user);
        $summary = App::make(ReportService::class)->quranAttendanceSummary(['class_id' => $class->id]);

        $this->assertSame(1, $summary['total']);
        $this->assertSame(1, $summary['present']);
        $this->assertSame(0, $summary['absent']);
    }

    // ── Company isolation & access control ───────────────────────────────

    public function test_teacher_attendance_company_isolation(): void
    {
        $userA = $this->admin();
        $classA = $this->makeClass($userA);
        QuranTeacherAttendance::factory()->create([
            'company_id' => $userA->company_id,
            'class_id' => $classA->id,
            'teacher_id' => $classA->teacher_id,
        ]);

        $companyB = Company::factory()->create();
        $classB = QuranClass::factory()->create(['company_id' => $companyB->id]);
        QuranTeacherAttendance::factory()->create([
            'company_id' => $companyB->id,
            'class_id' => $classB->id,
        ]);

        $this->actingAs($userA);
        $this->assertSame(1, QuranTeacherAttendance::count());
    }

    public function test_quran_teacher_role_only_sees_own_teacher_attendance(): void
    {
        $company = Company::factory()->create();
        $branch = Branch::factory()->create(['company_id' => $company->id]);

        $user = $this->createRoleUser($company, 'Quran Teacher', [
            'quran.attendance.view', 'quran.attendance.create', 'quran.attendance.update',
        ]);
        $teacherEmployee = Employee::factory()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'branch_id' => $branch->id,
        ]);
        $teacher = Teacher::factory()->create([
            'company_id' => $company->id,
            'employee_id' => $teacherEmployee->id,
        ]);
        $otherTeacher = Teacher::factory()->create([
            'company_id' => $company->id,
            'employee_id' => Employee::factory()->create([
                'company_id' => $company->id,
                'branch_id' => $branch->id,
            ])->id,
        ]);

        $ownClass = QuranClass::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'teacher_id' => $teacher->id,
        ]);
        $otherClass = QuranClass::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'teacher_id' => $otherTeacher->id,
        ]);

        QuranTeacherAttendance::factory()->create([
            'company_id' => $company->id,
            'class_id' => $ownClass->id,
            'teacher_id' => $teacher->id,
        ]);
        QuranTeacherAttendance::factory()->create([
            'company_id' => $company->id,
            'class_id' => $otherClass->id,
            'teacher_id' => $otherTeacher->id,
        ]);

        $this->actingAs($user);
        $this->assertSame(1, QuranTeacherAttendance::count());
        $this->assertSame($ownClass->id, QuranTeacherAttendance::first()->class_id);
    }

    public function test_store_requires_create_or_update_permission_for_teacher_absence(): void
    {
        $user = $this->createUserWithCompany(['quran.attendance.view']);
        $class = $this->makeClass($user);
        $employee = $this->addActiveMember($class, $user);
        $reason = AttendanceReason::factory()->create(['company_id' => $user->company_id]);

        $this->actingAs($user)
            ->post(route('quran-attendance.store'), [
                'date' => $this->attendanceDate(),
                'class_id' => $class->id,
                'attendance' => [$employee->id => null],
                'teacher_absent' => '1',
                'teacher_absence_reason_id' => $reason->id,
            ])
            ->assertForbidden();
    }
}
