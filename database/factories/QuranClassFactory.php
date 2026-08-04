<?php

namespace Database\Factories;

use App\Enums\Status;
use App\Models\Company;
use App\Models\QuranClass;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuranClass>
 */
class QuranClassFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'class_name' => 'Class '.fake()->word(),
            'class_code' => strtoupper(fake()->unique()->bothify('CLS-####')),
            'start_time' => fake()->time('H:i:s'),
            'end_time' => fake()->time('H:i:s'),
            'max_strength' => fake()->numberBetween(10, 50),
            'status' => Status::Active,
        ];
    }
}
