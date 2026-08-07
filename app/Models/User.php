<?php

namespace App\Models;

use App\Enums\Status;
use App\Models\Concerns\HasStatus;
use App\Support\Impersonation;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Contracts\Permission;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasStatus, Notifiable;

    // Aliased so hasPermissionTo() below can add the read-only impersonation
    // rule in front of Spatie's own answer.
    use HasRoles {
        hasPermissionTo as protected spatieHasPermissionTo;
    }

    /**
     * Auto-populate company_id when creating a new user if not explicitly set.
     * The User model intentionally does NOT use BelongsToCompany global scope
     * because User IS the company context source — scoping User queries by
     * company_id creates a circular dependency with the auth session guard.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model) {
            if (! $model->getAttribute('company_id')) {
                $actor = Auth::user();
                if ($actor instanceof self && $actor->getAttribute('company_id')) {
                    $model->setAttribute('company_id', $actor->getAttribute('company_id'));
                }
            }
        });
    }

    protected $fillable = [
        'company_id',
        'name',
        'email',
        'password',
        'mobile',
        'status',
        'last_login',
        'language',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'status' => Status::class,
            'last_login' => 'datetime',
        ];
    }

    /**
     * Return a user only when an email identifies exactly one account.
     *
     * Legacy data permits duplicate emails across companies. Authentication and
     * password reset flows must fail closed until those records are resolved.
     */
    public static function findByUniqueEmail(string $email): ?self
    {
        $users = static::query()
            ->where('email', $email)
            ->limit(2)
            ->get();

        return $users->count() === 1 ? $users->first() : null;
    }

    /**
     * Determine whether this user is the platform administrator.
     *
     * A tenant-local role with the same name must never disable company
     * scoping. The system company is the sole platform-administration context.
     */
    public function isSystemAdministrator(): bool
    {
        $company = $this->company;

        if ($company === null || ! $company->isPlatform()) {
            return false;
        }

        app(PermissionRegistrar::class)->setPermissionsTeamId($company->id);

        return $this->hasRole('Super Admin');
    }

    /**
     * A read-only impersonation session holds no write permissions.
     *
     * Overridden here rather than only at the Gate because Spatie answers
     * permission checks from its own `Gate::before` callback, which is
     * registered before ours and would short-circuit the denial. Refusing at
     * the source covers that callback, `can()`, `hasAnyPermission()` and every
     * `@can('employee.create')` in a view with one rule.
     *
     * Note that this narrows what RoleDataAccessService considers company-wide
     * access, so an impersonated role-limited user may see fewer records than
     * they normally would. That errs towards showing less, which is the safe
     * direction for a session that exists only to look.
     *
     * @param  string|int|\BackedEnum|Permission  $permission
     */
    public function hasPermissionTo($permission, ?string $guardName = null): bool
    {
        if (is_string($permission)
            && Impersonation::isReadOnly()
            && Impersonation::isWriteAbility($permission)) {
            return false;
        }

        return $this->spatieHasPermissionTo($permission, $guardName);
    }

    // ── Relationships ──────────────────────────────────────────────

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function passwordHistories(): HasMany
    {
        return $this->hasMany(PasswordHistory::class);
    }
}
