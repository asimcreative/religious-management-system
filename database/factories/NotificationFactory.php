<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Notification>
 */
class NotificationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'user_id' => User::factory(),
            'title' => fake()->sentence(4),
            'message' => fake()->paragraph(),
            'type' => fake()->randomElement(['info', 'warning', 'success', 'error']),
            'priority' => fake()->randomElement(['low', 'normal', 'high']),
        ];
    }

    public function read(): static
    {
        return $this->state(['read_at' => now()]);
    }
}
