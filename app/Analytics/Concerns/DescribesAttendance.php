<?php

namespace App\Analytics\Concerns;

use App\Enums\AttendanceReasonType;
use App\Models\AttendanceReason;
use App\Support\Analytics\AttendanceExpression;
use App\Support\Analytics\DateExpression;
use App\Support\Analytics\Dimension;
use App\Support\Analytics\Filter;
use App\Support\Analytics\Join;
use App\Support\Analytics\Measure;
use App\Support\Analytics\MeasureFormat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;

/**
 * What Quran attendance and Salah attendance have in common.
 *
 * Both are one row per person per occasion, both mark absence by carrying a
 * reason, and both are asked the same questions over time — by day, by week, by
 * month, by year, by day of the week. Sharing that here is what keeps the
 * attendance rate defined identically for the two modules; if the formula ever
 * needs to change it changes in one place and both reports move together.
 *
 * The rule that decides everything else: **a record with no reason is a person
 * who was present.** Recording an absence means recording why.
 */
trait DescribesAttendance
{
    /**
     * @return list<Join>
     */
    protected function reasonJoin(string $table): array
    {
        return [
            Join::make('attendance_reasons', 'attendance_reasons', fn (JoinClause $join) => $join
                ->on($table.'.attendance_reason_id', '=', 'attendance_reasons.id')),
        ];
    }

    /**
     * Present, absent, and the rate between them.
     *
     * The rate is derived rather than aggregated so the totals line recomputes
     * it from the totals — see Measure. Averaging per-group rates would let a
     * jamaat of three outweigh one of three hundred.
     *
     * @return list<Measure>
     */
    protected function attendanceMeasures(string $table): array
    {
        return [
            Measure::aggregate(
                key: 'records',
                label: __('analytics.measure_records'),
                expression: 'COUNT(*)',
            ),
            Measure::aggregate(
                key: 'present',
                label: __('analytics.measure_present'),
                expression: 'SUM(1 - ('.AttendanceExpression::absentCase($table).'))',
                primary: true,
                joins: ['attendance_reasons'],
            ),
            Measure::aggregate(
                key: 'absent',
                label: __('analytics.measure_absent'),
                expression: 'SUM('.AttendanceExpression::absentCase($table).')',
                joins: ['attendance_reasons'],
            ),
            Measure::derived(
                key: 'rate',
                label: __('analytics.measure_rate'),
                compute: static function (array $row): ?float {
                    $records = (float) ($row['records'] ?? 0);

                    // No records is not nought per cent — it is no answer. A
                    // zero here would read as "nobody attended" and drag any
                    // eye-scan of the column downwards for no reason.
                    return $records > 0.0
                        ? round(((float) ($row['present'] ?? 0) / $records) * 100, 1)
                        : null;
                },
                format: MeasureFormat::Percent,
            ),
        ];
    }

    /**
     * @return list<Dimension>
     */
    protected function attendanceDimensions(string $table): array
    {
        $when = __('analytics.group_time');
        $what = __('analytics.group_record');
        $date = $table.'.attendance_date';

        return [
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
            Dimension::raw(
                key: 'weekday',
                label: __('analytics.dim_weekday'),
                expression: DateExpression::weekday($date),
                group: $when,
                labelUsing: static fn (int|string|null $key): string => DateExpression::weekdayNames()[(int) $key]
                    ?? __('analytics.not_set'),
            ),
            Dimension::lookup(
                key: 'reason',
                label: __('analytics.dim_reason'),
                keyColumn: $table.'.attendance_reason_id',
                labelColumn: 'attendance_reasons.reason_name',
                joins: ['attendance_reasons'],
                drillFilter: 'attendance_reason_id',
                group: $what,
                // The null group here is not missing data — it is everyone who
                // turned up, and calling it "Not set" would be wrong.
                labelUsing: static fn (int|string|null $key, ?string $label): string => $key === null
                    ? __('analytics.present_no_reason')
                    : ($label ?? __('analytics.not_set')),
            ),
            Dimension::make(
                key: 'status',
                label: __('analytics.dim_status'),
                groupBy: '1 - ('.AttendanceExpression::absentCase($table).')',
                labelExpression: '1 - ('.AttendanceExpression::absentCase($table).')',
                joins: ['attendance_reasons'],
                group: $what,
                labelUsing: static fn (int|string|null $key): string => (int) $key === 1
                    ? __('analytics.status_present')
                    : __('analytics.status_absent'),
            ),
            Dimension::lookup(
                key: 'recorded_by',
                label: __('analytics.dim_recorded_by'),
                keyColumn: $table.'.created_by',
                labelColumn: 'recorders.name',
                joins: ['recorders'],
                group: $what,
            ),
        ];
    }

    /**
     * @return list<Filter>
     */
    protected function attendanceFilters(string $table, AttendanceReasonType $reasonType): array
    {
        $when = __('analytics.group_time');
        $what = __('analytics.group_record');
        $date = $table.'.attendance_date';

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
                key: 'weekday',
                label: __('analytics.dim_weekday'),
                options: static fn (): array => DateExpression::weekdayNames(),
                apply: fn (Builder $query, string $value) => $query->whereRaw(
                    DateExpression::weekday($date).' = ?',
                    [(int) $value],
                ),
                group: $when,
                width: 'field--sm',
            ),
            Filter::select(
                key: 'status',
                label: __('analytics.dim_status'),
                options: static fn (): array => [
                    'present' => __('analytics.status_present'),
                    'absent' => __('analytics.status_absent'),
                ],
                apply: fn (Builder $query, string $value) => $value === 'present'
                    ? $query->where(fn (Builder $inner) => $inner
                        ->whereNull($table.'.attendance_reason_id')
                        ->orWhere('attendance_reasons.counts_as_absent', false))
                    : $query->whereNotNull($table.'.attendance_reason_id')
                        ->where('attendance_reasons.counts_as_absent', true),
                joins: ['attendance_reasons'],
                group: $what,
                width: 'field--sm',
            ),
            Filter::select(
                key: 'attendance_reason_id',
                label: __('analytics.dim_reason'),
                options: static fn (): array => AttendanceReason::query()->ofType($reasonType)->orderBy('reason_name')->pluck('reason_name', 'id')->all(),
                apply: fn (Builder $query, string $value) => $query->where($table.'.attendance_reason_id', $value),
                group: $what,
            ),
            Filter::select(
                key: 'has_remarks',
                label: __('analytics.filter_remarks'),
                options: static fn (): array => [
                    'yes' => __('analytics.filter_remarks_yes'),
                    'no' => __('analytics.filter_remarks_no'),
                ],
                apply: fn (Builder $query, string $value) => $value === 'yes'
                    ? $query->whereNotNull($table.'.remarks')->where($table.'.remarks', '!=', '')
                    : $query->where(fn (Builder $inner) => $inner
                        ->whereNull($table.'.remarks')
                        ->orWhere($table.'.remarks', '=', '')),
                group: $what,
                width: 'field--sm',
            ),
        ];
    }

    /**
     * The user who entered the record — worth asking about when attendance for
     * a whole class or jamaat looks wrong and the question is who filed it.
     *
     * @return list<Join>
     */
    protected function recorderJoin(string $table): array
    {
        return [
            Join::make('recorders', 'users', fn (JoinClause $join) => $join
                ->on($table.'.created_by', '=', 'recorders.id'), [], 'recorders'),
        ];
    }
}
