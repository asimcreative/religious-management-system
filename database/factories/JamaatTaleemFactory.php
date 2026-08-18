<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Jamaat;
use App\Models\JamaatTaleem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JamaatTaleem>
 */
class JamaatTaleemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'attendance_date' => fake()->date(),
            'jamaat_id' => static fn (array $attributes): int => Jamaat::factory()
                ->create(['company_id' => $attributes['company_id']])
                ->id,
        ];
    }
}
