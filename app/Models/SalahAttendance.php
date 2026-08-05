<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\RestrictsRoleDataAccess;
use Database\Factories\SalahAttendanceFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class SalahAttendance extends Model
{
    /** @use HasFactory<SalahAttendanceFactory> */
    use BelongsToCompany, HasFactory, RestrictsRoleDataAccess;

    protected $table = 'salah_attendance';

    protected $fillable = [
        'company_id',
        'attendance_date',
        'prayer_id',
        'jamaat_id',
        'leader_id',
        'employee_id',
        'attendance_reason_id',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
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

    public function prayer(): BelongsTo
    {
        return $this->belongsTo(Prayer::class);
    }

    public function jamaat(): BelongsTo
    {
        return $this->belongsTo(Jamaat::class);
    }

    public function leader(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'leader_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
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

    public function scopeForPrayer(Builder $query, int $prayerId): Builder
    {
        return $query->where('prayer_id', $prayerId);
    }

    public function scopeForJamaat(Builder $query, int $jamaatId): Builder
    {
        return $query->where('jamaat_id', $jamaatId);
    }

    public function scopeForEmployee(Builder $query, int $employeeId): Builder
    {
        return $query->where('employee_id', $employeeId);
    }

    // ── Helpers ─────────────────────────────────────────────────────

    public function isPresent(): bool
    {
        return $this->attendance_reason_id === null;
    }
}
