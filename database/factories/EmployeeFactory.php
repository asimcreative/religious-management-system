<?php

namespace Database\Factories;

use App\Enums\Status;
use App\Models\Company;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'employee_code' => strtoupper(fake()->unique()->bothify('EMP-####')),
            'employee_name' => fake()->name('male'),
            'mobile' => fake()->phoneNumber(),
            'email' => fake()->safeEmail(),
            'dob' => fake()->date('Y-m-d', '-18 years'),
            'gender' => fake()->randomElement(['male', 'female']),
            'employment_status' => Status::Active,
        ];
    }

    public function withCnic(): static
    {
        return $this->state([
            'cnic' => fake()->numerify('#############'),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(['employment_status' => Status::Inactive]);
    }
}
