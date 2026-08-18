<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\QuranClass;
use App\Models\QuranTeacherAttendance;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuranTeacherAttendance>
 */
class QuranTeacherAttendanceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'attendance_date' => fake()->date(),
            'class_id' => static fn (array $attributes): int => QuranClass::factory()
                ->create(['company_id' => $attributes['company_id']])
                ->id,
        ];
    }
}
