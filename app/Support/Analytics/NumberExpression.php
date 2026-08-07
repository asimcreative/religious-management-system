<?php

namespace App\Support\Analytics;

use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Numeric grouping that means the same thing on MySQL and on SQLite.
 *
 * The two disagree here as thoroughly as they do about dates: SQLite has no
 * FLOOR, and MySQL's CAST does not accept INTEGER — it wants SIGNED. Writing
 * either one directly gives a report that works in production and fails under
 * test, or the other way round.
 */
final class NumberExpression
{
    /**
     * Bucket a value into bands of a fixed size — 0–9, 10–19, and so on.
     *
     * Returns the band index, not its bounds, so it can be grouped on and then
     * labelled in PHP. Values here are percentages and counts, never negative,
     * so truncation and flooring are the same thing.
     */
    public static function band(string $column, int $size): string
    {
        return match (self::driver()) {
            'mysql', 'mariadb' => "FLOOR({$column} / {$size})",
            'sqlite' => "CAST({$column} / {$size} AS INTEGER)",
            default => throw new RuntimeException(
                'Numeric banding is not implemented for the ['.self::driver().'] driver.'
            ),
        };
    }

    private static function driver(): string
    {
        return DB::connection()->getDriverName();
    }
}
