<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employee;
use App\Models\Notification;
use App\Models\Teacher;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Multi-tenant company isolation tests.
 *
 * The BelongsToCompany global scope must ensure that a user from Company A
 * can never read, count, or modify data belonging to Company B.
 */
class CompanyIsolationTest extends TestCase
{
    // ── Employees ─────────────────────────────────────────────────────────

    /** A user sees only their own company's employees. */
    public function test_employee_query_is_scoped_to_company(): void
    {
        $userA = $this->createUserWithCompany();
        $companyA = Company::find($userA->company_id);

        // Company A employee
        Employee::factory()->create(['company_id' => $companyA->id]);

        // Company B with its own employee
        $companyB = Company::factory()->create();
        Employee::factory()->create(['company_id' => $companyB->id]);

        $this->actingAs($userA);

        $this->assertSame(1, Employee::count());
    }

    /** A user cannot retrieve a specific employee from another company. */
    public function test_employee_find_cannot_cross_company_boundary(): void
    {
        $userA = $this->createUserWithCompany();
        $companyB = Company::factory()->create();
        $employeeB = Employee::factory()->create(['company_id' => $companyB->id]);

        $this->actingAs($userA);

        $this->assertNull(Employee::find($employeeB->id));
    }

    // ── Teachers ──────────────────────────────────────────────────────────

    /** A user sees only their own company's teachers. */
    public function test_teacher_query_is_scoped_to_company(): void
    {
        $userA = $this->createUserWithCompany();
        $companyA = Company::find($userA->company_id);

        Teacher::factory()->create(['company_id' => $companyA->id]);

        $companyB = Company::factory()->create();
        Teacher::factory()->create(['company_id' => $companyB->id]);

        $this->actingAs($userA);

        $this->assertSame(1, Teacher::count());
    }

    // ── Notifications ─────────────────────────────────────────────────────

    /** A user sees only their own company's notifications. */
    public function test_notification_query_is_scoped_to_company(): void
    {
        $userA = $this->createUserWithCompany();
        $companyA = Company::find($userA->company_id);

        Notification::factory()->create(['company_id' => $companyA->id, 'user_id' => $userA->id]);

        // Company B notification
        $companyB = Company::factory()->create();
        $userB = User::factory()->create(['company_id' => $companyB->id]);
        Notification::factory()->create(['company_id' => $companyB->id, 'user_id' => $userB->id]);

        $this->actingAs($userA);

        $this->assertSame(1, Notification::count());
    }

    // ── Super Admin bypass ────────────────────────────────────────────────

    /** Super Admin can see all employees across all companies. */
    public function test_super_admin_bypasses_company_scope(): void
    {
        $superAdmin = $this->createSuperAdmin();

        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        Employee::factory()->create(['company_id' => $companyA->id]);
        Employee::factory()->create(['company_id' => $companyB->id]);

        $this->actingAs($superAdmin);

        // Set the Spatie team context so hasRole() in BelongsToCompany scope works
        app(PermissionRegistrar::class)->setPermissionsTeamId($superAdmin->company_id);

        $this->assertSame(2, Employee::count());
    }

    /** A tenant-local role named Super Admin cannot bypass company isolation. */
    public function test_tenant_super_admin_does_not_bypass_company_scope(): void
    {
        $companyA = Company::factory()->create();
        $tenantAdmin = User::factory()->create(['company_id' => $companyA->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($companyA->id);
        $role = Role::firstOrCreate([
            'name' => 'Super Admin',
            'guard_name' => 'web',
        ]);
        $tenantAdmin->assignRole($role);

        Employee::factory()->create(['company_id' => $companyA->id]);
        $companyB = Company::factory()->create();
        Employee::factory()->create(['company_id' => $companyB->id]);

        $this->actingAs($tenantAdmin);
        app(PermissionRegistrar::class)->setPermissionsTeamId($companyA->id);

        $this->assertSame(1, Employee::count());
    }

    // ── Two isolated companies ────────────────────────────────────────────

    /** Two companies' users are fully isolated from each other. */
    public function test_two_companies_are_fully_isolated(): void
    {
        $userA = $this->createUserWithCompany();
        $companyA = Company::find($userA->company_id);

        $userB = $this->createUserWithCompany();
        $companyB = Company::find($userB->company_id);

        Employee::factory(3)->create(['company_id' => $companyA->id]);
        Employee::factory(2)->create(['company_id' => $companyB->id]);

        $this->actingAs($userA);
        $this->assertSame(3, Employee::count(), 'User A should only see 3 employees');

        $this->actingAs($userB);
        $this->assertSame(2, Employee::count(), 'User B should only see 2 employees');
    }
}
