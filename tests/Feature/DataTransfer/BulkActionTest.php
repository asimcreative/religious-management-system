<?php

namespace Tests\Feature\DataTransfer;

use App\Enums\Status;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Employee;
use App\Models\QuranAttendance;
use App\Models\QuranClass;
use App\Models\SavedFilter;
use App\Models\User;
use App\Support\DataTransfer\ResourceRegistry;
use Tests\TestCase;

/**
 * Bulk actions and saved filters.
 *
 * A bulk endpoint is the natural place for an authorisation hole: it is
 * tempting to check the permission once and then loop. These tests exist to
 * prove it is checked per record, and that a request can neither reach nor
 * change another company's rows.
 */
class BulkActionTest extends TestCase
{
    private function manager(): User
    {
        return $this->createUserWithCompany(['branch.manage']);
    }

    // ── Bulk delete ────────────────────────────────────────────────

    public function test_selected_records_are_deleted(): void
    {
        $user = $this->manager();
        $branches = Branch::factory()->count(3)->create(['company_id' => $user->company_id]);

        $response = $this->actingAs($user)
            ->from(route('masters.branches.index'))
            ->post(route('data.bulk', ['resource' => 'branches']), [
                'action' => 'delete',
                'ids' => [$branches[0]->id, $branches[1]->id],
            ]);

        $response->assertRedirect(route('masters.branches.index'));
        $response->assertSessionHas('success');

        $this->assertSoftDeleted('branches', ['id' => $branches[0]->id]);
        $this->assertSoftDeleted('branches', ['id' => $branches[1]->id]);
        $this->assertDatabaseHas('branches', ['id' => $branches[2]->id, 'deleted_at' => null]);
    }

    public function test_ids_from_another_company_are_never_touched(): void
    {
        $user = $this->manager();
        $other = Company::factory()->create();

        $mine = Branch::factory()->create(['company_id' => $user->company_id]);
        $theirs = Branch::factory()->create(['company_id' => $other->id]);

        $this->actingAs($user)
            ->from(route('masters.branches.index'))
            ->post(route('data.bulk', ['resource' => 'branches']), [
                'action' => 'delete',
                'ids' => [$mine->id, $theirs->id],
            ]);

        $this->assertSoftDeleted('branches', ['id' => $mine->id]);
        $this->assertDatabaseHas('branches', ['id' => $theirs->id, 'deleted_at' => null]);
    }

    public function test_a_user_without_the_delete_right_deletes_nothing(): void
    {
        $user = $this->createUserWithCompany(['employee.view']);
        $employees = Employee::factory()->count(2)->create(['company_id' => $user->company_id]);

        $response = $this->actingAs($user)
            ->from(route('employees.index'))
            ->post(route('data.bulk', ['resource' => 'employees']), [
                'action' => 'delete',
                'ids' => $employees->pluck('id')->all(),
            ]);

        $response->assertSessionHas('error');

        foreach ($employees as $employee) {
            $this->assertDatabaseHas('employees', ['id' => $employee->id, 'deleted_at' => null]);
        }
    }

    public function test_a_record_still_in_use_is_kept_and_reported(): void
    {
        $user = $this->createUserWithCompany(['employee.view', 'employee.delete']);

        $free = Employee::factory()->create(['company_id' => $user->company_id]);
        $inUse = Employee::factory()->create(['company_id' => $user->company_id]);

        // Attendance history is what makes an employee undeletable, on the
        // single-record path and here alike.
        $class = QuranClass::factory()->create(['company_id' => $user->company_id]);
        QuranAttendance::factory()->create([
            'company_id' => $user->company_id,
            'class_id' => $class->id,
            'employee_id' => $inUse->id,
        ]);

        $response = $this->actingAs($user)
            ->from(route('employees.index'))
            ->post(route('data.bulk', ['resource' => 'employees']), [
                'action' => 'delete',
                'ids' => [$free->id, $inUse->id],
            ]);

        $response->assertSessionHas('bulk_reasons');

        $this->assertSoftDeleted('employees', ['id' => $free->id]);
        $this->assertDatabaseHas('employees', ['id' => $inUse->id, 'deleted_at' => null]);
    }

    // ── Bulk status change ─────────────────────────────────────────

    public function test_status_is_changed_on_the_selected_records(): void
    {
        $user = $this->manager();
        $branches = Branch::factory()->count(2)->create([
            'company_id' => $user->company_id,
            'status' => Status::Active,
        ]);

        $this->actingAs($user)
            ->from(route('masters.branches.index'))
            ->post(route('data.bulk', ['resource' => 'branches']), [
                'action' => 'status',
                'status' => (string) Status::Inactive->value,
                'ids' => $branches->pluck('id')->all(),
            ])
            ->assertSessionHas('success');

        foreach ($branches as $branch) {
            $this->assertSame(Status::Inactive, $branch->refresh()->status);
        }
    }

    public function test_a_status_the_module_does_not_offer_is_rejected(): void
    {
        $user = $this->manager();
        $branch = Branch::factory()->create(['company_id' => $user->company_id, 'status' => Status::Active]);

        // Branches offer Active and Inactive only; Suspended exists on the enum
        // but not on this module's form, so import and bulk must both refuse it.
        $this->actingAs($user)
            ->from(route('masters.branches.index'))
            ->post(route('data.bulk', ['resource' => 'branches']), [
                'action' => 'status',
                'status' => (string) Status::Suspended->value,
                'ids' => [$branch->id],
            ])
            ->assertSessionHasErrors('status');

        $this->assertSame(Status::Active, $branch->refresh()->status);
    }

    public function test_a_status_change_without_a_status_is_rejected(): void
    {
        $user = $this->manager();
        $branch = Branch::factory()->create(['company_id' => $user->company_id]);

        $this->actingAs($user)
            ->from(route('masters.branches.index'))
            ->post(route('data.bulk', ['resource' => 'branches']), [
                'action' => 'status',
                'ids' => [$branch->id],
            ])
            ->assertSessionHasErrors('status');
    }

    // ── Opt-out ────────────────────────────────────────────────────

    public function test_modules_that_record_events_do_not_offer_bulk_actions(): void
    {
        $registry = app(ResourceRegistry::class);

        foreach (['quran-attendance', 'salah-attendance', 'notifications', 'quran-progress'] as $key) {
            $this->assertFalse(
                $registry->get($key)->supportsBulkActions(),
                "[{$key}] records events and must not offer bulk deletion.",
            );
        }

        foreach (['employees', 'branches', 'teachers', 'jamaats'] as $key) {
            $this->assertTrue($registry->get($key)->supportsBulkActions());
        }
    }

    public function test_the_toolbar_only_shows_selection_where_it_is_supported(): void
    {
        $user = $this->createUserWithCompany(['branch.manage', 'quran.attendance.view']);

        $this->actingAs($user)
            ->get(route('masters.branches.index'))
            ->assertOk()
            ->assertSee('data-row-select-all', false);

        $this->actingAs($user)
            ->get(route('quran-attendance.index'))
            ->assertOk()
            ->assertDontSee('data-row-select-all', false);
    }

    // ── Saved filters ──────────────────────────────────────────────

    public function test_a_filter_set_is_saved_against_the_user(): void
    {
        $user = $this->manager();

        $this->actingAs($user)
            ->from(route('masters.branches.index'))
            ->post(route('data.filters.store', ['resource' => 'branches']), [
                'name' => 'Active only',
                'query' => 'search=main&status=1&nonsense=x',
            ])
            ->assertSessionHas('success');

        $saved = SavedFilter::query()->firstOrFail();

        $this->assertSame('Active only', $saved->name);
        $this->assertSame($user->id, $saved->user_id);
        $this->assertSame($user->company_id, $saved->company_id);

        // Only the keys the module declares are kept.
        $this->assertSame(['search' => 'main', 'status' => '1'], $saved->query);
    }

    public function test_saving_the_same_name_replaces_the_previous_set(): void
    {
        $user = $this->manager();

        foreach (['search=one', 'search=two'] as $query) {
            $this->actingAs($user)
                ->from(route('masters.branches.index'))
                ->post(route('data.filters.store', ['resource' => 'branches']), [
                    'name' => 'My view',
                    'query' => $query,
                ]);
        }

        $this->assertSame(1, SavedFilter::query()->count());
        $this->assertSame(['search' => 'two'], SavedFilter::query()->firstOrFail()->query);
    }

    public function test_a_colleagues_filter_set_cannot_be_deleted(): void
    {
        $user = $this->manager();
        $colleague = User::factory()->create(['company_id' => $user->company_id]);

        $saved = SavedFilter::factory()->create([
            'company_id' => $user->company_id,
            'user_id' => $colleague->id,
        ]);

        $this->actingAs($user)
            ->from(route('masters.branches.index'))
            ->delete(route('data.filters.destroy', $saved))
            ->assertForbidden();

        $this->assertDatabaseHas('saved_filters', ['id' => $saved->id]);
    }

    public function test_only_your_own_filter_sets_appear_in_the_toolbar(): void
    {
        $user = $this->manager();
        $colleague = User::factory()->create(['company_id' => $user->company_id]);

        SavedFilter::factory()->create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'resource_key' => 'branches',
            'name' => 'Mine Only',
        ]);

        SavedFilter::factory()->create([
            'company_id' => $user->company_id,
            'user_id' => $colleague->id,
            'resource_key' => 'branches',
            'name' => 'Theirs Only',
        ]);

        $this->actingAs($user)
            ->get(route('masters.branches.index'))
            ->assertOk()
            ->assertSee('Mine Only')
            ->assertDontSee('Theirs Only');
    }
}
