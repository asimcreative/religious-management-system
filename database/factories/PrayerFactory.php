<?php

namespace Database\Factories;

use App\Enums\Status;
use App\Models\Prayer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Prayer>
 */
class PrayerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'prayer_name' => fake()->randomElement(['Fajr', 'Zuhr', 'Asr', 'Maghrib', 'Isha']),
            'prayer_name_ur' => fake()->randomElement(['فجر', 'ظہر', 'عصر', 'مغرب', 'عشاء']),
            'prayer_order' => fake()->unique()->numberBetween(1, 5),
            'status' => Status::Active,
        ];
    }
}
