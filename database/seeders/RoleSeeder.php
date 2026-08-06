<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeds tenant roles for every company and the platform role for SYSTEM.
 *
 * Roles are created per company (Spatie team_id = company_id).
 * Super Admin gets ALL permissions.
 * Idempotent — safe to run multiple times.
 */
class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $companies = Company::all();

        foreach ($companies as $company) {
            $this->seedRolesForCompany($company);
        }
    }

    private function seedRolesForCompany(Company $company): void
    {
        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId($company->id);

        $roles = $this->getRolePermissions();

        if ($company->company_code !== 'SYSTEM') {
            unset($roles['Super Admin']);
        }

        foreach ($roles as $roleName => $permissions) {
            // findOrCreate uses the team_id set via setPermissionsTeamId()
            $role = Role::findOrCreate($roleName, 'web');

            if ($permissions === '*') {
                // Super Admin gets all permissions
                $role->syncPermissions(
                    Permission::where('guard_name', 'web')->pluck('name')->toArray()
                );
            } else {
                $role->syncPermissions($permissions);
            }
        }
    }

    /**
     * Role definitions.
     *
     * Bulk transfer follows least privilege and does not simply track CRUD
     * rights. Export is granted wherever a role may already read the records —
     * it moves no new information, only its container. Import is granted only
     * to the administrative roles that curate a module (Company Admin, HR
     * Manager, Religious Affairs Manager): a Quran Teacher records attendance
     * one class at a time by design, and handing them a spreadsheet that
     * rewrites a term of it is a different privilege entirely.
     *
     * @return array<string, string|list<string>>
     */
    private function getRolePermissions(): array
    {
        return [
            'Super Admin' => '*',

            'Company Admin' => [
                // Users & Roles
                'user.view', 'user.create', 'user.update', 'user.delete', 'user.restore', 'user.export', 'user.import',
                'role.view', 'role.create', 'role.update', 'role.delete', 'permission.assign',
                // Employees
                'employee.view', 'employee.create', 'employee.update', 'employee.delete', 'employee.restore',
                'employee.import', 'employee.export', 'employee.print', 'employee.report', 'employee.dashboard',
                // Teachers
                'teacher.view', 'teacher.create', 'teacher.update', 'teacher.delete', 'teacher.restore',
                'teacher.assign_branch', 'teacher.assign_class', 'teacher.report', 'teacher.dashboard',
                'teacher.import', 'teacher.export',
                // Quran
                'quran.class.view', 'quran.class.create', 'quran.class.update', 'quran.class.delete', 'quran.class.restore',
                'quran.class.import', 'quran.class.export',
                'quran.attendance.view', 'quran.attendance.create', 'quran.attendance.update', 'quran.attendance.delete',
                'quran.attendance.lock', 'quran.attendance.report',
                'quran.attendance.import', 'quran.attendance.export',
                'quran.progress.view', 'quran.progress.create', 'quran.progress.update', 'quran.progress.history', 'quran.progress.report',
                'quran.progress.import', 'quran.progress.export',
                // Jamaat & Salah
                'jamaat.view', 'jamaat.create', 'jamaat.update', 'jamaat.delete', 'jamaat.restore', 'jamaat.report',
                'jamaat.import', 'jamaat.export',
                'salah.attendance.view', 'salah.attendance.create', 'salah.attendance.update', 'salah.attendance.delete',
                'salah.attendance.lock', 'salah.attendance.report',
                'salah.attendance.import', 'salah.attendance.export',
                // Reports
                'report.dashboard', 'report.employee', 'report.teacher', 'report.quran', 'report.salah',
                'report.export_excel', 'report.export_pdf', 'report.export_csv', 'report.print',
                // Masters
                'branch.manage', 'branch.import', 'branch.export',
                'department.manage', 'department.import', 'department.export',
                'designation.manage', 'designation.import', 'designation.export',
                'attendance_reason.manage', 'attendance_reason.import', 'attendance_reason.export',
                'quran_department.manage', 'quran_department.import', 'quran_department.export',
                'quran_status.manage', 'quran_status.import', 'quran_status.export',
                'language.manage', 'language.import', 'language.export',
                // Settings & Logs
                'settings.view', 'settings.update',
                'notification.view', 'notification.read', 'notification.delete', 'notification.send', 'notification.export',
                'activity.view', 'activity.export', 'audit.view', 'audit.export',
            ],

            'HR Manager' => [
                'employee.view', 'employee.create', 'employee.update', 'employee.delete', 'employee.restore',
                'employee.import', 'employee.export', 'employee.print', 'employee.report', 'employee.dashboard',
                'report.dashboard', 'report.employee',
                'report.export_excel', 'report.export_pdf', 'report.export_csv', 'report.print',
                'branch.manage', 'branch.import', 'branch.export',
                'department.manage', 'department.import', 'department.export',
                'designation.manage', 'designation.import', 'designation.export',
                'notification.view', 'notification.read',
            ],

            'Religious Affairs Manager' => [
                'employee.view',
                // Teachers
                'teacher.view', 'teacher.create', 'teacher.update', 'teacher.delete', 'teacher.restore',
                'teacher.assign_branch', 'teacher.assign_class', 'teacher.report', 'teacher.dashboard',
                'teacher.import', 'teacher.export',
                // Quran
                'quran.class.view', 'quran.class.create', 'quran.class.update', 'quran.class.delete', 'quran.class.restore',
                'quran.class.import', 'quran.class.export',
                'quran.attendance.view', 'quran.attendance.create', 'quran.attendance.update', 'quran.attendance.delete',
                'quran.attendance.lock', 'quran.attendance.report',
                'quran.attendance.import', 'quran.attendance.export',
                'quran.progress.view', 'quran.progress.create', 'quran.progress.update', 'quran.progress.history', 'quran.progress.report',
                'quran.progress.import', 'quran.progress.export',
                // Jamaat & Salah
                'jamaat.view', 'jamaat.create', 'jamaat.update', 'jamaat.delete', 'jamaat.restore', 'jamaat.report',
                'jamaat.import', 'jamaat.export',
                'salah.attendance.view', 'salah.attendance.create', 'salah.attendance.update', 'salah.attendance.delete',
                'salah.attendance.lock', 'salah.attendance.report',
                'salah.attendance.import', 'salah.attendance.export',
                // Reports
                'report.dashboard', 'report.teacher', 'report.quran', 'report.salah',
                'report.export_excel', 'report.export_pdf', 'report.export_csv', 'report.print',
                // Masters
                'attendance_reason.manage', 'attendance_reason.import', 'attendance_reason.export',
                'quran_department.manage', 'quran_department.import', 'quran_department.export',
                'quran_status.manage', 'quran_status.import', 'quran_status.export',
                'notification.view', 'notification.read', 'notification.send',
            ],

            // Export only: a teacher's own classes are theirs to take away,
            // but a spreadsheet that rewrites a term of attendance is not.
            'Quran Teacher' => [
                'quran.class.view', 'quran.class.export',
                'quran.attendance.view', 'quran.attendance.create', 'quran.attendance.update', 'quran.attendance.export',
                'quran.progress.view', 'quran.progress.create', 'quran.progress.update', 'quran.progress.history',
                'quran.progress.export',
                'report.quran',
                'report.dashboard',
                'notification.view', 'notification.read',
            ],

            'Jamaat Leader' => [
                'jamaat.view', 'jamaat.export',
                'salah.attendance.view', 'salah.attendance.create', 'salah.attendance.update', 'salah.attendance.export',
                'report.salah',
                'report.dashboard',
                'notification.view', 'notification.read',
            ],

            // Their exports are already narrowed to their own branch or
            // department by RoleDataAccessService.
            'Branch Manager' => [
                'employee.view', 'employee.report', 'employee.dashboard', 'employee.export',
                'teacher.view', 'teacher.report', 'teacher.export',
                'report.dashboard', 'report.employee', 'report.teacher',
                'report.export_excel', 'report.export_pdf', 'report.print',
                'notification.view', 'notification.read',
            ],

            'Department Manager' => [
                'employee.view', 'employee.report', 'employee.dashboard', 'employee.export',
                'report.dashboard', 'report.employee',
                'report.export_excel', 'report.export_pdf', 'report.print',
                'notification.view', 'notification.read',
            ],

            'Employee' => [
                'employee.view',
                'quran.progress.view',
                'quran.attendance.view',
                'salah.attendance.view',
                'notification.view', 'notification.read',
            ],

            // Reads everything and takes copies; changes nothing.
            'Auditor' => [
                'employee.view', 'employee.report', 'employee.export',
                'teacher.view', 'teacher.report', 'teacher.export',
                'quran.class.view', 'quran.class.export',
                'quran.attendance.view', 'quran.attendance.report', 'quran.attendance.export',
                'quran.progress.view', 'quran.progress.report', 'quran.progress.export',
                'jamaat.view', 'jamaat.report', 'jamaat.export',
                'salah.attendance.view', 'salah.attendance.report', 'salah.attendance.export',
                'report.dashboard', 'report.employee', 'report.teacher', 'report.quran', 'report.salah',
                'report.audit', 'report.activity',
                'report.export_excel', 'report.export_pdf', 'report.export_csv', 'report.print',
                'activity.view', 'activity.export',
                'audit.view', 'audit.export',
                'notification.view', 'notification.read',
            ],
        ];
    }
}
