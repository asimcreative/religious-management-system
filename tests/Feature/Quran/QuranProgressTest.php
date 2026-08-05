<?php

namespace Tests\Feature\Quran;

use App\Models\Employee;
use App\Models\QuranDepartment;
use App\Models\QuranProgress;
use App\Models\QuranStatus;
use App\Models\Teacher;
use App\Models\User;
use Tests\TestCase;

/**
 * Quran Progress — CRUD, permissions, company isolation.
 */
class QuranProgressTest extends TestCase
{
    /** Create a Company Admin with full quran.progress permissions. */
    private function admin(): User
    {
        return $this->createUserWithCompany([
            'quran.progress.view',
            'quran.progress.create',
            'quran.progress.update',
            'quran.progress.history',
            'quran.progress.report',
        ]);
    }

    /**
     * Build all related models (employee, teacher, department, status)
     * tied to the same company as $user.
     *
     * @return array{employee: Employee, teacher: Teacher, department: QuranDepartment, status: QuranStatus}
     */
    private function buildRelations(User $user): array
    {
        $companyId = $user->company_id;

        $employee = Employee::factory()->create(['company_id' => $companyId]);
        $teacher = Teacher::factory()->create([
            'company_id' => $companyId,
            'employee_id' => Employee::factory()->create(['company_id' => $companyId])->id,
        ]);
        $department = QuranDepartment::factory()->create(['company_id' => $companyId]);
        $status = QuranStatus::factory()->create(['company_id' => $companyId]);

        return compact('employee', 'teacher', 'department', 'status');
    }

    /** Return a valid store payload for the given related models. */
    private function validPayload(Employee $employee, Teacher $teacher, QuranDepartment $department, QuranStatus $status): array
    {
        return [
            'employee_id' => $employee->id,
            'teacher_id' => $teacher->id,
            'quran_department_id' => $department->id,
            'quran_status_id' => $status->id,
            'current_lesson' => 'Lesson 5',
            'current_surah' => 'Al-Fatiha',
            'current_sipara' => 1,
            'current_page' => 1,
            'completion_percentage' => 25.00,
            'remarks' => 'Making good progress.',
        ];
    }

    // ── Index ──────────────────────────────────────────────────────────────

    public function test_index_is_accessible_with_permission(): void
    {
        $user = $this->admin();

        $this->actingAs($user)
            ->get(route('quran-progress.index'))
            ->assertOk();
    }

    public function test_index_is_forbidden_without_permission(): void
    {
        $user = $this->createUserWithCompany();

        $this->actingAs($user)
            ->get(route('quran-progress.index'))
            ->assertForbidden();
    }

    // ── Create ─────────────────────────────────────────────────────────────

    public function test_create_form_is_accessible_with_permission(): void
    {
        $user = $this->admin();

        $this->actingAs($user)
            ->get(route('quran-progress.create'))
            ->assertOk();
    }

    public function test_create_form_is_forbidden_without_permission(): void
    {
        $user = $this->createUserWithCompany(['quran.progress.view']);

        $this->actingAs($user)
            ->get(route('quran-progress.create'))
            ->assertForbidden();
    }

    // ── Store ──────────────────────────────────────────────────────────────

    public function test_store_creates_progress_record(): void
    {
        $user = $this->admin();
        ['employee' => $emp, 'teacher' => $teacher, 'department' => $dept, 'status' => $status] = $this->buildRelations($user);

        $this->actingAs($user)
            ->post(route('quran-progress.store'), $this->validPayload($emp, $teacher, $dept, $status))
            ->assertRedirect();

        $this->assertDatabaseHas('quran_progress', [
            'company_id' => $user->company_id,
            'employee_id' => $emp->id,
            'teacher_id' => $teacher->id,
            'completion_percentage' => 25.00,
        ]);
    }

    public function test_store_rejects_duplicate_progress_for_same_employee(): void
    {
        $user = $this->admin();
        ['employee' => $emp, 'teacher' => $teacher, 'department' => $dept, 'status' => $status] = $this->buildRelations($user);

        // Create progress first
        QuranProgress::factory()->create([
            'company_id' => $user->company_id,
            'employee_id' => $emp->id,
        ]);

        $this->actingAs($user)
            ->post(route('quran-progress.store'), $this->validPayload($emp, $teacher, $dept, $status))
            ->assertSessionHasErrors('employee_id');
    }

    public function test_store_rejects_teacher_from_different_company(): void
    {
        $user = $this->admin();
        ['employee' => $emp, 'department' => $dept, 'status' => $status] = $this->buildRelations($user);

        // Teacher from another company
        $otherTeacher = Teacher::factory()->create();

        $this->actingAs($user)
            ->post(route('quran-progress.store'), $this->validPayload($emp, $otherTeacher, $dept, $status))
            ->assertSessionHasErrors('teacher_id');
    }

    public function test_store_rejects_employee_from_different_company(): void
    {
        $user = $this->admin();
        ['teacher' => $teacher, 'department' => $dept, 'status' => $status] = $this->buildRelations($user);

        $otherEmployee = Employee::factory()->create(); // different company

        $this->actingAs($user)
            ->post(route('quran-progress.store'), $this->validPayload($otherEmployee, $teacher, $dept, $status))
            ->assertSessionHasErrors('employee_id');
    }

    public function test_store_requires_completion_percentage(): void
    {
        $user = $this->admin();
        ['employee' => $emp, 'teacher' => $teacher, 'department' => $dept, 'status' => $status] = $this->buildRelations($user);

        $payload = $this->validPayload($emp, $teacher, $dept, $status);
        unset($payload['completion_percentage']);

        $this->actingAs($user)
            ->post(route('quran-progress.store'), $payload)
            ->assertSessionHasErrors('completion_percentage');
    }

    // ── Show ───────────────────────────────────────────────────────────────

    public function test_show_is_accessible_with_permission(): void
    {
        $user = $this->admin();

        $progress = QuranProgress::factory()->create([
            'company_id' => $user->company_id,
        ]);

        $this->actingAs($user)
            ->get(route('quran-progress.show', $progress))
            ->assertOk();
    }

    public function test_show_handles_a_soft_deleted_employee(): void
    {
        $user = $this->admin();
        $employee = Employee::factory()->create(['company_id' => $user->company_id]);
        $progress = QuranProgress::factory()->create([
            'company_id' => $user->company_id,
            'employee_id' => $employee->id,
        ]);
        $employee->delete();

        $this->actingAs($user)
            ->get(route('quran-progress.show', $progress))
            ->assertOk();
    }

    public function test_show_is_forbidden_without_permission(): void
    {
        $user = $this->createUserWithCompany();

        $progress = QuranProgress::factory()->create([
            'company_id' => $user->company_id,
        ]);

        $this->actingAs($user)
            ->get(route('quran-progress.show', $progress))
            ->assertForbidden();
    }

    // ── Edit / Update ──────────────────────────────────────────────────────

    public function test_edit_form_is_accessible_with_permission(): void
    {
        $user = $this->admin();

        $progress = QuranProgress::factory()->create([
            'company_id' => $user->company_id,
        ]);

        $this->actingAs($user)
            ->get(route('quran-progress.edit', $progress))
            ->assertOk();
    }

    public function test_edit_form_is_forbidden_without_update_permission(): void
    {
        $user = $this->createUserWithCompany(['quran.progress.view']);

        $progress = QuranProgress::factory()->create([
            'company_id' => $user->company_id,
        ]);

        $this->actingAs($user)
            ->get(route('quran-progress.edit', $progress))
            ->assertForbidden();
    }

    public function test_update_modifies_progress_record(): void
    {
        $user = $this->admin();
        ['employee' => $emp, 'teacher' => $teacher, 'department' => $dept, 'status' => $status] = $this->buildRelations($user);

        $progress = QuranProgress::factory()->create([
            'company_id' => $user->company_id,
            'employee_id' => $emp->id,
            'teacher_id' => $teacher->id,
            'quran_department_id' => $dept->id,
            'quran_status_id' => $status->id,
            'completion_percentage' => 10.00,
        ]);

        $payload = $this->validPayload($emp, $teacher, $dept, $status);
        $payload['completion_percentage'] = 75.00;

        $this->actingAs($user)
            ->put(route('quran-progress.update', $progress), $payload)
            ->assertRedirect();

        $this->assertDatabaseHas('quran_progress', [
            'id' => $progress->id,
            'completion_percentage' => 75.00,
        ]);
    }

    // ── Company Isolation ──────────────────────────────────────────────────

    public function test_cannot_view_another_companys_progress(): void
    {
        $user = $this->admin();

        // Progress belonging to a completely different company
        $otherProgress = QuranProgress::factory()->create();

        $this->actingAs($user)
            ->get(route('quran-progress.show', $otherProgress))
            ->assertNotFound();
    }

    public function test_cannot_edit_another_companys_progress(): void
    {
        $user = $this->admin();

        $otherProgress = QuranProgress::factory()->create();

        $this->actingAs($user)
            ->get(route('quran-progress.edit', $otherProgress))
            ->assertNotFound();
    }

    // ── isCompleted Helper ─────────────────────────────────────────────────

    public function test_is_completed_returns_true_when_percentage_is_100(): void
    {
        $progress = QuranProgress::factory()->make(['completion_percentage' => 100.00]);

        $this->assertTrue($progress->isCompleted());
    }

    public function test_is_completed_returns_false_when_percentage_is_less_than_100(): void
    {
        $progress = QuranProgress::factory()->make(['completion_percentage' => 99.99]);

        $this->assertFalse($progress->isCompleted());
    }
}
