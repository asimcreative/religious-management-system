<?php

namespace App\Services;

use App\Models\QuranAttendance;
use App\Models\Setting;
use App\Repositories\QuranAttendanceRepository;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class QuranAttendanceService extends BaseService
{
    private readonly QuranAttendanceRepository $attendanceRepository;

    public function __construct(QuranAttendanceRepository $repository)
    {
        parent::__construct($repository);
        $this->attendanceRepository = $repository;
    }

    public function search(?string $search, array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return $this->attendanceRepository->search($search, $filters, $perPage);
    }

    /**
     * Get attendance for a class on a specific date.
     */
    public function getForClassDate(int $classId, string $date): Collection
    {
        return $this->attendanceRepository->getForClassDate($classId, $date);
    }

    /**
     * Check if attendance already exists for a class on a date.
     */
    public function existsForClassDate(int $classId, string $date): bool
    {
        return $this->attendanceRepository->existsForClassDate($classId, $date);
    }

    /**
     * Validate that the given date is within the allowed backdating window.
     */
    public function isDateAllowed(string $date, int $companyId): bool
    {
        $attendanceDate = Carbon::parse($date);
        $today = Carbon::today();

        // Future dates are never allowed
        if ($attendanceDate->isAfter($today)) {
            return false;
        }

        // Get max backdate days from settings (default: 3)
        $maxDays = (int) Setting::where('company_id', $companyId)
            ->where('key', 'max_backdated_attendance_days')
            ->value('value') ?: 3;

        return $attendanceDate->diffInDays($today) <= $maxDays;
    }

    /**
     * Save attendance for a class on a date.
     * Each employee gets one attendance record per class per day.
     *
     * @param  array<int, int|null>  $attendanceData  employee_id => attendance_reason_id (null = present)
     * @param  array<int, string|null>  $remarksData  employee_id => remarks
     */
    public function saveAttendance(
        int $classId,
        int $teacherId,
        string $date,
        int $companyId,
        array $attendanceData,
        array $remarksData = []
    ): void {
        DB::transaction(function () use ($classId, $teacherId, $date, $companyId, $attendanceData, $remarksData) {
            // Delete existing attendance for this class+date (upsert pattern)
            QuranAttendance::where('class_id', $classId)
                ->where('attendance_date', $date)
                ->delete();

            // Insert new attendance records
            foreach ($attendanceData as $employeeId => $reasonId) {
                QuranAttendance::create([
                    'company_id' => $companyId,
                    'attendance_date' => $date,
                    'class_id' => $classId,
                    'teacher_id' => $teacherId,
                    'employee_id' => $employeeId,
                    'attendance_reason_id' => $reasonId ?: null,
                    'remarks' => $remarksData[$employeeId] ?? null,
                ]);
            }
        });
    }
}
