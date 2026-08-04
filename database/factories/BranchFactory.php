<?php

namespace Database\Factories;

use App\Enums\Status;
use App\Models\Branch;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Branch>
 */
class BranchFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'branch_name' => fake()->city().' Branch',
            'address' => fake()->address(),
            'phone' => fake()->phoneNumber(),
            'status' => Status::Active,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['status' => Status::Inactive]);
    }
}
