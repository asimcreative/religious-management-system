<?php

namespace Database\Factories;

use App\Enums\Status;
use App\Models\Company;
use App\Models\QuranDepartment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuranDepartment>
 */
class QuranDepartmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'department_name' => fake()->randomElement([
                'Hifz', 'Nazra', 'Tajweed', 'Qirat', 'Takhassus',
            ]),
            'description' => fake()->sentence(),
            'display_order' => fake()->numberBetween(1, 10),
            'status' => Status::Active,
        ];
    }
}
