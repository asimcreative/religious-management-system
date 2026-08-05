<?php

namespace Tests\Feature\Quran;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Employee;
use App\Models\QuranClass;
use App\Models\Teacher;
use App\Models\User;
use Tests\TestCase;

/**
 * Quran Class — CRUD, member management, capacity enforcement.
 */
class QuranClassTest extends TestCase
{
    private function admin(): User
    {
        return $this->createUserWithCompany([
            'quran.class.view', 'quran.class.create', 'quran.class.update',
            'quran.class.delete', 'quran.class.restore',
        ]);
    }

    private function makeClass(User $user): QuranClass
    {
        $branch = Branch::factory()->create(['company_id' => $user->company_id]);
        $teacher = Teacher::factory()->create(['company_id' => $user->company_id]);

        return QuranClass::factory()->create([
            'company_id' => $user->company_id,
            'branch_id' => $branch->id,
            'teacher_id' => $teacher->id,
            'max_strength' => 5,
        ]);
    }

    private function validPayload(User $user): array
    {
        $branch = Branch::factory()->create(['company_id' => $user->company_id]);
        $teacher = Teacher::factory()->create(['company_id' => $user->company_id]);

        return [
            'branch_id' => $branch->id,
            'teacher_id' => $teacher->id,
            'class_name' => 'Quran Class Alpha',
            'class_code' => 'CLS-TEST-001',
            'start_time' => '08:00',
            'end_time' => '09:00',
            'max_strength' => 20,
            'status' => 1,
        ];
    }

    // ── Index ──────────────────────────────────────────────────────────────

    public function test_quran_class_index_requires_auth(): void
    {
        $this->get(route('quran-classes.index'))->assertRedirect(route('login'));
    }

    public function test_quran_class_index_requires_permission(): void
    {
        $user = $this->createUserWithCompany();
        $this->actingAs($user)->get(route('quran-classes.index'))->assertForbidden();
    }

    public function test_quran_class_index_returns_ok(): void
    {
        $user = $this->createUserWithCompany(['quran.class.view']);
        $this->actingAs($user)->get(route('quran-classes.index'))->assertOk();
    }

    public function test_index_scoped_to_company(): void
    {
        $userA = $this->createUserWithCompany(['quran.class.view']);
        $this->makeClass($userA);

        $companyB = Company::factory()->create();
        QuranClass::factory()->create(['company_id' => $companyB->id]);

        $this->actingAs($userA)->get(route('quran-classes.index'))->assertOk();
        $this->assertSame(1, QuranClass::count());
    }

    // ── Store ─────────────────────────────────────────────────────────────

    public function test_store_creates_quran_class(): void
    {
        $user = $this->admin();
        $payload = $this->validPayload($user);

        $this->actingAs($user)
            ->post(route('quran-classes.store'), $payload)
            ->assertRedirect();

        $this->assertDatabaseHas('quran_classes', [
            'class_code' => 'CLS-TEST-001',
            'company_id' => $user->company_id,
        ]);
    }

    public function test_store_requires_create_permission(): void
    {
        $user = $this->createUserWithCompany(['quran.class.view']);
        $payload = $this->validPayload($user);

        $this->actingAs($user)
            ->post(route('quran-classes.store'), $payload)
            ->assertForbidden();
    }

    public function test_store_fails_on_missing_required_fields(): void
    {
        $user = $this->admin();
        $this->actingAs($user)
            ->post(route('quran-classes.store'), [])
            ->assertSessionHasErrors(['branch_id', 'teacher_id', 'class_name', 'class_code', 'max_strength', 'status']);
    }

    public function test_end_time_must_be_after_start_time(): void
    {
        $user = $this->admin();
        $payload = $this->validPayload($user);
        $payload['start_time'] = '10:00';
        $payload['end_time'] = '09:00'; // before start

        $this->actingAs($user)
            ->post(route('quran-classes.store'), $payload)
            ->assertSessionHasErrors('end_time');
    }

    public function test_duplicate_class_code_fails_in_same_company(): void
    {
        $user = $this->admin();
        $payload = $this->validPayload($user);

        $this->actingAs($user)->post(route('quran-classes.store'), $payload);

        $payload2 = $this->validPayload($user);
        $payload2['class_code'] = $payload['class_code'];

        $this->actingAs($user)
            ->post(route('quran-classes.store'), $payload2)
            ->assertSessionHasErrors('class_code');
    }

    public function test_teacher_must_belong_to_same_company(): void
    {
        $user = $this->admin();
        $companyB = Company::factory()->create();
        $teacherB = Teacher::factory()->create(['company_id' => $companyB->id]);

        $branch = Branch::factory()->create(['company_id' => $user->company_id]);

        $this->actingAs($user)
            ->post(route('quran-classes.store'), [
                'branch_id' => $branch->id,
                'teacher_id' => $teacherB->id,
                'class_name' => 'Test',
                'class_code' => 'CLS-X',
                'start_time' => '08:00',
                'end_time' => '09:00',
                'max_strength' => 10,
                'status' => 1,
            ])
            ->assertSessionHasErrors('teacher_id');
    }

    // ── Show / Edit ───────────────────────────────────────────────────────

    public function test_show_returns_class_detail(): void
    {
        $user = $this->createUserWithCompany(['quran.class.view']);
        $class = $this->makeClass($user);

        $this->actingAs($user)
            ->get(route('quran-classes.show', $class))
            ->assertOk();
    }

    public function test_show_cannot_access_other_companys_class(): void
    {
        $userA = $this->createUserWithCompany(['quran.class.view']);
        $companyB = Company::factory()->create();
        $classB = QuranClass::factory()->create(['company_id' => $companyB->id]);

        $this->actingAs($userA)
            ->get(route('quran-classes.show', $classB))
            ->assertNotFound();
    }

    // ── Update / Delete ───────────────────────────────────────────────────

    public function test_update_modifies_class(): void
    {
        $user = $this->admin();
        $class = $this->makeClass($user);

        $payload = $this->validPayload($user);
        $payload['class_name'] = 'Updated Class Name';

        $this->actingAs($user)
            ->put(route('quran-classes.update', $class), $payload)
            ->assertRedirect();

        $this->assertDatabaseHas('quran_classes', [
            'id' => $class->id,
            'class_name' => 'Updated Class Name',
        ]);
    }

    public function test_delete_soft_deletes_class(): void
    {
        $user = $this->admin();
        $class = $this->makeClass($user);

        $this->actingAs($user)
            ->delete(route('quran-classes.destroy', $class))
            ->assertRedirect();

        $this->assertSoftDeleted('quran_classes', ['id' => $class->id]);
    }

    public function test_restore_recovers_deleted_class(): void
    {
        $user = $this->admin();
        $class = $this->makeClass($user);
        $class->delete();

        $this->actingAs($user)
            ->post(route('quran-classes.restore', $class->id))
            ->assertRedirect();

        $this->assertNotSoftDeleted('quran_classes', ['id' => $class->id]);
    }

    // ── Members ───────────────────────────────────────────────────────────

    public function test_add_member_to_class(): void
    {
        $user = $this->createUserWithCompany(['quran.class.view', 'quran.class.update']);
        $class = $this->makeClass($user);
        $employee = Employee::factory()->create(['company_id' => $user->company_id]);

        $this->actingAs($user)
            ->post(route('quran-classes.members.store', $class), [
                'employee_id' => $employee->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('quran_class_members', [
            'class_id' => $class->id,
            'employee_id' => $employee->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'table_name' => 'quran_class_members',
            'action' => 'created',
        ]);
    }

    public function test_cannot_add_member_from_other_company(): void
    {
        $user = $this->createUserWithCompany(['quran.class.view', 'quran.class.update']);
        $class = $this->makeClass($user);
        $companyB = Company::factory()->create();
        $employeeB = Employee::factory()->create(['company_id' => $companyB->id]);

        $this->actingAs($user)
            ->post(route('quran-classes.members.store', $class), [
                'employee_id' => $employeeB->id,
            ])
            ->assertSessionHasErrors('employee_id');
    }

    public function test_readding_an_active_member_keeps_them_active(): void
    {
        $user = $this->createUserWithCompany(['quran.class.view', 'quran.class.update']);
        $class = $this->makeClass($user);
        $employee = Employee::factory()->create(['company_id' => $user->company_id]);

        $this->actingAs($user)
            ->post(route('quran-classes.members.store', $class), ['employee_id' => $employee->id])
            ->assertRedirect();
        $this->actingAs($user)
            ->post(route('quran-classes.members.store', $class), ['employee_id' => $employee->id])
            ->assertRedirect();

        $this->assertDatabaseHas('quran_class_members', [
            'class_id' => $class->id,
            'employee_id' => $employee->id,
            'is_active' => 1,
        ]);
    }

    public function test_remove_member_from_class(): void
    {
        $user = $this->createUserWithCompany(['quran.class.view', 'quran.class.update']);
        $class = $this->makeClass($user);
        $employee = Employee::factory()->create(['company_id' => $user->company_id]);
        $class->members()->attach($employee->id, ['is_active' => true, 'joined_at' => now()]);

        $this->actingAs($user)
            ->delete(route('quran-classes.members.destroy', [$class, $employee]))
            ->assertRedirect();

        $this->assertDatabaseMissing('quran_class_members', [
            'class_id' => $class->id,
            'employee_id' => $employee->id,
            'is_active' => 1,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'table_name' => 'quran_class_members',
            'action' => 'updated',
        ]);
    }
}
