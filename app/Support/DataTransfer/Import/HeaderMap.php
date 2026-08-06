<?php

namespace App\Support\DataTransfer\Import;

/**
 * Which spreadsheet column holds which field, plus what did not line up.
 */
class HeaderMap
{
    /**
     * @param  array<string, int>  $positions  Column key => zero-based sheet column.
     * @param  array<int, string>  $missingRequired  Labels of required columns absent from the file.
     * @param  array<int, string>  $unknown  Headings in the file that no column claims.
     */
    public function __construct(
        public readonly array $positions,
        public readonly array $missingRequired,
        public readonly array $unknown,
    ) {}

    public function isUsable(): bool
    {
        return $this->positions !== [] && $this->missingRequired === [];
    }

    public function has(string $columnKey): bool
    {
        return array_key_exists($columnKey, $this->positions);
    }

    /**
     * The raw cell for a column, or null when the file omits that column.
     *
     * @param  array<int, mixed>  $values
     */
    public function valueFor(string $columnKey, array $values): mixed
    {
        if (! $this->has($columnKey)) {
            return null;
        }

        return $values[$this->positions[$columnKey]] ?? null;
    }
}
