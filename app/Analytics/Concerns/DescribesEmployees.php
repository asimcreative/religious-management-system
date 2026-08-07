<?php

namespace App\Analytics\Concerns;

use App\Enums\Status;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Designation;
use App\Models\QuranDepartment;
use App\Models\QuranStatus;
use App\Support\Analytics\Dimension;
use App\Support\Analytics\Filter;
use App\Support\Analytics\Join;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;

/**
 * Everything that can be asked *about the person* a record belongs to.
 *
 * Quran attendance, Salah attendance and Quran progress each hang off an
 * employee, so "by department", "by designation", "by branch", "by gender" —
 * and the matching filters — are the same question against all three. Written
 * once here rather than three times, which is also why they cannot drift apart
 * and start reporting different numbers for the same thing.
 *
 * Master data is read fresh for each dropdown rather than cached: it is small,
 * a report screen renders it once, and a stale option list is worse than a
 * query — the reader would filter by a branch that no longer exists and get an
 * empty report with nothing saying why.
 */
trait DescribesEmployees
{
    /**
     * @param  string  $table  The dataset's own table.
     * @param  string  $foreignKey  Column on it pointing at employees.id.
     * @return list<Join>
     */
    protected function employeeJoins(string $table, string $foreignKey = 'employee_id'): array
    {
        return [
            // Matched on company as well as id. The foreign key alone would be
            // enough while the data is sound; adding the tenant column means a
            // report cannot cross companies even if it is not.
            Join::make('employees', 'employees', fn (JoinClause $join) => $join
                ->on($table.'.'.$foreignKey, '=', 'employees.id')
                ->on($table.'.company_id', '=', 'employees.company_id')),

            Join::make('branches', 'branches', fn (JoinClause $join) => $join
                ->on('employees.branch_id', '=', 'branches.id'), ['employees']),

            Join::make('departments', 'departments', fn (JoinClause $join) => $join
                ->on('employees.department_id', '=', 'departments.id'), ['employees']),

            Join::make('designations', 'designations', fn (JoinClause $join) => $join
                ->on('employees.designation_id', '=', 'designations.id'), ['employees']),

            Join::make(
                'employee_quran_departments',
                'quran_departments',
                fn (JoinClause $join) => $join
                    ->on('employees.quran_department_id', '=', 'employee_quran_departments.id'),
                ['employees'],
                'employee_quran_departments'
            ),

            Join::make(
                'employee_quran_statuses',
                'quran_statuses',
                fn (JoinClause $join) => $join
                    ->on('employees.quran_status_id', '=', 'employee_quran_statuses.id'),
                ['employees'],
                'employee_quran_statuses'
            ),
        ];
    }

    /**
     * @return list<Dimension>
     */
    protected function employeeDimensions(): array
    {
        $group = __('analytics.group_person');

        return [
            Dimension::lookup(
                key: 'branch',
                label: __('analytics.dim_branch'),
                keyColumn: 'employees.branch_id',
                labelColumn: 'branches.branch_name',
                joins: ['branches'],
                drillFilter: 'branch_id',
                group: $group,
            ),
            Dimension::lookup(
                key: 'department',
                label: __('analytics.dim_department'),
                keyColumn: 'employees.department_id',
                labelColumn: 'departments.department_name',
                joins: ['departments'],
                drillFilter: 'department_id',
                group: $group,
            ),
            Dimension::lookup(
                key: 'designation',
                label: __('analytics.dim_designation'),
                keyColumn: 'employees.designation_id',
                labelColumn: 'designations.designation_name',
                joins: ['designations'],
                drillFilter: 'designation_id',
                group: $group,
            ),
            Dimension::lookup(
                key: 'employee',
                label: __('analytics.dim_employee'),
                keyColumn: 'employees.id',
                labelColumn: 'employees.employee_name',
                joins: ['employees'],
                drillFilter: 'employee_id',
                group: $group,
            ),
            Dimension::raw(
                key: 'gender',
                label: __('analytics.dim_gender'),
                expression: 'employees.gender',
                joins: ['employees'],
                drillFilter: 'gender',
                group: $group,
            ),
            Dimension::raw(
                key: 'employment_status',
                label: __('analytics.dim_employment_status'),
                expression: 'employees.employment_status',
                joins: ['employees'],
                drillFilter: 'employment_status',
                group: $group,
            ),
            Dimension::lookup(
                key: 'employee_quran_department',
                label: __('analytics.dim_quran_department'),
                keyColumn: 'employees.quran_department_id',
                labelColumn: 'employee_quran_departments.department_name',
                joins: ['employee_quran_departments'],
                drillFilter: 'quran_department_id',
                group: $group,
            ),
            Dimension::lookup(
                key: 'employee_quran_status',
                label: __('analytics.dim_quran_status'),
                keyColumn: 'employees.quran_status_id',
                labelColumn: 'employee_quran_statuses.status_name',
                joins: ['employee_quran_statuses'],
                drillFilter: 'quran_status_id',
                group: $group,
            ),
        ];
    }

    /**
     * @return list<Filter>
     */
    protected function employeeFilters(): array
    {
        $group = __('analytics.group_person');

        return [
            Filter::text(
                key: 'employee',
                label: __('analytics.filter_employee'),
                apply: fn (Builder $query, string $value) => $query->where(function (Builder $inner) use ($value): void {
                    $inner->where('employees.employee_name', 'like', "%{$value}%")
                        ->orWhere('employees.employee_code', 'like', "%{$value}%");
                }),
                joins: ['employees'],
                placeholder: __('analytics.filter_employee_placeholder'),
                group: $group,
                width: 'field--md',
            ),
            Filter::select(
                key: 'branch_id',
                label: __('analytics.dim_branch'),
                options: static fn (): array => Branch::query()->orderBy('branch_name')->pluck('branch_name', 'id')->all(),
                apply: fn (Builder $query, string $value) => $query->where('employees.branch_id', $value),
                joins: ['employees'],
                group: $group,
            ),
            Filter::select(
                key: 'department_id',
                label: __('analytics.dim_department'),
                options: static fn (): array => Department::query()->orderBy('department_name')->pluck('department_name', 'id')->all(),
                apply: fn (Builder $query, string $value) => $query->where('employees.department_id', $value),
                joins: ['employees'],
                group: $group,
            ),
            Filter::select(
                key: 'designation_id',
                label: __('analytics.dim_designation'),
                options: static fn (): array => Designation::query()->orderBy('designation_name')->pluck('designation_name', 'id')->all(),
                apply: fn (Builder $query, string $value) => $query->where('employees.designation_id', $value),
                joins: ['employees'],
                group: $group,
            ),
            Filter::select(
                key: 'gender',
                label: __('analytics.dim_gender'),
                options: static fn (): array => [
                    'male' => __('employees.male'),
                    'female' => __('employees.female'),
                ],
                apply: fn (Builder $query, string $value) => $query->where('employees.gender', $value),
                joins: ['employees'],
                group: $group,
                width: 'field--sm',
            ),
            Filter::select(
                key: 'employment_status',
                label: __('analytics.dim_employment_status'),
                options: static fn (): array => self::statusOptions(),
                apply: fn (Builder $query, string $value) => $query->where('employees.employment_status', $value),
                joins: ['employees'],
                group: $group,
                width: 'field--sm',
            ),
            Filter::select(
                key: 'quran_department_id',
                label: __('analytics.dim_quran_department'),
                options: static fn (): array => QuranDepartment::query()->orderBy('display_order')->orderBy('department_name')->pluck('department_name', 'id')->all(),
                apply: fn (Builder $query, string $value) => $query->where('employees.quran_department_id', $value),
                joins: ['employees'],
                group: $group,
            ),
            Filter::select(
                key: 'quran_status_id',
                label: __('analytics.dim_quran_status'),
                options: static fn (): array => QuranStatus::query()->orderBy('display_order')->orderBy('status_name')->pluck('status_name', 'id')->all(),
                apply: fn (Builder $query, string $value) => $query->where('employees.quran_status_id', $value),
                joins: ['employees'],
                group: $group,
            ),
        ];
    }

    /** @return array<int, string> */
    private static function statusOptions(): array
    {
        $options = [];

        foreach (Status::cases() as $status) {
            $options[$status->value] = $status->label();
        }

        return $options;
    }
}
