<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'user_id' => User::factory(),
            'module' => fake()->randomElement(['employees', 'teachers', 'attendance']),
            'action' => fake()->randomElement(['created', 'updated', 'deleted']),
            'table_name' => fake()->randomElement(['employees', 'teachers', 'quran_attendance']),
            'record_id' => fake()->numberBetween(1, 1000),
            'old_values' => null,
            'new_values' => ['name' => fake()->name()],
            'ip_address' => fake()->ipv4(),
            'browser' => 'Chrome',
            'operating_system' => 'Windows',
        ];
    }
}
