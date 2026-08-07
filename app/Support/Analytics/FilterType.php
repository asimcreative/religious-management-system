<?php

namespace App\Support\Analytics;

/**
 * How a filter is drawn, and how its value is read back.
 */
enum FilterType: string
{
    case Select = 'select';
    case Date = 'date';
    case Text = 'text';
    case Number = 'number';

    /** The HTML input type, for the filters that render as one. */
    public function inputType(): string
    {
        return match ($this) {
            self::Date => 'date',
            self::Number => 'number',
            self::Text => 'search',
            self::Select => 'select',
        };
    }

    public function isSelect(): bool
    {
        return $this === self::Select;
    }
}
