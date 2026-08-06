<?php

namespace App\Support\DataTransfer\Import;

use App\Support\DataTransfer\Column;
use App\Support\DataTransfer\ColumnType;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Throwable;

/**
 * Turns a raw spreadsheet cell into the value the model expects.
 *
 * The awkwardness this class absorbs is real: Excel hands back dates as
 * floating-point serial numbers, times as fractions of a day, and numbers with
 * whatever thousands separators the author's locale used. People also type
 * "yes", "Y" and "1" for the same boolean, and write an enum by its label
 * because that is what the template told them to do.
 *
 * Being generous here is what stops an import returning fifty errors that all
 * mean "you formatted that cell differently from how I expected".
 */
class ValueCaster
{
    /** @var array<int, string> */
    private const TRUTHY = ['1', 'true', 'yes', 'y', 't', 'active', 'on'];

    /** @var array<int, string> */
    private const FALSY = ['0', 'false', 'no', 'n', 'f', 'inactive', 'off'];

    public function cast(Column $column, mixed $raw): CastResult
    {
        $value = $this->normalizeBlank($raw);

        if ($value === null) {
            return CastResult::ok(null);
        }

        return match ($column->type) {
            ColumnType::Boolean => $this->castBoolean($column, $value),
            ColumnType::Integer => $this->castInteger($column, $value),
            ColumnType::Decimal => $this->castDecimal($column, $value),
            ColumnType::Date => $this->castDate($column, $value, 'Y-m-d'),
            ColumnType::DateTime => $this->castDate($column, $value, 'Y-m-d H:i:s'),
            ColumnType::Time => $this->castTime($column, $value),
            ColumnType::Enum => $this->castChoice($column, $value),
            ColumnType::Lookup => CastResult::ok(trim((string) $value)),
            ColumnType::Cnic => CastResult::ok($this->normalizeCnic((string) $value)),
            default => CastResult::ok(trim((string) $value)),
        };
    }

    /**
     * Treat whitespace-only cells as empty. Excel readily produces " " where
     * the author believes they left the cell blank.
     */
    private function normalizeBlank(mixed $raw): mixed
    {
        if ($raw === null) {
            return null;
        }

        if (is_string($raw)) {
            $trimmed = trim($raw);

            return $trimmed === '' ? null : $trimmed;
        }

        return $raw;
    }

    private function castBoolean(Column $column, mixed $value): CastResult
    {
        if (is_bool($value)) {
            return CastResult::ok($value);
        }

        $normalized = mb_strtolower(trim((string) $value));

        if (in_array($normalized, self::TRUTHY, true) || $normalized === mb_strtolower(__('data_transfer.yes'))) {
            return CastResult::ok(true);
        }

        if (in_array($normalized, self::FALSY, true) || $normalized === mb_strtolower(__('data_transfer.no'))) {
            return CastResult::ok(false);
        }

        return CastResult::failed(
            __('data_transfer.errors.boolean_invalid', ['column' => $column->getLabel()]),
            __('data_transfer.fixes.boolean_invalid'),
        );
    }

    private function castInteger(Column $column, mixed $value): CastResult
    {
        $clean = $this->stripNumericNoise($value);

        if (! is_numeric($clean)) {
            return CastResult::failed(
                __('data_transfer.errors.numeric_invalid', ['column' => $column->getLabel()]),
                __('data_transfer.fixes.generic'),
            );
        }

        return CastResult::ok((int) round((float) $clean));
    }

    private function castDecimal(Column $column, mixed $value): CastResult
    {
        $clean = $this->stripNumericNoise($value);

        if (! is_numeric($clean)) {
            return CastResult::failed(
                __('data_transfer.errors.numeric_invalid', ['column' => $column->getLabel()]),
                __('data_transfer.fixes.generic'),
            );
        }

        return CastResult::ok((float) $clean);
    }

    private function stripNumericNoise(mixed $value): string
    {
        return str_replace([',', ' ', "\u{00A0}"], '', trim((string) $value));
    }

    private function castDate(Column $column, mixed $value, string $format): CastResult
    {
        $date = $this->toCarbon($value);

        if ($date === null) {
            return CastResult::failed(
                __('data_transfer.errors.date_invalid', ['column' => $column->getLabel()]),
                __('data_transfer.fixes.date_invalid'),
            );
        }

        return CastResult::ok($date->format($format));
    }

    private function castTime(Column $column, mixed $value): CastResult
    {
        // Excel stores a bare time as a fraction of a 24-hour day.
        if (is_numeric($value) && (float) $value < 1) {
            $seconds = (int) round((float) $value * 86400);

            return CastResult::ok(sprintf('%02d:%02d', intdiv($seconds, 3600), intdiv($seconds % 3600, 60)));
        }

        $text = trim((string) $value);

        if (preg_match('/^(\d{1,2}):(\d{2})(?::\d{2})?$/', $text, $matches) === 1) {
            $hour = (int) $matches[1];
            $minute = (int) $matches[2];

            if ($hour <= 23 && $minute <= 59) {
                return CastResult::ok(sprintf('%02d:%02d', $hour, $minute));
            }
        }

        $date = $this->toCarbon($value);

        if ($date !== null) {
            return CastResult::ok($date->format('H:i'));
        }

        return CastResult::failed(
            __('data_transfer.errors.time_invalid', ['column' => $column->getLabel()]),
            __('data_transfer.fixes.time_invalid'),
        );
    }

    private function toCarbon(mixed $value): ?Carbon
    {
        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value);
        }

        // A numeric cell in a date column is an Excel serial number.
        if (is_numeric($value)) {
            try {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value));
            } catch (Throwable) {
                return null;
            }
        }

        try {
            return Carbon::parse(trim((string) $value));
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Accept either the label the template shows ("Active") or the value the
     * database stores (1) — an export fed back in must import cleanly.
     */
    private function castChoice(Column $column, mixed $value): CastResult
    {
        $map = $column->valueMap();
        $needle = mb_strtolower(trim((string) $value));

        foreach ($map as $stored => $label) {
            if (mb_strtolower((string) $label) === $needle || mb_strtolower((string) $stored) === $needle) {
                return CastResult::ok($stored);
            }
        }

        return CastResult::failed(
            __('data_transfer.errors.enum_invalid', [
                'column' => $column->getLabel(),
                'allowed' => implode(', ', $column->allowedValues()),
            ]),
            __('data_transfer.fixes.enum_invalid', ['allowed' => implode(', ', $column->allowedValues())]),
        );
    }

    /**
     * Accept 3520112345671 or 35201-1234567-1 and store the dashed form the
     * application's own validation rule expects.
     */
    private function normalizeCnic(string $value): string
    {
        $digits = preg_replace('/\D/', '', $value) ?? $value;

        if (strlen($digits) !== 13) {
            return trim($value);
        }

        return substr($digits, 0, 5).'-'.substr($digits, 5, 7).'-'.substr($digits, 12, 1);
    }
}
