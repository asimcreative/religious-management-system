<?php

namespace App\Support\Analytics;

use Illuminate\Database\Eloquent\Builder;

/**
 * Present vs absent, defined the same way everywhere it is counted.
 *
 * A reason existing is not the same as a reason counting against the person —
 * an admin who ships "On Leave" or a custom reason like "Prayed" with
 * counts_as_absent = false means it precisely. This is the one place that
 * rule is written, so ReportService, DashboardService and the analytics
 * engine cannot drift apart on what "absent" means.
 */
final class AttendanceExpression
{
    /**
     * The CASE that decides whether one row counts as absent — 1 or 0.
     * Requires attendance_reasons already LEFT JOINed via joinReasons().
     */
    public static function absentCase(string $table): string
    {
        return "CASE WHEN {$table}.attendance_reason_id IS NOT NULL AND attendance_reasons.counts_as_absent = 1 THEN 1 ELSE 0 END";
    }

    /**
     * LEFT JOIN attendance_reasons onto {$table}'s FK. Every attendance row
     * carries at most one reason (attendance_reasons.id is unique), so this
     * never multiplies rows — safe to add unconditionally before a COUNT/SUM.
     */
    public static function joinReasons(Builder $query, string $table): Builder
    {
        return $query->leftJoin('attendance_reasons', "{$table}.attendance_reason_id", '=', 'attendance_reasons.id');
    }

    /** A scalar count of rows that count as absent, on an already-filtered query. */
    public static function countAbsent(Builder $query, string $table): int
    {
        return (int) self::joinReasons($query, $table)
            ->whereNotNull("{$table}.attendance_reason_id")
            ->where('attendance_reasons.counts_as_absent', true)
            ->count();
    }
}
