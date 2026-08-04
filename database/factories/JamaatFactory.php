<?php

namespace Database\Factories;

use App\Enums\Status;
use App\Models\Company;
use App\Models\Jamaat;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Jamaat>
 */
class JamaatFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'jamaat_number' => strtoupper(fake()->unique()->bothify('JMT-####')),
            'jamaat_name' => 'Jamaat '.fake()->word(),
            'status' => Status::Active,
        ];
    }
}
