<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Multi-tenant scope trait.
 *
 * Automatically scopes all queries to the authenticated user's company_id.
 * Every business model that has a company_id column MUST use this trait.
 *
 * Usage: use BelongsToCompany; in your model class.
 */
trait BelongsToCompany
{
    public static function bootBelongsToCompany(): void
    {
        static::addGlobalScope('company', function (Builder $builder) {
            $user = Auth::user();

            if ($user instanceof User && $user->getAttribute('company_id')) {
                $builder->where(
                    $builder->getModel()->getTable().'.company_id',
                    $user->getAttribute('company_id')
                );
            }
        });

        static::creating(function (Model $model) {
            $user = Auth::user();

            if ($user instanceof User && $user->getAttribute('company_id') && ! $model->getAttribute('company_id')) {
                $model->setAttribute('company_id', $user->getAttribute('company_id'));
            }
        });
    }

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where($this->getTable().'.company_id', $companyId);
    }
}
