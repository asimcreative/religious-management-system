<?php

namespace App\Models\Concerns;

use App\Enums\Status;
use Illuminate\Database\Eloquent\Builder;

/**
 * Status scope trait.
 *
 * Provides common query scopes for models that have a status column.
 * Works with the Status enum (int-backed: 1=Active, 0=Inactive, 2=Suspended).
 */
trait HasStatus
{
    public function scopeActive(Builder $query): Builder
    {
        return $query->where($this->getTable().'.status', Status::Active);
    }

    public function scopeInactive(Builder $query): Builder
    {
        return $query->where($this->getTable().'.status', Status::Inactive);
    }

    public function scopeByStatus(Builder $query, Status $status): Builder
    {
        return $query->where($this->getTable().'.status', $status);
    }

    public function isActive(): bool
    {
        return $this->status === Status::Active;
    }

    public function isInactive(): bool
    {
        return $this->status === Status::Inactive;
    }

    public function isSuspended(): bool
    {
        return $this->status === Status::Suspended;
    }
}
