<?php

namespace App\Models\Concerns;

use App\Models\User;
use App\Services\RoleDataAccessService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Adds documented role-specific record boundaries on top of company isolation.
 */
trait RestrictsRoleDataAccess
{
    public static function bootRestrictsRoleDataAccess(): void
    {
        static::addGlobalScope('role_data_access', function (Builder $builder): void {
            $user = Auth::user();

            if ($user instanceof User) {
                app(RoleDataAccessService::class)->apply($builder, $user);
            }
        });
    }
}
