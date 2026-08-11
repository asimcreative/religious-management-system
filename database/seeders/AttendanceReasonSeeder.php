<?php

namespace Database\Seeders;

use App\Services\CompanyProvisioningService;
use Illuminate\Database\Seeder;

/**
 * Gives every tenant the default attendance reasons from config/master-data.php.
 *
 * Without them the attendance sheets can only record "present", because presence
 * is stored as attendance_reason_id = NULL and every other status is a row here.
 *
 * Idempotent — a company that already holds reasons is left untouched, so this
 * never overwrites or resurrects what an admin has changed.
 */
class AttendanceReasonSeeder extends Seeder
{
    public function __construct(
        private readonly CompanyProvisioningService $provisioning,
    ) {}

    public function run(): void
    {
        $created = $this->provisioning->provisionAllTenants();

        $this->command?->info("Attendance reasons provisioned: {$created}");
    }
}
