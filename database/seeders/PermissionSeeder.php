<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeds all system permissions per the Permission Matrix (docs/31).
 *
 * Idempotent — safe to run multiple times via firstOrCreate.
 * Permissions are created without a team_id so they are available globally.
 */
class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = $this->getPermissions();

        // Temporarily disable team scope for global permission creation
        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId(null);

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }
    }

    /**
     * @return list<string>
     */
    private function getPermissions(): array
    {
        return [
            // ── Company ──────────────────────────────────────────
            'company.view',
            'company.create',
            'company.update',
            'company.delete',
            'company.restore',
            'company.settings',
            'company.subscription',

            // ── User ─────────────────────────────────────────────
            'user.view',
            'user.create',
            'user.update',
            'user.delete',
            'user.restore',
            'user.export',
            'user.import',

            // ── Role ─────────────────────────────────────────────
            'role.view',
            'role.create',
            'role.update',
            'role.delete',
            'permission.assign',

            // ── Employee ─────────────────────────────────────────
            'employee.view',
            'employee.create',
            'employee.update',
            'employee.delete',
            'employee.restore',
            'employee.import',
            'employee.export',
            'employee.print',
            'employee.report',
            'employee.dashboard',

            // ── Teacher ──────────────────────────────────────────
            'teacher.view',
            'teacher.create',
            'teacher.update',
            'teacher.delete',
            'teacher.restore',
            'teacher.assign_branch',
            'teacher.assign_class',
            'teacher.report',
            'teacher.dashboard',

            // ── Quran Class ──────────────────────────────────────
            'quran.class.view',
            'quran.class.create',
            'quran.class.update',
            'quran.class.delete',
            'quran.class.restore',

            // ── Quran Attendance ──────────────────────────────────
            'quran.attendance.view',
            'quran.attendance.create',
            'quran.attendance.update',
            'quran.attendance.delete',
            'quran.attendance.lock',
            'quran.attendance.report',

            // ── Quran Progress ────────────────────────────────────
            'quran.progress.view',
            'quran.progress.create',
            'quran.progress.update',
            'quran.progress.history',
            'quran.progress.report',

            // ── Jamaat ────────────────────────────────────────────
            'jamaat.view',
            'jamaat.create',
            'jamaat.update',
            'jamaat.delete',
            'jamaat.restore',
            'jamaat.report',

            // ── Salah Attendance ──────────────────────────────────
            'salah.attendance.view',
            'salah.attendance.create',
            'salah.attendance.update',
            'salah.attendance.delete',
            'salah.attendance.lock',
            'salah.attendance.report',

            // ── Reports ──────────────────────────────────────────
            'report.dashboard',
            'report.employee',
            'report.teacher',
            'report.quran',
            'report.salah',
            'report.audit',
            'report.activity',
            'report.export_excel',
            'report.export_pdf',
            'report.export_csv',
            'report.print',

            // ── Master Data ──────────────────────────────────────
            'branch.manage',
            'department.manage',
            'designation.manage',
            'attendance_reason.manage',
            'quran_department.manage',
            'quran_status.manage',
            'language.manage',

            // ── Settings ─────────────────────────────────────────
            'settings.view',
            'settings.update',
            'smtp.manage',
            'backup.manage',
            'system.logs',

            // ── Notifications ────────────────────────────────────
            'notification.view',
            'notification.read',
            'notification.delete',
            'notification.send',

            // ── Activity/Audit Logs ──────────────────────────────
            'activity.view',
            'activity.export',
            'audit.view',
            'audit.export',

            // ── API ──────────────────────────────────────────────
            'api.access',
            'api.generate_token',
            'api.revoke_token',
        ];
    }
}
