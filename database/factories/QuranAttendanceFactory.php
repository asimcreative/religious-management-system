<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Employee;
use App\Models\QuranAttendance;
use App\Models\QuranClass;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuranAttendance>
 */
class QuranAttendanceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'attendance_date' => fake()->date(),
            'class_id' => static fn (array $attributes): int => QuranClass::factory()
                ->create(['company_id' => $attributes['company_id']])
                ->id,
            'employee_id' => static fn (array $attributes): int => Employee::factory()
                ->create(['company_id' => $attributes['company_id']])
                ->id,
        ];
    }
}
