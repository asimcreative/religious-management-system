<?php

namespace App\Services;

use App\Enums\Status;
use App\Models\Employee;
use App\Models\Jamaat;
use App\Models\QuranAttendance;
use App\Models\QuranClass;
use App\Models\QuranProgress;
use App\Models\SalahAttendance;
use App\Models\Teacher;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class DashboardService
{
    /**
     * Cache TTL constants (in seconds).
     * Key convention: company:{company_id}:dashboard:{segment}
     */
    private const TTL_KPI = 300;       // 5 minutes — overview stats

    private const TTL_TODAY = 120;     // 2 minutes — today's live attendance

    private const TTL_SUMMARY = 600;   // 10 minutes — module summaries

    /**
     * Resolve the current company_id for cache key scoping.
     */
    private function companyId(): int
    {
        $user = Auth::user();

        return $user instanceof User ? (int) $user->getAttribute('company_id') : 0;
    }

    /**
     * Build a company-scoped cache key.
     */
    private function key(string $segment): string
    {
        return 'company:'.$this->companyId().':dashboard:'.$segment;
    }

    /**
     * Forget all dashboard caches for the current company.
     * Call this after bulk data changes (imports, mass updates).
     */
    public function clearCache(): void
    {
        $id = $this->companyId();
        foreach (['overview', 'today_quran', 'today_salah', 'quran_summary', 'salah_summary'] as $seg) {
            Cache::forget("company:{$id}:dashboard:{$seg}");
        }
    }

    /**
     * Overview KPI cards — total and active counts for main entities.
     *
     * @return array<string, int>
     */
    public function overviewStats(): array
    {
        return Cache::remember($this->key('overview'), self::TTL_KPI, function () {
            return [
                'total_employees' => Employee::count(),
                'active_employees' => Employee::where('employment_status', Status::Active)->count(),
                'total_teachers' => Teacher::count(),
                'active_teachers' => Teacher::where('status', Status::Active)->count(),
                'total_quran_classes' => QuranClass::count(),
                'active_quran_classes' => QuranClass::where('status', Status::Active)->count(),
                'total_jamaats' => Jamaat::count(),
                'active_jamaats' => Jamaat::where('status', Status::Active)->count(),
            ];
        });
    }

    /**
     * Today's quran attendance stats.
     *
     * @return array<string, int|float>
     */
    public function todayQuranAttendance(): array
    {
        return Cache::remember($this->key('today_quran'), self::TTL_TODAY, function () {
            $today = Carbon::today()->toDateString();

            $total = QuranAttendance::where('attendance_date', $today)->count();
            $present = QuranAttendance::where('attendance_date', $today)->whereNull('attendance_reason_id')->count();
            $absent = $total - $present;

            return [
                'total' => $total,
                'present' => $present,
                'absent' => $absent,
                'percentage' => $total > 0 ? round(($present / $total) * 100, 1) : 0,
            ];
        });
    }

    /**
     * Today's salah attendance stats.
     *
     * @return array<string, int|float>
     */
    public function todaySalahAttendance(): array
    {
        return Cache::remember($this->key('today_salah'), self::TTL_TODAY, function () {
            $today = Carbon::today()->toDateString();

            $total = SalahAttendance::where('attendance_date', $today)->count();
            $present = SalahAttendance::where('attendance_date', $today)->whereNull('attendance_reason_id')->count();
            $absent = $total - $present;

            return [
                'total' => $total,
                'present' => $present,
                'absent' => $absent,
                'percentage' => $total > 0 ? round(($present / $total) * 100, 1) : 0,
            ];
        });
    }

    /**
     * Quran module summary — progress and attendance totals.
     *
     * @return array<string, int|float>
     */
    public function quranSummary(): array
    {
        return Cache::remember($this->key('quran_summary'), self::TTL_SUMMARY, function () {
            $totalProgress = QuranProgress::count();
            $avgCompletion = QuranProgress::avg('completion_percentage') ?? 0;
            $totalAttendance = QuranAttendance::count();

            return [
                'total_progress_records' => $totalProgress,
                'avg_completion' => round((float) $avgCompletion, 1),
                'total_attendance_records' => $totalAttendance,
            ];
        });
    }

    /**
     * Salah module summary — total attendance records.
     *
     * @return array<string, int>
     */
    public function salahSummary(): array
    {
        return Cache::remember($this->key('salah_summary'), self::TTL_SUMMARY, function () {
            return [
                'total_attendance_records' => SalahAttendance::count(),
            ];
        });
    }
}
