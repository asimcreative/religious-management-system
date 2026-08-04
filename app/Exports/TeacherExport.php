<?php

namespace App\Exports;

use App\Models\Teacher;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TeacherExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    /** @param  array<string, mixed>  $filters */
    public function __construct(
        private readonly array $filters = [],
    ) {}

    public function query(): Builder
    {
        return Teacher::query()
            ->with(['employee', 'branches'])
            ->when($this->filters['search'] ?? null, function (Builder $q, $search) {
                $q->where('teacher_code', 'like', "%{$search}%")
                    ->orWhereHas('employee', fn (Builder $eq) => $eq->where('employee_name', 'like', "%{$search}%"));
            })
            ->when($this->filters['branch_id'] ?? null, function (Builder $q, $branchId) {
                $q->whereHas('branches', fn (Builder $bq) => $bq->where('branches.id', $branchId));
            })
            ->when(isset($this->filters['status']) && $this->filters['status'] !== '', fn (Builder $q) => $q->where('status', $this->filters['status']))
            ->orderBy('teacher_code');
    }

    /** @return array<int, string> */
    public function headings(): array
    {
        return ['#', 'Teacher Code', 'Teacher Name', 'Assigned Branches', 'Status'];
    }

    /** @return array<int, mixed> */
    public function map(mixed $row): array
    {
        static $index = 0;

        return [
            ++$index,
            $row->teacher_code,
            $row->getEmployeeName(),
            $row->branches->pluck('branch_name')->implode(', '),
            $row->status->label(),
        ];
    }
}
