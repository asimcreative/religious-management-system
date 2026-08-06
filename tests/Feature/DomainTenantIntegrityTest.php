<?php

namespace Tests\Feature;

use App\Models\AttendanceReason;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Jamaat;
use App\Models\JamaatMember;
use App\Models\Prayer;
use App\Models\QuranAttendance;
use App\Models\QuranClass;
use App\Models\QuranClassMember;
use App\Models\QuranDepartment;
use App\Models\QuranProgress;
use App\Models\QuranStatus;
use App\Models\SalahAttendance;
use App\Models\Teacher;
use App\Services\EmployeeService;
use App\Services\JamaatService;
use App\Services\QuranClassService;
use App\Services\TeacherService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class DomainTenantIntegrityTest extends TestCase
{
    public function test_salah_prayer_summary_is_limited_to_the_requested_company(): void
    {
        $user = $this->createUserWithCompany(['report.salah']);
        app(PermissionRegistrar::class)->setPermissionsTeamId($user->company_id);
        $user->assignRole(Role::findOrCreate('Super Admin', 'web'));
        $this->assertFalse($user->isSystemAdministrator());

        $companyA = Company::findOrFail($user->company_id);
        $companyB = Company::factory()->create();
        $prayer = Prayer::factory()->create();
        $employeeA = Employee::factory()->create(['company_id' => $companyA->id]);
        $employeeB = Employee::factory()->create(['company_id' => $companyB->id]);

        SalahAttendance::factory()->create([
            'company_id' => $companyA->id,
            'prayer_id' => $prayer->id,
            'employee_id' => $employeeA->id,
        ]);
        SalahAttendance::factory()->create([
            'company_id' => $companyB->id,
            'prayer_id' => $prayer->id,
            'employee_id' => $employeeB->id,
        ]);

        $response = $this->actingAs($user)->get(route('reports.salah-attendance'));
        $summary = $response->viewData('prayerWise');

        $response->assertOk();
        $this->assertCount(1, $summary);
        $this->assertSame(1, (int) $summary->first()->total);
    }

    public function test_only_the_system_super_admin_gets_global_salah_report_data(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $permission = Permission::firstOrCreate(['name' => 'report.salah', 'guard_name' => 'web']);
        app(PermissionRegistrar::class)->setPermissionsTeamId($superAdmin->company_id);
        Role::findByName('Super Admin', 'web')->givePermissionTo($permission);

        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $prayer = Prayer::factory()->create();
        $employeeA = Employee::factory()->create(['company_id' => $companyA->id]);
        $employeeB = Employee::factory()->create(['company_id' => $companyB->id]);
        SalahAttendance::factory()->create([
            'company_id' => $companyA->id,
            'prayer_id' => $prayer->id,
            'employee_id' => $employeeA->id,
        ]);
        SalahAttendance::factory()->create([
            'company_id' => $companyB->id,
            'prayer_id' => $prayer->id,
            'employee_id' => $employeeB->id,
        ]);

        $response = $this->actingAs($superAdmin)->get(route('reports.salah-attendance'));
        $summary = $response->viewData('prayerWise');

        $response->assertOk();
        $this->assertCount(1, $summary);
        $this->assertSame(2, (int) $summary->first()->total);
    }

    public function test_quran_class_member_store_rejects_an_employee_from_another_company(): void
    {
        $user = $this->createUserWithCompany(['quran.class.update']);
        $class = QuranClass::factory()->create(['company_id' => $user->company_id]);
        $otherEmployee = Employee::factory()->create(['company_id' => Company::factory()->create()->id]);

        $this->actingAs($user)
            ->post(route('quran-classes.members.store', $class), ['employee_id' => $otherEmployee->id])
            ->assertSessionHasErrors('employee_id');

        $this->assertDatabaseMissing('quran_class_members', [
            'class_id' => $class->id,
            'employee_id' => $otherEmployee->id,
        ]);
    }

    public function test_jamaat_member_store_rejects_an_employee_from_another_company(): void
    {
        $user = $this->createUserWithCompany(['jamaat.update']);
        $jamaat = Jamaat::factory()->create(['company_id' => $user->company_id]);
        $otherEmployee = Employee::factory()->create(['company_id' => Company::factory()->create()->id]);

        $this->actingAs($user)
            ->post(route('jamaats.members.store', $jamaat), ['employee_id' => $otherEmployee->id])
            ->assertSessionHasErrors('employee_id');

        $this->assertDatabaseMissing('jamaat_members', [
            'jamaat_id' => $jamaat->id,
            'employee_id' => $otherEmployee->id,
        ]);
    }

    public function test_employee_creation_rejects_a_soft_deleted_company_master(): void
    {
        $user = $this->createUserWithCompany(['employee.create']);
        $branch = Branch::factory()->create(['company_id' => $user->company_id]);
        $department = Department::factory()->create(['company_id' => $user->company_id]);
        $designation = Designation::factory()->create(['company_id' => $user->company_id]);
        $branch->delete();

        $this->actingAs($user)
            ->post(route('employees.store'), [
                'employee_code' => 'EMP-SOFT-DELETED',
                'employee_name' => 'Soft Deleted Reference',
                'branch_id' => $branch->id,
                'department_id' => $department->id,
                'designation_id' => $designation->id,
                'employment_status' => 1,
            ])
            ->assertSessionHasErrors('branch_id');

        $this->assertDatabaseMissing('employees', [
            'company_id' => $user->company_id,
            'employee_code' => 'EMP-SOFT-DELETED',
        ]);
    }

    public function test_quran_attendance_rejects_employee_ids_outside_the_class_roster(): void
    {
        $user = $this->createUserWithCompany(['quran.attendance.create']);
        $class = QuranClass::factory()->create(['company_id' => $user->company_id]);
        $member = Employee::factory()->create(['company_id' => $user->company_id]);
        $otherEmployee = Employee::factory()->create(['company_id' => Company::factory()->create()->id]);
        QuranClassMember::create([
            'class_id' => $class->id,
            'employee_id' => $member->id,
            'is_active' => true,
            'joined_at' => now()->toDateString(),
        ]);

        $this->actingAs($user)
            ->post(route('quran-attendance.store'), [
                'class_id' => $class->id,
                'date' => now()->toDateString(),
                'attendance' => [
                    $member->id => null,
                    $otherEmployee->id => null,
                ],
            ])
            ->assertSessionHasErrors('attendance');

        $this->assertDatabaseCount('quran_attendance', 0);
    }

    public function test_quran_attendance_rejects_another_companys_attendance_reason(): void
    {
        $user = $this->createUserWithCompany(['quran.attendance.create']);
        $class = QuranClass::factory()->create(['company_id' => $user->company_id]);
        $member = Employee::factory()->create(['company_id' => $user->company_id]);
        $otherReason = AttendanceReason::factory()->create(['company_id' => Company::factory()->create()->id]);
        QuranClassMember::create([
            'class_id' => $class->id,
            'employee_id' => $member->id,
            'is_active' => true,
            'joined_at' => now()->toDateString(),
        ]);

        $this->actingAs($user)
            ->post(route('quran-attendance.store'), [
                'class_id' => $class->id,
                'date' => now()->toDateString(),
                'attendance' => [$member->id => $otherReason->id],
            ])
            ->assertSessionHasErrors('attendance.'.$member->id);

        $this->assertDatabaseCount('quran_attendance', 0);
    }

    public function test_salah_attendance_rejects_employee_ids_outside_the_jamaat_roster(): void
    {
        $user = $this->createUserWithCompany(['salah.attendance.create']);
        $jamaat = Jamaat::factory()->create(['company_id' => $user->company_id]);
        $prayer = Prayer::factory()->create();
        $member = Employee::factory()->create(['company_id' => $user->company_id]);
        $otherEmployee = Employee::factory()->create(['company_id' => Company::factory()->create()->id]);
        JamaatMember::create([
            'jamaat_id' => $jamaat->id,
            'employee_id' => $member->id,
            'is_active' => true,
            'joined_at' => now()->toDateString(),
        ]);

        $this->actingAs($user)
            ->post(route('salah-attendance.store'), [
                'jamaat_id' => $jamaat->id,
                'date' => now()->toDateString(),
                'attendance' => [
                    $member->id => [$prayer->id => null],
                    $otherEmployee->id => [$prayer->id => null],
                ],
            ])
            ->assertSessionHasErrors('attendance');

        $this->assertDatabaseCount('salah_attendance', 0);
    }

    public function test_create_only_user_cannot_replace_existing_quran_attendance(): void
    {
        $user = $this->createUserWithCompany(['quran.attendance.create']);
        $class = QuranClass::factory()->create(['company_id' => $user->company_id]);
        $member = Employee::factory()->create(['company_id' => $user->company_id]);
        QuranClassMember::create([
            'class_id' => $class->id,
            'employee_id' => $member->id,
            'is_active' => true,
            'joined_at' => now()->toDateString(),
        ]);
        QuranAttendance::create([
            'company_id' => $user->company_id,
            'class_id' => $class->id,
            'employee_id' => $member->id,
            'attendance_date' => now()->toDateString(),
        ]);

        $this->actingAs($user)
            ->post(route('quran-attendance.store'), [
                'class_id' => $class->id,
                'date' => now()->toDateString(),
                'attendance' => [$member->id => null],
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('quran_attendance', 1);
    }

    public function test_create_only_user_cannot_replace_existing_salah_attendance(): void
    {
        $user = $this->createUserWithCompany(['salah.attendance.create']);
        $jamaat = Jamaat::factory()->create(['company_id' => $user->company_id]);
        $prayer = Prayer::factory()->create();
        $member = Employee::factory()->create(['company_id' => $user->company_id]);
        JamaatMember::create([
            'jamaat_id' => $jamaat->id,
            'employee_id' => $member->id,
            'is_active' => true,
            'joined_at' => now()->toDateString(),
        ]);
        SalahAttendance::create([
            'company_id' => $user->company_id,
            'jamaat_id' => $jamaat->id,
            'prayer_id' => $prayer->id,
            'employee_id' => $member->id,
            'attendance_date' => now()->toDateString(),
        ]);

        $this->actingAs($user)
            ->post(route('salah-attendance.store'), [
                'jamaat_id' => $jamaat->id,
                'date' => now()->toDateString(),
                'attendance' => [$member->id => [$prayer->id => null]],
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('salah_attendance', 1);
    }

    public function test_create_only_user_cannot_update_quran_progress(): void
    {
        $user = $this->createUserWithCompany(['quran.progress.create']);
        [$progress, $teacher, $department, $status] = $this->createProgressContext((int) $user->company_id);

        $this->actingAs($user)
            ->put(route('quran-progress.update', $progress), [
                'employee_id' => $progress->employee_id,
                'teacher_id' => $teacher->id,
                'quran_department_id' => $department->id,
                'quran_status_id' => $status->id,
                'completion_percentage' => 25,
            ])
            ->assertForbidden();
    }

    public function test_progress_update_cannot_switch_to_another_employee(): void
    {
        $user = $this->createUserWithCompany(['quran.progress.update']);
        [$progress, $teacher, $department, $status] = $this->createProgressContext((int) $user->company_id);
        $otherEmployee = Employee::factory()->create(['company_id' => $user->company_id]);

        $this->actingAs($user)
            ->put(route('quran-progress.update', $progress), [
                'employee_id' => $otherEmployee->id,
                'teacher_id' => $teacher->id,
                'quran_department_id' => $department->id,
                'quran_status_id' => $status->id,
                'completion_percentage' => 25,
            ])
            ->assertSessionHasErrors('employee_id');

        $this->assertDatabaseHas('quran_progress', [
            'id' => $progress->id,
            'employee_id' => $progress->employee_id,
        ]);
    }

    public function test_progress_update_changes_the_bound_record_and_creates_history(): void
    {
        $user = $this->createUserWithCompany(['quran.progress.update']);
        [$progress, $teacher, $department, $status] = $this->createProgressContext((int) $user->company_id);

        $this->actingAs($user)
            ->put(route('quran-progress.update', $progress), [
                'employee_id' => $progress->employee_id,
                'teacher_id' => $teacher->id,
                'quran_department_id' => $department->id,
                'quran_status_id' => $status->id,
                'current_lesson' => 'Updated lesson',
                'completion_percentage' => 25,
            ])
            ->assertRedirect(route('quran-progress.show', $progress));

        $this->assertDatabaseHas('quran_progress', [
            'id' => $progress->id,
            'company_id' => $user->company_id,
            'employee_id' => $progress->employee_id,
            'current_lesson' => 'Updated lesson',
        ]);
        $this->assertDatabaseHas('quran_progress_history', [
            'progress_id' => $progress->id,
            'employee_id' => $progress->employee_id,
            'lesson' => 'Updated lesson',
        ]);
    }

    public function test_employee_with_an_active_membership_cannot_be_soft_deleted(): void
    {
        $user = $this->createUserWithCompany();
        $employee = Employee::factory()->create(['company_id' => $user->company_id]);
        $class = QuranClass::factory()->create(['company_id' => $user->company_id]);
        QuranClassMember::create([
            'class_id' => $class->id,
            'employee_id' => $employee->id,
            'is_active' => true,
            'joined_at' => now()->toDateString(),
        ]);

        $this->actingAs($user);

        $this->assertFalse(app(EmployeeService::class)->canDelete($employee->id));
    }

    public function test_active_members_and_teacher_dependencies_prevent_soft_deletes(): void
    {
        $user = $this->createUserWithCompany();
        $employee = Employee::factory()->create(['company_id' => $user->company_id]);
        $teacherEmployee = Employee::factory()->create(['company_id' => $user->company_id]);
        $teacher = Teacher::factory()->create([
            'company_id' => $user->company_id,
            'employee_id' => $teacherEmployee->id,
        ]);
        $class = QuranClass::factory()->create([
            'company_id' => $user->company_id,
            'teacher_id' => $teacher->id,
        ]);
        $jamaat = Jamaat::factory()->create(['company_id' => $user->company_id]);
        QuranClassMember::create([
            'class_id' => $class->id,
            'employee_id' => $employee->id,
            'is_active' => true,
            'joined_at' => now()->toDateString(),
        ]);
        JamaatMember::create([
            'jamaat_id' => $jamaat->id,
            'employee_id' => $employee->id,
            'is_active' => true,
            'joined_at' => now()->toDateString(),
        ]);

        $this->actingAs($user);

        $this->assertFalse(app(QuranClassService::class)->canDelete($class->id));
        $this->assertFalse(app(JamaatService::class)->canDelete($jamaat->id));
        $this->assertFalse(app(TeacherService::class)->canDelete($teacher->id));
    }

    /** @return array{QuranProgress, Teacher, QuranDepartment, QuranStatus} */
    private function createProgressContext(int $companyId): array
    {
        $employee = Employee::factory()->create(['company_id' => $companyId]);
        $teacherEmployee = Employee::factory()->create(['company_id' => $companyId]);
        $teacher = Teacher::factory()->create([
            'company_id' => $companyId,
            'employee_id' => $teacherEmployee->id,
        ]);
        $department = QuranDepartment::factory()->create(['company_id' => $companyId]);
        $status = QuranStatus::factory()->create(['company_id' => $companyId]);
        $progress = QuranProgress::factory()->create([
            'company_id' => $companyId,
            'employee_id' => $employee->id,
            'teacher_id' => $teacher->id,
            'quran_department_id' => $department->id,
            'quran_status_id' => $status->id,
        ]);

        return [$progress, $teacher, $department, $status];
    }
}
