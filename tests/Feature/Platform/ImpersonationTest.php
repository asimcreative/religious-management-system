<?php

namespace Tests\Feature\Platform;

use App\Enums\Status;
use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use App\Support\Impersonation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * The platform account looking inside a company.
 *
 * It signs in as that company's own administrator rather than being granted a
 * cross-tenant view, so every scope, policy and permission applies unchanged
 * and there is no second code path that could disagree with the first. The
 * session is read-only: it exists to answer "what does this company see?", not
 * to act on their behalf.
 */
class ImpersonationTest extends TestCase
{
    private function platformAccount(): User
    {
        $user = $this->createSuperAdmin();

        app(PermissionRegistrar::class)->setPermissionsTeamId($user->company_id);
        $role = Role::findByName('Super Admin', 'web');

        foreach (['company.view', 'company.update'] as $name) {
            $role->givePermissionTo(Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']));
        }

        return $user;
    }

    /**
     * A tenant with a Company Admin holding the given permissions.
     *
     * @param  list<string>  $permissions
     * @param  array<string, mixed>  $attributes
     * @return array{0: Company, 1: User}
     */
    private function tenant(array $permissions = ['employee.view', 'employee.create'], array $attributes = []): array
    {
        $company = Company::factory()->create($attributes);
        $admin = User::factory()->create(['company_id' => $company->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($company->id);
        $role = Role::findOrCreate('Company Admin', 'web');

        foreach ($permissions as $name) {
            $role->givePermissionTo(Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']));
        }

        $admin->assignRole($role);

        return [$company, $admin];
    }

    // ── Entering ───────────────────────────────────────────────────

    public function test_the_platform_account_signs_in_as_the_companys_own_administrator(): void
    {
        $platform = $this->platformAccount();
        [$company, $admin] = $this->tenant();

        // A second, role-less user proves the Company Admin is chosen rather
        // than simply the first account in the company.
        User::factory()->create(['company_id' => $company->id]);

        $this->actingAs($platform)
            ->post(route('impersonate.start', $company))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('success');

        $this->assertSame($admin->id, Auth::id());
        $this->assertTrue(Impersonation::isActive());
        $this->assertSame($platform->id, Impersonation::impersonatorId());
    }

    public function test_the_impersonated_session_sees_only_that_companys_records(): void
    {
        $platform = $this->platformAccount();
        [$company] = $this->tenant();

        Employee::factory()->create(['company_id' => $company->id, 'employee_name' => 'Ours Only']);

        $otherCompany = Company::factory()->create();
        Employee::factory()->create(['company_id' => $otherCompany->id, 'employee_name' => 'Somebody Else']);

        $this->actingAs($platform)->post(route('impersonate.start', $company));

        $this->get(route('employees.index'))
            ->assertOk()
            ->assertSee('Ours Only')
            ->assertDontSee('Somebody Else');
    }

    public function test_the_banner_names_the_company_and_carries_the_way_out(): void
    {
        $platform = $this->platformAccount();
        [$company] = $this->tenant();

        $this->actingAs($platform)->post(route('impersonate.start', $company));

        $this->get(route('employees.index'))
            ->assertOk()
            ->assertSee($company->company_name)
            ->assertSee(route('impersonate.stop'), false);
    }

    // ── Read only ──────────────────────────────────────────────────

    public function test_a_write_request_is_refused_however_it_is_made(): void
    {
        $platform = $this->platformAccount();
        [$company] = $this->tenant();

        $this->actingAs($platform)->post(route('impersonate.start', $company));

        $this->from(route('employees.index'))
            ->post(route('employees.store'), [
                'employee_code' => 'EMP-9001',
                'employee_name' => 'Should Not Exist',
            ])
            ->assertRedirect(route('employees.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('employees', ['employee_code' => 'EMP-9001']);
    }

    public function test_write_permissions_and_policy_abilities_are_both_denied(): void
    {
        $platform = $this->platformAccount();
        [$company] = $this->tenant();

        $this->actingAs($platform)->post(route('impersonate.start', $company));

        // Reading is exactly as the tenant admin normally has it…
        $this->assertTrue(Gate::allows('employee.view'));
        $this->assertTrue(Gate::allows('viewAny', Employee::class));

        // …and every way of asking to write is refused.
        $this->assertFalse(Gate::allows('employee.create'));
        $this->assertFalse(Gate::allows('create', Employee::class));
        $this->assertFalse(Auth::user()?->can('employee.create'));
        $this->assertFalse(Auth::user()?->hasPermissionTo('employee.create'));
    }

    public function test_the_create_button_is_not_offered_while_viewing(): void
    {
        $platform = $this->platformAccount();
        [$company] = $this->tenant();

        $this->actingAs($platform)->post(route('impersonate.start', $company));

        $this->get(route('employees.index'))
            ->assertOk()
            ->assertDontSee(route('employees.create'), false);
    }

    public function test_the_platform_account_keeps_its_own_write_permissions_when_not_viewing(): void
    {
        $platform = $this->platformAccount();

        $this->actingAs($platform);

        $this->assertTrue(Gate::allows('company.update'));
        $this->assertTrue(Gate::allows('update', Company::factory()->create()));
    }

    // ── Leaving ────────────────────────────────────────────────────

    public function test_leaving_returns_to_the_platform_account(): void
    {
        $platform = $this->platformAccount();
        [$company] = $this->tenant();

        $this->actingAs($platform)->post(route('impersonate.start', $company));
        $this->assertNotSame($platform->id, Auth::id());

        $this->post(route('impersonate.stop'))
            ->assertRedirect(route('companies.index'))
            ->assertSessionHas('success');

        $this->assertSame($platform->id, Auth::id());
        $this->assertFalse(Impersonation::isActive());
    }

    public function test_leaving_is_never_blocked_by_the_read_only_rule(): void
    {
        $platform = $this->platformAccount();
        [$company] = $this->tenant();

        $this->actingAs($platform)->post(route('impersonate.start', $company));

        // The stop route is a POST, which the read-only middleware refuses for
        // everything else. Trapping the platform account inside a tenant would
        // be worse than any write it could attempt.
        $this->post(route('impersonate.stop'))->assertRedirect(route('companies.index'));
    }

    public function test_the_platform_boundary_returns_once_the_platform_account_is_itself_again(): void
    {
        $platform = $this->platformAccount();
        [$company] = $this->tenant();

        $this->actingAs($platform)->post(route('impersonate.start', $company));
        $this->get(route('employees.index'))->assertOk();

        $this->post(route('impersonate.stop'));

        $this->get(route('employees.index'))->assertRedirect(route('companies.index'));
    }

    // ── Refusals ───────────────────────────────────────────────────

    public function test_an_ordinary_company_admin_cannot_sign_in_to_another_company(): void
    {
        $intruder = $this->createUserWithCompany(['company.view', 'company.update']);
        $target = Company::factory()->create();

        $this->actingAs($intruder)
            ->post(route('impersonate.start', $target))
            ->assertForbidden();
    }

    public function test_the_platform_company_itself_cannot_be_opened(): void
    {
        $platform = $this->platformAccount();
        $system = Company::findOrFail($platform->company_id);

        $this->actingAs($platform)
            ->post(route('impersonate.start', $system))
            ->assertForbidden();
    }

    public function test_a_suspended_company_is_refused_with_a_reason(): void
    {
        $platform = $this->platformAccount();
        [$company] = $this->tenant(attributes: ['status' => Status::Suspended]);

        $this->actingAs($platform)
            ->from(route('companies.index'))
            ->post(route('impersonate.start', $company))
            ->assertRedirect(route('companies.index'))
            ->assertSessionHas('error');

        $this->assertFalse(Impersonation::isActive());
    }

    public function test_a_company_with_no_active_user_is_refused(): void
    {
        $platform = $this->platformAccount();
        $company = Company::factory()->create();
        User::factory()->inactive()->create(['company_id' => $company->id]);

        $this->actingAs($platform)
            ->from(route('companies.index'))
            ->post(route('impersonate.start', $company))
            ->assertRedirect(route('companies.index'))
            ->assertSessionHas('error');

        $this->assertFalse(Impersonation::isActive());
    }

    public function test_a_second_company_cannot_be_opened_without_leaving_the_first(): void
    {
        $platform = $this->platformAccount();
        [$first] = $this->tenant();
        $second = Company::factory()->create();
        User::factory()->create(['company_id' => $second->id]);

        $this->actingAs($platform)->post(route('impersonate.start', $first));

        $this->from(route('dashboard'))
            ->post(route('impersonate.start', $second))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error');
    }

    // ── Audit ──────────────────────────────────────────────────────

    public function test_entering_and_leaving_are_both_recorded_against_the_platform_account(): void
    {
        $platform = $this->platformAccount();
        [$company, $admin] = $this->tenant();

        $this->actingAs($platform)->post(route('impersonate.start', $company));

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $platform->id,
            'module' => 'impersonation',
            'action' => 'start',
            'record_id' => $admin->id,
        ]);

        $this->post(route('impersonate.stop'));

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $platform->id,
            'module' => 'impersonation',
            'action' => 'stop',
            'record_id' => $admin->id,
        ]);
    }
}
