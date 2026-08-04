<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Employee;
use App\Models\QuranProgress;
use App\Models\QuranProgressHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuranProgressHistory>
 */
class QuranProgressHistoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'progress_id' => QuranProgress::factory(),
            'employee_id' => Employee::factory(),
            'lesson' => 'Lesson '.fake()->numberBetween(1, 30),
            'surah' => fake()->randomElement(['Al-Fatiha', 'Al-Baqarah', 'Al-Imran']),
            'sipara' => fake()->numberBetween(1, 30),
            'page' => fake()->numberBetween(1, 604),
            'percentage' => fake()->randomFloat(2, 0, 100),
        ];
    }
}
