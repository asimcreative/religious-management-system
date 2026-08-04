<?php

namespace Database\Factories;

use App\Enums\Status;
use App\Models\Company;
use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Department>
 */
class DepartmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'department_name' => fake()->randomElement([
                'Administration', 'Finance', 'Education', 'Maintenance', 'Security',
            ]),
            'status' => Status::Active,
        ];
    }
}
