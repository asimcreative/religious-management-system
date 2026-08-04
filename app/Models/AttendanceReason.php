<?php

namespace App\Models;

use App\Enums\Status;
use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasAuditColumns;
use App\Models\Concerns\HasStatus;
use Database\Factories\AttendanceReasonFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AttendanceReason extends Model
{
    /** @use HasFactory<AttendanceReasonFactory> */
    use BelongsToCompany, HasAuditColumns, HasFactory, HasStatus, SoftDeletes;

    protected $fillable = [
        'company_id',
        'reason_name',
        'color',
        'icon',
        'counts_as_absent',
        'counts_as_leave',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => Status::class,
            'counts_as_absent' => 'boolean',
            'counts_as_leave' => 'boolean',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function quranAttendances(): HasMany
    {
        return $this->hasMany(QuranAttendance::class);
    }

    public function salahAttendances(): HasMany
    {
        return $this->hasMany(SalahAttendance::class);
    }
}
