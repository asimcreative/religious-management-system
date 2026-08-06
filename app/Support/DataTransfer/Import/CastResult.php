<?php

namespace App\Support\DataTransfer\Import;

/**
 * The outcome of turning one spreadsheet cell into a model attribute.
 */
class CastResult
{
    private function __construct(
        public readonly mixed $value,
        public readonly ?string $error,
        public readonly ?string $fix,
    ) {}

    public static function ok(mixed $value): self
    {
        return new self($value, null, null);
    }

    public static function failed(string $error, ?string $fix = null): self
    {
        return new self(null, $error, $fix);
    }

    public function isValid(): bool
    {
        return $this->error === null;
    }
}
