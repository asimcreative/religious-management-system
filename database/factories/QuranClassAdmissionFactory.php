<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\QuranClassAdmission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuranClassAdmission>
 *
 * QuranClassMember has no factory of its own (it's created only through
 * QuranClassMemberService::addMember(), never seeded standalone), so
 * quran_class_member_id has no default here — callers must always pass one.
 */
class QuranClassAdmissionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'current_reading_level' => fake()->numberBetween(1, 10),
            'previously_completed_quran' => fake()->boolean(),
            'admission_date' => fake()->dateThisYear(),
            'classes_per_week' => fake()->randomElement([5, 6]),
            'remarks' => fake()->optional()->sentence(),
        ];
    }
}
