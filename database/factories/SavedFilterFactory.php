<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\SavedFilter;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SavedFilter>
 */
class SavedFilterFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'user_id' => User::factory(),
            'resource_key' => 'employees',
            'name' => fake()->words(2, true),
            'query' => ['search' => fake()->word()],
            'is_default' => false,
        ];
    }
}
