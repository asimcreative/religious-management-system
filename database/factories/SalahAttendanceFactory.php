<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Employee;
use App\Models\Prayer;
use App\Models\SalahAttendance;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SalahAttendance>
 */
class SalahAttendanceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'attendance_date' => fake()->date(),
            'prayer_id' => Prayer::factory(),
            'employee_id' => static fn (array $attributes): int => Employee::factory()
                ->create(['company_id' => $attributes['company_id']])
                ->id,
        ];
    }
}
