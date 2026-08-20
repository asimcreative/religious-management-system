<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\RestrictsRoleDataAccess;
use Database\Factories\QuranProgressFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class QuranProgress extends Model
{
    /** @use HasFactory<QuranProgressFactory> */
    use BelongsToCompany, HasFactory, RestrictsRoleDataAccess;

    protected $table = 'quran_progress';

    protected $fillable = [
        'company_id',
        'employee_id',
        'teacher_id',
        'quran_department_id',
        'quran_status_id',
        'current_lesson',
        'current_surah',
        'current_sipara',
        'current_page',
        'field_values',
        'completion_percentage',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'current_sipara' => 'integer',
            'current_page' => 'integer',
            'field_values' => 'array',
            'completion_percentage' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (Auth::check()) {
                $model->setAttribute('updated_by', Auth::id());
            }
        });

        static::updating(function (self $model) {
            if (Auth::check()) {
                $model->setAttribute('updated_by', Auth::id());
            }
        });
    }

    // ── Relationships ──────────────────────────────────────────────

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function quranDepartment(): BelongsTo
    {
        return $this->belongsTo(QuranDepartment::class);
    }

    public function quranStatus(): BelongsTo
    {
        return $this->belongsTo(QuranStatus::class);
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function history(): HasMany
    {
        return $this->hasMany(QuranProgressHistory::class, 'progress_id');
    }

    // ── Helpers ─────────────────────────────────────────────────────

    public function isCompleted(): bool
    {
        return (float) $this->completion_percentage >= 100.00;
    }
}
