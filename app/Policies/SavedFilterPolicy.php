<?php

namespace App\Policies;

use App\Models\SavedFilter;
use App\Models\User;

/**
 * Saved filters are personal.
 *
 * The company scope already keeps them inside the tenant; this adds the
 * ownership boundary, so one colleague cannot delete another's shortcuts.
 */
class SavedFilterPolicy
{
    public function view(User $user, SavedFilter $savedFilter): bool
    {
        return $savedFilter->user_id === $user->id;
    }

    public function delete(User $user, SavedFilter $savedFilter): bool
    {
        return $savedFilter->user_id === $user->id;
    }
}
