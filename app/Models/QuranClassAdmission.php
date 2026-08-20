<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasAuditColumns;
use Database\Factories\QuranClassAdmissionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuranClassAdmission extends Model
{
    /** @use HasFactory<QuranClassAdmissionFactory> */
    use BelongsToCompany, HasAuditColumns, HasFactory;

    protected $fillable = [
        'company_id',
        'quran_class_member_id',
        'current_reading_level',
        'previously_completed_quran',
        'admission_date',
        'classes_per_week',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'current_reading_level' => 'integer',
            'previously_completed_quran' => 'boolean',
            'admission_date' => 'date',
            'classes_per_week' => 'integer',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function quranClassMember(): BelongsTo
    {
        return $this->belongsTo(QuranClassMember::class);
    }
}
