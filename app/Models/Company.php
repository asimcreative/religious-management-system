<?php

namespace App\Models;

use App\Enums\Status;
use App\Models\Concerns\HasStatus;
use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    /** @use HasFactory<CompanyFactory> */
    use HasFactory, HasStatus, SoftDeletes;

    /**
     * The code of the company that administers the platform rather than using it.
     *
     * Its users are not tenant users: they manage the register of companies and
     * hold no business data of their own.
     */
    public const PLATFORM_CODE = 'SYSTEM';

    protected $fillable = [
        'company_code',
        'company_name',
        'logo',
        'email',
        'phone',
        'address',
        'city',
        'country',
        'timezone',
        'default_language',
        'subscription_plan',
        'subscription_expiry',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => Status::class,
            'subscription_expiry' => 'date',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function teachers(): HasMany
    {
        return $this->hasMany(Teacher::class);
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function designations(): HasMany
    {
        return $this->hasMany(Designation::class);
    }

    public function languages(): HasMany
    {
        return $this->hasMany(Language::class);
    }

    public function attendanceReasons(): HasMany
    {
        return $this->hasMany(AttendanceReason::class);
    }

    public function quranDepartments(): HasMany
    {
        return $this->hasMany(QuranDepartment::class);
    }

    public function quranStatuses(): HasMany
    {
        return $this->hasMany(QuranStatus::class);
    }

    public function quranClasses(): HasMany
    {
        return $this->hasMany(QuranClass::class);
    }

    public function jamaats(): HasMany
    {
        return $this->hasMany(Jamaat::class);
    }

    public function settings(): HasMany
    {
        return $this->hasMany(Setting::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    // ── Scopes ──────────────────────────────────────────────────────

    /**
     * Only the companies that actually use the platform.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeTenants(Builder $query): Builder
    {
        return $query->where('company_code', '!=', self::PLATFORM_CODE);
    }

    // ── Helpers ─────────────────────────────────────────────────────

    public function isPlatform(): bool
    {
        return $this->company_code === self::PLATFORM_CODE;
    }

    public function setting(string $key, mixed $default = null): mixed
    {
        /** @var Setting|null $setting */
        $setting = $this->settings()->where('key', $key)->first();

        return $setting ? $setting->value : $default;
    }
}
