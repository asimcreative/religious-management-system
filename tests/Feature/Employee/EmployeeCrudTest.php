<?php

namespace Tests\Feature\Employee;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\User;
use Tests\TestCase;

/**
 * Employee CRUD — full HTTP lifecycle tests.
 *
 * Covers: create, read, update, soft-delete, restore, search,
 * validation, permission enforcement, and company isolation.
 */
class EmployeeCrudTest extends TestCase
{
    // ── Helpers ───────────────────────────────────────────────────────────

    private function companyAdmin(): User
    {
        return $this->createUserWithCompany([
            'employee.view', 'employee.create', 'employee.update',
            'employee.delete', 'employee.restore',
        ]);
    }

    private function validPayload(User $user): array
    {
        $company = Company::find($user->company_id);
        $branch = Branch::factory()->create(['company_id' => $company->id]);
        $dept = Department::factory()->create(['company_id' => $company->id]);
        $desig = Designation::factory()->create(['company_id' => $company->id]);

        return [
            'employee_code' => 'EMP-TEST-001',
            'employee_name' => 'Ahmed Raza',
            'mobile' => '0300-1234567',
            'email' => 'ahmed@example.com',
            'gender' => 'male',
            'employment_status' => 1,
            'branch_id' => $branch->id,
            'department_id' => $dept->id,
            'designation_id' => $desig->id,
        ];
    }

    // ── Index ──────────────────────────────────────────────────────────────

    public function test_employee_index_requires_authentication(): void
    {
        $this->get(route('employees.index'))->assertRedirect(route('login'));
    }

    public function test_employee_index_requires_view_permission(): void
    {
        $user = $this->createUserWithCompany(); // no permissions
        $this->actingAs($user)
            ->get(route('employees.index'))
            ->assertForbidden();
    }

    public function test_employee_index_returns_200_with_permission(): void
    {
        $user = $this->createUserWithCompany(['employee.view']);
        $this->actingAs($user)
            ->get(route('employees.index'))
            ->assertOk();
    }

    public function test_employee_index_only_shows_own_company_employees(): void
    {
        $userA = $this->createUserWithCompany(['employee.view']);
        $employeeA = Employee::factory()->create([
            'company_id' => $userA->company_id,
            'employee_name' => 'Own Tenant Employee',
        ]);

        $companyB = Company::factory()->create();
        $employeeB = Employee::factory()->create([
            'company_id' => $companyB->id,
            'employee_name' => 'Other Tenant Employee',
        ]);

        $this->actingAs($userA)
            ->get(route('employees.index'))
            ->assertOk()
            ->assertSee($employeeA->employee_name)
            ->assertDontSee($employeeB->employee_name);
    }

    // ── Create / Store ────────────────────────────────────────────────────

    public function test_create_page_requires_create_permission(): void
    {
        $user = $this->createUserWithCompany(['employee.view']); // no create
        $this->actingAs($user)
            ->get(route('employees.create'))
            ->assertForbidden();
    }

    public function test_create_page_accessible_with_permission(): void
    {
        $user = $this->createUserWithCompany(['employee.view', 'employee.create']);
        $this->actingAs($user)
            ->get(route('employees.create'))
            ->assertOk();
    }

    public function test_store_creates_employee_with_valid_data(): void
    {
        $user = $this->companyAdmin();
        $payload = $this->validPayload($user);

        $this->actingAs($user)
            ->post(route('employees.store'), $payload)
            ->assertRedirect();

        $this->assertDatabaseHas('employees', [
            'employee_code' => 'EMP-TEST-001',
            'employee_name' => 'Ahmed Raza',
            'company_id' => $user->company_id,
        ]);
    }

    public function test_store_scopes_new_employee_to_current_company(): void
    {
        $userA = $this->companyAdmin();
        $payload = $this->validPayload($userA);

        $this->actingAs($userA)
            ->post(route('employees.store'), $payload);

        $this->assertDatabaseHas('employees', [
            'employee_code' => $payload['employee_code'],
            'company_id' => $userA->company_id,
        ]);
    }

    public function test_store_fails_without_required_fields(): void
    {
        $user = $this->companyAdmin();

        $this->actingAs($user)
            ->post(route('employees.store'), [])
            ->assertSessionHasErrors(['employee_code', 'employee_name']);
    }

    public function test_store_rejects_duplicate_employee_code_within_same_company(): void
    {
        $user = $this->companyAdmin();
        $payload = $this->validPayload($user);

        // First insert succeeds
        $this->actingAs($user)->post(route('employees.store'), $payload);

        // Duplicate employee_code in same company → validation error
        $payload2 = $this->validPayload($user);
        $payload2['employee_code'] = $payload['employee_code'];

        $this->actingAs($user)
            ->post(route('employees.store'), $payload2)
            ->assertSessionHasErrors('employee_code');
    }

    public function test_same_employee_code_allowed_in_different_companies(): void
    {
        $userA = $this->companyAdmin();
        $payloadA = $this->validPayload($userA);
        $payloadA['employee_code'] = 'SHARED-001';

        $this->actingAs($userA)->post(route('employees.store'), $payloadA);

        $userB = $this->companyAdmin();
        $payloadB = $this->validPayload($userB);
        $payloadB['employee_code'] = 'SHARED-001';

        $this->actingAs($userB)
            ->post(route('employees.store'), $payloadB)
            ->assertSessionDoesntHaveErrors('employee_code');
    }

    public function test_store_rejects_invalid_gender(): void
    {
        $user = $this->companyAdmin();
        $payload = $this->validPayload($user);
        $payload['gender'] = 'other'; // invalid

        $this->actingAs($user)
            ->post(route('employees.store'), $payload)
            ->assertSessionHasErrors('gender');
    }

    public function test_store_rejects_future_date_of_birth(): void
    {
        $user = $this->companyAdmin();
        $payload = $this->validPayload($user);
        $payload['dob'] = now()->addYear()->format('Y-m-d');

        $this->actingAs($user)
            ->post(route('employees.store'), $payload)
            ->assertSessionHasErrors('dob');
    }

    public function test_store_requires_create_permission(): void
    {
        $user = $this->createUserWithCompany(['employee.view']); // no create
        $payload = $this->validPayload($user);

        $this->actingAs($user)
            ->post(route('employees.store'), $payload)
            ->assertForbidden();
    }

    // ── Show ─────────────────────────────────────────────────────────────

    public function test_show_renders_employee_detail(): void
    {
        $user = $this->createUserWithCompany(['employee.view']);
        $employee = Employee::factory()->create(['company_id' => $user->company_id]);

        $this->actingAs($user)
            ->get(route('employees.show', $employee))
            ->assertOk()
            ->assertSee($employee->employee_name);
    }

    public function test_show_cannot_access_other_companys_employee(): void
    {
        $userA = $this->createUserWithCompany(['employee.view']);
        $companyB = Company::factory()->create();
        $employeeB = Employee::factory()->create(['company_id' => $companyB->id]);

        $this->actingAs($userA)
            ->get(route('employees.show', $employeeB))
            ->assertNotFound(); // BelongsToCompany scope returns 404
    }

    // ── Edit / Update ─────────────────────────────────────────────────────

    public function test_update_modifies_employee_data(): void
    {
        $user = $this->companyAdmin();
        $employee = Employee::factory()->create(['company_id' => $user->company_id]);

        $payload = $this->validPayload($user);
        $payload['employee_name'] = 'Updated Name';
        $payload['employee_code'] = $employee->employee_code;

        $this->actingAs($user)
            ->put(route('employees.update', $employee), $payload)
            ->assertRedirect();

        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'employee_name' => 'Updated Name',
        ]);
    }

    public function test_update_cannot_modify_other_companys_employee(): void
    {
        $userA = $this->companyAdmin();
        $companyB = Company::factory()->create();
        $employeeB = Employee::factory()->create(['company_id' => $companyB->id]);

        $payload = $this->validPayload($userA);

        $this->actingAs($userA)
            ->put(route('employees.update', $employeeB->id), $payload)
            ->assertNotFound();
    }

    // ── Delete / Restore ──────────────────────────────────────────────────

    public function test_delete_soft_deletes_employee(): void
    {
        $user = $this->companyAdmin();
        $employee = Employee::factory()->create(['company_id' => $user->company_id]);

        $this->actingAs($user)
            ->delete(route('employees.destroy', $employee))
            ->assertRedirect();

        $this->assertSoftDeleted('employees', ['id' => $employee->id]);
    }

    public function test_delete_requires_delete_permission(): void
    {
        $user = $this->createUserWithCompany(['employee.view', 'employee.create']);
        $employee = Employee::factory()->create(['company_id' => $user->company_id]);

        $this->actingAs($user)
            ->delete(route('employees.destroy', $employee))
            ->assertForbidden();
    }

    public function test_restore_recovers_soft_deleted_employee(): void
    {
        $user = $this->companyAdmin();
        $employee = Employee::factory()->create(['company_id' => $user->company_id]);
        $employee->delete();

        $this->actingAs($user)
            ->post(route('employees.restore', $employee->id))
            ->assertRedirect();

        $this->assertNotSoftDeleted('employees', ['id' => $employee->id]);
    }

    public function test_restore_requires_restore_permission(): void
    {
        $user = $this->createUserWithCompany(['employee.view', 'employee.delete']);
        $employee = Employee::factory()->create(['company_id' => $user->company_id]);
        $employee->delete();

        $this->actingAs($user)
            ->post(route('employees.restore', $employee->id))
            ->assertForbidden();
    }

    public function test_delete_cannot_delete_other_companys_employee(): void
    {
        $userA = $this->companyAdmin();
        $companyB = Company::factory()->create();
        $employeeB = Employee::factory()->create(['company_id' => $companyB->id]);

        $this->actingAs($userA)
            ->delete(route('employees.destroy', $employeeB->id))
            ->assertNotFound();
    }

    // ── Audit Log Integration ─────────────────────────────────────────────

    public function test_creating_employee_records_audit_log(): void
    {
        $user = $this->companyAdmin();
        $payload = $this->validPayload($user);

        $this->actingAs($user)
            ->post(route('employees.store'), $payload);

        $this->assertDatabaseHas('audit_logs', [
            'company_id' => $user->company_id,
            'module' => 'employees',
            'action' => 'created',
        ]);
    }
}
