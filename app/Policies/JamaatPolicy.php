<?php

namespace App\Policies;

use App\Models\Jamaat;
use App\Models\User;

class JamaatPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('jamaat.view');
    }

    public function view(User $user, Jamaat $jamaat): bool
    {
        return $user->can('jamaat.view');
    }

    public function create(User $user): bool
    {
        return $user->can('jamaat.create');
    }

    public function update(User $user, Jamaat $jamaat): bool
    {
        return $user->can('jamaat.update');
    }

    public function delete(User $user, Jamaat $jamaat): bool
    {
        return $user->can('jamaat.delete');
    }

    public function restore(User $user, Jamaat $jamaat): bool
    {
        return $user->can('jamaat.restore');
    }
}
