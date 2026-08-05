<?php

namespace App\Models;

use App\Enums\Status;
use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasAuditColumns;
use App\Models\Concerns\HasStatus;
use App\Models\Concerns\RestrictsRoleDataAccess;
use Database\Factories\TeacherFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Teacher extends Model
{
    /** @use HasFactory<TeacherFactory> */
    use BelongsToCompany, HasAuditColumns, HasFactory, HasStatus, RestrictsRoleDataAccess, SoftDeletes;

    protected bool $tracksDeletedBy = true;

    protected $fillable = [
        'company_id',
        'employee_id',
        'teacher_code',
        'status',
        'notes',
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

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'teacher_branch');
    }

    public function quranClasses(): HasMany
    {
        return $this->hasMany(QuranClass::class);
    }

    public function quranAttendances(): HasMany
    {
        return $this->hasMany(QuranAttendance::class);
    }

    public function quranProgress(): HasMany
    {
        return $this->hasMany(QuranProgress::class);
    }

    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    // ── Helpers ─────────────────────────────────────────────────────

    /**
     * Get the employee name from the linked employee record.
     */
    public function getEmployeeName(): string
    {
        $employee = $this->employee;

        if ($employee instanceof Employee) {
            return $employee->employee_name;
        }

        return $this->teacher_code;
    }
}
