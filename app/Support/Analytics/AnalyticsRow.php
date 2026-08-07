<?php

namespace App\Support\Analytics;

/**
 * One line of a breakdown.
 *
 * `key` is what the group actually was — a branch id, a date, an enum value —
 * and is what a drill-down link carries. `label` is only what the reader sees.
 * Two departments with the same name stay two rows because they are keyed
 * apart, and clicking either one narrows to the right records.
 */
final class AnalyticsRow
{
    /**
     * @param  array<string, float|int|null>  $measures  Keyed by measure key.
     */
    public function __construct(
        public readonly int|string|null $key,
        public readonly string $label,
        public readonly array $measures,
        public readonly int|string|null $secondaryKey = null,
        public readonly ?string $secondaryLabel = null,
    ) {}
}
