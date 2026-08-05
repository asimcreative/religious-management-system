<?php

namespace App\Exports\Concerns;

trait SanitizesSpreadsheetValues
{
    /** @param array<int, mixed> $values
     * @return array<int, mixed>
     */
    protected function sanitizeSpreadsheetValues(array $values): array
    {
        return array_map(function (mixed $value): mixed {
            if (is_string($value) && preg_match('/^[\s\x{FEFF}]*[=+@-]/u', $value) === 1) {
                return "'{$value}";
            }

            return $value;
        }, $values);
    }
}
