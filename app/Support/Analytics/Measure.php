<?php

namespace App\Support\Analytics;

use Closure;

/**
 * One number in a breakdown row.
 *
 * Two kinds, and the distinction is the whole point:
 *
 * - **aggregate** — computed by SQL (COUNT, SUM, AVG). Adds up across rows.
 * - **derived** — computed in PHP from the aggregates of the same row.
 *
 * An attendance rate is derived, never aggregated. Averaging the per-department
 * rates would weight a department of three people the same as one of three
 * hundred and quietly report a figure that is true of nothing. Deriving it from
 * the underlying counts gives the same formula for a row and for the totals
 * line, so the two can never disagree.
 */
final class Measure
{
    /**
     * @param  string|null  $expression  SQL aggregate, for aggregate measures.
     * @param  (Closure(array<string, float|int|null>): (float|int|null))|null  $compute
     *                                                                                    Row maths, for derived measures.
     */
    private function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly ?string $expression,
        public readonly ?Closure $compute,
        public readonly MeasureFormat $format,
        public readonly bool $primary,
    ) {}

    public static function aggregate(
        string $key,
        string $label,
        string $expression,
        MeasureFormat $format = MeasureFormat::Integer,
        bool $primary = false,
    ): self {
        return new self($key, $label, $expression, null, $format, $primary);
    }

    /**
     * @param  Closure(array<string, float|int|null>): (float|int|null)  $compute
     */
    public static function derived(
        string $key,
        string $label,
        Closure $compute,
        MeasureFormat $format = MeasureFormat::Percent,
        bool $primary = false,
    ): self {
        return new self($key, $label, null, $compute, $format, $primary);
    }

    public function isDerived(): bool
    {
        return $this->compute !== null;
    }

    /**
     * @param  array<string, float|int|null>  $row
     */
    public function valueFor(array $row): float|int|null
    {
        return $this->compute === null
            ? ($row[$this->key] ?? null)
            : ($this->compute)($row);
    }
}
