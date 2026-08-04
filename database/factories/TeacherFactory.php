<?php

namespace Database\Factories;

use App\Enums\Status;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Teacher>
 */
class TeacherFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'employee_id' => Employee::factory(),
            'teacher_code' => strtoupper(fake()->unique()->bothify('TCH-####')),
            'status' => Status::Active,
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(['status' => Status::Inactive]);
    }
}
