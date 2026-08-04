<?php

namespace App\Policies;

use App\Models\QuranStatus;
use App\Models\User;

class QuranStatusPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('quran_status.manage');
    }

    public function view(User $user, QuranStatus $quranStatus): bool
    {
        return $user->can('quran_status.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('quran_status.manage');
    }

    public function update(User $user, QuranStatus $quranStatus): bool
    {
        return $user->can('quran_status.manage');
    }

    public function delete(User $user, QuranStatus $quranStatus): bool
    {
        return $user->can('quran_status.manage');
    }

    public function restore(User $user, QuranStatus $quranStatus): bool
    {
        return $user->can('quran_status.manage');
    }
}
