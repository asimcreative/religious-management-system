<?php

namespace App\Analytics\Definitions;

use App\Analytics\Concerns\DescribesEmployees;
use App\Models\QuranDepartment;
use App\Models\QuranProgress;
use App\Models\QuranStatus;
use App\Models\Teacher;
use App\Support\Analytics\AbstractAnalyticsDefinition;
use App\Support\Analytics\Dimension;
use App\Support\Analytics\Filter;
use App\Support\Analytics\Join;
use App\Support\Analytics\Measure;
use App\Support\Analytics\MeasureFormat;
use App\Support\Analytics\NumberExpression;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;

/**
 * Quran progress, sliced any way it can be sliced.
 *
 * One row per student rather than one per occasion, so the questions are about
 * standing rather than turnout: how far has this qari's group got, which
 * department is furthest behind, how many have finished. The breakdowns are the
 * same set — by qari, by Quran department, by Quran status, by branch, by
 * department, by designation, by gender.
 *
 * The progress record carries its own Quran department and status, and so does
 * the employee. They can disagree — the employee record is the person's
 * standing on file, the progress record is what their teacher last recorded —
 * so both are offered and named apart rather than one being picked silently.
 */
class QuranProgressAnalytics extends AbstractAnalyticsDefinition
{
    use DescribesEmployees;

    private const TABLE = 'quran_progress';

    public function key(): string
    {
        return 'quran-progress';
    }

    public function label(): string
    {
        return __('analytics.quran_progress');
    }

    public function description(): string
    {
        return __('analytics.quran_progress_description');
    }

    public function permission(): string
    {
        return 'report.quran';
    }

    public function table(): string
    {
        return self::TABLE;
    }

    public function query(): Builder
    {
        return QuranProgress::query();
    }

    public function defaultDimension(): string
    {
        return 'progress_status';
    }

    public function drillDown(): ?array
    {
        return [
            'route' => 'reports.quran-progress',
            'filters' => [
                'teacher_id' => 'teacher_id',
                'quran_department_id' => 'progress_department_id',
                'quran_status_id' => 'progress_status_id',
            ],
        ];
    }

    protected function joinList(): array
    {
        return array_merge(
            $this->employeeJoins(self::TABLE),
            [
                Join::make('teachers', 'teachers', fn (JoinClause $join) => $join
                    ->on(self::TABLE.'.teacher_id', '=', 'teachers.id')),

                Join::make(
                    'teacher_employees',
                    'employees',
                    fn (JoinClause $join) => $join
                        ->on('teachers.employee_id', '=', 'teacher_employees.id'),
                    ['teachers'],
                    'teacher_employees'
                ),

                // The department and status recorded against the progress row,
                // as opposed to the ones on the employee record.
                Join::make(
                    'progress_departments',
                    'quran_departments',
                    fn (JoinClause $join) => $join
                        ->on(self::TABLE.'.quran_department_id', '=', 'progress_departments.id'),
                    [],
                    'progress_departments'
                ),

                Join::make(
                    'progress_statuses',
                    'quran_statuses',
                    fn (JoinClause $join) => $join
                        ->on(self::TABLE.'.quran_status_id', '=', 'progress_statuses.id'),
                    [],
                    'progress_statuses'
                ),
            ],
        );
    }

    protected function dimensionList(): array
    {
        $quran = __('analytics.group_quran');

        return array_merge(
            [
                Dimension::lookup(
                    key: 'teacher',
                    label: __('analytics.dim_teacher'),
                    keyColumn: self::TABLE.'.teacher_id',
                    labelColumn: 'teacher_employees.employee_name',
                    joins: ['teacher_employees'],
                    drillFilter: 'teacher_id',
                    group: $quran,
                ),
                Dimension::lookup(
                    key: 'progress_department',
                    label: __('analytics.dim_progress_department'),
                    keyColumn: self::TABLE.'.quran_department_id',
                    labelColumn: 'progress_departments.department_name',
                    joins: ['progress_departments'],
                    orderBy: 'progress_departments.display_order',
                    drillFilter: 'progress_department_id',
                    group: $quran,
                ),
                Dimension::lookup(
                    key: 'progress_status',
                    label: __('analytics.dim_progress_status'),
                    keyColumn: self::TABLE.'.quran_status_id',
                    labelColumn: 'progress_statuses.status_name',
                    joins: ['progress_statuses'],
                    orderBy: 'progress_statuses.display_order',
                    drillFilter: 'progress_status_id',
                    group: $quran,
                ),
                Dimension::raw(
                    key: 'sipara',
                    label: __('analytics.dim_sipara'),
                    expression: self::TABLE.'.current_sipara',
                    group: $quran,
                ),
                // Completion bucketed into tens. A per-student percentage is
                // not a breakdown — it is the raw list — so the useful question
                // is how the group is spread across the range.
                Dimension::make(
                    key: 'completion_band',
                    label: __('analytics.dim_completion_band'),
                    groupBy: NumberExpression::band(self::TABLE.'.completion_percentage', 10),
                    labelExpression: NumberExpression::band(self::TABLE.'.completion_percentage', 10),
                    group: $quran,
                    labelUsing: static function (int|string|null $key): string {
                        $band = (int) $key;

                        // 100% lands in its own band of one; folding it into
                        // 90–100 would hide exactly the number people look for.
                        return $band >= 10
                            ? __('analytics.band_complete')
                            : ($band * 10).'–'.($band * 10 + 9).'%';
                    },
                ),
            ],
            $this->employeeDimensions(),
        );
    }

    protected function filterList(): array
    {
        $quran = __('analytics.group_quran');

        return array_merge(
            [
                Filter::select(
                    key: 'teacher_id',
                    label: __('analytics.dim_teacher'),
                    options: static fn (): array => Teacher::query()
                        ->join('employees', 'teachers.employee_id', '=', 'employees.id')
                        ->orderBy('employees.employee_name')
                        ->pluck('employees.employee_name', 'teachers.id')
                        ->all(),
                    apply: fn (Builder $query, string $value) => $query->where(self::TABLE.'.teacher_id', $value),
                    group: $quran,
                ),
                Filter::select(
                    key: 'progress_department_id',
                    label: __('analytics.dim_progress_department'),
                    options: static fn (): array => QuranDepartment::query()->orderBy('display_order')->orderBy('department_name')->pluck('department_name', 'id')->all(),
                    apply: fn (Builder $query, string $value) => $query->where(self::TABLE.'.quran_department_id', $value),
                    group: $quran,
                ),
                Filter::select(
                    key: 'progress_status_id',
                    label: __('analytics.dim_progress_status'),
                    options: static fn (): array => QuranStatus::query()->orderBy('display_order')->orderBy('status_name')->pluck('status_name', 'id')->all(),
                    apply: fn (Builder $query, string $value) => $query->where(self::TABLE.'.quran_status_id', $value),
                    group: $quran,
                ),
                Filter::number(
                    key: 'completion_min',
                    label: __('analytics.filter_completion_min'),
                    apply: fn (Builder $query, string $value) => $query->where(self::TABLE.'.completion_percentage', '>=', (float) $value),
                    placeholder: '0',
                    group: $quran,
                ),
                Filter::number(
                    key: 'completion_max',
                    label: __('analytics.filter_completion_max'),
                    apply: fn (Builder $query, string $value) => $query->where(self::TABLE.'.completion_percentage', '<=', (float) $value),
                    placeholder: '100',
                    group: $quran,
                ),
                Filter::number(
                    key: 'sipara',
                    label: __('analytics.dim_sipara'),
                    apply: fn (Builder $query, string $value) => $query->where(self::TABLE.'.current_sipara', (int) $value),
                    placeholder: '1–30',
                    group: $quran,
                ),
                Filter::date(
                    key: 'updated_from',
                    label: __('analytics.filter_updated_from'),
                    apply: fn (Builder $query, string $value) => $query->whereDate(self::TABLE.'.updated_at', '>=', $value),
                    group: $quran,
                ),
                Filter::date(
                    key: 'updated_to',
                    label: __('analytics.filter_updated_to'),
                    apply: fn (Builder $query, string $value) => $query->whereDate(self::TABLE.'.updated_at', '<=', $value),
                    group: $quran,
                ),
            ],
            $this->employeeFilters(),
        );
    }

    /** @return list<Measure> */
    public function measures(): array
    {
        return [
            Measure::aggregate(
                key: 'students',
                label: __('analytics.measure_students'),
                expression: 'COUNT(*)',
                primary: true,
            ),
            Measure::aggregate(
                key: 'average',
                label: __('analytics.measure_average_completion'),
                expression: 'AVG('.self::TABLE.'.completion_percentage)',
                format: MeasureFormat::Percent,
            ),
            Measure::aggregate(
                key: 'completed',
                label: __('analytics.measure_completed'),
                expression: 'SUM(CASE WHEN '.self::TABLE.'.completion_percentage >= 100 THEN 1 ELSE 0 END)',
            ),
            Measure::aggregate(
                key: 'not_started',
                label: __('analytics.measure_not_started'),
                expression: 'SUM(CASE WHEN '.self::TABLE.'.completion_percentage <= 0 THEN 1 ELSE 0 END)',
            ),
            Measure::derived(
                key: 'completed_rate',
                label: __('analytics.measure_completed_rate'),
                compute: static function (array $row): ?float {
                    $students = (float) ($row['students'] ?? 0);

                    return $students > 0.0
                        ? round(((float) ($row['completed'] ?? 0) / $students) * 100, 1)
                        : null;
                },
            ),
        ];
    }
}
