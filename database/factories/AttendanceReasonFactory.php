<?php

namespace Database\Factories;

use App\Enums\Status;
use App\Models\AttendanceReason;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceReason>
 */
class AttendanceReasonFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'reason_name' => fake()->randomElement(['Sick Leave', 'Travel', 'Personal', 'Emergency']),
            'color' => fake()->hexColor(),
            'icon' => 'bi-x-circle',
            'counts_as_absent' => true,
            'counts_as_leave' => false,
            'status' => Status::Active,
        ];
    }

    public function leave(): static
    {
        return $this->state([
            'counts_as_absent' => false,
            'counts_as_leave' => true,
        ]);
    }
}
