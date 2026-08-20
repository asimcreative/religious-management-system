<?php

use App\Enums\AttendanceReasonType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Gives every existing company a starting Taleem reason list.
 *
 * Taleem previously borrowed the Salah/Jamaat per-prayer reason list — there
 * was no 'taleem' type before this. jamaat_taleem has zero rows in production
 * as of this migration (checked directly), so there is no history to preserve
 * here, unlike the Salah/Quran split: this only needs to give companies a
 * sensible starting point, not reconcile existing FKs.
 *
 * Per company: clone its active 'salah'-typed reasons into new 'taleem'-typed
 * rows, skipped entirely if the company already holds any 'taleem'-typed row
 * (soft-deleted included) — the same non-destructive, per-type idempotency
 * rule as the rest of this feature, so a re-run or a company that has since
 * customised its Taleem list is left alone.
 */
return new class extends Migration
{
    public function up(): void
    {
        $companyIds = DB::table('attendance_reasons')
            ->where('type', AttendanceReasonType::Salah->value)
            ->whereNull('deleted_at')
            ->whereNotExists(function ($query): void {
                $query->select(DB::raw(1))
                    ->from('attendance_reasons AS taleem_reasons')
                    ->whereColumn('taleem_reasons.company_id', 'attendance_reasons.company_id')
                    ->where('taleem_reasons.type', AttendanceReasonType::Taleem->value);
            })
            ->distinct()
            ->pluck('company_id');

        if ($companyIds->isEmpty()) {
            return;
        }

        $active = DB::table('attendance_reasons')
            ->where('type', AttendanceReasonType::Salah->value)
            ->whereNull('deleted_at')
            ->whereIn('company_id', $companyIds)
            ->get();

        if ($active->isEmpty()) {
            return;
        }

        $now = now();

        DB::table('attendance_reasons')->insert(
            $active->map(fn ($row): array => [
                'company_id' => $row->company_id,
                'type' => AttendanceReasonType::Taleem->value,
                'reason_name' => $row->reason_name,
                'color' => $row->color,
                'icon' => $row->icon,
                'counts_as_absent' => $row->counts_as_absent,
                'counts_as_leave' => $row->counts_as_leave,
                'status' => $row->status,
                'created_by' => null,
                'updated_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all()
        );
    }

    public function down(): void
    {
        // Taleem reasons become company data the moment they exist, and by
        // the time anyone rolls back an admin may already have edited them or
        // recorded a Taleem entry against one. There is no safe way to tell
        // an untouched clone apart from a since-edited real record, so a
        // rollback leaves every row in place — matching this feature's
        // established rollback rule.
    }
};
