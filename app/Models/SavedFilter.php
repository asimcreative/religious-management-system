<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Database\Factories\SavedFilterFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A named set of list filters belonging to one user.
 *
 * @property array<string, mixed> $query
 */
class SavedFilter extends Model
{
    /** @use HasFactory<SavedFilterFactory> */
    use BelongsToCompany, HasFactory;

    protected $fillable = [
        'company_id',
        'user_id',
        'resource_key',
        'name',
        'query',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'query' => 'array',
            'is_default' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForResource(Builder $query, string $resourceKey): Builder
    {
        return $query->where('resource_key', $resourceKey);
    }

    /** Saved filters are personal; nobody sees anybody else's. */
    public function scopeOwnedBy(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * The stored filters as a query string, for building the link.
     */
    public function toQueryString(): string
    {
        return http_build_query($this->query ?? []);
    }
}
