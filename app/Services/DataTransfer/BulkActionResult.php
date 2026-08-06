<?php

namespace App\Services\DataTransfer;

/**
 * What a bulk action actually did.
 *
 * Refusals are counted separately from successes because "12 of 15 deleted"
 * and "12 deleted" are different answers, and the user needs the first one.
 */
class BulkActionResult
{
    /** @param  array<int, string>  $reasons */
    public function __construct(
        public readonly int $affected = 0,
        public readonly int $forbidden = 0,
        public readonly int $blocked = 0,
        public readonly array $reasons = [],
    ) {}

    public function requested(): int
    {
        return $this->affected + $this->forbidden + $this->blocked;
    }

    public function isCompleteSuccess(): bool
    {
        return $this->affected > 0 && $this->forbidden === 0 && $this->blocked === 0;
    }

    public function didNothing(): bool
    {
        return $this->affected === 0;
    }

    /**
     * A single sentence describing the outcome, for the flash message.
     */
    public function message(string $action): string
    {
        if ($this->didNothing()) {
            return __('data_transfer.bulk.none', ['action' => $action]);
        }

        if ($this->isCompleteSuccess()) {
            return __('data_transfer.bulk.done', ['count' => $this->affected, 'action' => $action]);
        }

        return __('data_transfer.bulk.partial', [
            'count' => $this->affected,
            'total' => $this->requested(),
            'action' => $action,
            'skipped' => $this->forbidden + $this->blocked,
        ]);
    }
}
