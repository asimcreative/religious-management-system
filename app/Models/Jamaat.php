<?php

namespace App\Models;

use App\Enums\Status;
use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasAuditColumns;
use App\Models\Concerns\HasStatus;
use App\Models\Concerns\RestrictsRoleDataAccess;
use Database\Factories\JamaatFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Jamaat extends Model
{
    /** @use HasFactory<JamaatFactory> */
    use BelongsToCompany, HasAuditColumns, HasFactory, HasStatus, RestrictsRoleDataAccess, SoftDeletes;

    protected bool $tracksDeletedBy = true;

    protected $table = 'jamaats';

    protected $fillable = [
        'company_id',
        'branch_id',
        'jamaat_number',
        'jamaat_name',
        'leader_id',
        'vice_leader_id',
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

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function leader(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'leader_id');
    }

    public function viceLeader(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'vice_leader_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'jamaat_members')
            ->withPivot(['is_active', 'joined_at', 'left_at']);
    }

    public function activeMembers(): BelongsToMany
    {
        return $this->members()->wherePivot('is_active', true);
    }

    public function salahAttendances(): HasMany
    {
        return $this->hasMany(SalahAttendance::class);
    }

    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
