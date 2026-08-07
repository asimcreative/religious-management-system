<?php

namespace App\Services;

use App\Support\Analytics\AbstractAnalyticsDefinition;
use App\Support\Analytics\AnalyticsResult;
use App\Support\Analytics\AnalyticsRow;
use App\Support\Analytics\Dimension;
use App\Support\Analytics\JoinPlan;
use App\Support\Analytics\Measure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Turns "break this down by X, narrowed to Y" into one grouped query.
 *
 * One query, whatever is asked. The breakdown and the filters each declare the
 * tables they need, JoinPlan joins those and only those, and the result comes
 * back already grouped — so a hundred thousand attendance rows become twenty
 * department rows in the database rather than in PHP.
 *
 * Everything that reaches SQL as raw text comes from a definition, never from
 * the request. The request only ever supplies a key that is looked up in the
 * definition's own list; an unrecognised one falls back rather than being
 * interpolated. That is what keeps a report builder from being an injection
 * surface.
 */
class AnalyticsService
{
    /**
     * Most rows a breakdown will return.
     *
     * Grouping by employee across a large tenant could otherwise put tens of
     * thousands of rows on one page. The cap is applied in SQL, and the result
     * records that it bit so the screen can say so rather than quietly showing
     * a partial answer as if it were the whole one.
     */
    public const MAX_ROWS = 500;

    /**
     * @param  array<string, string>  $filters  Raw request values, keyed by filter key.
     */
    public function run(
        AbstractAnalyticsDefinition $definition,
        array $filters,
        ?string $dimensionKey = null,
        ?string $secondaryKey = null,
    ): AnalyticsResult {
        $dimension = $definition->dimension($dimensionKey);

        // A secondary breakdown is optional, and never the same as the primary
        // — grouping by department within department is a longer way of saying
        // department, and the extra column would only be noise.
        $secondary = $definition->hasDimension($secondaryKey) && $secondaryKey !== $dimension->key
            ? $definition->dimensions()[$secondaryKey]
            : null;

        $active = $this->activeFilters($definition, $filters);

        $query = $definition->query();
        $plan = new JoinPlan($definition->joins());

        $plan->need($dimension->joins);

        if ($secondary !== null) {
            $plan->need($secondary->joins);
        }

        foreach ($active as $key => $value) {
            $plan->need($definition->filters()[$key]->joins);
        }

        $plan->applyTo($query);

        foreach ($active as $key => $value) {
            ($definition->filters()[$key]->apply)($query, $value);
        }

        $measures = $definition->measures();

        $this->select($query, $dimension, $secondary, $measures);
        $this->group($query, $dimension, $secondary);
        $this->order($query, $dimension, $secondary, $measures);

        // One extra row is fetched so a full page can be told apart from a
        // page that happens to be exactly at the cap.
        $records = $query->limit(self::MAX_ROWS + 1)->get();
        $truncated = $records->count() > self::MAX_ROWS;
        $records = $records->take(self::MAX_ROWS);

        $rows = [];
        $totals = [];

        foreach ($records as $record) {
            $values = [];

            foreach ($measures as $measure) {
                if ($measure->isDerived()) {
                    continue;
                }

                $value = $record->getAttribute($measure->key);
                $values[$measure->key] = $value === null ? null : (float) $value;
                $totals[$measure->key] = ($totals[$measure->key] ?? 0) + (float) $value;
            }

            foreach ($measures as $measure) {
                if ($measure->isDerived()) {
                    $values[$measure->key] = $measure->valueFor($values);
                }
            }

            $key = $record->getAttribute('dimension_key');

            $rows[] = new AnalyticsRow(
                key: $key,
                label: $dimension->displayLabel($key, $this->stringOrNull($record->getAttribute('dimension_label'))),
                measures: $values,
                secondaryKey: $secondary === null ? null : $record->getAttribute('secondary_key'),
                secondaryLabel: $secondary?->displayLabel(
                    $record->getAttribute('secondary_key'),
                    $this->stringOrNull($record->getAttribute('secondary_label')),
                ),
            );
        }

        return new AnalyticsResult(
            rows: $rows,
            totals: $this->totals($measures, $totals, $records->count()),
            measures: $measures,
            dimension: $dimension,
            secondary: $secondary,
            truncated: $truncated,
        );
    }

    /**
     * The filters the reader actually set, in the definition's own order.
     *
     * Anything the definition does not declare is dropped rather than passed
     * along, so a hand-edited URL cannot reach a condition that was never
     * offered.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, string>
     */
    public function activeFilters(AbstractAnalyticsDefinition $definition, array $filters): array
    {
        $active = [];

        foreach ($definition->filters() as $key => $filter) {
            $value = $filters[$key] ?? null;

            if ($value === null || $value === '' || is_array($value)) {
                continue;
            }

            $active[$key] = (string) $value;
        }

        return $active;
    }

    /**
     * @param  list<Measure>  $measures
     */
    private function select(Builder $query, Dimension $dimension, ?Dimension $secondary, array $measures): void
    {
        $select = [
            DB::raw($dimension->groupBy.' as dimension_key'),
            DB::raw($dimension->labelExpression.' as dimension_label'),
        ];

        if ($secondary !== null) {
            $select[] = DB::raw($secondary->groupBy.' as secondary_key');
            $select[] = DB::raw($secondary->labelExpression.' as secondary_label');
        }

        foreach ($measures as $measure) {
            if ($measure->expression !== null) {
                $select[] = DB::raw($measure->expression.' as '.$measure->key);
            }
        }

        $query->select($select);
    }

    private function group(Builder $query, Dimension $dimension, ?Dimension $secondary): void
    {
        // Both the key and the label are grouped on. Under MySQL's
        // ONLY_FULL_GROUP_BY a selected column that is neither grouped nor
        // aggregated is an error, and the label is exactly that.
        $groups = [$dimension->groupBy, $dimension->labelExpression];

        if ($secondary !== null) {
            $groups[] = $secondary->groupBy;
            $groups[] = $secondary->labelExpression;
        }

        $query->groupByRaw(implode(', ', array_unique($groups)));
    }

    /**
     * @param  list<Measure>  $measures
     */
    private function order(Builder $query, Dimension $dimension, ?Dimension $secondary, array $measures): void
    {
        // A dimension with a natural order keeps it — prayers read Fajr to
        // Isha, months read forwards. Everything else sorts by the number the
        // reader came for, biggest first.
        if ($dimension->orderBy !== null) {
            $query->orderByRaw($dimension->orderBy);

            if ($secondary?->orderBy !== null) {
                $query->orderByRaw($secondary->orderBy);
            }

            return;
        }

        $primary = $this->primary($measures);

        if ($primary?->expression !== null) {
            $query->orderByRaw($primary->expression.' DESC');
        }
    }

    /**
     * @param  list<Measure>  $measures
     * @param  array<string, float>  $sums
     * @return array<string, float|int|null>
     */
    private function totals(array $measures, array $sums, int $rowCount): array
    {
        $totals = [];

        foreach ($measures as $measure) {
            if ($measure->isDerived()) {
                continue;
            }

            // An average cannot be summed. Averaging the group averages is
            // still not the true mean — it weights a group of three the same
            // as a group of three hundred — but it is the honest best that the
            // grouped rows allow, and it is the figure the rows themselves add
            // up to on screen.
            $totals[$measure->key] = str_starts_with(strtoupper((string) $measure->expression), 'AVG')
                ? ($rowCount > 0 ? ($sums[$measure->key] ?? 0) / $rowCount : null)
                : ($sums[$measure->key] ?? 0);
        }

        foreach ($measures as $measure) {
            if ($measure->isDerived()) {
                $totals[$measure->key] = $measure->valueFor($totals);
            }
        }

        return $totals;
    }

    /**
     * @param  list<Measure>  $measures
     */
    private function primary(array $measures): ?Measure
    {
        foreach ($measures as $measure) {
            if ($measure->primary) {
                return $measure;
            }
        }

        return $measures[0] ?? null;
    }

    private function stringOrNull(mixed $value): ?string
    {
        return $value === null ? null : (string) $value;
    }
}
