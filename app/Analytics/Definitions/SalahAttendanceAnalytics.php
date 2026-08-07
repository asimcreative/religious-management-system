<?php

namespace App\Analytics\Definitions;

use App\Analytics\Concerns\DescribesAttendance;
use App\Analytics\Concerns\DescribesEmployees;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\Jamaat;
use App\Models\Prayer;
use App\Models\SalahAttendance;
use App\Support\Analytics\AbstractAnalyticsDefinition;
use App\Support\Analytics\Dimension;
use App\Support\Analytics\Filter;
use App\Support\Analytics\Join;
use App\Support\Analytics\Measure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;

/**
 * Salah attendance, sliced any way it can be sliced.
 *
 * The record itself knows the prayer, the jamaat and who led it. The person it
 * belongs to brings branch, department, designation, gender and Quran standing.
 * Between them that is every question this data can answer — by prayer, by
 * jamaat, by leader, by location, by department, by month, by weekday, or any
 * two of those at once.
 *
 * Note that a jamaat has its own branch and so does the employee. They are
 * different questions: "which mosque was this recorded at" and "where is this
 * person posted", and a report that conflated them would be quietly wrong for
 * anyone who prays away from their own branch. Both are offered, named apart.
 */
class SalahAttendanceAnalytics extends AbstractAnalyticsDefinition
{
    use DescribesAttendance, DescribesEmployees;

    private const TABLE = 'salah_attendance';

    public function key(): string
    {
        return 'salah-attendance';
    }

    public function label(): string
    {
        return __('analytics.salah_attendance');
    }

    public function description(): string
    {
        return __('analytics.salah_attendance_description');
    }

    public function permission(): string
    {
        return 'report.salah';
    }

    public function table(): string
    {
        return self::TABLE;
    }

    public function query(): Builder
    {
        // The model carries both the company scope and the role-data-access
        // scope, so a Jamaat Leader running this report sees their own jamaats
        // and nothing else without the report knowing that rule exists.
        return SalahAttendance::query();
    }

    public function defaultDimension(): string
    {
        return 'prayer';
    }

    public function drillDown(): ?array
    {
        return [
            'route' => 'reports.salah-attendance',
            'filters' => [
                'jamaat_id' => 'jamaat_id',
                'prayer_id' => 'prayer_id',
                'date_from' => 'date_from',
                'date_to' => 'date_to',
            ],
        ];
    }

    protected function joinList(): array
    {
        return array_merge(
            $this->employeeJoins(self::TABLE),
            $this->reasonJoin(self::TABLE),
            $this->recorderJoin(self::TABLE),
            [
                Join::make('prayers', 'prayers', fn (JoinClause $join) => $join
                    ->on(self::TABLE.'.prayer_id', '=', 'prayers.id')),

                Join::make('jamaats', 'jamaats', fn (JoinClause $join) => $join
                    ->on(self::TABLE.'.jamaat_id', '=', 'jamaats.id')),

                // The jamaat's own branch — where the congregation is, which is
                // not necessarily where its members are posted.
                Join::make(
                    'jamaat_branches',
                    'branches',
                    fn (JoinClause $join) => $join
                        ->on('jamaats.branch_id', '=', 'jamaat_branches.id'),
                    ['jamaats'],
                    'jamaat_branches'
                ),

                // Whoever led the prayer, as an employee in their own right.
                Join::make(
                    'leaders',
                    'employees',
                    fn (JoinClause $join) => $join
                        ->on(self::TABLE.'.leader_id', '=', 'leaders.id'),
                    [],
                    'leaders'
                ),
            ],
        );
    }

    protected function dimensionList(): array
    {
        $salah = __('analytics.group_salah');

        return array_merge(
            [
                Dimension::lookup(
                    key: 'prayer',
                    label: __('analytics.dim_prayer'),
                    keyColumn: self::TABLE.'.prayer_id',
                    labelColumn: 'prayers.prayer_name',
                    joins: ['prayers'],
                    orderBy: 'prayers.prayer_order',
                    drillFilter: 'prayer_id',
                    group: $salah,
                ),
                Dimension::lookup(
                    key: 'jamaat',
                    label: __('analytics.dim_jamaat'),
                    keyColumn: self::TABLE.'.jamaat_id',
                    labelColumn: 'jamaats.jamaat_name',
                    joins: ['jamaats'],
                    drillFilter: 'jamaat_id',
                    group: $salah,
                ),
                Dimension::lookup(
                    key: 'jamaat_branch',
                    label: __('analytics.dim_jamaat_branch'),
                    keyColumn: 'jamaats.branch_id',
                    labelColumn: 'jamaat_branches.branch_name',
                    joins: ['jamaat_branches'],
                    group: $salah,
                ),
                Dimension::lookup(
                    key: 'leader',
                    label: __('analytics.dim_leader'),
                    keyColumn: self::TABLE.'.leader_id',
                    labelColumn: 'leaders.employee_name',
                    joins: ['leaders'],
                    group: $salah,
                ),
            ],
            $this->attendanceDimensions(self::TABLE),
            $this->employeeDimensions(),
        );
    }

    protected function filterList(): array
    {
        $salah = __('analytics.group_salah');

        return array_merge(
            $this->attendanceFilters(self::TABLE),
            [
                Filter::select(
                    key: 'prayer_id',
                    label: __('analytics.dim_prayer'),
                    options: static fn (): array => Prayer::query()->orderBy('prayer_order')->pluck('prayer_name', 'id')->all(),
                    apply: fn (Builder $query, string $value) => $query->where(self::TABLE.'.prayer_id', $value),
                    group: $salah,
                ),
                Filter::select(
                    key: 'jamaat_id',
                    label: __('analytics.dim_jamaat'),
                    options: static fn (): array => Jamaat::query()->orderBy('jamaat_name')->pluck('jamaat_name', 'id')->all(),
                    apply: fn (Builder $query, string $value) => $query->where(self::TABLE.'.jamaat_id', $value),
                    group: $salah,
                ),
                Filter::select(
                    key: 'jamaat_branch_id',
                    label: __('analytics.dim_jamaat_branch'),
                    options: static fn (): array => Branch::query()->orderBy('branch_name')->pluck('branch_name', 'id')->all(),
                    apply: fn (Builder $query, string $value) => $query->where('jamaats.branch_id', $value),
                    joins: ['jamaats'],
                    group: $salah,
                ),
                Filter::select(
                    key: 'leader_id',
                    label: __('analytics.dim_leader'),
                    options: static fn (): array => Employee::query()
                        ->whereIn('id', Jamaat::query()->whereNotNull('leader_id')->select('leader_id'))
                        ->orderBy('employee_name')
                        ->pluck('employee_name', 'id')
                        ->all(),
                    apply: fn (Builder $query, string $value) => $query->where(self::TABLE.'.leader_id', $value),
                    group: $salah,
                ),
            ],
            $this->employeeFilters(),
        );
    }

    /** @return list<Measure> */
    public function measures(): array
    {
        return $this->attendanceMeasures(self::TABLE);
    }
}
