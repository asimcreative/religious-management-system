<?php

namespace App\Policies;

use App\Models\ExportLog;
use App\Models\User;

/**
 * Who may read the record of data taken out of a company.
 *
 * Mirrors ImportLogPolicy: your own exports are always visible, everyone
 * else's need the activity-log privilege.
 */
class ExportLogPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ExportLog $exportLog): bool
    {
        return $this->ownsOrOversees($user, $exportLog);
    }

    /**
     * Re-downloading a stored export hands over the exported records again,
     * so it is gated exactly like viewing.
     */
    public function download(User $user, ExportLog $exportLog): bool
    {
        return $this->ownsOrOversees($user, $exportLog);
    }

    public function viewOthers(User $user): bool
    {
        return $user->can('activity.view');
    }

    private function ownsOrOversees(User $user, ExportLog $exportLog): bool
    {
        return $exportLog->user_id === $user->id || $this->viewOthers($user);
    }
}
