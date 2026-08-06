<?php

namespace Tests\Feature\Administration;

use App\Models\Company;
use App\Models\Setting;
use App\Models\User;
use App\Services\RoleService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Roles, Companies and Settings.
 *
 * All three administer the system rather than its data, so the interesting
 * cases are the boundaries: a role from another tenant, a permission the
 * granter does not hold, a tenant register only the platform account may open.
 */
class AdminModulesTest extends TestCase
{
    private function roleIn(int $companyId, string $name): Role
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($companyId);

        return Role::findOrCreate($name, 'web');
    }

    // ── Roles ──────────────────────────────────────────────────────

    public function test_the_role_list_requires_permission_and_shows_only_this_company(): void
    {
        $this->actingAs($this->createUserWithCompany())
            ->get(route('roles.index'))
            ->assertForbidden();

        $admin = $this->createUserWithCompany(['role.view']);
        $other = Company::factory()->create();

        $this->roleIn((int) $admin->company_id, 'Ours');
        $this->roleIn($other->id, 'Theirs');

        $this->actingAs($admin)
            ->get(route('roles.index'))
            ->assertOk()
            ->assertSee('Ours')
            ->assertDontSee('Theirs');
    }

    public function test_a_role_is_created_in_the_acting_users_company(): void
    {
        $admin = $this->createUserWithCompany(['role.view', 'role.create', 'employee.view']);

        $this->actingAs($admin)->post(route('roles.store'), [
            'name' => 'Front Desk',
            'permissions' => ['employee.view'],
        ])->assertRedirect(route('roles.index'));

        $this->assertDatabaseHas('roles', [
            'name' => 'Front Desk',
            'guard_name' => 'web',
            'company_id' => $admin->company_id,
        ]);
    }

    public function test_a_permission_the_granter_does_not_hold_is_not_granted(): void
    {
        $admin = $this->createUserWithCompany(['role.view', 'role.create', 'employee.view']);
        Permission::firstOrCreate(['name' => 'employee.delete', 'guard_name' => 'web']);

        $this->actingAs($admin)->post(route('roles.store'), [
            'name' => 'Overreach',
            // The actor holds employee.view but not employee.delete.
            'permissions' => ['employee.view', 'employee.delete'],
        ]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($admin->company_id);
        $role = Role::findByName('Overreach', 'web');

        $this->assertTrue($role->hasPermissionTo('employee.view'));
        $this->assertFalse(
            $role->hasPermissionTo('employee.delete'),
            'Nobody may hand out a right they do not hold themselves.',
        );
    }

    public function test_a_role_from_another_company_is_not_found(): void
    {
        $admin = $this->createUserWithCompany(['role.view', 'role.update']);
        $other = Company::factory()->create();
        $foreign = $this->roleIn($other->id, 'Foreign');

        $this->actingAs($admin)
            ->get(route('roles.edit', $foreign->id))
            ->assertNotFound();
    }

    public function test_a_system_role_cannot_be_renamed_or_deleted(): void
    {
        $admin = $this->createUserWithCompany(['role.view', 'role.update', 'role.delete']);
        $role = $this->roleIn((int) $admin->company_id, 'Company Admin');

        $this->actingAs($admin)->put(route('roles.update', $role->id), [
            'name' => 'Renamed Admin',
            'permissions' => [],
        ]);

        $this->assertSame('Company Admin', $role->fresh()->name);

        $this->actingAs($admin)
            ->delete(route('roles.destroy', $role->id))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('roles', ['id' => $role->id]);
    }

    public function test_a_role_still_held_by_someone_is_not_deleted(): void
    {
        $admin = $this->createUserWithCompany(['role.view', 'role.delete']);
        $role = $this->roleIn((int) $admin->company_id, 'In Use');

        $holder = User::factory()->create(['company_id' => $admin->company_id]);
        app(PermissionRegistrar::class)->setPermissionsTeamId($admin->company_id);
        $holder->assignRole($role);

        $this->actingAs($admin)
            ->delete(route('roles.destroy', $role->id))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('roles', ['id' => $role->id]);
    }

    public function test_an_unused_role_is_deleted(): void
    {
        $admin = $this->createUserWithCompany(['role.view', 'role.delete']);
        $role = $this->roleIn((int) $admin->company_id, 'Spare');

        $this->actingAs($admin)
            ->delete(route('roles.destroy', $role->id))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }

    public function test_permissions_are_grouped_by_module_for_the_form(): void
    {
        Permission::firstOrCreate(['name' => 'quran.attendance.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'quran.attendance.create', 'guard_name' => 'web']);

        $groups = app(RoleService::class)->groupedPermissions();

        $this->assertArrayHasKey('quran.attendance', $groups);

        // Not an exact count: the transfer-permission migration seeds
        // quran.attendance.import and .export into every database too.
        $names = array_map(static fn ($permission) => $permission->name, $groups['quran.attendance']);
        $this->assertContains('quran.attendance.view', $names);
        $this->assertContains('quran.attendance.create', $names);

        // The grouping key is everything before the last dot, so a three-part
        // permission must not land under "quran".
        $this->assertArrayNotHasKey('quran', $groups);
    }

    // ── Companies ──────────────────────────────────────────────────

    public function test_the_tenant_register_is_closed_to_ordinary_company_admins(): void
    {
        // Holds every company.* permission but is not the platform account.
        $admin = $this->createUserWithCompany(['company.view', 'company.create', 'company.update', 'company.delete']);

        $this->actingAs($admin)->get(route('companies.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('companies.create'))->assertForbidden();
    }

    public function test_the_platform_account_administers_tenants(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $permission = Permission::firstOrCreate(['name' => 'company.view', 'guard_name' => 'web']);
        app(PermissionRegistrar::class)->setPermissionsTeamId($superAdmin->company_id);
        Role::findByName('Super Admin', 'web')->givePermissionTo($permission);

        Company::factory()->create(['company_name' => 'Listed Tenant']);

        $this->actingAs($superAdmin)
            ->get(route('companies.index'))
            ->assertOk()
            ->assertSee('Listed Tenant');
    }

    public function test_the_platform_company_cannot_be_deleted(): void
    {
        $superAdmin = $this->createSuperAdmin();
        foreach (['company.view', 'company.delete'] as $name) {
            $permission = Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
            app(PermissionRegistrar::class)->setPermissionsTeamId($superAdmin->company_id);
            Role::findByName('Super Admin', 'web')->givePermissionTo($permission);
        }

        $system = Company::findOrFail($superAdmin->company_id);

        $this->actingAs($superAdmin)
            ->delete(route('companies.destroy', $system))
            ->assertForbidden();

        $this->assertDatabaseHas('companies', ['id' => $system->id]);
    }

    // ── Settings ───────────────────────────────────────────────────

    public function test_settings_require_permission(): void
    {
        $this->actingAs($this->createUserWithCompany())
            ->get(route('settings.index'))
            ->assertForbidden();
    }

    public function test_settings_show_current_values_with_defaults_filled_in(): void
    {
        $admin = $this->createUserWithCompany(['settings.view']);

        Setting::create([
            'company_id' => $admin->company_id,
            'key' => 'attendance_lock_time',
            'value' => '18:45',
        ]);

        $this->actingAs($admin)
            ->get(route('settings.index'))
            ->assertOk()
            ->assertSee('18:45', false);
    }

    public function test_a_setting_is_saved_against_the_company(): void
    {
        $admin = $this->createUserWithCompany(['settings.view', 'settings.update']);

        $this->actingAs($admin)->put(route('settings.update'), [
            'attendance_lock_time' => '20:15',
            'max_backdated_attendance_days' => '3',
        ])->assertRedirect(route('settings.index'));

        $this->assertDatabaseHas('settings', [
            'company_id' => $admin->company_id,
            'key' => 'attendance_lock_time',
            'value' => '20:15',
        ]);
    }

    public function test_a_key_outside_the_catalogue_is_never_stored(): void
    {
        $admin = $this->createUserWithCompany(['settings.view', 'settings.update']);

        $this->actingAs($admin)->put(route('settings.update'), [
            'attendance_lock_time' => '21:00',
            'max_backdated_attendance_days' => '5',
            'invented_key' => 'nonsense',
        ]);

        $this->assertDatabaseMissing('settings', ['key' => 'invented_key']);
    }

    public function test_an_invalid_lock_time_is_rejected(): void
    {
        $admin = $this->createUserWithCompany(['settings.view', 'settings.update']);

        $this->actingAs($admin)
            ->from(route('settings.index'))
            ->put(route('settings.update'), [
                'attendance_lock_time' => 'half past nine',
                'max_backdated_attendance_days' => '5',
            ])
            ->assertSessionHasErrors('attendance_lock_time');
    }
}
