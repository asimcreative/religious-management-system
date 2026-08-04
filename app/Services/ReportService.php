<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Jamaat;
use App\Models\QuranAttendance;
use App\Models\QuranClass;
use App\Models\QuranProgress;
use App\Models\SalahAttendance;
use App\Models\Teacher;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportService
{
    // ── Employee Report ───────────────────────────────────────────

    public function employeeReport(array $filters, int $perPage = 25): LengthAwarePaginator
    {
        return Employee::query()
            ->with(['branch', 'department', 'designation'])
            ->when($filters['search'] ?? null, function (Builder $q, $search) {
                $q->where(function (Builder $sq) use ($search) {
                    $sq->where('employee_name', 'like', "%{$search}%")
                        ->orWhere('employee_code', 'like', "%{$search}%")
                        ->orWhere('mobile', 'like', "%{$search}%");
                });
            })
            ->when($filters['branch_id'] ?? null, fn (Builder $q, $v) => $q->where('branch_id', $v))
            ->when($filters['department_id'] ?? null, fn (Builder $q, $v) => $q->where('department_id', $v))
            ->when($filters['designation_id'] ?? null, fn (Builder $q, $v) => $q->where('designation_id', $v))
            ->when(isset($filters['employment_status']) && $filters['employment_status'] !== '', fn (Builder $q) => $q->where('employment_status', $filters['employment_status']))
            ->orderBy('employee_name')
            ->paginate($perPage);
    }

    // ── Teacher Report ────────────────────────────────────────────

    public function teacherReport(array $filters, int $perPage = 25): LengthAwarePaginator
    {
        return Teacher::query()
            ->with(['employee', 'branches'])
            ->withCount(['quranClasses', 'quranClasses as active_classes_count' => function (Builder $q) {
                $q->where('status', 1);
            }])
            ->when($filters['search'] ?? null, function (Builder $q, $search) {
                $q->where(function (Builder $sq) use ($search) {
                    $sq->where('teacher_code', 'like', "%{$search}%")
                        ->orWhereHas('employee', function (Builder $eq) use ($search) {
                            $eq->where('employee_name', 'like', "%{$search}%");
                        });
                });
            })
            ->when($filters['branch_id'] ?? null, function (Builder $q, $branchId) {
                $q->whereHas('branches', fn (Builder $bq) => $bq->where('branches.id', $branchId));
            })
            ->when(isset($filters['status']) && $filters['status'] !== '', fn (Builder $q) => $q->where('status', $filters['status']))
            ->orderBy('teacher_code')
            ->paginate($perPage);
    }

    // ── Quran Attendance Report ───────────────────────────────────

    public function quranAttendanceReport(array $filters, int $perPage = 50): LengthAwarePaginator
    {
        return QuranAttendance::query()
            ->with(['quranClass', 'teacher.employee', 'employee', 'attendanceReason'])
            ->when($filters['class_id'] ?? null, fn (Builder $q, $v) => $q->where('class_id', $v))
            ->when($filters['teacher_id'] ?? null, fn (Builder $q, $v) => $q->where('teacher_id', $v))
            ->when($filters['date_from'] ?? null, fn (Builder $q, $v) => $q->where('attendance_date', '>=', $v))
            ->when($filters['date_to'] ?? null, fn (Builder $q, $v) => $q->where('attendance_date', '<=', $v))
            ->when($filters['search'] ?? null, function (Builder $q, $search) {
                $q->whereHas('employee', fn (Builder $eq) => $eq->where('employee_name', 'like', "%{$search}%"));
            })
            ->latest('attendance_date')
            ->paginate($perPage);
    }

    /**
     * @return array<string, mixed>
     */
    public function quranAttendanceSummary(array $filters): array
    {
        $query = QuranAttendance::query()
            ->when($filters['class_id'] ?? null, fn (Builder $q, $v) => $q->where('class_id', $v))
            ->when($filters['teacher_id'] ?? null, fn (Builder $q, $v) => $q->where('teacher_id', $v))
            ->when($filters['date_from'] ?? null, fn (Builder $q, $v) => $q->where('attendance_date', '>=', $v))
            ->when($filters['date_to'] ?? null, fn (Builder $q, $v) => $q->where('attendance_date', '<=', $v));

        $total = (clone $query)->count();
        $present = (clone $query)->whereNull('attendance_reason_id')->count();
        $absent = $total - $present;

        return [
            'total' => $total,
            'present' => $present,
            'absent' => $absent,
            'percentage' => $total > 0 ? round(($present / $total) * 100, 1) : 0,
        ];
    }

    // ── Salah Attendance Report ───────────────────────────────────

    public function salahAttendanceReport(array $filters, int $perPage = 50): LengthAwarePaginator
    {
        return SalahAttendance::query()
            ->with(['prayer', 'jamaat', 'employee', 'attendanceReason'])
            ->when($filters['jamaat_id'] ?? null, fn (Builder $q, $v) => $q->where('jamaat_id', $v))
            ->when($filters['prayer_id'] ?? null, fn (Builder $q, $v) => $q->where('prayer_id', $v))
            ->when($filters['date_from'] ?? null, fn (Builder $q, $v) => $q->where('attendance_date', '>=', $v))
            ->when($filters['date_to'] ?? null, fn (Builder $q, $v) => $q->where('attendance_date', '<=', $v))
            ->when($filters['search'] ?? null, function (Builder $q, $search) {
                $q->whereHas('employee', fn (Builder $eq) => $eq->where('employee_name', 'like', "%{$search}%"));
            })
            ->latest('attendance_date')
            ->paginate($perPage);
    }

    /**
     * @return array<string, mixed>
     */
    public function salahAttendanceSummary(array $filters): array
    {
        $query = SalahAttendance::query()
            ->when($filters['jamaat_id'] ?? null, fn (Builder $q, $v) => $q->where('jamaat_id', $v))
            ->when($filters['prayer_id'] ?? null, fn (Builder $q, $v) => $q->where('prayer_id', $v))
            ->when($filters['date_from'] ?? null, fn (Builder $q, $v) => $q->where('attendance_date', '>=', $v))
            ->when($filters['date_to'] ?? null, fn (Builder $q, $v) => $q->where('attendance_date', '<=', $v));

        $total = (clone $query)->count();
        $present = (clone $query)->whereNull('attendance_reason_id')->count();
        $absent = $total - $present;

        return [
            'total' => $total,
            'present' => $present,
            'absent' => $absent,
            'percentage' => $total > 0 ? round(($present / $total) * 100, 1) : 0,
        ];
    }

    /**
     * Prayer-wise attendance breakdown.
     *
     * @return Collection<int, \stdClass>
     */
    public function salahPrayerWiseSummary(array $filters): Collection
    {
        return DB::table('salah_attendance')
            ->join('prayers', 'salah_attendance.prayer_id', '=', 'prayers.id')
            ->when($filters['jamaat_id'] ?? null, fn (QueryBuilder $q, $v) => $q->where('salah_attendance.jamaat_id', $v))
            ->when($filters['date_from'] ?? null, fn (QueryBuilder $q, $v) => $q->where('salah_attendance.attendance_date', '>=', $v))
            ->when($filters['date_to'] ?? null, fn (QueryBuilder $q, $v) => $q->where('salah_attendance.attendance_date', '<=', $v))
            ->select(
                'prayers.prayer_name',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN salah_attendance.attendance_reason_id IS NULL THEN 1 ELSE 0 END) as present'),
                DB::raw('SUM(CASE WHEN salah_attendance.attendance_reason_id IS NOT NULL THEN 1 ELSE 0 END) as absent')
            )
            ->groupBy('prayers.id', 'prayers.prayer_name')
            ->orderBy('prayers.prayer_order')
            ->get();
    }

    // ── Quran Progress Report ─────────────────────────────────────

    public function quranProgressReport(array $filters, int $perPage = 25): LengthAwarePaginator
    {
        return QuranProgress::query()
            ->with(['employee', 'teacher.employee', 'quranDepartment', 'quranStatus'])
            ->when($filters['quran_department_id'] ?? null, fn (Builder $q, $v) => $q->where('quran_department_id', $v))
            ->when($filters['quran_status_id'] ?? null, fn (Builder $q, $v) => $q->where('quran_status_id', $v))
            ->when($filters['teacher_id'] ?? null, fn (Builder $q, $v) => $q->where('teacher_id', $v))
            ->when($filters['search'] ?? null, function (Builder $q, $search) {
                $q->whereHas('employee', fn (Builder $eq) => $eq->where('employee_name', 'like', "%{$search}%"));
            })
            ->orderBy('completion_percentage', 'desc')
            ->paginate($perPage);
    }

    // ── Dashboard Summary ─────────────────────────────────────────

    /**
     * @return array<string, mixed>
     */
    public function dashboardSummary(): array
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
            'total_quran_attendance' => QuranAttendance::count(),
            'total_salah_attendance' => SalahAttendance::count(),
            'total_quran_progress' => QuranProgress::count(),
        ];
    }
}
