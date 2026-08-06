<?php

namespace App\Support\DataTransfer\Export;

/**
 * A rendered PDF plus the facts the export log needs about it.
 */
class PdfResult
{
    public function __construct(
        public readonly string $contents,
        public readonly int $recordCount,
        public readonly bool $wasTruncated,
    ) {}

    public function size(): int
    {
        return strlen($this->contents);
    }
}
