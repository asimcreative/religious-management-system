<?php

namespace App\Analytics\Definitions;

use App\Analytics\Concerns\DescribesAttendance;
use App\Models\Branch;
use App\Models\Jamaat;
use App\Models\JamaatTaleem;
use App\Support\Analytics\AbstractAnalyticsDefinition;
use App\Support\Analytics\DateExpression;
use App\Support\Analytics\Dimension;
use App\Support\Analytics\Filter;
use App\Support\Analytics\Join;
use App\Support\Analytics\Measure;
use App\Support\Analytics\MeasureFormat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;

/**
 * Days Taleem was (or was not) held, sliced by jamaat, location or time.
 *
 * A separate dataset from SalahAttendanceAnalytics rather than another
 * dimension on it: every row here unconditionally carries a real held
 * boolean (not a derived attendance_reason_id IS NULL check), and there is
 * no employee dimension — Taleem is recorded once per jamaat per day, not
 * once per member.
 */
class JamaatTaleemAnalytics extends AbstractAnalyticsDefinition
{
    use DescribesAttendance;

    private const TABLE = 'jamaat_taleem';

    public function key(): string
    {
        return 'jamaat-taleem';
    }

    public function label(): string
    {
        return __('analytics.jamaat_taleem');
    }

    public function description(): string
    {
        return __('analytics.jamaat_taleem_description');
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
        return JamaatTaleem::query();
    }

    public function defaultDimension(): string
    {
        return 'jamaat';
    }

    public function drillDown(): ?array
    {
        return [
            'route' => 'reports.jamaat-taleem',
            'filters' => [
                'jamaat_id' => 'jamaat_id',
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
                Join::make('jamaats', 'jamaats', fn (JoinClause $join) => $join
                    ->on(self::TABLE.'.jamaat_id', '=', 'jamaats.id')),

                Join::make(
                    'jamaat_branches',
                    'branches',
                    fn (JoinClause $join) => $join->on('jamaats.branch_id', '=', 'jamaat_branches.id'),
                    ['jamaats'],
                    'jamaat_branches'
                ),

                Join::make(
                    'leaders',
                    'employees',
                    fn (JoinClause $join) => $join->on(self::TABLE.'.leader_id', '=', 'leaders.id'),
                    [],
                    'leaders'
                ),
            ],
        );
    }

    protected function dimensionList(): array
    {
        $salah = __('analytics.group_salah');
        $when = __('analytics.group_time');
        $date = self::TABLE.'.attendance_date';

        return [
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
            Dimension::lookup(
                key: 'reason',
                label: __('analytics.dim_reason'),
                keyColumn: self::TABLE.'.attendance_reason_id',
                labelColumn: 'attendance_reasons.reason_name',
                joins: ['attendance_reasons'],
                drillFilter: 'attendance_reason_id',
                group: $salah,
                labelUsing: static fn (int|string|null $key, ?string $label): string => $key === null
                    ? __('analytics.taleem_held_no_reason')
                    : ($label ?? __('analytics.not_set')),
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
        $salah = __('analytics.group_salah');
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
        ];
    }

    /** @return list<Measure> */
    public function measures(): array
    {
        return [
            Measure::aggregate(
                key: 'records',
                label: __('analytics.measure_records'),
                expression: 'COUNT(*)',
            ),
            Measure::aggregate(
                key: 'held',
                label: __('analytics.measure_taleem_held'),
                expression: "SUM(CASE WHEN {$this->table()}.held = 1 THEN 1 ELSE 0 END)",
                primary: true,
            ),
            Measure::aggregate(
                key: 'not_held',
                label: __('analytics.measure_taleem_not_held'),
                expression: "SUM(CASE WHEN {$this->table()}.held = 0 THEN 1 ELSE 0 END)",
            ),
            Measure::derived(
                key: 'rate',
                label: __('analytics.measure_rate'),
                compute: static function (array $row): ?float {
                    $records = (float) ($row['records'] ?? 0);

                    return $records > 0.0
                        ? round(((float) ($row['held'] ?? 0) / $records) * 100, 1)
                        : null;
                },
                format: MeasureFormat::Percent,
            ),
        ];
    }
}
