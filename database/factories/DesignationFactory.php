<?php

namespace Database\Factories;

use App\Enums\Status;
use App\Models\Company;
use App\Models\Designation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Designation>
 */
class DesignationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'designation_name' => fake()->randomElement([
                'Imam', 'Muezzin', 'Teacher', 'Administrator', 'Manager', 'Staff',
            ]),
            'status' => Status::Active,
        ];
    }
}
