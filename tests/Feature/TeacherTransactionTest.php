<?php

namespace Tests\Feature;

use App\Enums\Status;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\Teacher;
use App\Services\TeacherService;
use Illuminate\Database\QueryException;
use Tests\TestCase;

class TeacherTransactionTest extends TestCase
{
    public function test_create_with_branches_rolls_back_the_teacher_when_pivot_sync_fails(): void
    {
        $user = $this->createUserWithCompany();
        $employee = Employee::factory()->create(['company_id' => $user->company_id]);

        $this->actingAs($user);

        try {
            app(TeacherService::class)->createWithBranches([
                'company_id' => $user->company_id,
                'employee_id' => $employee->id,
                'teacher_code' => 'TCH-ROLLBACK-CREATE',
                'status' => Status::Active,
            ], [999999]);

            $this->fail('The invalid branch reference should fail the pivot synchronization.');
        } catch (QueryException) {
            $this->assertDatabaseMissing('teachers', [
                'company_id' => $user->company_id,
                'employee_id' => $employee->id,
            ]);
        }
    }

    public function test_update_with_branches_rolls_back_model_and_pivot_changes_when_sync_fails(): void
    {
        $user = $this->createUserWithCompany();
        $employee = Employee::factory()->create(['company_id' => $user->company_id]);
        $branch = Branch::factory()->create(['company_id' => $user->company_id]);
        $teacher = Teacher::factory()->create([
            'company_id' => $user->company_id,
            'employee_id' => $employee->id,
            'teacher_code' => 'TCH-ROLLBACK-ORIGINAL',
        ]);
        $teacher->branches()->sync([$branch->id]);

        $this->actingAs($user);

        try {
            app(TeacherService::class)->updateWithBranches($teacher->id, [
                'teacher_code' => 'TCH-ROLLBACK-UPDATED',
            ], [999999]);

            $this->fail('The invalid branch reference should fail the pivot synchronization.');
        } catch (QueryException) {
            $this->assertDatabaseHas('teachers', [
                'id' => $teacher->id,
                'teacher_code' => 'TCH-ROLLBACK-ORIGINAL',
            ]);
            $this->assertDatabaseHas('teacher_branch', [
                'teacher_id' => $teacher->id,
                'branch_id' => $branch->id,
            ]);
        }
    }
}
