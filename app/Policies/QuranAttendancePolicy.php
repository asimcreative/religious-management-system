<?php

namespace App\Policies;

use App\Models\QuranAttendance;
use App\Models\User;
use App\Services\RoleDataAccessService;

class QuranAttendancePolicy
{
    public function __construct(
        private readonly RoleDataAccessService $dataAccess,
    ) {}

    public function viewAny(User $user): bool
    {
        return $user->can('quran.attendance.view');
    }

    public function view(User $user, QuranAttendance $attendance): bool
    {
        return $user->can('quran.attendance.view')
            && $this->dataAccess->canAccessQuranAttendance($user, $attendance);
    }

    public function create(User $user): bool
    {
        return $user->can('quran.attendance.create');
    }

    public function update(User $user, QuranAttendance $attendance): bool
    {
        return $user->can('quran.attendance.update')
            && $this->dataAccess->canAccessQuranAttendance($user, $attendance);
    }

    public function delete(User $user, QuranAttendance $attendance): bool
    {
        return $user->can('quran.attendance.delete')
            && $this->dataAccess->canAccessQuranAttendance($user, $attendance);
    }

    public function lock(User $user): bool
    {
        return $user->can('quran.attendance.lock');
    }
}
