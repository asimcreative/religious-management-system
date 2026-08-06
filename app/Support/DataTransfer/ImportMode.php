<?php

namespace App\Support\DataTransfer;

/**
 * What to do when a file contains some good rows and some bad ones.
 *
 * The whole file is always validated before anything is written, so neither
 * mode can surprise the user with a half-finished import. The choice is only
 * about what happens to the good rows when bad ones exist.
 *
 * SkipInvalid is the default because docs/38_BUSINESS_RULES_MASTER.md states
 * "Import should continue for valid rows". Atomic is offered for callers who
 * need all-or-nothing.
 */
enum ImportMode: string
{
    /** Write every valid row; report the rest in the error file. */
    case SkipInvalid = 'skip_invalid';

    /** Write nothing unless every single row is valid. */
    case Atomic = 'atomic';

    public function label(): string
    {
        return __('data_transfer.modes.'.$this->value);
    }

    public function description(): string
    {
        return __('data_transfer.modes.'.$this->value.'_help');
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}
