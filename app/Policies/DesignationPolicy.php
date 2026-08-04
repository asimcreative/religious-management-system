<?php

namespace App\Policies;

use App\Models\Designation;
use App\Models\User;

class DesignationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('designation.manage');
    }

    public function view(User $user, Designation $designation): bool
    {
        return $user->can('designation.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('designation.manage');
    }

    public function update(User $user, Designation $designation): bool
    {
        return $user->can('designation.manage');
    }

    public function delete(User $user, Designation $designation): bool
    {
        return $user->can('designation.manage');
    }

    public function restore(User $user, Designation $designation): bool
    {
        return $user->can('designation.manage');
    }
}
