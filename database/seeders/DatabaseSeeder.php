<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Order matters: Companies → Prayers → Permissions → Roles → Users.
     */
    public function run(): void
    {
        $this->call([
            CompanySeeder::class,
            PrayerSeeder::class,
            PermissionSeeder::class,
            RoleSeeder::class,
            UserSeeder::class,
        ]);
    }
}
