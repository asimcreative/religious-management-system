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

    // ── Helpers ─────────────────────────────────────────────────────

    /**
     * Why an employee cannot lead or deputise for a jamaat other than one
     * they are already committed to: an active membership, or a leader/vice
     * leader seat, held elsewhere.
     *
     * Excludes by id ($exceptJamaatId, what the web form knows when editing)
     * or by jamaat_number ($exceptJamaatNumber, the natural key a CSV import
     * row carries for itself) — a jamaat's own existing leader must never be
     * flagged as conflicting with itself, whichever way the caller knows
     * "itself".
     *
     * @return array{jamaat: string, role: string}|null
     */
    public static function leadershipConflictFor(
        int $employeeId,
        ?int $exceptJamaatId = null,
        ?string $exceptJamaatNumber = null,
    ): ?array {
        $memberElsewhere = JamaatMember::query()
            ->with('jamaat')
            ->where('employee_id', $employeeId)
            ->where('is_active', true)
            ->when($exceptJamaatId !== null, fn ($q) => $q->where('jamaat_id', '!=', $exceptJamaatId))
            ->when($exceptJamaatNumber !== null, fn ($q) => $q->whereHas(
                'jamaat',
                fn ($jq) => $jq->where('jamaat_number', '!=', $exceptJamaatNumber)
            ))
            ->first();

        if ($memberElsewhere !== null) {
            return ['jamaat' => $memberElsewhere->jamaat?->jamaat_name ?? '', 'role' => 'member'];
        }

        $leadsElsewhere = static::query()
            ->when($exceptJamaatId !== null, fn ($q) => $q->where('id', '!=', $exceptJamaatId))
            ->when($exceptJamaatNumber !== null, fn ($q) => $q->where('jamaat_number', '!=', $exceptJamaatNumber))
            ->where(fn ($q) => $q->where('leader_id', $employeeId)->orWhere('vice_leader_id', $employeeId))
            ->first();

        if ($leadsElsewhere !== null) {
            return [
                'jamaat' => $leadsElsewhere->jamaat_name,
                'role' => (int) $leadsElsewhere->leader_id === $employeeId ? 'leader' : 'vice_leader',
            ];
        }

        return null;
    }
}
