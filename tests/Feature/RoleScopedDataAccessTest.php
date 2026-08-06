<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
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
use App\Models\User;
use App\Services\DashboardService;
use App\Services\ReportService;
use App\Support\DataTransfer\ResourceRegistry;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RoleScopedDataAccessTest extends TestCase
{
    public function test_quran_teacher_only_sees_assigned_classes_students_and_progress(): void
    {
        $company = Company::factory()->create();
        $branch = Branch::factory()->create(['company_id' => $company->id]);
        $user = $this->createRoleUser($company, 'Quran Teacher', [
            'quran.class.view',
            'quran.attendance.view',
            'quran.attendance.create',
            'quran.attendance.update',
            'quran.progress.view',
            'quran.progress.create',
            'quran.progress.update',
            'quran.progress.history',
            'report.quran',
            'report.dashboard',
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
        $assignedStudent = Employee::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
        ]);
        $unassignedStudent = Employee::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
        ]);
        $assignedClass = QuranClass::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'teacher_id' => $teacher->id,
        ]);
        $otherClass = QuranClass::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'teacher_id' => $otherTeacher->id,
        ]);
        QuranClassMember::create([
            'class_id' => $assignedClass->id,
            'employee_id' => $assignedStudent->id,
            'is_active' => true,
            'joined_at' => now()->toDateString(),
        ]);
        QuranClassMember::create([
            'class_id' => $otherClass->id,
            'employee_id' => $unassignedStudent->id,
            'is_active' => true,
            'joined_at' => now()->toDateString(),
        ]);
        $department = QuranDepartment::factory()->create(['company_id' => $company->id]);
        $status = QuranStatus::factory()->create(['company_id' => $company->id]);
        $assignedProgress = QuranProgress::factory()->create([
            'company_id' => $company->id,
            'employee_id' => $assignedStudent->id,
            'teacher_id' => $teacher->id,
            'quran_department_id' => $department->id,
            'quran_status_id' => $status->id,
        ]);
        $unassignedProgress = QuranProgress::factory()->create([
            'company_id' => $company->id,
            'employee_id' => $unassignedStudent->id,
            'teacher_id' => $otherTeacher->id,
            'quran_department_id' => $department->id,
            'quran_status_id' => $status->id,
        ]);

        $this->actingAs($user);
        $this->setPermissionTeam($company);

        $this->assertSame([$assignedClass->id], QuranClass::orderBy('id')->pluck('id')->all());
        $this->assertSame(
            [$teacherEmployee->id, $assignedStudent->id],
            Employee::orderBy('id')->pluck('id')->all(),
        );
        $this->assertSame([$assignedProgress->id], QuranProgress::orderBy('id')->pluck('id')->all());

        $this->get(route('quran-progress.create'))
            ->assertOk()
            ->assertSee($assignedStudent->employee_name)
            ->assertDontSee($unassignedStudent->employee_name);

        $this->post(route('quran-progress.store'), [
            'employee_id' => $unassignedStudent->id,
            'teacher_id' => $teacher->id,
            'quran_department_id' => $department->id,
            'quran_status_id' => $status->id,
            'completion_percentage' => 20,
        ])->assertSessionHasErrors('employee_id');

        $this->assertDatabaseHas('quran_progress', ['id' => $unassignedProgress->id]);
    }

    public function test_jamaat_leader_only_sees_own_jamaat_and_prayer_summary(): void
    {
        $company = Company::factory()->create();
        $branch = Branch::factory()->create(['company_id' => $company->id]);
        $user = $this->createRoleUser($company, 'Jamaat Leader', [
            'jamaat.view',
            'salah.attendance.view',
            'salah.attendance.create',
            'salah.attendance.update',
            'report.salah',
            'report.dashboard',
        ]);
        $leader = Employee::factory()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'branch_id' => $branch->id,
        ]);
        $otherLeader = Employee::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
        ]);
        $ownMember = Employee::factory()->create(['company_id' => $company->id, 'branch_id' => $branch->id]);
        $otherMember = Employee::factory()->create(['company_id' => $company->id, 'branch_id' => $branch->id]);
        $ownJamaat = Jamaat::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'leader_id' => $leader->id,
        ]);
        $otherJamaat = Jamaat::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'leader_id' => $otherLeader->id,
        ]);
        JamaatMember::create([
            'jamaat_id' => $ownJamaat->id,
            'employee_id' => $ownMember->id,
            'is_active' => true,
            'joined_at' => now()->toDateString(),
        ]);
        JamaatMember::create([
            'jamaat_id' => $otherJamaat->id,
            'employee_id' => $otherMember->id,
            'is_active' => true,
            'joined_at' => now()->toDateString(),
        ]);
        $prayer = Prayer::factory()->create();
        $ownAttendance = SalahAttendance::factory()->create([
            'company_id' => $company->id,
            'jamaat_id' => $ownJamaat->id,
            'leader_id' => $leader->id,
            'prayer_id' => $prayer->id,
            'employee_id' => $ownMember->id,
        ]);
        SalahAttendance::factory()->create([
            'company_id' => $company->id,
            'jamaat_id' => $otherJamaat->id,
            'leader_id' => $otherLeader->id,
            'prayer_id' => $prayer->id,
            'employee_id' => $otherMember->id,
        ]);

        $this->actingAs($user);
        $this->setPermissionTeam($company);

        $this->assertSame([$ownJamaat->id], Jamaat::orderBy('id')->pluck('id')->all());
        $this->assertSame([$ownAttendance->id], SalahAttendance::orderBy('id')->pluck('id')->all());

        $response = $this->get(route('reports.salah-attendance'));
        $summary = $response->viewData('prayerWise');

        $response->assertOk();
        $this->assertCount(1, $summary);
        $this->assertSame(1, (int) $summary->first()->total);
    }

    public function test_branch_manager_is_limited_to_branch_records_reports_exports_and_dashboard(): void
    {
        $company = Company::factory()->create();
        $branch = Branch::factory()->create(['company_id' => $company->id]);
        $otherBranch = Branch::factory()->create(['company_id' => $company->id]);
        $user = $this->createRoleUser($company, 'Branch Manager', [
            'employee.view',
            'teacher.view',
            'report.dashboard',
            'report.employee',
            'report.teacher',
            'report.export_excel',
        ]);
        $manager = Employee::factory()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'branch_id' => $branch->id,
        ]);
        $branchEmployee = Employee::factory()->create(['company_id' => $company->id, 'branch_id' => $branch->id]);
        $otherEmployee = Employee::factory()->create(['company_id' => $company->id, 'branch_id' => $otherBranch->id]);
        $branchTeacher = Teacher::factory()->create([
            'company_id' => $company->id,
            'employee_id' => $branchEmployee->id,
        ]);
        $otherTeacher = Teacher::factory()->create([
            'company_id' => $company->id,
            'employee_id' => $otherEmployee->id,
        ]);
        $branchTeacher->branches()->attach($branch->id);
        $otherTeacher->branches()->attach($otherBranch->id);

        $this->actingAs($user);
        $this->setPermissionTeam($company);

        $this->assertSame(
            [$manager->id, $branchEmployee->id],
            Employee::orderBy('id')->pluck('id')->all(),
        );
        $this->assertSame([$branchTeacher->id], Teacher::orderBy('id')->pluck('id')->all());
        $this->assertSame(
            [$manager->id, $branchEmployee->id],
            $this->sortedIds($this->exportQuery('employees')->pluck('id')->all()),
        );
        $this->assertSame([$branchTeacher->id], $this->exportQuery('teachers')->orderBy('id')->pluck('id')->all());

        $reportSummary = app(ReportService::class)->dashboardSummary();
        $overview = app(DashboardService::class)->overviewStats();

        $this->assertSame(2, $reportSummary['total_employees']);
        $this->assertSame(1, $reportSummary['total_teachers']);
        $this->assertSame(0, $reportSummary['total_quran_attendance']);
        $this->assertSame(2, $overview['total_employees']);
        $this->assertSame(1, $overview['total_teachers']);
    }

    public function test_department_manager_is_limited_to_department_employee_reports_and_exports(): void
    {
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $otherDepartment = Department::factory()->create(['company_id' => $company->id]);
        $user = $this->createRoleUser($company, 'Department Manager', [
            'employee.view',
            'report.dashboard',
            'report.employee',
            'report.export_excel',
        ]);
        $manager = Employee::factory()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'department_id' => $department->id,
        ]);
        $departmentEmployee = Employee::factory()->create([
            'company_id' => $company->id,
            'department_id' => $department->id,
        ]);
        $otherEmployee = Employee::factory()->create([
            'company_id' => $company->id,
            'department_id' => $otherDepartment->id,
        ]);

        $this->actingAs($user);
        $this->setPermissionTeam($company);

        $this->assertSame(
            [$manager->id, $departmentEmployee->id],
            Employee::orderBy('id')->pluck('id')->all(),
        );
        $this->assertSame(
            [$manager->id, $departmentEmployee->id],
            $this->sortedIds($this->exportQuery('employees')->pluck('id')->all()),
        );
        $this->assertSame(2, app(ReportService::class)->dashboardSummary()['total_employees']);
    }

    public function test_branch_and_department_manager_prayer_summaries_respect_their_data_scopes(): void
    {
        $company = Company::factory()->create();
        $branch = Branch::factory()->create(['company_id' => $company->id]);
        $otherBranch = Branch::factory()->create(['company_id' => $company->id]);
        $department = Department::factory()->create(['company_id' => $company->id]);
        $otherDepartment = Department::factory()->create(['company_id' => $company->id]);
        $prayer = Prayer::factory()->create();

        $branchManager = $this->createRoleUser($company, 'Branch Manager', ['report.salah']);
        $branchManagerEmployee = Employee::factory()->create([
            'company_id' => $company->id,
            'user_id' => $branchManager->id,
            'branch_id' => $branch->id,
            'department_id' => $department->id,
        ]);
        $branchEmployee = Employee::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'department_id' => $otherDepartment->id,
        ]);
        $otherBranchEmployee = Employee::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $otherBranch->id,
            'department_id' => $department->id,
        ]);
        $branchJamaat = Jamaat::factory()->create(['company_id' => $company->id, 'branch_id' => $branch->id]);
        $otherBranchJamaat = Jamaat::factory()->create(['company_id' => $company->id, 'branch_id' => $otherBranch->id]);
        SalahAttendance::factory()->create([
            'company_id' => $company->id,
            'jamaat_id' => $branchJamaat->id,
            'prayer_id' => $prayer->id,
            'employee_id' => $branchEmployee->id,
        ]);
        SalahAttendance::factory()->create([
            'company_id' => $company->id,
            'jamaat_id' => $otherBranchJamaat->id,
            'prayer_id' => $prayer->id,
            'employee_id' => $otherBranchEmployee->id,
        ]);

        $this->actingAs($branchManager);
        $this->setPermissionTeam($company);
        $branchResponse = $this->get(route('reports.salah-attendance'));

        $branchResponse->assertOk();
        $this->assertSame(1, (int) $branchResponse->viewData('prayerWise')->first()->total);

        $departmentManager = $this->createRoleUser($company, 'Department Manager', ['report.salah']);
        Employee::factory()->create([
            'company_id' => $company->id,
            'user_id' => $departmentManager->id,
            'branch_id' => $branch->id,
            'department_id' => $department->id,
        ]);

        $this->actingAs($departmentManager);
        $this->setPermissionTeam($company);
        $departmentResponse = $this->get(route('reports.salah-attendance'));

        $departmentResponse->assertOk();
        $this->assertSame(1, (int) $departmentResponse->viewData('prayerWise')->first()->total);
        $this->assertNotSame($branchManagerEmployee->id, $otherBranchEmployee->id);
    }

    public function test_employee_role_is_limited_to_its_own_profile_and_records(): void
    {
        $company = Company::factory()->create();
        $user = $this->createRoleUser($company, 'Employee', [
            'employee.view',
            'quran.attendance.view',
            'quran.progress.view',
            'salah.attendance.view',
        ]);
        $employee = Employee::factory()->create(['company_id' => $company->id, 'user_id' => $user->id]);
        $otherEmployee = Employee::factory()->create(['company_id' => $company->id]);
        $class = QuranClass::factory()->create(['company_id' => $company->id]);
        $prayer = Prayer::factory()->create();
        $ownQuranAttendance = QuranAttendance::factory()->create([
            'company_id' => $company->id,
            'class_id' => $class->id,
            'employee_id' => $employee->id,
        ]);
        QuranAttendance::factory()->create([
            'company_id' => $company->id,
            'class_id' => $class->id,
            'employee_id' => $otherEmployee->id,
        ]);
        $ownProgress = QuranProgress::factory()->create(['company_id' => $company->id, 'employee_id' => $employee->id]);
        QuranProgress::factory()->create(['company_id' => $company->id, 'employee_id' => $otherEmployee->id]);
        $ownSalahAttendance = SalahAttendance::factory()->create([
            'company_id' => $company->id,
            'prayer_id' => $prayer->id,
            'employee_id' => $employee->id,
        ]);
        SalahAttendance::factory()->create([
            'company_id' => $company->id,
            'prayer_id' => $prayer->id,
            'employee_id' => $otherEmployee->id,
        ]);

        $this->actingAs($user);
        $this->setPermissionTeam($company);

        $this->assertSame([$employee->id], Employee::pluck('id')->all());
        $this->assertSame([$ownQuranAttendance->id], QuranAttendance::pluck('id')->all());
        $this->assertSame([$ownProgress->id], QuranProgress::pluck('id')->all());
        $this->assertSame([$ownSalahAttendance->id], SalahAttendance::pluck('id')->all());
        $this->get(route('employees.show', $employee))->assertOk();
        $this->get(route('employees.show', $otherEmployee))->assertNotFound();
    }

    public function test_export_permission_cannot_be_used_without_the_source_report_permission(): void
    {
        $user = $this->createUserWithCompany(['report.export_excel']);

        $this->actingAs($user)
            ->get(route('reports.export.employees'))
            ->assertForbidden();
        $this->get(route('reports.export.teachers'))->assertForbidden();
        $this->get(route('reports.export.quran-attendance'))->assertForbidden();
        $this->get(route('reports.export.salah-attendance'))->assertForbidden();
    }

    public function test_report_center_requires_at_least_one_report_permission(): void
    {
        $user = $this->createUserWithCompany();

        $this->actingAs($user)->get(route('reports.index'))->assertForbidden();

        $authorizedUser = $this->createUserWithCompany(['report.employee']);
        $this->actingAs($authorizedUser)->get(route('reports.index'))->assertOk();
    }

    /** @param  list<string>  $permissions */
    private function createRoleUser(Company $company, string $roleName, array $permissions): User
    {
        $user = User::factory()->create(['company_id' => $company->id]);
        $this->setPermissionTeam($company);

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $role = Role::findOrCreate($roleName, 'web');
        $role->syncPermissions($permissions);
        $user->assignRole($role);

        return $user;
    }

    private function setPermissionTeam(Company $company): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($company->id);
    }

    /**
     * @param  list<int>  $ids
     * @return list<int>
     */
    private function sortedIds(array $ids): array
    {
        sort($ids);

        return $ids;
    }

    /**
     * The query a module's export would run, for the signed-in user.
     *
     * Exports must never widen what a role-restricted user can already see on
     * screen, so these assertions run against the real export query.
     */
    private function exportQuery(string $resourceKey): Builder
    {
        return app(ResourceRegistry::class)->get($resourceKey)->newQuery();
    }
}
