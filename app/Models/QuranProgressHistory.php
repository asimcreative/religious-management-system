<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\RestrictsRoleDataAccess;
use Database\Factories\QuranProgressHistoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class QuranProgressHistory extends Model
{
    /** @use HasFactory<QuranProgressHistoryFactory> */
    use BelongsToCompany, HasFactory, RestrictsRoleDataAccess;

    const UPDATED_AT = null;

    protected $table = 'quran_progress_history';

    protected $fillable = [
        'company_id',
        'progress_id',
        'employee_id',
        'teacher_id',
        'quran_department_id',
        'quran_status_id',
        'lesson',
        'surah',
        'sipara',
        'page',
        'field_values',
        'percentage',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'sipara' => 'integer',
            'page' => 'integer',
            'field_values' => 'array',
            'percentage' => 'decimal:2',
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

    public function progress(): BelongsTo
    {
        return $this->belongsTo(QuranProgress::class, 'progress_id');
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
