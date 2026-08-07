<?php

namespace App\Support\Analytics;

/**
 * How a measure is written out, on screen and in an export.
 */
enum MeasureFormat: string
{
    case Integer = 'integer';
    case Decimal = 'decimal';
    case Percent = 'percent';

    public function display(float|int|null $value): string
    {
        if ($value === null) {
            return '—';
        }

        return match ($this) {
            self::Integer => number_format((float) $value),
            self::Decimal => number_format((float) $value, 1),
            self::Percent => number_format((float) $value, 1).'%',
        };
    }

    /**
     * The raw value for a spreadsheet cell — a number stays a number so the
     * reader can sort and total it themselves.
     */
    public function raw(float|int|null $value): float|int|null
    {
        if ($value === null) {
            return null;
        }

        return match ($this) {
            self::Integer => (int) $value,
            self::Decimal, self::Percent => round((float) $value, 1),
        };
    }
}
