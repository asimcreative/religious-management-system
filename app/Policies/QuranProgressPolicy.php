<?php

namespace App\Policies;

use App\Models\QuranProgress;
use App\Models\User;
use App\Services\RoleDataAccessService;

class QuranProgressPolicy
{
    public function __construct(
        private readonly RoleDataAccessService $dataAccess,
    ) {}

    public function viewAny(User $user): bool
    {
        return $user->can('quran.progress.view');
    }

    public function view(User $user, QuranProgress $progress): bool
    {
        return $user->can('quran.progress.view')
            && $this->dataAccess->canAccessQuranProgress($user, $progress);
    }

    public function create(User $user): bool
    {
        return $user->can('quran.progress.create');
    }

    public function update(User $user, QuranProgress $progress): bool
    {
        return $user->can('quran.progress.update')
            && $this->dataAccess->canAccessQuranProgress($user, $progress);
    }

    public function viewHistory(User $user): bool
    {
        return $user->can('quran.progress.history');
    }
}
