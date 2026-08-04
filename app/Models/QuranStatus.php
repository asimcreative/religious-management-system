<?php

namespace App\Models;

use App\Enums\Status;
use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasAuditColumns;
use App\Models\Concerns\HasStatus;
use Database\Factories\QuranStatusFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuranStatus extends Model
{
    /** @use HasFactory<QuranStatusFactory> */
    use BelongsToCompany, HasAuditColumns, HasFactory, HasStatus, SoftDeletes;

    protected $fillable = [
        'company_id',
        'status_name',
        'description',
        'color',
        'icon',
        'display_order',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => Status::class,
            'display_order' => 'integer',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function quranProgress(): HasMany
    {
        return $this->hasMany(QuranProgress::class);
    }

    // ── Scopes ─────────────────────────────────────────────────────

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('display_order');
    }
}
