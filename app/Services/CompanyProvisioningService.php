<?php

namespace App\Services;

use App\Enums\Status;
use App\Models\AttendanceReason;
use App\Models\Company;
use Illuminate\Support\Facades\DB;

/**
 * Baseline master data for a newly created company.
 *
 * A tenant starts with none of its own master records, and some screens degrade
 * quietly without them rather than failing loudly. The attendance sheets are the
 * clearest case: presence is stored as attendance_reason_id = NULL and every
 * other status is a row in attendance_reasons, so a company with no reasons can
 * only ever record "present" — which reads as perfect attendance rather than as
 * missing configuration.
 *
 * The defaults live in config/master-data.php. They are a starting point, not a
 * fixture: once provisioned they belong to the company, and provisioning is
 * deliberately non-destructive so a re-run cannot undo what an admin has since
 * renamed, deactivated or deleted.
 */
class CompanyProvisioningService
{
    /**
     * Give a company the master data it needs before anyone uses it.
     *
     * Safe to call more than once, and skipped for the platform account, which
     * administers the register of companies and holds no business data.
     *
     * @return int the number of records created
     */
    public function provision(Company $company): int
    {
        if ($company->isPlatform()) {
            return 0;
        }

        return DB::transaction(
            fn (): int => $this->provisionAttendanceReasons((int) $company->getKey())
        );
    }

    /**
     * Provision every tenant that has none, leaving configured tenants alone.
     *
     * @return int the number of records created across all companies
     */
    public function provisionAllTenants(): int
    {
        return Company::query()
            ->tenants()
            ->get()
            ->sum(fn (Company $company): int => $this->provision($company));
    }

    /**
     * The company is seeded only when it holds no attendance reasons at all.
     *
     * Matching default-by-default would be the obvious approach and is the wrong
     * one: a company that renamed "Absent" would be handed a second Absent on
     * the next run, and one that deleted a default it does not use would get it
     * back. Soft-deleted rows count as held for the same reason — the record of
     * a deliberate deletion is the deletion itself.
     */
    private function provisionAttendanceReasons(int $companyId): int
    {
        $alreadyConfigured = AttendanceReason::withoutGlobalScope('company')
            ->withTrashed()
            ->forCompany($companyId)
            ->exists();

        if ($alreadyConfigured) {
            return 0;
        }

        $defaults = config('master-data.attendance_reasons', []);

        foreach ($defaults as $default) {
            AttendanceReason::withoutGlobalScope('company')->create([
                'company_id' => $companyId,
                'reason_name' => $default['reason_name'],
                'color' => $default['color'] ?? null,
                'icon' => $default['icon'] ?? null,
                'counts_as_absent' => $default['counts_as_absent'] ?? false,
                'counts_as_leave' => $default['counts_as_leave'] ?? false,
                'status' => Status::Active,
            ]);
        }

        return count($defaults);
    }
}
