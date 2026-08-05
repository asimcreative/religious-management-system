<?php

namespace App\Policies;

use App\Models\SalahAttendance;
use App\Models\User;
use App\Services\RoleDataAccessService;

class SalahAttendancePolicy
{
    public function __construct(
        private readonly RoleDataAccessService $dataAccess,
    ) {}

    public function viewAny(User $user): bool
    {
        return $user->can('salah.attendance.view');
    }

    public function view(User $user, SalahAttendance $salahAttendance): bool
    {
        return $user->can('salah.attendance.view')
            && $this->dataAccess->canAccessSalahAttendance($user, $salahAttendance);
    }

    public function create(User $user): bool
    {
        return $user->can('salah.attendance.create');
    }

    public function update(User $user, SalahAttendance $salahAttendance): bool
    {
        return $user->can('salah.attendance.update')
            && $this->dataAccess->canAccessSalahAttendance($user, $salahAttendance);
    }

    public function delete(User $user, SalahAttendance $salahAttendance): bool
    {
        return $user->can('salah.attendance.delete')
            && $this->dataAccess->canAccessSalahAttendance($user, $salahAttendance);
    }

    public function lock(User $user): bool
    {
        return $user->can('salah.attendance.lock');
    }
}
