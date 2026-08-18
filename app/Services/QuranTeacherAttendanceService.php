<?php

namespace App\Services;

use App\Contracts\Repositories\QuranTeacherAttendanceRepositoryInterface;
use App\Models\QuranClass;
use App\Models\QuranTeacherAttendance;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class QuranTeacherAttendanceService extends BaseService
{
    private readonly QuranTeacherAttendanceRepositoryInterface $attendanceRepository;

    public function __construct(QuranTeacherAttendanceRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->attendanceRepository = $repository;
    }

    public function search(?string $search, array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return $this->attendanceRepository->search($search, $filters, $perPage);
    }

    /**
     * Get the teacher-absence record for a class on a specific date, if any.
     */
    public function getForClassDate(int $classId, string $date): ?QuranTeacherAttendance
    {
        return $this->attendanceRepository->getForClassDate($classId, $date);
    }

    /**
     * Record (or update) that the teacher/qari did not hold class on this date.
     *
     * Must be called from inside an already-open transaction — see
     * QuranAttendanceService::saveAttendance(), which locks the QuranClass row
     * this depends on before calling here.
     */
    public function markAbsent(
        QuranClass $class,
        string $date,
        int $companyId,
        int $reasonId,
        ?string $remarks,
        User $actor,
    ): QuranTeacherAttendance {
        return QuranTeacherAttendance::query()->updateOrCreate(
            [
                'company_id' => $companyId,
                'class_id' => $class->id,
                'attendance_date' => $date,
            ],
            [
                'teacher_id' => $class->teacher_id,
                'attendance_reason_id' => $reasonId,
                'remarks' => $remarks,
                'created_by' => $actor->id,
            ],
        );
    }

    /**
     * Reverse markAbsent() for a re-save where the teacher is no longer marked
     * absent. Same transaction-participation contract as markAbsent().
     */
    public function clearAbsence(QuranClass $class, string $date, int $companyId): void
    {
        QuranTeacherAttendance::query()
            ->where('company_id', $companyId)
            ->where('class_id', $class->id)
            ->whereDate('attendance_date', $date)
            ->get()
            ->each(fn (QuranTeacherAttendance $attendance) => $attendance->delete());
    }
}
