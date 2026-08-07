<?php

namespace Tests\Feature\Platform;

use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * The platform account administers companies and nothing else.
 *
 * It holds every permission and the company scope steps aside for it, so
 * without a boundary the tenant modules would open and show every company's
 * records merged into one list — and anything it created there would be stamped
 * with the platform's own company_id and silently become platform data.
 */
class PlatformBoundaryTest extends TestCase
{
    /** A Super Admin in the SYSTEM company, holding the company.* permissions. */
    private function platformAccount(): User
    {
        $user = $this->createSuperAdmin();

        app(PermissionRegistrar::class)->setPermissionsTeamId($user->company_id);
        $role = Role::findByName('Super Admin', 'web');

        foreach (['company.view', 'company.create', 'company.update', 'company.delete', 'employee.view'] as $name) {
            $role->givePermissionTo(Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']));
        }

        return $user;
    }

    public function test_the_platform_account_is_turned_away_from_every_tenant_module(): void
    {
        $platform = $this->platformAccount();

        // Holds employee.view, so only the boundary can be refusing this.
        $this->actingAs($platform)
            ->get(route('employees.index'))
            ->assertRedirect(route('companies.index'))
            ->assertSessionHas('error');
    }

    public function test_a_module_added_later_is_closed_to_the_platform_account_by_default(): void
    {
        $platform = $this->platformAccount();

        // Nothing in the boundary names these routes; they are refused because
        // the allowlist names what is permitted, not what is forbidden.
        foreach (['settings.index', 'roles.index', 'users.index', 'reports.index'] as $route) {
            $this->actingAs($platform)
                ->get(route($route))
                ->assertRedirect(route('companies.index'));
        }
    }

    public function test_the_platform_account_reaches_the_tenant_register_and_its_own_account_pages(): void
    {
        $platform = $this->platformAccount();

        $this->actingAs($platform)->get(route('companies.index'))->assertOk();
        $this->actingAs($platform)->get(route('companies.create'))->assertOk();
        $this->actingAs($platform)->get(route('password.change.form'))->assertOk();
    }

    public function test_the_menu_offers_companies_and_no_tenant_module(): void
    {
        $platform = $this->platformAccount();

        $response = $this->actingAs($platform)->get(route('companies.index'));

        $response->assertOk();
        $response->assertSee(route('companies.index'), false);
        $response->assertDontSee(route('employees.index'), false);
        $response->assertDontSee(route('settings.index'), false);
    }

    public function test_the_platform_account_gets_a_dashboard_about_companies_not_about_employees(): void
    {
        $platform = $this->platformAccount();

        Company::factory()->create(['company_name' => 'Al Noor Trust']);

        $response = $this->actingAs($platform)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewIs('platform-dashboard');
        $response->assertSee('Al Noor Trust');
    }

    public function test_the_platform_dashboard_counts_tenants_and_leaves_out_the_platform_company(): void
    {
        $platform = $this->platformAccount();

        Company::factory()->count(3)->create();

        $response = $this->actingAs($platform)->get(route('dashboard'));

        // The SYSTEM company created alongside the platform account is not a
        // tenant and must not be counted as one.
        $response->assertViewHas('overview', fn (array $overview) => $overview['total_companies'] === 3);
    }

    public function test_an_ordinary_tenant_user_is_untouched_by_the_boundary(): void
    {
        $user = $this->createUserWithCompany(['employee.view']);

        Employee::factory()->create([
            'company_id' => $user->company_id,
            'employee_name' => 'Bilal Ahmed',
        ]);

        $this->actingAs($user)
            ->get(route('employees.index'))
            ->assertOk()
            ->assertSee('Bilal Ahmed');
    }

    public function test_a_tenant_super_admin_role_does_not_open_the_platform_boundary(): void
    {
        // Same role name, but in a tenant company rather than SYSTEM.
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($company->id);
        $role = Role::findOrCreate('Super Admin', 'web');
        $role->givePermissionTo(Permission::firstOrCreate(['name' => 'employee.view', 'guard_name' => 'web']));
        $user->assignRole($role);

        $this->actingAs($user)->get(route('employees.index'))->assertOk();
        $this->actingAs($user)->get(route('companies.index'))->assertForbidden();
    }
}
