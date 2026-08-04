<?php

namespace Database\Factories;

use App\Enums\Status;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'mobile' => fake()->phoneNumber(),
            'status' => Status::Active,
            'language' => 'en',
            'remember_token' => Str::random(10),
        ];
    }

    public function inactive(): static
    {
        return $this->state(['status' => Status::Inactive]);
    }
}
