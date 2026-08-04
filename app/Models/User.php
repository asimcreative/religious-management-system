<?php

namespace App\Models;

use App\Enums\Status;
use App\Models\Concerns\HasStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, HasStatus, Notifiable;

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

    // ── Relationships ──────────────────────────────────────────────

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
