<?php

namespace App\Analytics\Definitions;

use App\Analytics\Concerns\DescribesAttendance;
use App\Models\QuranClass;
use App\Models\QuranTeacherAttendance;
use App\Models\Teacher;
use App\Support\Analytics\AbstractAnalyticsDefinition;
use App\Support\Analytics\DateExpression;
use App\Support\Analytics\Dimension;
use App\Support\Analytics\Filter;
use App\Support\Analytics\Join;
use App\Support\Analytics\Measure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;

/**
 * Days each qari did not hold class — the "kitne din nahi aya" pivot.
 *
 * Every row in quran_teacher_attendance unconditionally *is* an absence — there
 * is no present/absent split to derive, unlike student attendance, so this is
 * a separate dataset rather than another dimension bolted onto
 * QuranAttendanceAnalytics. It shares only the presence-agnostic joins (reason,
 * recorder) with that trait; its time dimensions are declared directly rather
 * than through DescribesAttendance::attendanceDimensions(), which assumes a
 * present/absent status that does not exist here.
 */
class QuranTeacherAttendanceAnalytics extends AbstractAnalyticsDefinition
{
    use DescribesAttendance;

    private const TABLE = 'quran_teacher_attendance';

    public function key(): string
    {
        return 'quran-teacher-attendance';
    }

    public function label(): string
    {
        return __('analytics.quran_teacher_attendance');
    }

    public function description(): string
    {
        return __('analytics.quran_teacher_attendance_description');
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
        return QuranTeacherAttendance::query();
    }

    public function defaultDimension(): string
    {
        return 'teacher';
    }

    public function drillDown(): ?array
    {
        return [
            'route' => 'reports.quran-teacher-attendance',
            'filters' => [
                'class_id' => 'class_id',
                'teacher_id' => 'teacher_id',
                'date_from' => 'date_from',
                'date_to' => 'date_to',
            ],
        ];
    }

    protected function joinList(): array
    {
        return array_merge(
            $this->reasonJoin(self::TABLE),
            $this->recorderJoin(self::TABLE),
            [
                Join::make('quran_classes', 'quran_classes', fn (JoinClause $join) => $join
                    ->on(self::TABLE.'.class_id', '=', 'quran_classes.id')),

                Join::make(
                    'class_branches',
                    'branches',
                    fn (JoinClause $join) => $join->on('quran_classes.branch_id', '=', 'class_branches.id'),
                    ['quran_classes'],
                    'class_branches'
                ),

                Join::make('teachers', 'teachers', fn (JoinClause $join) => $join
                    ->on(self::TABLE.'.teacher_id', '=', 'teachers.id')),

                Join::make(
                    'teacher_employees',
                    'employees',
                    fn (JoinClause $join) => $join->on('teachers.employee_id', '=', 'teacher_employees.id'),
                    ['teachers'],
                    'teacher_employees'
                ),
            ],
        );
    }

    protected function dimensionList(): array
    {
        $quran = __('analytics.group_quran');
        $when = __('analytics.group_time');
        $date = self::TABLE.'.attendance_date';

        return [
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
                key: 'class',
                label: __('analytics.dim_class'),
                keyColumn: self::TABLE.'.class_id',
                labelColumn: 'quran_classes.class_name',
                joins: ['quran_classes'],
                drillFilter: 'class_id',
                group: $quran,
            ),
            Dimension::lookup(
                key: 'class_branch',
                label: __('analytics.dim_class_branch'),
                keyColumn: 'quran_classes.branch_id',
                labelColumn: 'class_branches.branch_name',
                joins: ['class_branches'],
                group: $quran,
            ),
            Dimension::lookup(
                key: 'reason',
                label: __('analytics.dim_reason'),
                keyColumn: self::TABLE.'.attendance_reason_id',
                labelColumn: 'attendance_reasons.reason_name',
                joins: ['attendance_reasons'],
                drillFilter: 'attendance_reason_id',
                group: $quran,
            ),
            Dimension::raw(
                key: 'day',
                label: __('analytics.dim_day'),
                expression: DateExpression::day($date),
                group: $when,
            ),
            Dimension::raw(
                key: 'week',
                label: __('analytics.dim_week'),
                expression: DateExpression::week($date),
                group: $when,
            ),
            Dimension::raw(
                key: 'month',
                label: __('analytics.dim_month'),
                expression: DateExpression::month($date),
                group: $when,
            ),
            Dimension::raw(
                key: 'year',
                label: __('analytics.dim_year'),
                expression: DateExpression::year($date),
                group: $when,
            ),
            Dimension::lookup(
                key: 'recorded_by',
                label: __('analytics.dim_recorded_by'),
                keyColumn: self::TABLE.'.created_by',
                labelColumn: 'recorders.name',
                joins: ['recorders'],
                group: __('analytics.group_record'),
            ),
        ];
    }

    protected function filterList(): array
    {
        $quran = __('analytics.group_quran');
        $when = __('analytics.group_time');
        $date = self::TABLE.'.attendance_date';

        return [
            Filter::date(
                key: 'date_from',
                label: __('analytics.filter_date_from'),
                apply: fn (Builder $query, string $value) => $query->whereDate($date, '>=', $value),
                group: $when,
            ),
            Filter::date(
                key: 'date_to',
                label: __('analytics.filter_date_to'),
                apply: fn (Builder $query, string $value) => $query->whereDate($date, '<=', $value),
                group: $when,
            ),
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
                key: 'class_id',
                label: __('analytics.dim_class'),
                options: static fn (): array => QuranClass::query()->orderBy('class_name')->pluck('class_name', 'id')->all(),
                apply: fn (Builder $query, string $value) => $query->where(self::TABLE.'.class_id', $value),
                group: $quran,
            ),
        ];
    }

    /** @return list<Measure> */
    public function measures(): array
    {
        return [
            Measure::aggregate(
                key: 'absent_days',
                label: __('analytics.measure_teacher_absent_days'),
                expression: 'COUNT(*)',
                primary: true,
            ),
            Measure::aggregate(
                key: 'classes_affected',
                label: __('analytics.measure_classes_affected'),
                expression: 'COUNT(DISTINCT '.self::TABLE.'.class_id)',
            ),
        ];
    }
}
