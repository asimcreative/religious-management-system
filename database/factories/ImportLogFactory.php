<?php

namespace Database\Factories;

use App\Enums\TransferStatus;
use App\Models\Company;
use App\Models\ImportLog;
use App\Models\User;
use App\Support\DataTransfer\DuplicateStrategy;
use App\Support\DataTransfer\ImportMode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ImportLog>
 */
class ImportLogFactory extends Factory
{
    public function definition(): array
    {
        $total = fake()->numberBetween(10, 500);
        $failed = fake()->numberBetween(0, (int) floor($total / 10));

        return [
            'company_id' => Company::factory(),
            'user_id' => User::factory(),
            'resource_key' => 'employees',
            'module_label' => 'Employees',
            'file_name' => 'employees.xlsx',
            'file_path' => 'data-transfer/1/imports/employees.xlsx',
            'file_size' => fake()->numberBetween(1024, 512000),
            'format' => 'xlsx',
            'mode' => ImportMode::SkipInvalid->value,
            'duplicate_strategy' => DuplicateStrategy::Skip->value,
            'total_rows' => $total,
            'imported_rows' => $total - $failed,
            'updated_rows' => 0,
            'skipped_rows' => 0,
            'failed_rows' => $failed,
            'error_file_path' => null,
            'error_summary' => null,
            'status' => $failed > 0 ? TransferStatus::CompletedWithErrors : TransferStatus::Completed,
            'started_at' => now()->subMinute(),
            'finished_at' => now(),
            'duration_ms' => fake()->numberBetween(200, 60000),
            'ip_address' => fake()->ipv4(),
        ];
    }

    public function queued(): self
    {
        return $this->state(fn (): array => [
            'status' => TransferStatus::Pending,
            'imported_rows' => 0,
            'updated_rows' => 0,
            'skipped_rows' => 0,
            'failed_rows' => 0,
            'started_at' => null,
            'finished_at' => null,
            'duration_ms' => null,
        ]);
    }

    public function failed(): self
    {
        return $this->state(fn (): array => [
            'status' => TransferStatus::Failed,
            'imported_rows' => 0,
            'exception' => 'The file could not be read.',
        ]);
    }
}
