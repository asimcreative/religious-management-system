<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\RestrictsRoleDataAccess;
use Database\Factories\JamaatTaleemFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class JamaatTaleem extends Model
{
    /** @use HasFactory<JamaatTaleemFactory> */
    use BelongsToCompany, HasFactory, RestrictsRoleDataAccess;

    protected $table = 'jamaat_taleem';

    protected $fillable = [
        'company_id',
        'attendance_date',
        'jamaat_id',
        'leader_id',
        'held',
        'attendance_reason_id',
        'remarks',
        'created_by',
    ];

    protected $attributes = [
        'held' => true,
    ];

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'held' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (Auth::check() && ! $model->getAttribute('created_by')) {
                $model->setAttribute('created_by', Auth::id());
            }
        });
    }

    // ── Relationships ──────────────────────────────────────────────

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function jamaat(): BelongsTo
    {
        return $this->belongsTo(Jamaat::class);
    }

    public function leader(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'leader_id');
    }

    public function attendanceReason(): BelongsTo
    {
        return $this->belongsTo(AttendanceReason::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Scopes ─────────────────────────────────────────────────────

    public function scopeForDate(Builder $query, string $date): Builder
    {
        return $query->where('attendance_date', $date);
    }

    public function scopeForJamaat(Builder $query, int $jamaatId): Builder
    {
        return $query->where('jamaat_id', $jamaatId);
    }

    public function scopeHeld(Builder $query): Builder
    {
        return $query->where('held', true);
    }

    // ── Helpers ─────────────────────────────────────────────────────

    public function isHeld(): bool
    {
        return (bool) $this->held;
    }
}
