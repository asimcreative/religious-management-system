<?php

namespace App\Repositories;

use App\Contracts\Repositories\QuranAttendanceRepositoryInterface;
use App\Models\QuranAttendance;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class QuranAttendanceRepository extends BaseRepository implements QuranAttendanceRepositoryInterface
{
    public function __construct(QuranAttendance $model)
    {
        parent::__construct($model);
    }

    public function search(?string $search, array $filters, int $perPage = 25): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->with(['quranClass', 'teacher.employee', 'employee', 'attendanceReason'])
            ->when($filters['class_id'] ?? null, fn (Builder $q, $v) => $q->where('class_id', $v))
            ->when($filters['teacher_id'] ?? null, fn (Builder $q, $v) => $q->where('teacher_id', $v))
            ->when($filters['date_from'] ?? null, fn (Builder $q, $v) => $q->where('attendance_date', '>=', $v))
            ->when($filters['date_to'] ?? null, fn (Builder $q, $v) => $q->where('attendance_date', '<=', $v))
            ->when($search, function (Builder $query) use ($search) {
                $query->whereHas('employee', function (Builder $q) use ($search) {
                    $q->where('employee_name', 'like', "%{$search}%")
                        ->orWhere('employee_code', 'like', "%{$search}%");
                });
            })
            ->latest('attendance_date')
            ->paginate($perPage);
    }

    /**
     * Get attendance for a specific class and date.
     */
    public function getForClassDate(int $classId, string $date): Collection
    {
        return $this->model->newQuery()
            ->with(['employee', 'attendanceReason'])
            ->where('class_id', $classId)
            ->whereDate('attendance_date', $date)
            ->get();
    }

    /**
     * Check if attendance already exists for a class on a date.
     */
    public function existsForClassDate(int $classId, string $date): bool
    {
        return $this->model->newQuery()
            ->where('class_id', $classId)
            ->whereDate('attendance_date', $date)
            ->exists();
    }
}
