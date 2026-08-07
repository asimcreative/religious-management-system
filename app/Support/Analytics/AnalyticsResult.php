<?php

namespace App\Support\Analytics;

/**
 * The answer to one question: the breakdown rows, and the totals line.
 *
 * The totals are computed the same way a row is — aggregates summed, derived
 * measures recalculated from those sums — so the bottom line is always the
 * whole set under the same formula, never the average of the rows above it.
 */
final class AnalyticsResult
{
    /**
     * @param  list<AnalyticsRow>  $rows
     * @param  array<string, float|int|null>  $totals  Keyed by measure key.
     * @param  list<Measure>  $measures
     */
    public function __construct(
        public readonly array $rows,
        public readonly array $totals,
        public readonly array $measures,
        public readonly Dimension $dimension,
        public readonly ?Dimension $secondary,
        public readonly bool $truncated,
    ) {}

    public function isEmpty(): bool
    {
        return $this->rows === [];
    }

    /**
     * The biggest primary-measure value in the set, for scaling the bars.
     *
     * Bars are drawn against the largest row rather than against the total,
     * because with twenty groups every bar against the total would be a sliver
     * and the comparison the chart exists for would be invisible.
     */
    public function peak(): float
    {
        $primary = $this->primaryMeasure();

        if ($primary === null) {
            return 0.0;
        }

        $values = array_map(
            static fn (AnalyticsRow $row): float => (float) ($row->measures[$primary->key] ?? 0),
            $this->rows,
        );

        return $values === [] ? 0.0 : max(max($values), 0.0);
    }

    public function primaryMeasure(): ?Measure
    {
        foreach ($this->measures as $measure) {
            if ($measure->primary) {
                return $measure;
            }
        }

        return $this->measures[0] ?? null;
    }
}
