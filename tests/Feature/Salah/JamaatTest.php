<?php

namespace Tests\Feature\Salah;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Jamaat;
use App\Models\User;
use Tests\TestCase;

/**
 * Jamaat — CRUD, member management, company isolation.
 */
class JamaatTest extends TestCase
{
    private function admin(): User
    {
        return $this->createUserWithCompany([
            'jamaat.view', 'jamaat.create', 'jamaat.update',
            'jamaat.delete', 'jamaat.restore',
        ]);
    }

    private function makeJamaat(User $user): Jamaat
    {
        $branch = Branch::factory()->create(['company_id' => $user->company_id]);
        $leader = Employee::factory()->create(['company_id' => $user->company_id]);

        return Jamaat::factory()->create([
            'company_id' => $user->company_id,
            'branch_id' => $branch->id,
            'leader_id' => $leader->id,
        ]);
    }

    private function validPayload(User $user): array
    {
        $branch = Branch::factory()->create(['company_id' => $user->company_id]);
        $leader = Employee::factory()->create(['company_id' => $user->company_id]);

        return [
            'branch_id' => $branch->id,
            'jamaat_number' => '101',
            'jamaat_name' => 'Jamaat Alpha',
            'leader_id' => $leader->id,
            'status' => 1,
        ];
    }

    // ── Index ──────────────────────────────────────────────────────────────

    public function test_index_requires_auth(): void
    {
        $this->get(route('jamaats.index'))->assertRedirect(route('login'));
    }

    public function test_index_requires_view_permission(): void
    {
        $user = $this->createUserWithCompany();
        $this->actingAs($user)->get(route('jamaats.index'))->assertForbidden();
    }

    public function test_index_returns_ok_with_permission(): void
    {
        $user = $this->createUserWithCompany(['jamaat.view']);
        $this->actingAs($user)->get(route('jamaats.index'))->assertOk();
    }

    public function test_index_scoped_to_company(): void
    {
        $userA = $this->createUserWithCompany(['jamaat.view']);
        $this->makeJamaat($userA);

        $companyB = Company::factory()->create();
        Jamaat::factory()->create(['company_id' => $companyB->id]);

        $this->actingAs($userA);
        $this->assertSame(1, Jamaat::count());
    }

    // ── Store ─────────────────────────────────────────────────────────────

    public function test_store_creates_jamaat(): void
    {
        $user = $this->admin();
        $payload = $this->validPayload($user);

        $this->actingAs($user)
            ->post(route('jamaats.store'), $payload)
            ->assertRedirect();

        $this->assertDatabaseHas('jamaats', [
            'jamaat_name' => 'Jamaat Alpha',
            'company_id' => $user->company_id,
        ]);
    }

    public function test_store_requires_create_permission(): void
    {
        $user = $this->createUserWithCompany(['jamaat.view']);
        $payload = $this->validPayload($user);

        $this->actingAs($user)
            ->post(route('jamaats.store'), $payload)
            ->assertForbidden();
    }

    public function test_store_fails_without_required_fields(): void
    {
        $user = $this->admin();
        $this->actingAs($user)
            ->post(route('jamaats.store'), [])
            ->assertSessionHasErrors(['branch_id', 'jamaat_number', 'jamaat_name', 'leader_id', 'status']);
    }

    public function test_duplicate_jamaat_number_rejected_in_same_company(): void
    {
        $user = $this->admin();
        $payload = $this->validPayload($user);

        $this->actingAs($user)->post(route('jamaats.store'), $payload);

        // Same jamaat_number, second store
        $payload2 = $this->validPayload($user);
        $payload2['jamaat_number'] = $payload['jamaat_number'];

        $this->actingAs($user)
            ->post(route('jamaats.store'), $payload2)
            ->assertSessionHasErrors('jamaat_number');
    }

    public function test_leader_must_belong_to_same_company(): void
    {
        $user = $this->admin();
        $companyB = Company::factory()->create();
        $leaderB = Employee::factory()->create(['company_id' => $companyB->id]);
        $branch = Branch::factory()->create(['company_id' => $user->company_id]);

        $this->actingAs($user)
            ->post(route('jamaats.store'), [
                'branch_id' => $branch->id,
                'jamaat_number' => 201,
                'jamaat_name' => 'Test',
                'leader_id' => $leaderB->id,
                'status' => 1,
            ])
            ->assertSessionHasErrors('leader_id');
    }

    // ── Show / Edit / Update ──────────────────────────────────────────────

    public function test_show_renders_jamaat_detail(): void
    {
        $user = $this->createUserWithCompany(['jamaat.view']);
        $jamaat = $this->makeJamaat($user);

        $this->actingAs($user)
            ->get(route('jamaats.show', $jamaat))
            ->assertOk();
    }

    public function test_cannot_view_other_companys_jamaat(): void
    {
        $userA = $this->createUserWithCompany(['jamaat.view']);
        $companyB = Company::factory()->create();
        $jamaatB = Jamaat::factory()->create(['company_id' => $companyB->id]);

        $this->actingAs($userA)
            ->get(route('jamaats.show', $jamaatB))
            ->assertNotFound();
    }

    public function test_update_modifies_jamaat(): void
    {
        $user = $this->admin();
        $jamaat = $this->makeJamaat($user);

        $payload = $this->validPayload($user);
        $payload['jamaat_name'] = 'Updated Jamaat Name';

        $this->actingAs($user)
            ->put(route('jamaats.update', $jamaat), $payload)
            ->assertRedirect();

        $this->assertDatabaseHas('jamaats', [
            'id' => $jamaat->id,
            'jamaat_name' => 'Updated Jamaat Name',
        ]);
    }

    // ── Delete / Restore ──────────────────────────────────────────────────

    public function test_delete_soft_deletes_jamaat(): void
    {
        $user = $this->admin();
        $jamaat = $this->makeJamaat($user);

        $this->actingAs($user)
            ->delete(route('jamaats.destroy', $jamaat))
            ->assertRedirect();

        $this->assertSoftDeleted('jamaats', ['id' => $jamaat->id]);
    }

    public function test_restore_recovers_jamaat(): void
    {
        $user = $this->admin();
        $jamaat = $this->makeJamaat($user);
        $jamaat->delete();

        $this->actingAs($user)
            ->post(route('jamaats.restore', $jamaat->id))
            ->assertRedirect();

        $this->assertNotSoftDeleted('jamaats', ['id' => $jamaat->id]);
    }

    // ── Members ───────────────────────────────────────────────────────────

    public function test_add_member_to_jamaat(): void
    {
        $user = $this->admin();
        $jamaat = $this->makeJamaat($user);
        $employee = Employee::factory()->create(['company_id' => $user->company_id]);

        $this->actingAs($user)
            ->post(route('jamaats.members.store', $jamaat), [
                'employee_id' => $employee->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('jamaat_members', [
            'jamaat_id' => $jamaat->id,
            'employee_id' => $employee->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'table_name' => 'jamaat_members',
            'action' => 'created',
        ]);
    }

    public function test_cannot_add_member_from_other_company_to_jamaat(): void
    {
        $user = $this->admin();
        $jamaat = $this->makeJamaat($user);
        $companyB = Company::factory()->create();
        $employeeB = Employee::factory()->create(['company_id' => $companyB->id]);

        $this->actingAs($user)
            ->post(route('jamaats.members.store', $jamaat), [
                'employee_id' => $employeeB->id,
            ])
            ->assertSessionHasErrors('employee_id');
    }

    public function test_readding_an_active_member_keeps_them_active(): void
    {
        $user = $this->admin();
        $jamaat = $this->makeJamaat($user);
        $employee = Employee::factory()->create(['company_id' => $user->company_id]);

        $this->actingAs($user)
            ->post(route('jamaats.members.store', $jamaat), ['employee_id' => $employee->id])
            ->assertRedirect();
        $this->actingAs($user)
            ->post(route('jamaats.members.store', $jamaat), ['employee_id' => $employee->id])
            ->assertRedirect();

        $this->assertDatabaseHas('jamaat_members', [
            'jamaat_id' => $jamaat->id,
            'employee_id' => $employee->id,
            'is_active' => 1,
        ]);
    }

    public function test_remove_member_from_jamaat_is_audited(): void
    {
        $user = $this->admin();
        $jamaat = $this->makeJamaat($user);
        $employee = Employee::factory()->create(['company_id' => $user->company_id]);
        $jamaat->members()->attach($employee->id, ['is_active' => true, 'joined_at' => now()]);

        $this->actingAs($user)
            ->delete(route('jamaats.members.destroy', [$jamaat, $employee]))
            ->assertRedirect();

        $this->assertDatabaseMissing('jamaat_members', [
            'jamaat_id' => $jamaat->id,
            'employee_id' => $employee->id,
            'is_active' => 1,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'table_name' => 'jamaat_members',
            'action' => 'updated',
        ]);
    }
}
