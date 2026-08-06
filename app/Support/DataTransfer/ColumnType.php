<?php

namespace App\Support\DataTransfer;

/**
 * The kinds of value a transferable column can hold.
 *
 * The type drives four things at once, which is the whole point of declaring
 * it once: baseline validation rules, how a raw spreadsheet cell is cast on
 * import, how the value is formatted on export, and what the sample sheet
 * tells the user to type.
 */
enum ColumnType: string
{
    case String = 'string';
    case Text = 'text';
    case Integer = 'integer';
    case Decimal = 'decimal';
    case Boolean = 'boolean';
    case Date = 'date';
    case DateTime = 'datetime';
    case Time = 'time';
    case Email = 'email';
    case Phone = 'phone';
    case Cnic = 'cnic';
    case Enum = 'enum';
    case Lookup = 'lookup';
    case Computed = 'computed';

    /**
     * Validation rules every column of this type gets for free.
     *
     * Definitions add their own on top via Column::rules(); they are merged,
     * never replaced, so a definition cannot accidentally drop a type guard.
     *
     * @return array<int, string>
     */
    public function baseRules(): array
    {
        return match ($this) {
            self::String, self::Phone => ['string', 'max:255'],
            self::Text => ['string', 'max:65535'],
            self::Integer => ['integer'],
            self::Decimal => ['numeric'],
            self::Boolean => ['boolean'],
            self::Date, self::DateTime => ['date'],
            self::Time => ['date_format:H:i'],
            self::Email => ['string', 'email', 'max:255'],
            self::Cnic => ['string', 'regex:/^\d{5}-\d{7}-\d{1}$/'],
            self::Lookup => ['integer'],
            self::Enum, self::Computed => [],
        };
    }

    /**
     * The format string PhpSpreadsheet applies to the exported cell.
     *
     * Returning null leaves the cell as General, which is correct for text.
     */
    public function numberFormat(): ?string
    {
        return match ($this) {
            self::Date => 'yyyy-mm-dd',
            self::DateTime => 'yyyy-mm-dd hh:mm',
            self::Time => 'hh:mm',
            self::Integer => '0',
            self::Decimal => '0.00',
            default => null,
        };
    }

    /**
     * Human wording for the sample sheet's Instructions page.
     */
    public function describe(): string
    {
        return __('data_transfer.types.'.$this->value);
    }

    /**
     * Whether a value of this type is stored as a number in the sheet.
     *
     * Used by the export writer to avoid coercing numerics to text, which
     * would break sorting and totals for whoever opens the file.
     */
    public function isNumeric(): bool
    {
        return in_array($this, [self::Integer, self::Decimal], true);
    }

    /**
     * Whether the value is a date/time and therefore needs Excel serial
     * handling rather than plain string parsing on import.
     */
    public function isTemporal(): bool
    {
        return in_array($this, [self::Date, self::DateTime, self::Time], true);
    }
}
