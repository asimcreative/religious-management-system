<?php

namespace App\Services;

use App\Models\SalahAttendance;
use App\Models\Setting;
use App\Contracts\Repositories\SalahAttendanceRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class SalahAttendanceService extends BaseService
{
    private readonly SalahAttendanceRepositoryInterface $attendanceRepository;

    public function __construct(SalahAttendanceRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->attendanceRepository = $repository;
    }

    public function search(?string $search, array $filters = [], int $perPage = 50): LengthAwarePaginator
    {
        return $this->attendanceRepository->search($search, $filters, $perPage);
    }

    /**
     * Get attendance for a Jamaat on a specific date and prayer.
     */
    public function getForJamaatDatePrayer(int $jamaatId, string $date, int $prayerId): Collection
    {
        return $this->attendanceRepository->getForJamaatDatePrayer($jamaatId, $date, $prayerId);
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
     * Save attendance for a Jamaat on a date and prayer.
     * Each employee gets one attendance record per prayer per day.
     *
     * @param  array<int, int|null>  $attendanceData  employee_id => attendance_reason_id (null = present)
     * @param  array<int, string|null>  $remarksData  employee_id => remarks
     */
    public function saveAttendance(
        int $jamaatId,
        int $prayerId,
        string $date,
        int $companyId,
        int $leaderId,
        array $attendanceData,
        array $remarksData = []
    ): void {
        DB::transaction(function () use ($jamaatId, $prayerId, $date, $companyId, $leaderId, $attendanceData, $remarksData) {
            // Delete existing attendance for this jamaat+date+prayer (upsert pattern)
            SalahAttendance::where('jamaat_id', $jamaatId)
                ->where('attendance_date', $date)
                ->where('prayer_id', $prayerId)
                ->delete();

            // Insert new attendance records
            foreach ($attendanceData as $employeeId => $reasonId) {
                SalahAttendance::create([
                    'company_id' => $companyId,
                    'attendance_date' => $date,
                    'prayer_id' => $prayerId,
                    'jamaat_id' => $jamaatId,
                    'leader_id' => $leaderId,
                    'employee_id' => $employeeId,
                    'attendance_reason_id' => $reasonId ?: null,
                    'remarks' => $remarksData[$employeeId] ?? null,
                ]);
            }
        });
    }
}
