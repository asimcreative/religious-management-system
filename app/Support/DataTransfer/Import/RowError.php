<?php

namespace App\Support\DataTransfer\Import;

/**
 * One thing wrong with one row, stated so the user can act on it.
 *
 * The row number is the spreadsheet's own row number, not an index into the
 * data, so "Row 8" means the eighth row of the file the user is looking at.
 */
class RowError
{
    public function __construct(
        public readonly int $row,
        public readonly ?string $column,
        public readonly string $message,
        public readonly ?string $fix = null,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'row' => $this->row,
            'column' => $this->column,
            'message' => $this->message,
            'fix' => $this->fix,
        ];
    }

    /** @param  array<string, mixed>  $data */
    public static function fromArray(array $data): self
    {
        return new self(
            (int) ($data['row'] ?? 0),
            $data['column'] ?? null,
            (string) ($data['message'] ?? ''),
            $data['fix'] ?? null,
        );
    }
}
