<?php

namespace Database\Factories;

use App\Enums\TransferStatus;
use App\Models\Company;
use App\Models\ExportLog;
use App\Models\User;
use App\Support\DataTransfer\ExportFormat;
use App\Support\DataTransfer\ExportScope;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExportLog>
 */
class ExportLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'user_id' => User::factory(),
            'resource_key' => 'employees',
            'module_label' => 'Employees',
            'format' => ExportFormat::Xlsx->value,
            'scope' => ExportScope::All->value,
            'filters' => [],
            'record_count' => fake()->numberBetween(1, 5000),
            'was_truncated' => false,
            'file_name' => 'employees_'.now()->format('Y-m-d').'.xlsx',
            'file_path' => null,
            'file_size' => fake()->numberBetween(1024, 512000),
            'status' => TransferStatus::Completed,
            'duration_ms' => fake()->numberBetween(50, 20000),
            'ip_address' => fake()->ipv4(),
        ];
    }

    public function filtered(array $filters): self
    {
        return $this->state(fn (): array => [
            'scope' => ExportScope::Filtered->value,
            'filters' => $filters,
        ]);
    }
}
