<?php

namespace App\Support\DataTransfer;

/**
 * What to do with an imported row whose unique key already exists
 * in this company's data.
 */
enum DuplicateStrategy: string
{
    /** Leave the stored record alone and report the row as skipped. */
    case Skip = 'skip';

    /** Overwrite the stored record with the sheet's values. */
    case Update = 'update';

    /** Treat the duplicate as an error and put the row in the error file. */
    case Fail = 'fail';

    public function label(): string
    {
        return __('data_transfer.duplicates.'.$this->value);
    }

    public function description(): string
    {
        return __('data_transfer.duplicates.'.$this->value.'_help');
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}
