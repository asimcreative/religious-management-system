<?php

namespace App\DataTransfer\Definitions;

use App\Models\AttendanceReason;
use App\Models\Employee;
use App\Models\QuranAttendance;
use App\Models\QuranClass;
use App\Models\Teacher;
use App\Models\User;
use App\Services\AttendanceLockService;
use App\Support\DataTransfer\AbstractResourceDefinition;
use App\Support\DataTransfer\Column;
use Closure;

class QuranAttendanceDefinition extends AbstractResourceDefinition
{
    public function __construct(
        private readonly AttendanceLockService $lock,
    ) {}

    public function key(): string
    {
        return 'quran-attendance';
    }

    public function modelClass(): string
    {
        return QuranAttendance::class;
    }

    public function label(): string
    {
        return __('quran_attendance.quran_attendance');
    }

    public function singularLabel(): string
    {
        return __('quran_attendance.quran_attendance');
    }

    public function icon(): string
    {
        return 'bi-clipboard-check';
    }

    public function indexRoute(): string
    {
        return 'quran-attendance.index';
    }

    /** @return array<string, string> */
    public function permissions(): array
    {
        return [
            'view' => 'quran.attendance.view',
            'import' => 'quran.attendance.import',
            'export' => 'quran.attendance.export',
            'sample' => 'quran.attendance.import',
        ];
    }

    /** @return array<int, Column> */
    protected function defineColumns(): array
    {
        return [
            Column::date('attendance_date')
                ->label(__('quran_attendance.date'))
                ->required()
                ->sample(now()->format('Y-m-d')),

            Column::lookup('class_id', QuranClass::class, 'class_code')
                ->label(__('quran_attendance.class'))
                ->required()
                // The relation is quranClass; "class" is a reserved word and
                // cannot name a method.
                ->relation('quranClass')
                ->sample('QC-001')
                ->width(14),

            Column::lookup('employee_id', Employee::class, 'employee_code')
                ->label(__('quran_attendance.employee'))
                ->required()
                ->sample('EMP-0001')
                ->width(16),

            Column::related('employee_name', 'employee', 'employee_name')
                ->label(__('employees.employee_name'))
                ->width(24),

            Column::lookup('teacher_id', Teacher::class, 'teacher_code')
                ->label(__('quran_attendance.teacher'))
                ->sample('TCH-001')
                ->width(14),

            // Blank means present: the attendance screens record a reason only
            // when someone is missing, and the template must not invert that.
            Column::lookup('attendance_reason_id', AttendanceReason::class, 'reason_name')
                ->label(__('quran_attendance.status'))
                ->help(__('quran_attendance.present'))
                ->sample('')
                ->width(18),

            Column::text('remarks')
                ->label(__('quran_attendance.remarks'))
                ->rules(['max:5000'])
                ->sample('')
                ->width(28),
        ];
    }

    /**
     * One record per employee per class per day, matching the database's
     * quran_att_date_class_employee_unique index.
     *
     * @return array<int, array<int, string>>
     */
    public function uniqueGroups(): array
    {
        return [['attendance_date', 'class_id', 'employee_id']];
    }

    /** @return array<int, string> */
    protected function extraEagerLoads(): array
    {
        return ['employee'];
    }

    /**
     * Attendance carries no text of its own, so a search matches the person
     * the row is about — which is what someone typing a name expects.
     *
     * @return array<string, array<int, string>>
     */
    protected function searchRelations(): array
    {
        return ['employee' => ['employee_name', 'employee_code']];
    }

    /**
     * Attendance closes at the company's configured lock time. Import obeys
     * the same boundary the attendance screen does — a spreadsheet must not
     * be a way around a control the UI enforces. Holders of the lock-override
     * right may still backfill, exactly as they can on screen.
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

        if ($user instanceof User && $user->can('quran.attendance.lock')) {
            return [];
        }

        return $this->lock->isLocked((int) $companyId, (string) $date)
            ? [__('quran_attendance.attendance_locked')]
            : [];
    }

    /** @return array<string, string|Closure> */
    protected function filters(): array
    {
        return [
            'class_id' => 'class_id',
            'teacher_id' => 'teacher_id',
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
