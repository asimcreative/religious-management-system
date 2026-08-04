<?php

namespace App\Policies;

use App\Models\QuranClass;
use App\Models\User;

class QuranClassPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('quran.class.view');
    }

    public function view(User $user, QuranClass $quranClass): bool
    {
        return $user->can('quran.class.view');
    }

    public function create(User $user): bool
    {
        return $user->can('quran.class.create');
    }

    public function update(User $user, QuranClass $quranClass): bool
    {
        return $user->can('quran.class.update');
    }

    public function delete(User $user, QuranClass $quranClass): bool
    {
        return $user->can('quran.class.delete');
    }

    public function restore(User $user, QuranClass $quranClass): bool
    {
        return $user->can('quran.class.restore');
    }
}
