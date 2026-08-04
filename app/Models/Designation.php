<?php

namespace App\Models;

use App\Enums\Status;
use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasAuditColumns;
use App\Models\Concerns\HasStatus;
use Database\Factories\DesignationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Designation extends Model
{
    /** @use HasFactory<DesignationFactory> */
    use BelongsToCompany, HasAuditColumns, HasFactory, HasStatus, SoftDeletes;

    protected bool $tracksDeletedBy = true;

    protected $fillable = [
        'company_id',
        'designation_name',
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

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
