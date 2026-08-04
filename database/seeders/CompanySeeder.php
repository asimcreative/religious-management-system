<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

/**
 * Seeds the system company and a demo company.
 *
 * Idempotent — safe to run multiple times via updateOrCreate.
 */
class CompanySeeder extends Seeder
{
    public function run(): void
    {
        // System company — houses the Super Admin
        Company::updateOrCreate(
            ['company_code' => 'SYSTEM'],
            [
                'company_name' => 'RAMS System',
                'email' => 'system@rams.test',
                'timezone' => 'Asia/Karachi',
                'default_language' => 'en',
                'status' => 1,
            ]
        );

        // Demo company for testing
        Company::updateOrCreate(
            ['company_code' => 'DEMO'],
            [
                'company_name' => 'Demo Organization',
                'email' => 'admin@demo.test',
                'phone' => '+92-300-0000000',
                'city' => 'Karachi',
                'country' => 'Pakistan',
                'timezone' => 'Asia/Karachi',
                'default_language' => 'en',
                'status' => 1,
            ]
        );
    }
}
