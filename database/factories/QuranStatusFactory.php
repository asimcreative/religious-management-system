<?php

namespace Database\Factories;

use App\Enums\Status;
use App\Models\Company;
use App\Models\QuranStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuranStatus>
 */
class QuranStatusFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'status_name' => fake()->randomElement([
                'In Progress', 'Completed', 'Paused', 'Not Started',
            ]),
            'description' => fake()->sentence(),
            'color' => fake()->hexColor(),
            'icon' => 'bi-book',
            'display_order' => fake()->numberBetween(1, 10),
            'status' => Status::Active,
        ];
    }
}
