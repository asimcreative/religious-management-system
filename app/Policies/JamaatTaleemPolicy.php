<?php

namespace App\Policies;

use App\Models\JamaatTaleem;
use App\Models\User;
use App\Services\RoleDataAccessService;

/**
 * No create/update/delete abilities: this model is never mutated through its
 * own controller/UI, only as a byproduct of
 * SalahAttendanceService::saveAllPrayersAttendance(), which is already gated
 * by salah.attendance.create/update.
 */
class JamaatTaleemPolicy
{
    public function __construct(
        private readonly RoleDataAccessService $dataAccess,
    ) {}

    public function viewAny(User $user): bool
    {
        return $user->can('salah.attendance.view');
    }

    public function view(User $user, JamaatTaleem $taleem): bool
    {
        return $user->can('salah.attendance.view')
            && $this->dataAccess->canAccessJamaatTaleem($user, $taleem);
    }
}
