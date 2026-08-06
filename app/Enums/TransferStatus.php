<?php

namespace App\Enums;

/**
 * Lifecycle of one import or export run.
 *
 * A queued transfer moves Pending → Processing → a terminal state. A
 * synchronous one skips Pending. CompletedWithErrors is deliberately distinct
 * from Completed: an import that saved 900 of 1,000 rows succeeded, but the
 * operator still has 100 rows to deal with and must not be told "done".
 */
enum TransferStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case CompletedWithErrors = 'completed_with_errors';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return __('data_transfer.statuses.'.$this->value);
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Completed => 'bg-success',
            self::CompletedWithErrors => 'bg-warning text-dark',
            self::Processing => 'bg-info text-dark',
            self::Pending => 'bg-secondary',
            self::Failed => 'bg-danger',
            self::Cancelled => 'bg-secondary',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Completed => 'bi-check-circle',
            self::CompletedWithErrors => 'bi-exclamation-triangle',
            self::Processing => 'bi-arrow-repeat',
            self::Pending => 'bi-hourglass-split',
            self::Failed => 'bi-x-circle',
            self::Cancelled => 'bi-slash-circle',
        };
    }

    /** Whether the run has stopped and its counters will not change again. */
    public function isFinal(): bool
    {
        return in_array($this, [
            self::Completed,
            self::CompletedWithErrors,
            self::Failed,
            self::Cancelled,
        ], true);
    }

    /** Whether the UI should keep polling for updates. */
    public function isInProgress(): bool
    {
        return ! $this->isFinal();
    }
}
