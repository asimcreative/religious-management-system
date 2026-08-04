<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;
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

        // ── Super Admin ──────────────────────────────────────────
        $systemCompany = Company::where('company_code', 'SYSTEM')->firstOrFail();
        $registrar->setPermissionsTeamId($systemCompany->id);

        $superAdmin = User::withoutGlobalScopes()->updateOrCreate(
            ['email' => 'superadmin@rams.test', 'company_id' => $systemCompany->id],
            [
                'name' => 'Super Admin',
                'password' => 'SuperAdmin@1234',
                'status' => 1,
                'language' => 'en',
            ]
        );
        $superAdmin->assignRole('Super Admin');

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
