<?php

namespace App\Policies;

use App\Models\Branch;
use App\Models\User;

class BranchPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('branch.manage');
    }

    public function view(User $user, Branch $branch): bool
    {
        return $user->can('branch.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('branch.manage');
    }

    public function update(User $user, Branch $branch): bool
    {
        return $user->can('branch.manage');
    }

    public function delete(User $user, Branch $branch): bool
    {
        return $user->can('branch.manage');
    }

    public function restore(User $user, Branch $branch): bool
    {
        return $user->can('branch.manage');
    }
}
