<?php

namespace App\Policies;

use App\Models\AttendanceReason;
use App\Models\User;

class AttendanceReasonPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('attendance_reason.manage');
    }

    public function view(User $user, AttendanceReason $attendanceReason): bool
    {
        return $user->can('attendance_reason.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('attendance_reason.manage');
    }

    public function update(User $user, AttendanceReason $attendanceReason): bool
    {
        return $user->can('attendance_reason.manage');
    }

    public function delete(User $user, AttendanceReason $attendanceReason): bool
    {
        return $user->can('attendance_reason.manage');
    }

    public function restore(User $user, AttendanceReason $attendanceReason): bool
    {
        return $user->can('attendance_reason.manage');
    }
}
