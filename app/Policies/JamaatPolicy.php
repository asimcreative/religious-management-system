<?php

namespace App\Policies;

use App\Models\Jamaat;
use App\Models\User;
use App\Services\RoleDataAccessService;

class JamaatPolicy
{
    public function __construct(
        private readonly RoleDataAccessService $dataAccess,
    ) {}

    public function viewAny(User $user): bool
    {
        return $user->can('jamaat.view');
    }

    public function view(User $user, Jamaat $jamaat): bool
    {
        return $user->can('jamaat.view')
            && $this->dataAccess->canAccessJamaat($user, $jamaat);
    }

    public function create(User $user): bool
    {
        return $user->can('jamaat.create');
    }

    public function update(User $user, Jamaat $jamaat): bool
    {
        return $user->can('jamaat.update')
            && $this->dataAccess->canAccessJamaat($user, $jamaat);
    }

    public function delete(User $user, Jamaat $jamaat): bool
    {
        return $user->can('jamaat.delete')
            && $this->dataAccess->canAccessJamaat($user, $jamaat);
    }

    public function restore(User $user, Jamaat $jamaat): bool
    {
        return $user->can('jamaat.restore')
            && $this->dataAccess->canAccessJamaat($user, $jamaat);
    }
}
