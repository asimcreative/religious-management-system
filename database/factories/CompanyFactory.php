<?php

namespace Database\Factories;

use App\Enums\Status;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_code' => strtoupper(fake()->unique()->bothify('COMP-####')),
            'company_name' => fake()->company(),
            'email' => fake()->unique()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'city' => fake()->city(),
            'country' => 'Pakistan',
            'timezone' => 'Asia/Karachi',
            'default_language' => 'en',
            'status' => Status::Active,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['status' => Status::Inactive]);
    }

    public function suspended(): static
    {
        return $this->state(['status' => Status::Suspended]);
    }

    public function withSubscription(string $plan = 'standard', int $daysRemaining = 30): static
    {
        return $this->state([
            'subscription_plan' => $plan,
            'subscription_expiry' => now()->addDays($daysRemaining),
        ]);
    }
}
