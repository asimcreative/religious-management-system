<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JamaatMember extends Model
{
    public $timestamps = false;

    protected $table = 'jamaat_members';

    protected $fillable = [
        'jamaat_id',
        'employee_id',
        'is_active',
        'joined_at',
        'left_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'joined_at' => 'date',
            'left_at' => 'date',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function jamaat(): BelongsTo
    {
        return $this->belongsTo(Jamaat::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    // ── Scopes ─────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('is_active', false);
    }
}
