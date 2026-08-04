<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeds the 10 system roles with their permissions for each company.
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

        foreach ($this->getRolePermissions() as $roleName => $permissions) {
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
                // Quran
                'quran.class.view', 'quran.class.create', 'quran.class.update', 'quran.class.delete', 'quran.class.restore',
                'quran.attendance.view', 'quran.attendance.create', 'quran.attendance.update', 'quran.attendance.delete',
                'quran.attendance.lock', 'quran.attendance.report',
                'quran.progress.view', 'quran.progress.create', 'quran.progress.update', 'quran.progress.history', 'quran.progress.report',
                // Jamaat & Salah
                'jamaat.view', 'jamaat.create', 'jamaat.update', 'jamaat.delete', 'jamaat.restore', 'jamaat.report',
                'salah.attendance.view', 'salah.attendance.create', 'salah.attendance.update', 'salah.attendance.delete',
                'salah.attendance.lock', 'salah.attendance.report',
                // Reports
                'report.dashboard', 'report.employee', 'report.teacher', 'report.quran', 'report.salah',
                'report.export_excel', 'report.export_pdf', 'report.export_csv', 'report.print',
                // Masters
                'branch.manage', 'department.manage', 'designation.manage', 'attendance_reason.manage',
                'quran_department.manage', 'quran_status.manage', 'language.manage',
                // Settings & Logs
                'settings.view', 'settings.update',
                'notification.view', 'notification.read', 'notification.delete', 'notification.send',
                'activity.view', 'activity.export', 'audit.view', 'audit.export',
            ],

            'HR Manager' => [
                'employee.view', 'employee.create', 'employee.update', 'employee.delete', 'employee.restore',
                'employee.import', 'employee.export', 'employee.print', 'employee.report', 'employee.dashboard',
                'report.dashboard', 'report.employee',
                'report.export_excel', 'report.export_pdf', 'report.export_csv', 'report.print',
                'branch.manage', 'department.manage', 'designation.manage',
                'notification.view', 'notification.read',
            ],

            'Religious Affairs Manager' => [
                'employee.view',
                // Teachers
                'teacher.view', 'teacher.create', 'teacher.update', 'teacher.delete', 'teacher.restore',
                'teacher.assign_branch', 'teacher.assign_class', 'teacher.report', 'teacher.dashboard',
                // Quran
                'quran.class.view', 'quran.class.create', 'quran.class.update', 'quran.class.delete', 'quran.class.restore',
                'quran.attendance.view', 'quran.attendance.create', 'quran.attendance.update', 'quran.attendance.delete',
                'quran.attendance.lock', 'quran.attendance.report',
                'quran.progress.view', 'quran.progress.create', 'quran.progress.update', 'quran.progress.history', 'quran.progress.report',
                // Jamaat & Salah
                'jamaat.view', 'jamaat.create', 'jamaat.update', 'jamaat.delete', 'jamaat.restore', 'jamaat.report',
                'salah.attendance.view', 'salah.attendance.create', 'salah.attendance.update', 'salah.attendance.delete',
                'salah.attendance.lock', 'salah.attendance.report',
                // Reports
                'report.dashboard', 'report.teacher', 'report.quran', 'report.salah',
                'report.export_excel', 'report.export_pdf', 'report.export_csv', 'report.print',
                // Masters
                'attendance_reason.manage', 'quran_department.manage', 'quran_status.manage',
                'notification.view', 'notification.read', 'notification.send',
            ],

            'Quran Teacher' => [
                'quran.class.view',
                'quran.attendance.view', 'quran.attendance.create', 'quran.attendance.update',
                'quran.progress.view', 'quran.progress.create', 'quran.progress.update', 'quran.progress.history',
                'report.quran',
                'report.dashboard',
                'notification.view', 'notification.read',
            ],

            'Jamaat Leader' => [
                'jamaat.view',
                'salah.attendance.view', 'salah.attendance.create', 'salah.attendance.update',
                'report.salah',
                'report.dashboard',
                'notification.view', 'notification.read',
            ],

            'Branch Manager' => [
                'employee.view', 'employee.report', 'employee.dashboard',
                'teacher.view', 'teacher.report',
                'report.dashboard', 'report.employee', 'report.teacher',
                'report.export_excel', 'report.export_pdf', 'report.print',
                'notification.view', 'notification.read',
            ],

            'Department Manager' => [
                'employee.view', 'employee.report', 'employee.dashboard',
                'report.dashboard', 'report.employee',
                'report.export_excel', 'report.export_pdf', 'report.print',
                'notification.view', 'notification.read',
            ],

            'Employee' => [
                'quran.progress.view',
                'quran.attendance.view',
                'salah.attendance.view',
                'notification.view', 'notification.read',
            ],

            'Auditor' => [
                'employee.view', 'employee.report',
                'teacher.view', 'teacher.report',
                'quran.class.view', 'quran.attendance.view', 'quran.attendance.report',
                'quran.progress.view', 'quran.progress.report',
                'jamaat.view', 'jamaat.report',
                'salah.attendance.view', 'salah.attendance.report',
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
