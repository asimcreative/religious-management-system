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
            'teacher.import',
            'teacher.export',

            // ── Quran Class ──────────────────────────────────────
            'quran.class.view',
            'quran.class.create',
            'quran.class.update',
            'quran.class.delete',
            'quran.class.restore',
            'quran.class.import',
            'quran.class.export',

            // ── Quran Attendance ──────────────────────────────────
            'quran.attendance.view',
            'quran.attendance.create',
            'quran.attendance.update',
            'quran.attendance.delete',
            'quran.attendance.lock',
            'quran.attendance.report',
            'quran.attendance.import',
            'quran.attendance.export',

            // ── Quran Progress ────────────────────────────────────
            'quran.progress.view',
            'quran.progress.create',
            'quran.progress.update',
            'quran.progress.history',
            'quran.progress.report',
            'quran.progress.import',
            'quran.progress.export',

            // ── Jamaat ────────────────────────────────────────────
            'jamaat.view',
            'jamaat.create',
            'jamaat.update',
            'jamaat.delete',
            'jamaat.restore',
            'jamaat.report',
            'jamaat.import',
            'jamaat.export',

            // ── Salah Attendance ──────────────────────────────────
            'salah.attendance.view',
            'salah.attendance.create',
            'salah.attendance.update',
            'salah.attendance.delete',
            'salah.attendance.lock',
            'salah.attendance.report',
            'salah.attendance.import',
            'salah.attendance.export',

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
            // Master modules keep their single ".manage" right for CRUD, but
            // bulk transfer is separable: a role may be trusted to edit a
            // branch one at a time without being trusted to replace the whole
            // branch list from a spreadsheet.
            'branch.manage',
            'branch.import',
            'branch.export',
            'department.manage',
            'department.import',
            'department.export',
            'designation.manage',
            'designation.import',
            'designation.export',
            'attendance_reason.manage',
            'attendance_reason.import',
            'attendance_reason.export',
            'quran_department.manage',
            'quran_department.import',
            'quran_department.export',
            'quran_status.manage',
            'quran_status.import',
            'quran_status.export',
            'language.manage',
            'language.import',
            'language.export',

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
            'notification.export',

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
