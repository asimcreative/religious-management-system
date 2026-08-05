<?php

namespace Tests\Unit\Policies;

use App\Models\Company;
use App\Models\Employee;
use App\Models\Teacher;
use App\Models\User;
use App\Policies\EmployeePolicy;
use App\Policies\JamaatPolicy;
use App\Policies\QuranClassPolicy;
use App\Policies\TeacherPolicy;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Policy unit tests — verify every gate method returns correct bool
 * without going through HTTP.
 */
class PolicyTest extends TestCase
{
    // ── EmployeePolicy ────────────────────────────────────────────────────

    public function test_employee_policy_view_any_requires_employee_view(): void
    {
        $userWith = $this->createUserWithCompany(['employee.view']);
        $userWithout = $this->createUserWithCompany([]);

        $policy = app(EmployeePolicy::class);

        $this->assertTrue($policy->viewAny($this->inUserCompanyContext($userWith)));
        $this->assertFalse($policy->viewAny($this->inUserCompanyContext($userWithout)));
    }

    public function test_employee_policy_create_requires_employee_create(): void
    {
        $userWith = $this->createUserWithCompany(['employee.create']);
        $userWithout = $this->createUserWithCompany(['employee.view']);

        $policy = app(EmployeePolicy::class);

        $this->assertTrue($policy->create($this->inUserCompanyContext($userWith)));
        $this->assertFalse($policy->create($this->inUserCompanyContext($userWithout)));
    }

    public function test_employee_policy_update_requires_permission_and_company_match(): void
    {
        $userA = $this->createUserWithCompany(['employee.update']);
        $employeeA = Employee::factory()->create(['company_id' => $userA->company_id]);

        $companyB = Company::factory()->create();
        $employeeB = Employee::factory()->create(['company_id' => $companyB->id]);

        $policy = app(EmployeePolicy::class);

        // Same company — should pass (RoleDataAccessService will allow admin)
        $this->actingAs($userA);
        $result = $policy->update($this->inUserCompanyContext($userA), $employeeA);
        $this->assertTrue($result);
    }

    public function test_employee_policy_delete_requires_permission(): void
    {
        $userWith = $this->createUserWithCompany(['employee.delete']);
        $userWithout = $this->createUserWithCompany(['employee.view']);
        $employee = Employee::factory()->create(['company_id' => $userWith->company_id]);

        $policy = app(EmployeePolicy::class);

        $this->actingAs($userWith);
        $this->assertTrue($policy->delete($this->inUserCompanyContext($userWith), $employee));

        $this->actingAs($userWithout);
        $this->assertFalse($policy->delete($this->inUserCompanyContext($userWithout), $employee));
    }

    public function test_employee_policy_restore_requires_restore_permission(): void
    {
        $userWith = $this->createUserWithCompany(['employee.restore']);
        $userWithout = $this->createUserWithCompany(['employee.view', 'employee.delete']);
        $employee = Employee::factory()->create(['company_id' => $userWith->company_id]);

        $policy = app(EmployeePolicy::class);

        $this->actingAs($userWith);
        $this->assertTrue($policy->restore($this->inUserCompanyContext($userWith), $employee));

        $this->actingAs($userWithout);
        $this->assertFalse($policy->restore($this->inUserCompanyContext($userWithout), $employee));
    }

    public function test_employee_policy_import_requires_import_permission(): void
    {
        $userWith = $this->createUserWithCompany(['employee.import']);
        $userWithout = $this->createUserWithCompany(['employee.view']);

        $policy = app(EmployeePolicy::class);

        $this->assertTrue($policy->import($this->inUserCompanyContext($userWith)));
        $this->assertFalse($policy->import($this->inUserCompanyContext($userWithout)));
    }

    public function test_employee_policy_export_requires_export_permission(): void
    {
        $userWith = $this->createUserWithCompany(['employee.export']);
        $userWithout = $this->createUserWithCompany(['employee.view']);

        $policy = app(EmployeePolicy::class);

        $this->assertTrue($policy->export($this->inUserCompanyContext($userWith)));
        $this->assertFalse($policy->export($this->inUserCompanyContext($userWithout)));
    }

    // ── TeacherPolicy ─────────────────────────────────────────────────────

    public function test_teacher_policy_view_any(): void
    {
        $userWith = $this->createUserWithCompany(['teacher.view']);
        $userWithout = $this->createUserWithCompany([]);

        $policy = app(TeacherPolicy::class);

        $this->assertTrue($policy->viewAny($this->inUserCompanyContext($userWith)));
        $this->assertFalse($policy->viewAny($this->inUserCompanyContext($userWithout)));
    }

    public function test_teacher_policy_create(): void
    {
        $userWith = $this->createUserWithCompany(['teacher.create']);
        $userWithout = $this->createUserWithCompany(['teacher.view']);

        $policy = app(TeacherPolicy::class);

        $this->assertTrue($policy->create($this->inUserCompanyContext($userWith)));
        $this->assertFalse($policy->create($this->inUserCompanyContext($userWithout)));
    }

    public function test_teacher_policy_delete(): void
    {
        $userWith = $this->createUserWithCompany(['teacher.delete']);
        $userWithout = $this->createUserWithCompany(['teacher.view']);
        $teacher = Teacher::factory()->create(['company_id' => $userWith->company_id]);

        $policy = app(TeacherPolicy::class);

        $this->actingAs($userWith);
        $this->assertTrue($policy->delete($this->inUserCompanyContext($userWith), $teacher));

        $this->actingAs($userWithout);
        $this->assertFalse($policy->delete($this->inUserCompanyContext($userWithout), $teacher));
    }

    // ── QuranClassPolicy ──────────────────────────────────────────────────

    public function test_quran_class_policy_view_any(): void
    {
        $userWith = $this->createUserWithCompany(['quran.class.view']);
        $userWithout = $this->createUserWithCompany([]);

        $policy = app(QuranClassPolicy::class);

        $this->assertTrue($policy->viewAny($this->inUserCompanyContext($userWith)));
        $this->assertFalse($policy->viewAny($this->inUserCompanyContext($userWithout)));
    }

    public function test_quran_class_policy_create(): void
    {
        $userWith = $this->createUserWithCompany(['quran.class.create']);
        $userWithout = $this->createUserWithCompany(['quran.class.view']);

        $policy = app(QuranClassPolicy::class);

        $this->assertTrue($policy->create($this->inUserCompanyContext($userWith)));
        $this->assertFalse($policy->create($this->inUserCompanyContext($userWithout)));
    }

    // ── JamaatPolicy ─────────────────────────────────────────────────────

    public function test_jamaat_policy_view_any(): void
    {
        $userWith = $this->createUserWithCompany(['jamaat.view']);
        $userWithout = $this->createUserWithCompany([]);

        $policy = app(JamaatPolicy::class);

        $this->assertTrue($policy->viewAny($this->inUserCompanyContext($userWith)));
        $this->assertFalse($policy->viewAny($this->inUserCompanyContext($userWithout)));
    }

    public function test_jamaat_policy_create(): void
    {
        $userWith = $this->createUserWithCompany(['jamaat.create']);
        $userWithout = $this->createUserWithCompany(['jamaat.view']);

        $policy = app(JamaatPolicy::class);

        $this->assertTrue($policy->create($this->inUserCompanyContext($userWith)));
        $this->assertFalse($policy->create($this->inUserCompanyContext($userWithout)));
    }

    private function inUserCompanyContext(User $user): User
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId((int) $user->company_id);

        return $user;
    }
}
