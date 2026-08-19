<?php

namespace App\DataTransfer\Definitions;

use App\Models\AttendanceReason;
use App\Models\Employee;
use App\Models\Jamaat;
use App\Models\JamaatTaleem;
use App\Support\DataTransfer\AbstractResourceDefinition;
use App\Support\DataTransfer\Column;
use Closure;

/**
 * Export only.
 *
 * A Taleem row is only ever written by SalahAttendanceService::saveAllPrayersAttendance(),
 * inside the same transaction that also validates and writes the day's prayer
 * roster. Importing this table directly would let a spreadsheet manufacture
 * a "Taleem happened/did not happen" event with no matching prayer-attendance
 * submission behind it — the same reasoning as QuranTeacherAttendanceDefinition.
 */
class JamaatTaleemDefinition extends AbstractResourceDefinition
{
    public function key(): string
    {
        return 'jamaat-taleem';
    }

    public function modelClass(): string
    {
        return JamaatTaleem::class;
    }

    public function label(): string
    {
        return __('reports.jamaat_taleem_report');
    }

    public function singularLabel(): string
    {
        return __('reports.jamaat_taleem_report');
    }

    public function icon(): string
    {
        return 'bi-book';
    }

    public function indexRoute(): string
    {
        return 'reports.jamaat-taleem';
    }

    public function supportsImport(): bool
    {
        return false;
    }

    /**
     * A Taleem day is a record of what happened, not a row to bulk delete —
     * same reasoning as SalahAttendanceDefinition.
     */
    public function supportsBulkActions(): bool
    {
        return false;
    }

    /** @return array<string, string> */
    public function permissions(): array
    {
        return [
            'view' => 'salah.attendance.view',
            'export' => 'salah.attendance.export',
        ];
    }

    /** @return array<int, Column> */
    protected function defineColumns(): array
    {
        return [
            Column::date('attendance_date')
                ->label(__('salah_attendance.date'))
                ->required(),

            Column::lookup('jamaat_id', Jamaat::class, 'jamaat_number')
                ->label(__('salah_attendance.jamaat'))
                ->width(14),

            Column::lookup('leader_id', Employee::class, 'employee_code')
                ->label(__('salah_attendance.leader'))
                ->relation('leader')
                ->width(16),

            Column::lookup('attendance_reason_id', AttendanceReason::class, 'reason_name')
                ->label(__('reports.taleem_status'))
                ->width(18),

            Column::text('remarks')
                ->label(__('salah_attendance.remarks'))
                ->rules(['max:5000'])
                ->width(28),
        ];
    }

    /** @return array<string, string|Closure> */
    protected function filters(): array
    {
        return [
            'jamaat_id' => 'jamaat_id',
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
