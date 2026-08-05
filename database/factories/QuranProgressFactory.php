<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Employee;
use App\Models\QuranProgress;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuranProgress>
 */
class QuranProgressFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'employee_id' => static fn (array $attributes): int => Employee::factory()
                ->create(['company_id' => $attributes['company_id']])
                ->id,
            'current_lesson' => 'Lesson '.fake()->numberBetween(1, 30),
            'current_surah' => fake()->randomElement(['Al-Fatiha', 'Al-Baqarah', 'Al-Imran', 'An-Nisa']),
            'current_sipara' => fake()->numberBetween(1, 30),
            'current_page' => fake()->numberBetween(1, 604),
            'completion_percentage' => fake()->randomFloat(2, 0, 100),
        ];
    }
}
