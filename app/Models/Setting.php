<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Database\Factories\SettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Setting extends Model
{
    /** @use HasFactory<SettingFactory> */
    use BelongsToCompany, HasFactory;

    protected $fillable = [
        'company_id',
        'key',
        'value',
    ];

    // ── Relationships ──────────────────────────────────────────────

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
