<?php

namespace App\DataTransfer\Definitions;

use App\Models\AttendanceReason;
use App\Models\Employee;
use App\Models\Jamaat;
use App\Models\Prayer;
use App\Models\SalahAttendance;
use App\Models\User;
use App\Services\AttendanceLockService;
use App\Support\DataTransfer\AbstractResourceDefinition;
use App\Support\DataTransfer\Column;
use Closure;

class SalahAttendanceDefinition extends AbstractResourceDefinition
{
    public function __construct(
        private readonly AttendanceLockService $lock,
    ) {}

    public function key(): string
    {
        return 'salah-attendance';
    }

    public function modelClass(): string
    {
        return SalahAttendance::class;
    }

    public function label(): string
    {
        return __('salah_attendance.salah_attendance');
    }

    public function singularLabel(): string
    {
        return __('salah_attendance.salah_attendance');
    }

    public function icon(): string
    {
        return 'bi-moon-stars';
    }

    public function indexRoute(): string
    {
        return 'salah-attendance.index';
    }

    /** @return array<string, string> */
    public function permissions(): array
    {
        return [
            'view' => 'salah.attendance.view',
            'import' => 'salah.attendance.import',
            'export' => 'salah.attendance.export',
            'sample' => 'salah.attendance.import',
        ];
    }

    /** @return array<int, Column> */
    protected function defineColumns(): array
    {
        return [
            Column::date('attendance_date')
                ->label(__('salah_attendance.date'))
                ->required()
                ->sample(now()->format('Y-m-d')),

            // Prayers are platform reference data shared by every company,
            // so this lookup is intentionally not tenant-scoped.
            Column::lookup('prayer_id', Prayer::class, 'prayer_name')
                ->label(__('salah_attendance.prayer'))
                ->required()
                ->sample('Fajr')
                ->width(14),

            Column::lookup('jamaat_id', Jamaat::class, 'jamaat_number')
                ->label(__('salah_attendance.jamaat'))
                ->sample('J-01')
                ->width(14),

            Column::lookup('employee_id', Employee::class, 'employee_code')
                ->label(__('salah_attendance.employee'))
                ->required()
                ->sample('EMP-0001')
                ->width(16),

            Column::related('employee_name', 'employee', 'employee_name')
                ->label(__('salah_attendance.employee_name'))
                ->width(24),

            Column::lookup('leader_id', Employee::class, 'employee_code')
                ->label(__('salah_attendance.leader'))
                ->relation('leader')
                ->sample('EMP-0002')
                ->width(16),

            // Blank means the person attended; a reason is recorded only when
            // they did not.
            Column::lookup('attendance_reason_id', AttendanceReason::class, 'reason_name')
                ->label(__('salah_attendance.reason'))
                ->help(__('salah_attendance.present'))
                ->sample('')
                ->width(18),

            Column::text('remarks')
                ->label(__('salah_attendance.remarks'))
                ->rules(['max:5000'])
                ->sample('')
                ->width(28),
        ];
    }

    /**
     * Matches salah_att_date_prayer_employee_unique: one record per person
     * per prayer per day.
     *
     * @return array<int, array<int, string>>
     */
    public function uniqueGroups(): array
    {
        return [['attendance_date', 'prayer_id', 'employee_id']];
    }

    /** @return array<int, string> */
    protected function extraEagerLoads(): array
    {
        return ['employee'];
    }

    /**
     * Attendance carries no text of its own, so a search matches the person
     * the row is about.
     *
     * @return array<string, array<int, string>>
     */
    protected function searchRelations(): array
    {
        return ['employee' => ['employee_name', 'employee_code']];
    }

    /**
     * The same lock the attendance screen enforces, for the same reason.
     *
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $context
     * @return array<int, string>
     */
    public function validateRow(array $attributes, array $context): array
    {
        $date = $attributes['attendance_date'] ?? null;
        $companyId = $context['company_id'] ?? null;

        if ($date === null || $companyId === null) {
            return [];
        }

        $user = $context['user'] ?? null;

        if ($user instanceof User && $user->can('salah.attendance.lock')) {
            return [];
        }

        return $this->lock->isLocked((int) $companyId, (string) $date)
            ? [__('salah_attendance.attendance_locked')]
            : [];
    }

    /** @return array<string, string|Closure> */
    protected function filters(): array
    {
        return [
            'jamaat_id' => 'jamaat_id',
            'prayer_id' => 'prayer_id',
            'date_from' => fn ($query, $value) => $query->whereDate('attendance_date', '>=', $value),
            'date_to' => fn ($query, $value) => $query->whereDate('attendance_date', '<=', $value),
        ];
    }

    /** @return array<string, string> */
    protected function defaultSort(): array
    {
        return ['attendance_date' => 'desc'];
    }

    /**
     * Attendance rows are a record of what happened on a day; deleting forty at once loses history rather than saving time.
     */
    public function supportsBulkActions(): bool
    {
        return false;
    }
}
