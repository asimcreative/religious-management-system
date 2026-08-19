<?php

namespace App\Services;

use App\Contracts\Repositories\SalahAttendanceRepositoryInterface;
use App\Enums\Status;
use App\Helpers\TimezoneHelper;
use App\Models\AttendanceReason;
use App\Models\Jamaat;
use App\Models\SalahAttendance;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SalahAttendanceService extends BaseService
{
    private readonly SalahAttendanceRepositoryInterface $attendanceRepository;

    public function __construct(
        SalahAttendanceRepositoryInterface $repository,
        private readonly AttendanceLockService $attendanceLockService,
        private readonly AuditLogService $auditLogService,
        private readonly JamaatTaleemService $taleemService,
    ) {
        parent::__construct($repository);
        $this->attendanceRepository = $repository;
    }

    public function search(?string $search, array $filters = [], int $perPage = 50): LengthAwarePaginator
    {
        return $this->attendanceRepository->search($search, $filters, $perPage);
    }

    public function searchGrouped(?string $search, array $filters = [], int $perPage = 50): LengthAwarePaginator
    {
        return $this->attendanceRepository->searchGrouped($search, $filters, $perPage);
    }

    /**
     * Get attendance for a Jamaat on a specific date and prayer.
     */
    public function getForJamaatDatePrayer(int $jamaatId, string $date, int $prayerId): Collection
    {
        return $this->attendanceRepository->getForJamaatDatePrayer($jamaatId, $date, $prayerId);
    }

    /**
     * Get all attendance for a Jamaat on a specific date (all prayers).
     * Returns a nested collection keyed by [employee_id][prayer_id].
     *
     * @return \Illuminate\Support\Collection<int, \Illuminate\Support\Collection<int, SalahAttendance>>
     */
    public function getForJamaatDate(int $jamaatId, string $date): \Illuminate\Support\Collection
    {
        return $this->attendanceRepository
            ->getForJamaatDate($jamaatId, $date)
            ->groupBy('employee_id')
            ->map(fn ($rows) => $rows->keyBy('prayer_id'));
    }

    /**
     * Validate that the given date is within the allowed backdating window.
     */
    public function isDateAllowed(string $date, int $companyId): bool
    {
        $timezone = TimezoneHelper::getCompanyTimezone($companyId);
        $attendanceDate = Carbon::parse($date, $timezone)->startOfDay();
        $today = Carbon::now($timezone)->startOfDay();

        // Future dates are never allowed
        if ($attendanceDate->isAfter($today)) {
            return false;
        }

        // Get max backdate days from settings (default: 3)
        $settingValue = Setting::where('company_id', $companyId)
            ->where('key', 'max_backdated_attendance_days')
            ->value('value');
        $maxDays = is_numeric($settingValue) ? max(0, (int) $settingValue) : 3;

        return $attendanceDate->diffInDays($today) <= $maxDays;
    }

    /**
     * Save attendance for all prayers in one submission.
     * attendance[employee_id][prayer_id] = reason_id|null (null = present)
     *
     * @param  array<int, array<int, int|null>>  $attendanceData
     * @param  array<int, string|null>  $remarksData
     */
    public function saveAllPrayersAttendance(
        int $jamaatId,
        string $date,
        int $companyId,
        User $actor,
        array $attendanceData,
        array $remarksData = [],
        bool $taleemHeld = true,
        ?int $taleemReasonId = null,
        ?string $taleemRemarks = null,
    ): void {
        if (! $this->isDateAllowed($date, $companyId)) {
            throw ValidationException::withMessages([
                'date' => __('salah_attendance.date_not_allowed'),
            ]);
        }

        DB::transaction(function () use (
            $jamaatId,
            $date,
            $companyId,
            $actor,
            $attendanceData,
            $remarksData,
            $taleemHeld,
            $taleemReasonId,
            $taleemRemarks,
        ): void {
            $jamaat = Jamaat::query()->lockForUpdate()->findOrFail($jamaatId);

            if ((int) $jamaat->company_id !== $companyId) {
                throw (new ModelNotFoundException)->setModel(Jamaat::class, [$jamaatId]);
            }

            if ((int) $actor->company_id !== $companyId) {
                throw new AuthorizationException;
            }

            $lockOverride = $this->attendanceLockService->isLocked($companyId, $date);
            if ($lockOverride && ! $actor->can('salah.attendance.lock')) {
                throw new AuthorizationException;
            }

            $memberIds = DB::table('jamaat_members')
                ->join('employees', 'employees.id', '=', 'jamaat_members.employee_id')
                ->where('jamaat_members.jamaat_id', $jamaat->id)
                ->where('jamaat_members.is_active', true)
                ->where('employees.company_id', $companyId)
                ->whereNull('employees.deleted_at')
                ->pluck('employees.id')
                ->map(static fn ($id): int => (int) $id)
                ->all();

            $submittedEmployeeIds = array_map(static fn ($id): int => (int) $id, array_keys($attendanceData));
            sort($submittedEmployeeIds);
            sort($memberIds);

            if ($submittedEmployeeIds !== $memberIds) {
                throw ValidationException::withMessages([
                    'attendance' => 'The submitted attendance roster is invalid.',
                ]);
            }

            // Delete all existing attendance for this jamaat + date (all prayers)
            $existingAny = SalahAttendance::query()
                ->where('company_id', $companyId)
                ->where('jamaat_id', $jamaat->id)
                ->whereDate('attendance_date', $date)
                ->lockForUpdate()
                ->get();

            if ($existingAny->isNotEmpty() && ! $actor->can('salah.attendance.update')) {
                throw new AuthorizationException;
            }

            SalahAttendance::query()
                ->where('company_id', $companyId)
                ->where('jamaat_id', $jamaat->id)
                ->whereDate('attendance_date', $date)
                ->delete();

            // Re-insert all prayers for all employees
            foreach ($attendanceData as $employeeId => $prayerData) {
                foreach ($prayerData as $prayerId => $reasonId) {
                    SalahAttendance::create([
                        'company_id' => $companyId,
                        'attendance_date' => $date,
                        'prayer_id' => (int) $prayerId,
                        'jamaat_id' => $jamaat->id,
                        'leader_id' => $jamaat->leader_id,
                        'employee_id' => (int) $employeeId,
                        'attendance_reason_id' => ($reasonId !== null && $reasonId !== '') ? (int) $reasonId : null,
                        'remarks' => $remarksData[$employeeId] ?? null,
                    ]);
                }
            }

            // Whether Taleem was held is independent of prayer attendance —
            // it does not affect who is marked present or absent above.
            if (! $taleemHeld) {
                $this->validateTaleemReason($taleemReasonId, $companyId);
            }

            $this->taleemService->saveForJamaatDate(
                $jamaat,
                $date,
                $companyId,
                $taleemHeld,
                $taleemReasonId,
                $taleemRemarks,
                $actor,
            );

            if ($lockOverride) {
                $this->auditLogService->logAttendanceLockOverride(
                    $actor,
                    'salah_attendance',
                    'salah_attendance',
                    $date,
                    $this->attendanceLockService->lockTime($companyId),
                    ['jamaat_id' => $jamaat->id],
                );
            }
        });
    }

    /**
     * Defense-in-depth behind the Form Request's required_if rule — the
     * service must not trust the controller layer alone, consistent with how
     * validateAttendancePayload() already re-validates reason IDs server-side.
     */
    private function validateTaleemReason(?int $reasonId, int $companyId): void
    {
        $valid = $reasonId !== null && AttendanceReason::query()
            ->where('company_id', $companyId)
            ->where('status', Status::Active->value)
            ->whereKey($reasonId)
            ->exists();

        if (! $valid) {
            throw ValidationException::withMessages([
                'taleem_reason_id' => 'The selected Taleem reason is invalid.',
            ]);
        }
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
        User $actor,
        array $attendanceData,
        array $remarksData = []
    ): void {
        if (! $this->isDateAllowed($date, $companyId)) {
            throw ValidationException::withMessages([
                'date' => __('salah_attendance.date_not_allowed'),
            ]);
        }

        DB::transaction(function () use ($jamaatId, $prayerId, $date, $companyId, $actor, $attendanceData, $remarksData): void {
            $jamaat = Jamaat::query()->lockForUpdate()->findOrFail($jamaatId);

            if ((int) $jamaat->company_id !== $companyId) {
                throw (new ModelNotFoundException)->setModel(Jamaat::class, [$jamaatId]);
            }

            if ((int) $actor->company_id !== $companyId) {
                throw new AuthorizationException;
            }

            $lockOverride = $this->attendanceLockService->isLocked($companyId, $date);
            if ($lockOverride && ! $actor->can('salah.attendance.lock')) {
                throw new AuthorizationException;
            }

            $memberIds = DB::table('jamaat_members')
                ->join('employees', 'employees.id', '=', 'jamaat_members.employee_id')
                ->where('jamaat_members.jamaat_id', $jamaat->id)
                ->where('jamaat_members.is_active', true)
                ->where('employees.company_id', $companyId)
                ->whereNull('employees.deleted_at')
                ->pluck('employees.id')
                ->map(static fn ($id): int => (int) $id)
                ->all();

            $this->validateAttendancePayload($attendanceData, $remarksData, $memberIds, $companyId);

            $existingAttendance = SalahAttendance::query()
                ->where('company_id', $companyId)
                ->where('jamaat_id', $jamaat->id)
                ->whereDate('attendance_date', $date)
                ->where('prayer_id', $prayerId)
                ->lockForUpdate()
                ->get();

            if ($existingAttendance->isNotEmpty() && ! $actor->can('salah.attendance.update')) {
                throw new AuthorizationException;
            }

            foreach ($existingAttendance as $attendance) {
                $attendance->delete();
            }

            foreach ($attendanceData as $employeeId => $reasonId) {
                SalahAttendance::create([
                    'company_id' => $companyId,
                    'attendance_date' => $date,
                    'prayer_id' => $prayerId,
                    'jamaat_id' => $jamaat->id,
                    'leader_id' => $jamaat->leader_id,
                    'employee_id' => (int) $employeeId,
                    'attendance_reason_id' => $reasonId ?: null,
                    'remarks' => $remarksData[$employeeId] ?? null,
                ]);
            }

            if ($lockOverride) {
                $this->auditLogService->logAttendanceLockOverride(
                    $actor,
                    'salah_attendance',
                    'salah_attendance',
                    $date,
                    $this->attendanceLockService->lockTime($companyId),
                    ['jamaat_id' => $jamaat->id, 'prayer_id' => $prayerId],
                );
            }
        });
    }

    /**
     * @param  array<int|string, int|string|null>  $attendanceData
     * @param  array<int|string, string|null>  $remarksData
     * @param  list<int>  $memberIds
     */
    private function validateAttendancePayload(
        array $attendanceData,
        array $remarksData,
        array $memberIds,
        int $companyId
    ): void {
        sort($memberIds);

        $submittedEmployeeIds = array_map(static fn ($id): int => (int) $id, array_keys($attendanceData));
        sort($submittedEmployeeIds);

        if ($submittedEmployeeIds !== $memberIds) {
            throw ValidationException::withMessages([
                'attendance' => 'The submitted attendance roster is invalid.',
            ]);
        }

        $remarkEmployeeIds = array_unique(array_map(static fn ($id): int => (int) $id, array_keys($remarksData)));
        if (array_diff($remarkEmployeeIds, $memberIds) !== []) {
            throw ValidationException::withMessages([
                'remarks' => 'The submitted attendance remarks are invalid.',
            ]);
        }

        $reasonIds = array_values(array_unique(array_map(
            static fn ($reasonId): int => (int) $reasonId,
            array_filter($attendanceData, static fn ($reasonId): bool => $reasonId !== null && $reasonId !== '')
        )));

        if ($reasonIds === []) {
            return;
        }

        $validReasonIds = AttendanceReason::query()
            ->where('company_id', $companyId)
            ->where('status', Status::Active->value)
            ->whereIn('id', $reasonIds)
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        sort($validReasonIds);
        sort($reasonIds);

        if ($validReasonIds !== $reasonIds) {
            throw ValidationException::withMessages([
                'attendance' => 'The selected attendance reason is invalid.',
            ]);
        }
    }
}
