<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

/**
 * Give existing roles the new import/export rights they have already earned.
 *
 * RoleSeeder is the authority for a fresh install, but it uses syncPermissions
 * and is not re-run against live tenants. Without this, every role on an
 * existing installation would silently lose access to a toolbar that appears
 * on every list screen.
 *
 * The rule is deliberately conservative and derived, not hard-coded per role:
 *
 *   export — granted wherever the role can already read the module. Exporting
 *            moves no information the role could not already see on screen;
 *            it changes the container, not the audience.
 *
 *   import — granted only where the role can already create in that module.
 *            A role that cannot add one record must not be able to add ten
 *            thousand.
 *
 * No role gains a capability it did not already have in single-record form.
 */
return new class extends Migration
{
    /**
     * New permission => the existing permission a role must hold to receive it.
     *
     * @var array<string, string>
     */
    private const GRANTS = [
        // Export follows read access.
        'teacher.export' => 'teacher.view',
        'quran.class.export' => 'quran.class.view',
        'quran.attendance.export' => 'quran.attendance.view',
        'quran.progress.export' => 'quran.progress.view',
        'jamaat.export' => 'jamaat.view',
        'salah.attendance.export' => 'salah.attendance.view',
        'notification.export' => 'notification.view',

        // Import follows create access.
        'teacher.import' => 'teacher.create',
        'quran.class.import' => 'quran.class.create',
        'quran.attendance.import' => 'quran.attendance.create',
        'quran.progress.import' => 'quran.progress.create',
        'jamaat.import' => 'jamaat.create',
        'salah.attendance.import' => 'salah.attendance.create',

        // Master modules express all of CRUD as one ".manage" right, so both
        // sides of the transfer follow it.
        'branch.export' => 'branch.manage',
        'branch.import' => 'branch.manage',
        'department.export' => 'department.manage',
        'department.import' => 'department.manage',
        'designation.export' => 'designation.manage',
        'designation.import' => 'designation.manage',
        'attendance_reason.export' => 'attendance_reason.manage',
        'attendance_reason.import' => 'attendance_reason.manage',
        'quran_department.export' => 'quran_department.manage',
        'quran_department.import' => 'quran_department.manage',
        'quran_status.export' => 'quran_status.manage',
        'quran_status.import' => 'quran_status.manage',
        'language.export' => 'language.manage',
        'language.import' => 'language.manage',
    ];

    public function up(): void
    {
        $tables = config('permission.table_names');
        $permissionsTable = $tables['permissions'];
        $pivotTable = $tables['role_has_permissions'];

        $assignments = [];

        foreach (self::GRANTS as $newPermission => $prerequisite) {
            $newId = $this->permissionId($permissionsTable, $newPermission);
            $prerequisiteId = $this->existingPermissionId($permissionsTable, $prerequisite);

            if ($prerequisiteId === null) {
                continue;
            }

            foreach ($this->rolesHolding($pivotTable, $prerequisiteId) as $roleId) {
                $assignments[] = ['permission_id' => $newId, 'role_id' => $roleId];
            }
        }

        // Super Admin holds everything by definition, including modules whose
        // CRUD pages do not exist yet.
        foreach ($this->platformRoleIds() as $roleId) {
            foreach (array_keys(self::GRANTS) as $newPermission) {
                $assignments[] = [
                    'permission_id' => $this->permissionId($permissionsTable, $newPermission),
                    'role_id' => $roleId,
                ];
            }
        }

        foreach (array_chunk($assignments, 500) as $chunk) {
            DB::table($pivotTable)->insertOrIgnore($chunk);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Role configuration is production data. Removing rights on rollback
        // would break working installations, so the grants are retained.
    }

    private function permissionId(string $table, string $name): int
    {
        $id = $this->existingPermissionId($table, $name);

        if ($id !== null) {
            return $id;
        }

        return (int) DB::table($table)->insertGetId([
            'name' => $name,
            'guard_name' => 'web',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function existingPermissionId(string $table, string $name): ?int
    {
        $id = DB::table($table)
            ->where('name', $name)
            ->where('guard_name', 'web')
            ->value('id');

        return $id === null ? null : (int) $id;
    }

    /** @return Collection<int, int> */
    private function rolesHolding(string $pivotTable, int $permissionId): Collection
    {
        return DB::table($pivotTable)
            ->where('permission_id', $permissionId)
            ->pluck('role_id')
            ->map(static fn ($id): int => (int) $id);
    }

    /** @return Collection<int, int> */
    private function platformRoleIds(): Collection
    {
        return DB::table(config('permission.table_names')['roles'])
            ->where('name', 'Super Admin')
            ->where('guard_name', 'web')
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id);
    }
};
