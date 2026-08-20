<?php

namespace App\Services;

use App\Contracts\Repositories\QuranAttendanceRepositoryInterface;
use App\Enums\AttendanceReasonType;
use App\Enums\Status;
use App\Helpers\TimezoneHelper;
use App\Models\AttendanceReason;
use App\Models\QuranAttendance;
use App\Models\QuranClass;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuranAttendanceService extends BaseService
{
    private readonly QuranAttendanceRepositoryInterface $attendanceRepository;

    public function __construct(
        QuranAttendanceRepositoryInterface $repository,
        private readonly AttendanceLockService $attendanceLockService,
        private readonly AuditLogService $auditLogService,
        private readonly QuranTeacherAttendanceService $teacherAttendanceService,
    ) {
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
     * Save attendance for a class on a date.
     * Each employee gets one attendance record per class per day.
     *
     * @param  array<int, int|null>  $attendanceData  employee_id => attendance_reason_id (null = present)
     * @param  array<int, string|null>  $remarksData  employee_id => remarks
     */
    public function saveAttendance(
        int $classId,
        string $date,
        int $companyId,
        User $actor,
        array $attendanceData,
        array $remarksData = [],
        bool $teacherAbsent = false,
        ?int $teacherAbsenceReasonId = null,
        ?string $teacherAbsenceRemarks = null,
    ): void {
        if (! $this->isDateAllowed($date, $companyId)) {
            throw ValidationException::withMessages([
                'date' => __('quran_attendance.date_not_allowed'),
            ]);
        }

        DB::transaction(function () use (
            $classId,
            $date,
            $companyId,
            $actor,
            $attendanceData,
            $remarksData,
            $teacherAbsent,
            $teacherAbsenceReasonId,
            $teacherAbsenceRemarks,
        ): void {
            $class = QuranClass::query()->lockForUpdate()->findOrFail($classId);

            if ((int) $class->company_id !== $companyId) {
                throw (new ModelNotFoundException)->setModel(QuranClass::class, [$classId]);
            }

            if ((int) $actor->company_id !== $companyId) {
                throw new AuthorizationException;
            }

            $lockOverride = $this->attendanceLockService->isLocked($companyId, $date);
            if ($lockOverride && ! $actor->can('quran.attendance.lock')) {
                throw new AuthorizationException;
            }

            $memberIds = DB::table('quran_class_members')
                ->join('employees', 'employees.id', '=', 'quran_class_members.employee_id')
                ->where('quran_class_members.class_id', $class->id)
                ->where('quran_class_members.is_active', true)
                ->where('employees.company_id', $companyId)
                ->whereNull('employees.deleted_at')
                ->pluck('employees.id')
                ->map(static fn ($id): int => (int) $id)
                ->all();

            if ($teacherAbsent) {
                if ($class->teacher_id === null) {
                    throw ValidationException::withMessages([
                        'teacher_absent' => __('quran_attendance.no_teacher_assigned'),
                    ]);
                }

                // The class did not happen: no submitted per-student reason
                // survives, regardless of what the payload said. This also
                // closes the gap where a stale/tampered payload could mark a
                // student absent on a no-class day.
                $attendanceData = array_fill_keys(array_keys($attendanceData), null);
            }

            $this->validateAttendancePayload($attendanceData, $remarksData, $memberIds, $companyId);

            $existingAttendance = QuranAttendance::query()
                ->where('company_id', $companyId)
                ->where('class_id', $class->id)
                ->whereDate('attendance_date', $date)
                ->lockForUpdate()
                ->get();

            if ($existingAttendance->isNotEmpty() && ! $actor->can('quran.attendance.update')) {
                throw new AuthorizationException;
            }

            foreach ($existingAttendance as $attendance) {
                $attendance->delete();
            }

            foreach ($attendanceData as $employeeId => $reasonId) {
                QuranAttendance::create([
                    'company_id' => $companyId,
                    'attendance_date' => $date,
                    'class_id' => $class->id,
                    'teacher_id' => $class->teacher_id,
                    'employee_id' => (int) $employeeId,
                    'attendance_reason_id' => $reasonId ?: null,
                    'class_held' => ! $teacherAbsent,
                    'remarks' => $remarksData[$employeeId] ?? null,
                ]);
            }

            if ($teacherAbsent) {
                $this->validateTeacherAbsenceReason($teacherAbsenceReasonId, $companyId);

                $this->teacherAttendanceService->markAbsent(
                    $class,
                    $date,
                    $companyId,
                    (int) $teacherAbsenceReasonId,
                    $teacherAbsenceRemarks,
                    $actor,
                );
            } else {
                $this->teacherAttendanceService->clearAbsence($class, $date, $companyId);
            }

            if ($lockOverride) {
                $this->auditLogService->logAttendanceLockOverride(
                    $actor,
                    'quran_attendance',
                    'quran_attendance',
                    $date,
                    $this->attendanceLockService->lockTime($companyId),
                    ['class_id' => $class->id],
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
            ->where('type', AttendanceReasonType::Quran)
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

    /**
     * Defense-in-depth behind the Form Request's required_if rule — the
     * service must not trust the controller layer alone, consistent with how
     * validateAttendancePayload() already re-validates reason IDs server-side.
     */
    private function validateTeacherAbsenceReason(?int $reasonId, int $companyId): void
    {
        $valid = $reasonId !== null && AttendanceReason::query()
            ->where('company_id', $companyId)
            ->where('type', AttendanceReasonType::Quran)
            ->where('status', Status::Active->value)
            ->whereKey($reasonId)
            ->exists();

        if (! $valid) {
            throw ValidationException::withMessages([
                'teacher_absence_reason_id' => 'The selected teacher absence reason is invalid.',
            ]);
        }
    }
}
