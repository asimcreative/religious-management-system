<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Audit columns trait.
 *
 * Automatically sets created_by and updated_by columns
 * based on the authenticated user.
 *
 * For models with a deleted_by column, declare:
 *   protected bool $tracksDeletedBy = true;
 *
 * Requires created_by and updated_by nullable columns in the migration.
 */
trait HasAuditColumns
{
    public static function bootHasAuditColumns(): void
    {
        static::creating(function (Model $model) {
            if (Auth::check()) {
                if (! $model->getAttribute('created_by')) {
                    $model->setAttribute('created_by', Auth::id());
                }
                $model->setAttribute('updated_by', Auth::id());
            }
        });

        static::updating(function (Model $model) {
            if (Auth::check()) {
                $model->setAttribute('updated_by', Auth::id());
            }
        });

        static::deleting(function (Model $model) {
            if (
                Auth::check()
                && property_exists($model, 'tracksDeletedBy')
                && $model->tracksDeletedBy === true
                && method_exists($model, 'isForceDeleting')
                && ! $model->isForceDeleting()
            ) {
                $model->newModelQuery()
                    ->where($model->getKeyName(), $model->getKey())
                    ->update(['deleted_by' => Auth::id()]);
            }
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
