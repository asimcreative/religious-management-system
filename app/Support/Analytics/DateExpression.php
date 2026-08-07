<?php

namespace App\Support\Analytics;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Date grouping that means the same thing on MySQL and on SQLite.
 *
 * Production runs MySQL and the test suite runs SQLite, and the two disagree
 * about almost every date function there is — DATE_FORMAT against strftime,
 * DAYOFWEEK counting from Sunday as 1 against %w counting it as 0. A report
 * that grouped by month would silently return one row per record under test and
 * one row per month in production, so the tests would prove nothing.
 *
 * Every expression here is written twice and normalised to the same shape, so
 * "by month" is '2026-08' on both, and "by weekday" is 1 for Sunday on both.
 */
final class DateExpression
{
    /** One row per calendar day. */
    public static function day(string $column): string
    {
        // DATE() is the one function the two engines agree on outright.
        return "DATE({$column})";
    }

    /** One row per week, labelled with the year so weeks never collide. */
    public static function week(string $column): string
    {
        return match (self::driver()) {
            'mysql', 'mariadb' => "DATE_FORMAT({$column}, '%x-W%v')",
            'sqlite' => "strftime('%Y-W%W', {$column})",
            default => throw self::unsupported(),
        };
    }

    /** One row per month, as 2026-08. */
    public static function month(string $column): string
    {
        return match (self::driver()) {
            'mysql', 'mariadb' => "DATE_FORMAT({$column}, '%Y-%m')",
            'sqlite' => "strftime('%Y-%m', {$column})",
            default => throw self::unsupported(),
        };
    }

    /** One row per year. */
    public static function year(string $column): string
    {
        return match (self::driver()) {
            'mysql', 'mariadb' => "DATE_FORMAT({$column}, '%Y')",
            'sqlite' => "strftime('%Y', {$column})",
            default => throw self::unsupported(),
        };
    }

    /**
     * Day of the week as 1..7 with Sunday as 1.
     *
     * MySQL's DAYOFWEEK already numbers it that way; SQLite counts Sunday as 0,
     * so it is shifted to match rather than leaving each engine to its own
     * numbering and hoping the labels line up.
     */
    public static function weekday(string $column): string
    {
        return match (self::driver()) {
            'mysql', 'mariadb' => "DAYOFWEEK({$column})",
            'sqlite' => "(CAST(strftime('%w', {$column}) AS INTEGER) + 1)",
            default => throw self::unsupported(),
        };
    }

    /**
     * The names behind weekday(), in its numbering.
     *
     * @return array<int, string>
     */
    public static function weekdayNames(): array
    {
        // Anchored to a known Sunday so the names follow the application locale
        // rather than being hard-coded in English.
        $sunday = CarbonImmutable::create(2026, 1, 4);
        $names = [];

        for ($offset = 0; $offset < 7; $offset++) {
            $names[$offset + 1] = $sunday->addDays($offset)->translatedFormat('l');
        }

        return $names;
    }

    private static function driver(): string
    {
        return DB::connection()->getDriverName();
    }

    private static function unsupported(): RuntimeException
    {
        return new RuntimeException(
            'Date grouping is not implemented for the ['.self::driver().'] driver.'
        );
    }
}
