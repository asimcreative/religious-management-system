<?php

namespace App\Policies;

use App\Models\QuranClass;
use App\Models\User;
use App\Services\RoleDataAccessService;

class QuranClassPolicy
{
    public function __construct(
        private readonly RoleDataAccessService $dataAccess,
    ) {}

    public function viewAny(User $user): bool
    {
        return $user->can('quran.class.view');
    }

    public function view(User $user, QuranClass $quranClass): bool
    {
        return $user->can('quran.class.view')
            && $this->dataAccess->canAccessQuranClass($user, $quranClass);
    }

    public function create(User $user): bool
    {
        return $user->can('quran.class.create');
    }

    public function update(User $user, QuranClass $quranClass): bool
    {
        return $user->can('quran.class.update')
            && $this->dataAccess->canAccessQuranClass($user, $quranClass);
    }

    public function delete(User $user, QuranClass $quranClass): bool
    {
        return $user->can('quran.class.delete')
            && $this->dataAccess->canAccessQuranClass($user, $quranClass);
    }

    public function restore(User $user, QuranClass $quranClass): bool
    {
        return $user->can('quran.class.restore')
            && $this->dataAccess->canAccessQuranClass($user, $quranClass);
    }
}
