<?php

namespace App\Policies;

use App\Models\SalahAttendance;
use App\Models\User;

class SalahAttendancePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('salah.attendance.view');
    }

    public function view(User $user, SalahAttendance $salahAttendance): bool
    {
        return $user->can('salah.attendance.view');
    }

    public function create(User $user): bool
    {
        return $user->can('salah.attendance.create');
    }

    public function update(User $user, SalahAttendance $salahAttendance): bool
    {
        return $user->can('salah.attendance.update');
    }

    public function delete(User $user, SalahAttendance $salahAttendance): bool
    {
        return $user->can('salah.attendance.delete');
    }

    public function lock(User $user): bool
    {
        return $user->can('salah.attendance.lock');
    }
}
