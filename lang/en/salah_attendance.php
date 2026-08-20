<?php

return [
    // Module
    'salah_attendance' => 'Salah Attendance',
    'mark_attendance' => 'Mark Attendance',
    'subtitle' => 'Daily prayer attendance across every jamaat.',
    'mark_subtitle' => 'Choose a jamaat and date, then record every prayer for the day.',

    // Empty / helper copy
    'empty_title' => 'No Salah attendance recorded yet',
    'empty_text' => 'Attendance you record for jamaats will appear here.',
    'choose_jamaat_title' => 'Choose a jamaat and date',
    'choose_jamaat_text' => 'Select a jamaat and the date you are recording for; the member list loads automatically.',
    'no_members_title' => 'This jamaat has no members',
    'member_count' => ':count member|:count members',
    'not_recorded' => 'Not recorded',
    'optional_remarks' => 'Optional remarks',
    'bulk_intro' => 'Set a status for a whole prayer at once:',
    'bulk_prayer_label' => 'Prayer to update',
    'bulk_status_label' => 'Status to apply',
    'all_prayers' => 'All prayers',
    'apply' => 'Apply',
    'save_hint' => 'Anyone left without a reason is recorded as present.',

    // Taleem
    'taleem' => 'Taleem',
    'taleem_held_label' => 'Taleem was held today',
    'taleem_held_help' => 'Uncheck if Taleem did not happen today, and give a reason — this is separate from prayer attendance above.',
    'taleem_held_short' => 'Held',
    'taleem_not_held_short' => 'Not held',
    'taleem_reason' => 'Reason Taleem was not held',
    'taleem_select_reason' => '-- Select Reason --',

    'date_not_allowed_title' => 'Date outside the allowed window',
    'attendance_locked_title' => 'Attendance is locked',
    'attendance_history' => 'Attendance History',

    // Fields
    'jamaat' => 'Jamaat',
    'prayer' => 'Prayer',
    'date' => 'Date',
    'employee' => 'Employee',
    'employee_name' => 'Employee Name',
    'attendance_status' => 'Attendance Status',
    'reason' => 'Reason',
    'remarks' => 'Remarks',
    'present' => 'Prayed',
    'absent' => 'Not Prayed',
    'actions' => 'Actions',
    'leader' => 'Marked By',

    // Form
    'select_jamaat' => '-- Select Jamaat --',
    'select_prayer' => '-- Select Prayer --',
    'select_reason' => 'Present',
    'select_date' => 'Select Date',
    'load_members' => 'Load Members',
    'save_attendance' => 'Save Attendance',
    'no_members' => 'No active members in this Jamaat.',

    // Filters
    'all_jamaats' => 'All Jamaats',
    'all_prayers' => 'All Prayers',
    'date_from' => 'Date From',
    'date_to' => 'Date To',
    'filter' => 'Filter',
    'reset' => 'Reset',
    'search_placeholder' => 'Search by employee name...',

    // Messages
    'saved' => 'Attendance saved successfully.',
    'date_not_allowed' => 'This date is outside the allowed backdating window.',
    'attendance_locked' => 'Attendance is locked for this date.',
    'no_records' => 'No attendance records found.',
    'attendance_exists' => 'Attendance already exists for this selection. You can update it below.',

    // Step labels
    'step1' => 'Step 1: Select Jamaat & Date',
    'step2' => 'Step 2: Mark Attendance',
];
