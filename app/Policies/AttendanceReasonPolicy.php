<?php

namespace App\Policies;

use App\Enums\AttendanceReasonType;
use App\Models\AttendanceReason;
use App\Models\User;

class AttendanceReasonPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('attendance_reason.manage');
    }

    public function view(User $user, AttendanceReason $attendanceReason, ?AttendanceReasonType $type = null): bool
    {
        return $user->can('attendance_reason.manage') && $this->matchesType($attendanceReason, $type);
    }

    public function create(User $user): bool
    {
        return $user->can('attendance_reason.manage');
    }

    public function update(User $user, AttendanceReason $attendanceReason, ?AttendanceReasonType $type = null): bool
    {
        return $user->can('attendance_reason.manage') && $this->matchesType($attendanceReason, $type);
    }

    public function delete(User $user, AttendanceReason $attendanceReason, ?AttendanceReasonType $type = null): bool
    {
        return $user->can('attendance_reason.manage') && $this->matchesType($attendanceReason, $type);
    }

    public function restore(User $user, AttendanceReason $attendanceReason, ?AttendanceReasonType $type = null): bool
    {
        return $user->can('attendance_reason.manage') && $this->matchesType($attendanceReason, $type);
    }

    /**
     * Both the Salah and Quran screens bind the same AttendanceReason model
     * class, so route-model-binding alone cannot stop a Salah-typed id being
     * opened through the Quran controller (or vice versa) by guessing/editing
     * the URL. Passing the controller's own type here closes that gap; when
     * no type is given (e.g. a caller that doesn't distinguish) the check is
     * skipped, matching how this policy behaved before the split.
     */
    private function matchesType(AttendanceReason $attendanceReason, ?AttendanceReasonType $type): bool
    {
        return $type === null || $attendanceReason->type === $type;
    }
}
