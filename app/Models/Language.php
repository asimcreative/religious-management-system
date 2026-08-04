<?php

namespace App\Models;

use App\Enums\Status;
use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasAuditColumns;
use App\Models\Concerns\HasStatus;
use Database\Factories\LanguageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Language extends Model
{
    /** @use HasFactory<LanguageFactory> */
    use BelongsToCompany, HasAuditColumns, HasFactory, HasStatus, SoftDeletes;

    protected $fillable = [
        'company_id',
        'language_name',
        'native_name',
        'locale',
        'direction',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => Status::class,
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
