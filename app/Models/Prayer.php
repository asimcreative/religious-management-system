<?php

namespace App\Models;

use App\Enums\Status;
use App\Models\Concerns\HasStatus;
use Database\Factories\PrayerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Prayer extends Model
{
    /** @use HasFactory<PrayerFactory> */
    use HasFactory, HasStatus;

    protected $fillable = [
        'prayer_name',
        'prayer_name_ur',
        'prayer_order',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => Status::class,
            'prayer_order' => 'integer',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function salahAttendances(): HasMany
    {
        return $this->hasMany(SalahAttendance::class);
    }
}
