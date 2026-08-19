<?php

return [
    'title' => 'Report Analysis',
    'subtitle' => 'Break attendance and progress down any way you need — then filter it down further.',
    'open' => 'Open',
    'datasets' => 'Datasets',
    'tip_title' => 'One report, many questions.',
    'tip_text' => 'Pick what to break the numbers down by, add as many filters as you like, then export or print exactly what you see.',

    'n_breakdowns' => ':count breakdowns',
    'n_filters' => ':count filters',

    // ── Datasets ────────────────────────────────────────────────────
    'salah_attendance' => 'Salah Attendance',
    'salah_attendance_description' => 'Prayer attendance, by prayer, jamaat, leader, location, department or time.',
    'jamaat_taleem' => 'Jamaat Taleem',
    'jamaat_taleem_description' => 'Which days each jamaat held Taleem, by jamaat, location, leader, reason or time.',
    'quran_attendance' => 'Quran Attendance',
    'quran_attendance_description' => 'Class attendance, by qari, class, location, department or time.',
    'quran_teacher_attendance' => 'Teacher Attendance',
    'quran_teacher_attendance_description' => 'Days each qari did not hold class, by class, location or time.',
    'quran_progress' => 'Quran Progress',
    'quran_progress_description' => 'How far each student has reached, by qari, department, status or location.',

    // ── Builder ─────────────────────────────────────────────────────
    'build_report' => 'Build your report',
    'build_hint' => 'Choose how to break the numbers down, then narrow with any filters you need.',
    'group_by' => 'Break down by',
    'then_by' => 'Then by',
    'then_by_none' => 'Nothing further',
    'by' => 'By :dimension',
    'and_then' => 'Then by :dimension',
    'any' => 'Any',
    'total' => 'Total',
    'not_set' => 'Not set',

    // ── Dimension / filter groupings ────────────────────────────────
    'group_time' => 'Time',
    'group_record' => 'Record',
    'group_person' => 'Person',
    'group_salah' => 'Salah',
    'group_quran' => 'Quran',
    'group_other' => 'Other',

    // ── Dimensions ──────────────────────────────────────────────────
    'dim_day' => 'Day',
    'dim_week' => 'Week',
    'dim_month' => 'Month',
    'dim_year' => 'Year',
    'dim_weekday' => 'Day of week',
    'dim_status' => 'Present / Absent',
    'dim_reason' => 'Absence reason',
    'dim_recorded_by' => 'Recorded by',

    'dim_prayer' => 'Prayer',
    'dim_jamaat' => 'Jamaat',
    'dim_jamaat_branch' => 'Jamaat location',
    'dim_leader' => 'Jamaat leader',

    'dim_teacher' => 'Qari / Teacher',
    'dim_class' => 'Quran class',
    'dim_class_branch' => 'Class location',
    'dim_progress_department' => 'Quran department (progress)',
    'dim_progress_status' => 'Quran status (progress)',
    'dim_sipara' => 'Sipara',
    'dim_completion_band' => 'Completion band',
    'band_complete' => 'Completed',

    'dim_branch' => 'Location / Branch',
    'dim_department' => 'Department',
    'dim_designation' => 'Designation',
    'dim_employee' => 'Employee',
    'dim_gender' => 'Gender',
    'dim_employment_status' => 'Employment status',
    'dim_quran_department' => 'Quran department',
    'dim_quran_status' => 'Quran status',

    // ── Filters ─────────────────────────────────────────────────────
    'filter_date_from' => 'From',
    'filter_date_to' => 'To',
    'filter_employee' => 'Employee',
    'filter_employee_placeholder' => 'Name or code…',
    'filter_remarks' => 'Remarks',
    'filter_remarks_yes' => 'Has remarks',
    'filter_remarks_no' => 'No remarks',
    'filter_completion_min' => 'Completion from %',
    'filter_completion_max' => 'Completion to %',
    'filter_updated_from' => 'Updated from',
    'filter_updated_to' => 'Updated to',

    // ── Measures ────────────────────────────────────────────────────
    'measure_records' => 'Records',
    'measure_present' => 'Present',
    'measure_absent' => 'Absent',
    'measure_rate' => 'Attendance %',
    'measure_students' => 'Students',
    'measure_average_completion' => 'Average completion',
    'measure_completed' => 'Completed',
    'measure_not_started' => 'Not started',
    'measure_completed_rate' => 'Completed %',
    'measure_teacher_absent_days' => 'Absent Days',
    'measure_classes_affected' => 'Classes Affected',
    'measure_taleem_held' => 'Taleem Held',
    'measure_taleem_not_held' => 'Taleem Not Held',

    'status_present' => 'Present',
    'status_absent' => 'Absent',
    'present_no_reason' => 'Present (no reason recorded)',
    'taleem_held_no_reason' => 'Taleem held',

    // ── States ──────────────────────────────────────────────────────
    'empty_title' => 'Nothing to show',
    'empty_text' => 'No records match these filters. Try widening the date range or clearing a filter.',
    'truncated' => 'Showing the first :count groups only. Narrow the filters or choose a broader breakdown to see everything.',

    // ── Provenance ──────────────────────────────────────────────────
    'about_this_report' => 'About this report',
    'meta_company' => 'Company',
    'meta_generated_by' => 'Generated by',
    'meta_generated_at' => 'Generated at',
    'meta_breakdown' => 'Broken down by',
    'meta_rows' => 'Groups shown',
    'meta_filters' => 'Filters applied',
    'meta_no_filters' => 'None — this covers everything you may see.',
];
