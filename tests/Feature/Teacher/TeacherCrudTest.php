<?php

namespace Tests\Feature\Teacher;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Teacher;
use App\Models\User;
use Tests\TestCase;

/**
 * Teacher CRUD — full HTTP lifecycle tests.
 *
 * Covers: create, read, update, soft-delete, restore,
 * permission enforcement, company isolation, and branch assignment.
 */
class TeacherCrudTest extends TestCase
{
    private function admin(): User
    {
        return $this->createUserWithCompany([
            'teacher.view', 'teacher.create', 'teacher.update',
            'teacher.delete', 'teacher.restore',
        ]);
    }

    private function validPayload(User $user): array
    {
        $employee = Employee::factory()->create(['company_id' => $user->company_id]);
        $branch = Branch::factory()->create(['company_id' => $user->company_id]);

        return [
            'employee_id' => $employee->id,
            'teacher_code' => 'TCH-TEST-001',
            'status' => 1,
            'branch_ids' => [$branch->id],
        ];
    }

    // ── Index ──────────────────────────────────────────────────────────────

    public function test_teacher_index_redirects_guest(): void
    {
        $this->get(route('teachers.index'))->assertRedirect(route('login'));
    }

    public function test_teacher_index_requires_view_permission(): void
    {
        $user = $this->createUserWithCompany();
        $this->actingAs($user)->get(route('teachers.index'))->assertForbidden();
    }

    public function test_teacher_index_returns_ok_with_permission(): void
    {
        $user = $this->createUserWithCompany(['teacher.view']);
        $this->actingAs($user)->get(route('teachers.index'))->assertOk();
    }

    public function test_teacher_index_is_scoped_to_company(): void
    {
        $userA = $this->createUserWithCompany(['teacher.view']);
        Teacher::factory()->create(['company_id' => $userA->company_id]);

        $companyB = Company::factory()->create();
        Teacher::factory()->create(['company_id' => $companyB->id]);

        $this->actingAs($userA)->get(route('teachers.index'))->assertOk();

        $this->assertSame(1, Teacher::count());
    }

    // ── Create / Store ────────────────────────────────────────────────────

    public function test_store_creates_teacher_with_valid_data(): void
    {
        $user = $this->admin();
        $payload = $this->validPayload($user);

        $this->actingAs($user)
            ->post(route('teachers.store'), $payload)
            ->assertRedirect();

        $this->assertDatabaseHas('teachers', [
            'teacher_code' => 'TCH-TEST-001',
            'company_id' => $user->company_id,
        ]);
    }

    public function test_store_requires_create_permission(): void
    {
        $user = $this->createUserWithCompany(['teacher.view']);
        $payload = $this->validPayload($user);

        $this->actingAs($user)
            ->post(route('teachers.store'), $payload)
            ->assertForbidden();
    }

    public function test_store_fails_with_missing_required_fields(): void
    {
        $user = $this->admin();
        $this->actingAs($user)
            ->post(route('teachers.store'), [])
            ->assertSessionHasErrors(['employee_id', 'teacher_code', 'status']);
    }

    public function test_store_rejects_duplicate_teacher_code_in_same_company(): void
    {
        $user = $this->admin();
        $payload = $this->validPayload($user);

        $this->actingAs($user)->post(route('teachers.store'), $payload);

        // Create new employee for second teacher, same code
        $payload2 = $this->validPayload($user);
        $payload2['teacher_code'] = $payload['teacher_code'];

        $this->actingAs($user)
            ->post(route('teachers.store'), $payload2)
            ->assertSessionHasErrors('teacher_code');
    }

    public function test_same_employee_cannot_be_teacher_twice(): void
    {
        $user = $this->admin();
        $employee = Employee::factory()->create(['company_id' => $user->company_id]);
        $branch = Branch::factory()->create(['company_id' => $user->company_id]);

        $this->actingAs($user)->post(route('teachers.store'), [
            'employee_id' => $employee->id,
            'teacher_code' => 'TCH-A',
            'status' => 1,
            'branch_ids' => [$branch->id],
        ]);

        $this->actingAs($user)
            ->post(route('teachers.store'), [
                'employee_id' => $employee->id,
                'teacher_code' => 'TCH-B',
                'status' => 1,
                'branch_ids' => [$branch->id],
            ])
            ->assertSessionHasErrors('employee_id');
    }

    // ── Cannot use cross-company employee ─────────────────────────────────

    public function test_cannot_create_teacher_with_other_companys_employee(): void
    {
        $userA = $this->admin();
        $companyB = Company::factory()->create();
        $employeeB = Employee::factory()->create(['company_id' => $companyB->id]);

        $this->actingAs($userA)
            ->post(route('teachers.store'), [
                'employee_id' => $employeeB->id,
                'teacher_code' => 'TCH-CROSS',
                'status' => 1,
            ])
            ->assertSessionHasErrors('employee_id');
    }

    // ── Show ─────────────────────────────────────────────────────────────

    public function test_show_renders_teacher_detail(): void
    {
        $user = $this->createUserWithCompany(['teacher.view']);
        $teacher = Teacher::factory()->create(['company_id' => $user->company_id]);

        $this->actingAs($user)
            ->get(route('teachers.show', $teacher))
            ->assertOk();
    }

    public function test_show_cannot_access_other_companys_teacher(): void
    {
        $userA = $this->createUserWithCompany(['teacher.view']);
        $companyB = Company::factory()->create();
        $teacherB = Teacher::factory()->create(['company_id' => $companyB->id]);

        $this->actingAs($userA)
            ->get(route('teachers.show', $teacherB))
            ->assertNotFound();
    }

    // ── Update ───────────────────────────────────────────────────────────

    public function test_update_modifies_teacher(): void
    {
        $user = $this->admin();
        $teacher = Teacher::factory()->create(['company_id' => $user->company_id]);
        $branch = Branch::factory()->create(['company_id' => $user->company_id]);

        $this->actingAs($user)
            ->put(route('teachers.update', $teacher), [
                'employee_id' => $teacher->employee_id,
                'teacher_code' => 'TCH-UPDATED',
                'status' => 1,
                'branch_ids' => [$branch->id],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('teachers', [
            'id' => $teacher->id,
            'teacher_code' => 'TCH-UPDATED',
        ]);
    }

    public function test_update_requires_update_permission(): void
    {
        $user = $this->createUserWithCompany(['teacher.view']);
        $teacher = Teacher::factory()->create(['company_id' => $user->company_id]);

        $this->actingAs($user)
            ->put(route('teachers.update', $teacher), [
                'employee_id' => $teacher->employee_id,
                'teacher_code' => 'TCH-X',
                'status' => 1,
            ])
            ->assertForbidden();
    }

    // ── Delete / Restore ──────────────────────────────────────────────────

    public function test_delete_soft_deletes_teacher(): void
    {
        $user = $this->admin();
        $teacher = Teacher::factory()->create(['company_id' => $user->company_id]);

        $this->actingAs($user)
            ->delete(route('teachers.destroy', $teacher))
            ->assertRedirect();

        $this->assertSoftDeleted('teachers', ['id' => $teacher->id]);
    }

    public function test_restore_recovers_soft_deleted_teacher(): void
    {
        $user = $this->admin();
        $teacher = Teacher::factory()->create(['company_id' => $user->company_id]);
        $teacher->delete();

        $this->actingAs($user)
            ->post(route('teachers.restore', $teacher->id))
            ->assertRedirect();

        $this->assertNotSoftDeleted('teachers', ['id' => $teacher->id]);
    }

    public function test_cannot_delete_other_companys_teacher(): void
    {
        $userA = $this->admin();
        $companyB = Company::factory()->create();
        $teacherB = Teacher::factory()->create(['company_id' => $companyB->id]);

        $this->actingAs($userA)
            ->delete(route('teachers.destroy', $teacherB->id))
            ->assertNotFound();
    }

    // ── Branch Assignment ─────────────────────────────────────────────────

    public function test_teacher_branch_assignment_must_belong_to_same_company(): void
    {
        $user = $this->admin();
        $employee = Employee::factory()->create(['company_id' => $user->company_id]);
        $companyB = Company::factory()->create();
        $branchB = Branch::factory()->create(['company_id' => $companyB->id]);

        $this->actingAs($user)
            ->post(route('teachers.store'), [
                'employee_id' => $employee->id,
                'teacher_code' => 'TCH-BR',
                'status' => 1,
                'branch_ids' => [$branchB->id],
            ])
            ->assertSessionHasErrors('branch_ids.0');
    }
}
