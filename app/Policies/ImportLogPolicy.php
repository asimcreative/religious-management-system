<?php

namespace App\Policies;

use App\Models\ImportLog;
use App\Models\User;

/**
 * Who may read the record of files brought into a company.
 *
 * Everyone sees their own uploads — a user must be able to check what happened
 * to the file they just submitted. Seeing colleagues' uploads is a broader
 * privilege and reuses the existing activity-log permission rather than
 * inventing a parallel one.
 */
class ImportLogPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ImportLog $importLog): bool
    {
        return $this->ownsOrOversees($user, $importLog);
    }

    /** Downloading the error report exposes the uploaded data itself. */
    public function download(User $user, ImportLog $importLog): bool
    {
        return $this->ownsOrOversees($user, $importLog);
    }

    /**
     * Whether the caller may see runs other than their own.
     *
     * The controller asks this to decide between a company-wide list and a
     * personal one, so the two stay consistent with view().
     */
    public function viewOthers(User $user): bool
    {
        return $user->can('activity.view');
    }

    private function ownsOrOversees(User $user, ImportLog $importLog): bool
    {
        return $importLog->user_id === $user->id || $this->viewOthers($user);
    }
}
