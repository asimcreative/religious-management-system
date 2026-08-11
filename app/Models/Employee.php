<?php

namespace App\Models;

use App\Enums\Status;
use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasAuditColumns;
use App\Models\Concerns\HasEncryptedCnic;
use App\Models\Concerns\RestrictsRoleDataAccess;
use Database\Factories\EmployeeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    /** @use HasFactory<EmployeeFactory> */
    use BelongsToCompany, HasAuditColumns, HasEncryptedCnic, HasFactory, RestrictsRoleDataAccess, SoftDeletes;

    protected bool $tracksDeletedBy = true;

    protected $fillable = [
        'company_id',
        'user_id',
        'employee_code',
        'employee_name',
        'cnic',
        'mobile',
        'email',
        'dob',
        'gender',
        'photo',
        'branch_id',
        'department_id',
        'designation_id',
        'employment_status',
        'quran_department_id',
        'quran_status_id',
        'notes',
    ];

    protected $hidden = [
        'cnic',
        'cnic_hash',
    ];

    protected function casts(): array
    {
        return [
            'employment_status' => Status::class,
            'dob' => 'date',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class);
    }

    public function quranDepartment(): BelongsTo
    {
        return $this->belongsTo(QuranDepartment::class);
    }

    public function quranStatusRelation(): BelongsTo
    {
        return $this->belongsTo(QuranStatus::class, 'quran_status_id');
    }

    public function teacher(): HasOne
    {
        return $this->hasOne(Teacher::class);
    }

    public function quranClasses(): BelongsToMany
    {
        return $this->belongsToMany(QuranClass::class, 'quran_class_members', 'employee_id', 'class_id')
            ->withPivot(['is_active', 'joined_at', 'left_at']);
    }

    public function jamaats(): BelongsToMany
    {
        return $this->belongsToMany(Jamaat::class, 'jamaat_members')
            ->withPivot(['is_active', 'joined_at', 'left_at']);
    }

    public function quranProgress(): HasOne
    {
        return $this->hasOne(QuranProgress::class);
    }

    public function quranProgressHistory(): HasMany
    {
        return $this->hasMany(QuranProgressHistory::class);
    }

    public function quranAttendances(): HasMany
    {
        return $this->hasMany(QuranAttendance::class);
    }

    public function salahAttendances(): HasMany
    {
        return $this->hasMany(SalahAttendance::class);
    }

    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    // ── Scopes (employment_status column, not 'status') ────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('employees.employment_status', Status::Active);
    }

    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('employees.employment_status', Status::Inactive);
    }

    public function scopeByEmploymentStatus(Builder $query, Status $status): Builder
    {
        return $query->where('employees.employment_status', $status);
    }

    /**
     * Employees free to join a jamaat.
     *
     * One employee belongs to at most one active jamaat, so anyone who has one
     * is unavailable to every jamaat — including, by the same token, the one
     * they are already in.
     *
     * @param  Builder<Employee>  $query
     * @return Builder<Employee>
     */
    public function scopeWithoutActiveJamaat(Builder $query): Builder
    {
        return $query->whereDoesntHave('activeJamaat');
    }

    /**
     * Employees free to join a Quran class. Counterpart of the jamaat rule.
     *
     * @param  Builder<Employee>  $query
     * @return Builder<Employee>
     */
    public function scopeWithoutActiveQuranClass(Builder $query): Builder
    {
        return $query->whereDoesntHave('activeQuranClass');
    }

    public function isActive(): bool
    {
        return $this->employment_status === Status::Active;
    }

    public function isInactive(): bool
    {
        return $this->employment_status === Status::Inactive;
    }

    // ── Active Membership Helpers ──────────────────────────────────

    public function activeQuranClass(): BelongsToMany
    {
        return $this->quranClasses()->wherePivot('is_active', true);
    }

    public function activeJamaat(): BelongsToMany
    {
        return $this->jamaats()->wherePivot('is_active', true);
    }
}
