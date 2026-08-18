<?php

namespace App\Repositories;

use App\Contracts\Repositories\QuranTeacherAttendanceRepositoryInterface;
use App\Models\QuranTeacherAttendance;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class QuranTeacherAttendanceRepository extends BaseRepository implements QuranTeacherAttendanceRepositoryInterface
{
    public function __construct(QuranTeacherAttendance $model)
    {
        parent::__construct($model);
    }

    public function search(?string $search, array $filters, int $perPage = 25): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->with(['quranClass', 'teacher.employee', 'attendanceReason'])
            ->when($filters['class_id'] ?? null, fn (Builder $q, $v) => $q->where('class_id', $v))
            ->when($filters['teacher_id'] ?? null, fn (Builder $q, $v) => $q->where('teacher_id', $v))
            ->when($filters['date_from'] ?? null, fn (Builder $q, $v) => $q->where('attendance_date', '>=', $v))
            ->when($filters['date_to'] ?? null, fn (Builder $q, $v) => $q->where('attendance_date', '<=', $v))
            ->when($search, function (Builder $query) use ($search) {
                $query->whereHas('teacher.employee', function (Builder $q) use ($search) {
                    $q->where('employee_name', 'like', "%{$search}%")
                        ->orWhere('employee_code', 'like', "%{$search}%");
                });
            })
            ->latest('attendance_date')
            ->paginate($perPage);
    }

    /**
     * Get the teacher-absence record for a specific class and date, if any.
     */
    public function getForClassDate(int $classId, string $date): ?QuranTeacherAttendance
    {
        return $this->model->newQuery()
            ->with(['attendanceReason'])
            ->where('class_id', $classId)
            ->whereDate('attendance_date', $date)
            ->first();
    }

    public function existsForClassDate(int $classId, string $date): bool
    {
        return $this->model->newQuery()
            ->where('class_id', $classId)
            ->whereDate('attendance_date', $date)
            ->exists();
    }
}
