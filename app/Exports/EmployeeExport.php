<?php

namespace App\Exports;

use App\Exports\Concerns\SanitizesSpreadsheetValues;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class EmployeeExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    use SanitizesSpreadsheetValues;

    private int $index = 0;

    /** @param  array<string, mixed>  $filters */
    public function __construct(
        private readonly array $filters = [],
    ) {}

    public function query(): Builder
    {
        return Employee::query()
            ->with(['branch', 'department', 'designation'])
            ->when($this->filters['search'] ?? null, function (Builder $q, $search) {
                $q->where(function (Builder $sq) use ($search) {
                    $sq->where('employee_name', 'like', "%{$search}%")
                        ->orWhere('employee_code', 'like', "%{$search}%");
                });
            })
            ->when($this->filters['branch_id'] ?? null, fn (Builder $q, $v) => $q->where('branch_id', $v))
            ->when($this->filters['department_id'] ?? null, fn (Builder $q, $v) => $q->where('department_id', $v))
            ->when($this->filters['designation_id'] ?? null, fn (Builder $q, $v) => $q->where('designation_id', $v))
            ->when(isset($this->filters['employment_status']) && $this->filters['employment_status'] !== '', fn (Builder $q) => $q->where('employment_status', $this->filters['employment_status']))
            ->orderBy('employee_name');
    }

    /** @return array<int, string> */
    public function headings(): array
    {
        return ['#', 'Employee Code', 'Employee Name', 'Branch', 'Department', 'Designation', 'Mobile', 'Status'];
    }

    /** @return array<int, mixed> */
    public function map(mixed $row): array
    {
        return $this->sanitizeSpreadsheetValues([
            ++$this->index,
            $row->employee_code,
            $row->employee_name,
            $row->branch->branch_name ?? '-',
            $row->department->department_name ?? '-',
            $row->designation->designation_name ?? '-',
            $row->mobile ?? '-',
            $row->employment_status->label(),
        ]);
    }
}
