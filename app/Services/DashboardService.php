<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Jamaat;
use App\Models\QuranAttendance;
use App\Models\QuranClass;
use App\Models\QuranProgress;
use App\Models\SalahAttendance;
use App\Models\Teacher;
use Carbon\Carbon;

class DashboardService
{
    /**
     * Overview KPI cards — total and active counts for main entities.
     *
     * @return array<string, int>
     */
    public function overviewStats(): array
    {
        return [
            'total_employees' => Employee::count(),
            'active_employees' => Employee::where('employment_status', 1)->count(),
            'total_teachers' => Teacher::count(),
            'active_teachers' => Teacher::where('status', 1)->count(),
            'total_quran_classes' => QuranClass::count(),
            'active_quran_classes' => QuranClass::where('status', 1)->count(),
            'total_jamaats' => Jamaat::count(),
            'active_jamaats' => Jamaat::where('status', 1)->count(),
        ];
    }

    /**
     * Today's quran attendance stats.
     *
     * @return array<string, int|float>
     */
    public function todayQuranAttendance(): array
    {
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
    }

    /**
     * Today's salah attendance stats.
     *
     * @return array<string, int|float>
     */
    public function todaySalahAttendance(): array
    {
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
    }

    /**
     * Quran module summary — progress and attendance totals.
     *
     * @return array<string, int|float>
     */
    public function quranSummary(): array
    {
        $totalProgress = QuranProgress::count();
        $avgCompletion = QuranProgress::avg('completion_percentage') ?? 0;
        $totalAttendance = QuranAttendance::count();

        return [
            'total_progress_records' => $totalProgress,
            'avg_completion' => round((float) $avgCompletion, 1),
            'total_attendance_records' => $totalAttendance,
        ];
    }

    /**
     * Salah module summary — total attendance records.
     *
     * @return array<string, int>
     */
    public function salahSummary(): array
    {
        return [
            'total_attendance_records' => SalahAttendance::count(),
        ];
    }
}
