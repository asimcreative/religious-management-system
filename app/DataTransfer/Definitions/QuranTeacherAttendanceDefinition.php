<?php

namespace App\DataTransfer\Definitions;

use App\Models\AttendanceReason;
use App\Models\QuranClass;
use App\Models\QuranTeacherAttendance;
use App\Models\Teacher;
use App\Support\DataTransfer\AbstractResourceDefinition;
use App\Support\DataTransfer\Column;
use Closure;

/**
 * Export only.
 *
 * A teacher-absence row is only ever written by QuranAttendanceService::saveAttendance(),
 * in the same transaction that also flips every student's class_held for that
 * date. Importing this table directly would let a spreadsheet manufacture a
 * "teacher was absent" event with no matching student-side effect, desyncing
 * the two tables — the same reasoning as NotificationDefinition, one level
 * more consequential because two tables must agree here.
 */
class QuranTeacherAttendanceDefinition extends AbstractResourceDefinition
{
    public function key(): string
    {
        return 'quran-teacher-attendance';
    }

    public function modelClass(): string
    {
        return QuranTeacherAttendance::class;
    }

    public function label(): string
    {
        return __('reports.quran_teacher_attendance_report');
    }

    public function singularLabel(): string
    {
        return __('reports.quran_teacher_attendance_report');
    }

    public function icon(): string
    {
        return 'bi-person-x';
    }

    public function indexRoute(): string
    {
        return 'reports.quran-teacher-attendance';
    }

    public function supportsImport(): bool
    {
        return false;
    }

    /**
     * A teacher-absence day is a record of what happened, not a row to bulk
     * delete — same reasoning as QuranAttendanceDefinition.
     */
    public function supportsBulkActions(): bool
    {
        return false;
    }

    /** @return array<string, string> */
    public function permissions(): array
    {
        return [
            'view' => 'quran.attendance.view',
            'export' => 'quran.attendance.export',
        ];
    }

    /** @return array<int, Column> */
    protected function defineColumns(): array
    {
        return [
            Column::date('attendance_date')
                ->label(__('quran_attendance.date'))
                ->required(),

            Column::lookup('class_id', QuranClass::class, 'class_code')
                ->label(__('quran_attendance.class'))
                ->relation('quranClass')
                ->width(14),

            Column::lookup('teacher_id', Teacher::class, 'teacher_code')
                ->label(__('quran_attendance.teacher'))
                ->width(14),

            Column::lookup('attendance_reason_id', AttendanceReason::class, 'reason_name')
                ->label(__('quran_attendance.status'))
                ->width(18),

            Column::text('remarks')
                ->label(__('quran_attendance.remarks'))
                ->width(28),
        ];
    }

    /** @return array<int, string> */
    protected function extraEagerLoads(): array
    {
        return ['teacher.employee'];
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
}
