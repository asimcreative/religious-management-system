<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Attendance Reasons
    |--------------------------------------------------------------------------
    |
    | A company is provisioned with these the moment it is created, so the Salah
    | and Quran attendance sheets can record something other than "present" from
    | day one. Afterwards they are ordinary master records: a Company Admin may
    | rename, recolour, deactivate or delete any of them and add their own, and
    | nothing here reaches back in to undo that.
    |
    | "Present" is deliberately not in this list. Attendance stores presence as
    | attendance_reason_id = NULL, so a Present row would be a second way to say
    | the same thing and would split every report in two.
    |
    | Salah/Jamaat, Quran and Taleem are provisioned independently
    | (AttendanceReasonType) so a Company Admin can diverge one list from the
    | others later without it looking like a bug. They start identical;
    | nothing keeps them that way.
    |
    */

    'attendance_reasons' => [
        'salah' => [
            ['reason_name' => 'Absent', 'color' => '#DC3545', 'icon' => 'bi-x-circle', 'counts_as_absent' => true, 'counts_as_leave' => false],
            ['reason_name' => 'Late', 'color' => '#FD7E14', 'icon' => 'bi-clock', 'counts_as_absent' => false, 'counts_as_leave' => false],
            ['reason_name' => 'On Leave', 'color' => '#0DCAF0', 'icon' => 'bi-calendar-x', 'counts_as_absent' => false, 'counts_as_leave' => true],
            ['reason_name' => 'Sick', 'color' => '#6F42C1', 'icon' => 'bi-bandaid', 'counts_as_absent' => false, 'counts_as_leave' => true],
            ['reason_name' => 'Official Duty', 'color' => '#0D6EFD', 'icon' => 'bi-briefcase', 'counts_as_absent' => false, 'counts_as_leave' => false],
            ['reason_name' => 'Travelling', 'color' => '#20C997', 'icon' => 'bi-airplane', 'counts_as_absent' => false, 'counts_as_leave' => true],
        ],
        'quran' => [
            ['reason_name' => 'Absent', 'color' => '#DC3545', 'icon' => 'bi-x-circle', 'counts_as_absent' => true, 'counts_as_leave' => false],
            ['reason_name' => 'Late', 'color' => '#FD7E14', 'icon' => 'bi-clock', 'counts_as_absent' => false, 'counts_as_leave' => false],
            ['reason_name' => 'On Leave', 'color' => '#0DCAF0', 'icon' => 'bi-calendar-x', 'counts_as_absent' => false, 'counts_as_leave' => true],
            ['reason_name' => 'Sick', 'color' => '#6F42C1', 'icon' => 'bi-bandaid', 'counts_as_absent' => false, 'counts_as_leave' => true],
            ['reason_name' => 'Official Duty', 'color' => '#0D6EFD', 'icon' => 'bi-briefcase', 'counts_as_absent' => false, 'counts_as_leave' => false],
            ['reason_name' => 'Travelling', 'color' => '#20C997', 'icon' => 'bi-airplane', 'counts_as_absent' => false, 'counts_as_leave' => true],
        ],
        'taleem' => [
            ['reason_name' => 'Absent', 'color' => '#DC3545', 'icon' => 'bi-x-circle', 'counts_as_absent' => true, 'counts_as_leave' => false],
            ['reason_name' => 'Late', 'color' => '#FD7E14', 'icon' => 'bi-clock', 'counts_as_absent' => false, 'counts_as_leave' => false],
            ['reason_name' => 'On Leave', 'color' => '#0DCAF0', 'icon' => 'bi-calendar-x', 'counts_as_absent' => false, 'counts_as_leave' => true],
            ['reason_name' => 'Sick', 'color' => '#6F42C1', 'icon' => 'bi-bandaid', 'counts_as_absent' => false, 'counts_as_leave' => true],
            ['reason_name' => 'Official Duty', 'color' => '#0D6EFD', 'icon' => 'bi-briefcase', 'counts_as_absent' => false, 'counts_as_leave' => false],
            ['reason_name' => 'Travelling', 'color' => '#20C997', 'icon' => 'bi-airplane', 'counts_as_absent' => false, 'counts_as_leave' => true],
        ],
    ],

];
