<?php

namespace Tests\Feature\Security;

use App\Models\Company;
use App\Models\Employee;
use App\Models\Teacher;
use Tests\TestCase;

/**
 * Search filters combine `orWhere` clauses with the `BelongsToCompany` global
 * scope. If the `orWhere` group is ever written at the top level instead of
 * inside a closure, the generated SQL degrades from
 *
 *     WHERE company_id = ? AND (name LIKE ? OR email LIKE ?)
 * to
 *     WHERE company_id = ? AND name LIKE ? OR email LIKE ?
 *
 * which resolves as `(company_id = ? AND name LIKE ?) OR email LIKE ?` and
 * leaks other companies' rows to anyone who can type in a search box.
 *
 * The current repositories group correctly. These tests exist so that a future
 * edit cannot silently undo it.
 *
 * NOTE: assertions deliberately target a field that is NOT the search term.
 * The search term itself is echoed back into the filter input's `value`
 * attribute, so asserting on it would fail even when zero rows are returned.
 */
class SearchTenantLeakTest extends TestCase
{
    public function test_employee_search_by_name_cannot_return_another_companys_records(): void
    {
        $user = $this->createUserWithCompany(['employee.view']);

        $otherCompany = Company::factory()->create();
        $foreign = Employee::factory()->create([
            'company_id' => $otherCompany->id,
            'employee_name' => 'Zainab Foreign Tenant',
            'employee_code' => 'LEAKPROBE-001',
            'email' => 'zainab@other-tenant.test',
        ]);

        // Positive control: an own-company employee matching the SAME search
        // term. Without this the test could pass simply because search is
        // broken and returns nothing at all.
        Employee::factory()->create([
            'company_id' => $user->company_id,
            'employee_name' => 'Zainab Foreign Tenant',
            'employee_code' => 'OWNPROBE-001',
        ]);

        $this->actingAs($user);

        // Search by a term matching an employee in BOTH companies.
        $response = $this->get(route('employees.index', ['search' => 'Zainab Foreign Tenant']));

        $response->assertOk();
        $response->assertSee('OWNPROBE-001', false);      // own row IS found
        $response->assertDontSee('LEAKPROBE-001', false); // foreign row is NOT
        $response->assertDontSee($foreign->email, false);
    }

    public function test_employee_search_by_email_cannot_cross_the_tenant_boundary(): void
    {
        $user = $this->createUserWithCompany(['employee.view']);

        $otherCompany = Company::factory()->create();
        Employee::factory()->create([
            'company_id' => $otherCompany->id,
            'employee_name' => 'Other Tenant Person',
            'employee_code' => 'LEAKPROBE-002',
            'email' => 'leak-probe@other-tenant.test',
        ]);

        $this->actingAs($user);

        $this->get(route('employees.index', ['search' => 'leak-probe@other-tenant.test']))
            ->assertOk()
            ->assertDontSee('Other Tenant Person', false)
            ->assertDontSee('LEAKPROBE-002', false);
    }

    public function test_teacher_search_cannot_return_another_companys_records(): void
    {
        $user = $this->createUserWithCompany(['teacher.view']);

        $otherCompany = Company::factory()->create();
        $foreignEmployee = Employee::factory()->create([
            'company_id' => $otherCompany->id,
            'employee_name' => 'Foreign Teacher Person',
            'employee_code' => 'LEAKPROBE-003',
        ]);
        Teacher::factory()->create([
            'company_id' => $otherCompany->id,
            'employee_id' => $foreignEmployee->id,
            'teacher_code' => 'LEAKPROBE-TCH-003',
        ]);

        $this->actingAs($user);

        $this->get(route('teachers.index', ['search' => 'Foreign Teacher Person']))
            ->assertOk()
            ->assertDontSee('LEAKPROBE-003', false)
            ->assertDontSee('LEAKPROBE-TCH-003', false);
    }
}
