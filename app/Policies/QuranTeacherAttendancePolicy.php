<?php

namespace App\Policies;

use App\Models\QuranTeacherAttendance;
use App\Models\User;
use App\Services\RoleDataAccessService;

/**
 * No create/update/delete abilities: this model is never mutated through its
 * own controller/UI, only as a byproduct of QuranAttendanceService::saveAttendance(),
 * which is already gated by quran.attendance.create/update.
 */
class QuranTeacherAttendancePolicy
{
    public function __construct(
        private readonly RoleDataAccessService $dataAccess,
    ) {}

    public function viewAny(User $user): bool
    {
        return $user->can('quran.attendance.view');
    }

    public function view(User $user, QuranTeacherAttendance $attendance): bool
    {
        return $user->can('quran.attendance.view')
            && $this->dataAccess->canAccessQuranTeacherAttendance($user, $attendance);
    }
}
