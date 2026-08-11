<?php

use App\Models\Company;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Backfills the companies that predate attendance-reason provisioning.
 *
 * Presence is stored as attendance_reason_id = NULL and every other status is a
 * row in attendance_reasons, so a company with no reasons can only ever record
 * "present" — which reads as perfect attendance rather than as an unconfigured
 * module. Companies created from here on are provisioned at creation time; this
 * catches the ones already in the database.
 *
 * Deliberately written against the query builder rather than the provisioning
 * service: a migration is a fixed record of what happened at this point in the
 * schema's history, and must keep producing that result even after the service
 * it would have called has moved on.
 */
return new class extends Migration
{
    public function up(): void
    {
        $defaults = config('master-data.attendance_reasons', []);

        if ($defaults === []) {
            return;
        }

        $unconfigured = DB::table('companies')
            ->where('company_code', '!=', Company::PLATFORM_CODE)
            ->whereNull('deleted_at')
            // Soft-deleted reasons count as held: a company that deleted its
            // reasons made a decision, and a backfill must not overrule it.
            ->whereNotExists(function ($query): void {
                $query->select(DB::raw(1))
                    ->from('attendance_reasons')
                    ->whereColumn('attendance_reasons.company_id', 'companies.id');
            })
            ->pluck('id');

        if ($unconfigured->isEmpty()) {
            return;
        }

        $now = now();

        DB::table('attendance_reasons')->insert(
            $unconfigured->flatMap(fn (int $companyId): array => array_map(
                fn (array $default): array => [
                    'company_id' => $companyId,
                    'reason_name' => $default['reason_name'],
                    'color' => $default['color'] ?? null,
                    'icon' => $default['icon'] ?? null,
                    'counts_as_absent' => $default['counts_as_absent'] ?? false,
                    'counts_as_leave' => $default['counts_as_leave'] ?? false,
                    'status' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                $defaults,
            ))->all()
        );
    }

    public function down(): void
    {
        // Attendance reasons become company data the moment they are provisioned,
        // and an admin may already have edited or recorded attendance against
        // them. There is no safe way to tell those apart from the untouched
        // defaults, so a rollback leaves them in place.
    }
};
