<?php

namespace App\Policies;

use App\Models\Language;
use App\Models\User;

class LanguagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('language.manage');
    }

    public function view(User $user, Language $language): bool
    {
        return $user->can('language.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('language.manage');
    }

    public function update(User $user, Language $language): bool
    {
        return $user->can('language.manage');
    }

    public function delete(User $user, Language $language): bool
    {
        return $user->can('language.manage');
    }

    public function restore(User $user, Language $language): bool
    {
        return $user->can('language.manage');
    }
}
