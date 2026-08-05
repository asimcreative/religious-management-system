<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $tableNames = config('permission.table_names');
        $permissionsTable = $tableNames['permissions'];
        $rolesTable = $tableNames['roles'];
        $rolePermissionsTable = $tableNames['role_has_permissions'];

        $permissionId = DB::table($permissionsTable)
            ->where('name', 'employee.view')
            ->where('guard_name', 'web')
            ->value('id');

        if ($permissionId === null) {
            $permissionId = DB::table($permissionsTable)->insertGetId([
                'name' => 'employee.view',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $roleIds = DB::table($rolesTable)
            ->where('name', 'Employee')
            ->where('guard_name', 'web')
            ->pluck('id');

        if ($roleIds->isNotEmpty()) {
            DB::table($rolePermissionsTable)->insertOrIgnore(
                $roleIds->map(fn (int $roleId): array => [
                    'permission_id' => $permissionId,
                    'role_id' => $roleId,
                ])->all(),
            );
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Permission assignments are production role configuration; retain them on rollback.
    }
};
