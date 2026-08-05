<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;
use LogicException;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeds the initial users: Super Admin and Demo Company Admin.
 *
 * Idempotent — safe to run multiple times via updateOrCreate.
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        $registrar = app(PermissionRegistrar::class);

        $superAdminEmail = 'superadmin@rams.test';
        $superAdminPassword = 'SuperAdmin@1234';

        if (app()->isProduction()) {
            $superAdminEmail = config('seed.initial_super_admin.email');
            $superAdminPassword = config('seed.initial_super_admin.password');

            if (! is_string($superAdminEmail) || blank($superAdminEmail)
                || ! is_string($superAdminPassword) || blank($superAdminPassword)) {
                throw new LogicException(
                    'INITIAL_SUPER_ADMIN_EMAIL and INITIAL_SUPER_ADMIN_PASSWORD are required when seeding production users.'
                );
            }
        }

        // ── Super Admin ──────────────────────────────────────────
        $systemCompany = Company::where('company_code', 'SYSTEM')->firstOrFail();
        $registrar->setPermissionsTeamId($systemCompany->id);

        $superAdmin = User::withoutGlobalScopes()->updateOrCreate(
            ['email' => $superAdminEmail, 'company_id' => $systemCompany->id],
            [
                'name' => 'Super Admin',
                'password' => $superAdminPassword,
                'status' => 1,
                'language' => 'en',
            ]
        );
        $superAdmin->assignRole('Super Admin');

        if (app()->isProduction()) {
            return;
        }

        // ── Demo Company Admin ───────────────────────────────────
        $demoCompany = Company::where('company_code', 'DEMO')->firstOrFail();
        $registrar->setPermissionsTeamId($demoCompany->id);

        $demoAdmin = User::withoutGlobalScopes()->updateOrCreate(
            ['email' => 'admin@demo.test', 'company_id' => $demoCompany->id],
            [
                'name' => 'Demo Admin',
                'password' => 'DemoAdmin@1234',
                'status' => 1,
                'language' => 'en',
            ]
        );
        $demoAdmin->assignRole('Company Admin');
    }
}
