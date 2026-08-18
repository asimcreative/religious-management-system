<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Jamaat;
use App\Models\QuranAttendance;
use App\Models\QuranClass;
use App\Models\QuranProgress;
use App\Models\QuranTeacherAttendance;
use App\Models\SalahAttendance;
use App\Models\Teacher;
use App\Models\User;
use App\Support\Analytics\DateExpression;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
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
            ->where('class_held', true)
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

    // ── Teacher Attendance Report ──────────────────────────────────

    public function teacherAttendanceReport(array $filters, int $perPage = 50): LengthAwarePaginator
    {
        return QuranTeacherAttendance::query()
            ->with(['quranClass', 'teacher.employee', 'attendanceReason'])
            ->when($filters['class_id'] ?? null, fn (Builder $q, $v) => $q->where('class_id', $v))
            ->when($filters['teacher_id'] ?? null, fn (Builder $q, $v) => $q->where('teacher_id', $v))
            ->when($filters['date_from'] ?? null, fn (Builder $q, $v) => $q->where('attendance_date', '>=', $v))
            ->when($filters['date_to'] ?? null, fn (Builder $q, $v) => $q->where('attendance_date', '<=', $v))
            ->latest('attendance_date')
            ->paginate($perPage);
    }

    /**
     * Absence days per teacher per month — the "jab chahe" pivot: how many
     * days each qari did not hold class, sliceable by any date range.
     *
     * @return Collection<int, object{teacher_id: int, employee_name: string, month: string, absent_days: int}>
     */
    public function teacherAttendanceMonthlySummary(array $filters): Collection
    {
        return QuranTeacherAttendance::query()
            ->join('teachers', 'quran_teacher_attendance.teacher_id', '=', 'teachers.id')
            ->join('employees', 'teachers.employee_id', '=', 'employees.id')
            ->when($filters['class_id'] ?? null, fn (Builder $q, $v) => $q->where('quran_teacher_attendance.class_id', $v))
            ->when($filters['teacher_id'] ?? null, fn (Builder $q, $v) => $q->where('quran_teacher_attendance.teacher_id', $v))
            ->when($filters['date_from'] ?? null, fn (Builder $q, $v) => $q->where('quran_teacher_attendance.attendance_date', '>=', $v))
            ->when($filters['date_to'] ?? null, fn (Builder $q, $v) => $q->where('quran_teacher_attendance.attendance_date', '<=', $v))
            ->selectRaw(
                'teachers.id as teacher_id, employees.employee_name, '
                .DateExpression::month('quran_teacher_attendance.attendance_date').' as month, '
                .'COUNT(*) as absent_days'
            )
            ->groupBy('teachers.id', 'employees.employee_name')
            ->groupByRaw(DateExpression::month('quran_teacher_attendance.attendance_date'))
            ->orderByDesc('month')
            ->orderBy('employees.employee_name')
            ->get();
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
    /**
     * @param  list<int>|null  $allowedJamaatIds
     */
    public function salahPrayerWiseSummary(array $filters, ?int $companyId, ?array $allowedJamaatIds = null): Collection
    {
        return SalahAttendance::query()
            ->join('prayers', 'salah_attendance.prayer_id', '=', 'prayers.id')
            ->when($companyId !== null, fn (Builder $q) => $q->where('salah_attendance.company_id', $companyId))
            ->when($allowedJamaatIds !== null, fn (Builder $q) => $q->whereIn('salah_attendance.jamaat_id', $allowedJamaatIds))
            ->when($filters['jamaat_id'] ?? null, fn (Builder $q, $v) => $q->where('salah_attendance.jamaat_id', $v))
            ->when($filters['date_from'] ?? null, fn (Builder $q, $v) => $q->where('salah_attendance.attendance_date', '>=', $v))
            ->when($filters['date_to'] ?? null, fn (Builder $q, $v) => $q->where('salah_attendance.attendance_date', '<=', $v))
            ->select(
                'prayers.prayer_name',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN salah_attendance.attendance_reason_id IS NULL THEN 1 ELSE 0 END) as present'),
                DB::raw('SUM(CASE WHEN salah_attendance.attendance_reason_id IS NOT NULL THEN 1 ELSE 0 END) as absent')
            )
            ->groupBy('prayers.id', 'prayers.prayer_name')
            ->orderBy('prayers.prayer_order')
            ->toBase()
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
        $user = Auth::user();
        $canEmployee = $this->can($user, 'report.employee');
        $canTeacher = $this->can($user, 'report.teacher');
        $canQuran = $this->can($user, 'report.quran');
        $canSalah = $this->can($user, 'report.salah');

        return [
            'total_employees' => $canEmployee ? Employee::count() : 0,
            'active_employees' => $canEmployee ? Employee::where('employment_status', 1)->count() : 0,
            'total_teachers' => $canTeacher ? Teacher::count() : 0,
            'active_teachers' => $canTeacher ? Teacher::where('status', 1)->count() : 0,
            'total_quran_classes' => $canQuran ? QuranClass::count() : 0,
            'active_quran_classes' => $canQuran ? QuranClass::where('status', 1)->count() : 0,
            'total_jamaats' => $canSalah ? Jamaat::count() : 0,
            'active_jamaats' => $canSalah ? Jamaat::where('status', 1)->count() : 0,
            'total_quran_attendance' => $canQuran ? QuranAttendance::count() : 0,
            'total_salah_attendance' => $canSalah ? SalahAttendance::count() : 0,
            'total_quran_progress' => $canQuran ? QuranProgress::count() : 0,
        ];
    }

    private function can(?User $user, string $permission): bool
    {
        return $user instanceof User && $user->can($permission);
    }
}
