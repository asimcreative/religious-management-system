<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\RestrictsRoleDataAccess;
use Database\Factories\QuranAttendanceFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class QuranAttendance extends Model
{
    /** @use HasFactory<QuranAttendanceFactory> */
    use BelongsToCompany, HasFactory, RestrictsRoleDataAccess;

    protected $table = 'quran_attendance';

    /**
     * Mirrors the migration's column default so a freshly created instance
     * reads as held immediately, without needing a round-trip to the DB —
     * callers that create a row without passing class_held (i.e. every path
     * except the teacher-absence flow) still get correct isPresent() results
     * right away.
     */
    protected $attributes = [
        'class_held' => true,
    ];

    protected $fillable = [
        'company_id',
        'attendance_date',
        'class_id',
        'teacher_id',
        'employee_id',
        'attendance_reason_id',
        'class_held',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'class_held' => 'boolean',
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

    public function quranClass(): BelongsTo
    {
        return $this->belongsTo(QuranClass::class, 'class_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
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

    public function scopeForClass(Builder $query, int $classId): Builder
    {
        return $query->where('class_id', $classId);
    }

    public function scopeForEmployee(Builder $query, int $employeeId): Builder
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeHeld(Builder $query): Builder
    {
        return $query->where('class_held', true);
    }

    // ── Helpers ─────────────────────────────────────────────────────

    public function isClassHeld(): bool
    {
        return (bool) $this->class_held;
    }

    public function isPresent(): bool
    {
        return $this->class_held && $this->attendance_reason_id === null;
    }

    /**
     * Whether this record should count against the person. A reason existing
     * is not the same thing — an admin can configure a reason with
     * counts_as_absent = false precisely so it doesn't count as an absence.
     * No explicit class_held gate needed: when a teacher is marked absent
     * every student row's attendance_reason_id is forced null before
     * class_held = false is set (see QuranAttendanceService::saveAttendance),
     * so a not-held day already evaluates false here by construction.
     */
    public function isAbsent(): bool
    {
        return $this->attendance_reason_id !== null && ($this->attendanceReason?->counts_as_absent ?? false);
    }
}
