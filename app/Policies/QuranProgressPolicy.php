<?php

namespace App\Policies;

use App\Models\QuranProgress;
use App\Models\User;

class QuranProgressPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('quran.progress.view');
    }

    public function view(User $user, QuranProgress $progress): bool
    {
        return $user->can('quran.progress.view');
    }

    public function create(User $user): bool
    {
        return $user->can('quran.progress.create');
    }

    public function update(User $user, QuranProgress $progress): bool
    {
        return $user->can('quran.progress.update');
    }

    public function viewHistory(User $user): bool
    {
        return $user->can('quran.progress.history');
    }
}
