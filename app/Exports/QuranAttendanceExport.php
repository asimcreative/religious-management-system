<?php

namespace App\Exports;

use App\Models\QuranAttendance;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class QuranAttendanceExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    /** @param  array<string, mixed>  $filters */
    public function __construct(
        private readonly array $filters = [],
    ) {}

    public function query(): Builder
    {
        return QuranAttendance::query()
            ->with(['quranClass', 'teacher.employee', 'employee', 'attendanceReason'])
            ->when($this->filters['class_id'] ?? null, fn (Builder $q, $v) => $q->where('class_id', $v))
            ->when($this->filters['teacher_id'] ?? null, fn (Builder $q, $v) => $q->where('teacher_id', $v))
            ->when($this->filters['date_from'] ?? null, fn (Builder $q, $v) => $q->where('attendance_date', '>=', $v))
            ->when($this->filters['date_to'] ?? null, fn (Builder $q, $v) => $q->where('attendance_date', '<=', $v))
            ->latest('attendance_date');
    }

    /** @return array<int, string> */
    public function headings(): array
    {
        return ['#', 'Date', 'Class', 'Teacher', 'Employee', 'Status', 'Remarks'];
    }

    /** @return array<int, mixed> */
    public function map(mixed $row): array
    {
        static $index = 0;

        return [
            ++$index,
            $row->attendance_date->format('d M Y'),
            $row->quranClass->class_name ?? '-',
            $row->teacher?->getEmployeeName() ?? '-',
            $row->employee->employee_name ?? '-',
            $row->attendance_reason_id === null ? 'Present' : ($row->attendanceReason->reason_name ?? 'Absent'),
            $row->remarks ?? '-',
        ];
    }
}
