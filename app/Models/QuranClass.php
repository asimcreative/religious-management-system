<?php

namespace App\Models;

use App\Enums\Status;
use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasAuditColumns;
use App\Models\Concerns\HasStatus;
use Database\Factories\QuranClassFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuranClass extends Model
{
    /** @use HasFactory<QuranClassFactory> */
    use BelongsToCompany, HasAuditColumns, HasFactory, HasStatus, SoftDeletes;

    protected bool $tracksDeletedBy = true;

    protected $table = 'quran_classes';

    protected $fillable = [
        'company_id',
        'branch_id',
        'teacher_id',
        'class_name',
        'class_code',
        'start_time',
        'end_time',
        'max_strength',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => Status::class,
            'max_strength' => 'integer',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'quran_class_members', 'class_id', 'employee_id')
            ->withPivot(['is_active', 'joined_at', 'left_at']);
    }

    public function activeMembers(): BelongsToMany
    {
        return $this->members()->wherePivot('is_active', true);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(QuranAttendance::class, 'class_id');
    }

    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    // ── Helpers ─────────────────────────────────────────────────────

    public function isFull(): bool
    {
        return $this->activeMembers()->count() >= $this->max_strength;
    }
}
