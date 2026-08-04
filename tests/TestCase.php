<?php

namespace Tests;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        // Reset cached permission/role state between tests
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Create a company and a user belonging to it.
     * Optionally assign an array of permission names to the user.
     *
     * @param  array<string>  $permissions
     */
    protected function createUserWithCompany(array $permissions = []): User
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        // Set Spatie team context to this company
        app(PermissionRegistrar::class)->setPermissionsTeamId($company->id);

        if (! empty($permissions)) {
            foreach ($permissions as $name) {
                Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
            }

            $role = Role::firstOrCreate(
                ['name' => 'Test-Admin-'.$company->id, 'team_id' => $company->id, 'guard_name' => 'web'],
            );

            $role->givePermissionTo($permissions);
            $user->assignRole($role);
        }

        return $user;
    }

    /**
     * Create a Super Admin user that bypasses company scoping.
     *
     * The role is scoped to the user's own company (team_foreign_key is NOT NULL).
     * Callers must call setPermissionsTeamId($user->company_id) before invoking
     * hasRole() checks (e.g. before queries that trigger BelongsToCompany scope).
     */
    protected function createSuperAdmin(): User
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        // model_has_roles.company_id is NOT NULL, so use the company as team context.
        // setPermissionsTeamId() injects the company_id column automatically during create,
        // so we do NOT pass team_id explicitly (would trigger MassAssignmentException).
        app(PermissionRegistrar::class)->setPermissionsTeamId($company->id);
        $role = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $user->assignRole($role);

        return $user;
    }
}
