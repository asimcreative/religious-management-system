<?php

namespace Tests\Feature;

use App\Models\Company;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class EmployeeRolePermissionMigrationTest extends TestCase
{
    public function test_existing_employee_roles_receive_the_employee_view_permission(): void
    {
        $company = Company::factory()->create();
        app(PermissionRegistrar::class)->setPermissionsTeamId($company->id);

        Permission::firstOrCreate(['name' => 'employee.view', 'guard_name' => 'web']);
        $role = Role::create(['name' => 'Employee', 'guard_name' => 'web']);

        /** @var Migration $migration */
        $migration = require database_path(
            'migrations/2026_08_04_130001_grant_employee_view_permission_to_existing_employee_roles.php',
        );
        $migration->up();

        $this->assertTrue($role->fresh()->hasPermissionTo('employee.view'));
    }
}
