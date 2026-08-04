<?php

namespace Database\Seeders;

use App\Models\Prayer;
use Illuminate\Database\Seeder;

/**
 * Seeds the 5 daily prayers.
 *
 * These are global (not company-specific).
 * Idempotent — safe to run multiple times via updateOrCreate.
 */
class PrayerSeeder extends Seeder
{
    public function run(): void
    {
        $prayers = [
            ['prayer_name' => 'Fajr', 'prayer_name_ur' => 'فجر', 'prayer_order' => 1, 'status' => 1],
            ['prayer_name' => 'Dhuhr', 'prayer_name_ur' => 'ظہر', 'prayer_order' => 2, 'status' => 1],
            ['prayer_name' => 'Asr', 'prayer_name_ur' => 'عصر', 'prayer_order' => 3, 'status' => 1],
            ['prayer_name' => 'Maghrib', 'prayer_name_ur' => 'مغرب', 'prayer_order' => 4, 'status' => 1],
            ['prayer_name' => 'Isha', 'prayer_name_ur' => 'عشاء', 'prayer_order' => 5, 'status' => 1],
        ];

        foreach ($prayers as $prayer) {
            Prayer::updateOrCreate(
                ['prayer_name' => $prayer['prayer_name']],
                $prayer
            );
        }
    }
}
