<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\RestrictsRoleDataAccess;
use Database\Factories\QuranTeacherAttendanceFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class QuranTeacherAttendance extends Model
{
    /** @use HasFactory<QuranTeacherAttendanceFactory> */
    use BelongsToCompany, HasFactory, RestrictsRoleDataAccess;

    protected $table = 'quran_teacher_attendance';

    protected $fillable = [
        'company_id',
        'attendance_date',
        'class_id',
        'teacher_id',
        'attendance_reason_id',
        'remarks',
        'created_by',
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

    public function quranClass(): BelongsTo
    {
        return $this->belongsTo(QuranClass::class, 'class_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
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

    public function scopeForTeacher(Builder $query, int $teacherId): Builder
    {
        return $query->where('teacher_id', $teacherId);
    }
}
