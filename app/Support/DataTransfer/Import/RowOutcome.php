<?php

namespace App\Support\DataTransfer\Import;

/**
 * What the validator decided about one row of the file.
 */
class RowOutcome
{
    public const VALID = 'valid';

    public const DUPLICATE = 'duplicate';

    public const INVALID = 'invalid';

    public const BLANK = 'blank';

    public const EXAMPLE = 'example';

    /**
     * @param  array<string, mixed>  $attributes  Cast, lookup-resolved values.
     * @param  array<int, mixed>  $raw  The original cells, for the error report.
     * @param  array<int, RowError>  $errors
     * @param  int|null  $existingId  The record this row duplicates, if any.
     */
    private function __construct(
        public readonly int $row,
        public readonly string $status,
        public readonly array $attributes,
        public readonly array $raw,
        public readonly array $errors,
        public readonly ?int $existingId,
    ) {}

    /** @param  array<string, mixed>  $attributes */
    public static function valid(int $row, array $attributes, array $raw): self
    {
        return new self($row, self::VALID, $attributes, $raw, [], null);
    }

    /** @param  array<string, mixed>  $attributes */
    public static function duplicate(int $row, array $attributes, array $raw, int $existingId): self
    {
        return new self($row, self::DUPLICATE, $attributes, $raw, [], $existingId);
    }

    /** @param  array<int, RowError>  $errors */
    public static function invalid(int $row, array $raw, array $errors): self
    {
        return new self($row, self::INVALID, [], $raw, $errors, null);
    }

    public static function blank(int $row): self
    {
        return new self($row, self::BLANK, [], [], [], null);
    }

    /** The untouched example row from the sample sheet, left in by mistake. */
    public static function example(int $row, array $raw): self
    {
        return new self($row, self::EXAMPLE, [], $raw, [], null);
    }

    public function isWritable(): bool
    {
        return $this->status === self::VALID || $this->status === self::DUPLICATE;
    }

    public function countsTowardTotal(): bool
    {
        return $this->status !== self::BLANK;
    }
}
