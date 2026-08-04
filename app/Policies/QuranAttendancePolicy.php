<?php

namespace App\Policies;

use App\Models\QuranAttendance;
use App\Models\User;

class QuranAttendancePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('quran.attendance.view');
    }

    public function view(User $user, QuranAttendance $attendance): bool
    {
        return $user->can('quran.attendance.view');
    }

    public function create(User $user): bool
    {
        return $user->can('quran.attendance.create');
    }

    public function update(User $user, QuranAttendance $attendance): bool
    {
        return $user->can('quran.attendance.update');
    }

    public function delete(User $user, QuranAttendance $attendance): bool
    {
        return $user->can('quran.attendance.delete');
    }

    public function lock(User $user): bool
    {
        return $user->can('quran.attendance.lock');
    }
}
