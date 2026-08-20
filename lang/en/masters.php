<?php

return [
    // ── Actions ────────────────────────────────────
    'created' => ':item created successfully.',
    'updated' => ':item updated successfully.',
    'deleted' => ':item deleted successfully.',
    'restored' => ':item restored successfully.',

    // ── Labels ─────────────────────────────────────
    'branch' => 'Branch',
    'branches' => 'Branches',
    'department' => 'Department',
    'departments' => 'Departments',
    'designation' => 'Designation',
    'designations' => 'Designations',
    'salah_attendance_reason' => 'Jamaat Attendance Reason',
    'salah_attendance_reasons' => 'Jamaat Attendance Reasons',
    'quran_attendance_reason' => 'Quran Attendance Reason',
    'quran_attendance_reasons' => 'Quran Attendance Reasons',
    'quran_department' => 'Quran Department',
    'quran_departments' => 'Quran Departments',
    'quran_status' => 'Quran Status',
    'quran_statuses' => 'Quran Statuses',
    'language' => 'Language',
    'languages' => 'Languages',
    'master_data' => 'Master Data',

    // ── Common Fields ──────────────────────────────
    'name' => 'Name',
    'status' => 'Status',
    'active' => 'Active',
    'inactive' => 'Inactive',
    'actions' => 'Actions',
    'search' => 'Search...',
    'add_new' => 'Add New',
    'edit' => 'Edit',
    'delete' => 'Delete',
    'restore' => 'Restore',
    'save' => 'Save',
    'cancel' => 'Cancel',
    'back' => 'Back',
    'no_records' => 'No records found.',
    'confirm_delete' => 'Are you sure you want to delete this item?',

    // ── Branch Fields ──────────────────────────────
    'branch_name' => 'Branch Name',
    'address' => 'Address',
    'phone' => 'Phone',

    // ── Department Fields ──────────────────────────
    'department_name' => 'Department Name',

    // ── Designation Fields ─────────────────────────
    'designation_name' => 'Designation Name',

    // ── Attendance Reason Fields ───────────────────
    'reason_name' => 'Reason Name',
    'color' => 'Color',
    'icon' => 'Icon',
    'counts_as_absent' => 'Counts as Absent',
    'counts_as_leave' => 'Counts as Leave',

    // ── Quran Department Fields ────────────────────
    'description' => 'Description',
    'display_order' => 'Display Order',

    // ── Quran Status Fields ────────────────────────
    'status_name' => 'Status Name',

    // ── Language Fields ────────────────────────────
    'language_name' => 'Language Name',
    'native_name' => 'Native Name',
    'locale' => 'Locale Code',
    'direction' => 'Direction',
    'ltr' => 'Left to Right',
    'rtl' => 'Right to Left',

    // ── UI copy ────────────────────────────────────
    'how_used' => 'How this is used',
    'empty_title' => 'No :item yet',
    'empty_text' => 'Add your first :item — it becomes selectable across the system straight away.',
    'status_help' => 'Inactive entries stay on existing records but cannot be selected again.',
    'display_order_help' => 'Lower numbers appear first in dropdowns.',
    'color_help' => 'Used to tint this item wherever it appears.',
    'icon_help' => 'Optional Bootstrap Icons name.',
    'counting_rules' => 'Counting rules',
    'counts_as_absent_help' => 'Attendance marked with this reason is reported as an absence.',
    'counts_as_leave_help' => 'Attendance marked with this reason is reported as approved leave.',
    'native_name_help' => 'The language’s own name, e.g. اردو.',
    'locale_help' => 'ISO code such as en or ur.',

    // ── Section intros ─────────────────────────────
    'branches_intro' => 'Physical locations employees, classes and jamaats belong to.',
    'departments_intro' => 'Organisational departments used to group employees.',
    'designations_intro' => 'Job titles assigned to employees.',
    'salah_attendance_reasons_intro' => 'Reasons available when someone is not present at Jamaat/Salah attendance.',
    'quran_attendance_reasons_intro' => 'Reasons available when someone is not present at a Quran class.',
    'no_attendance_reasons_title' => 'Only "Present" can be recorded',
    'no_attendance_reasons_text' => 'This company has no active :module reasons, so absences and leave cannot be marked.',
    'no_attendance_reasons_action' => 'Set up attendance reasons',
    'no_attendance_reasons_ask_admin' => 'Ask an administrator to set them up.',
    'quran_departments_intro' => 'Streams of Quran study, such as Nazira or Hifz.',
    'quran_statuses_intro' => 'Progress stages a student can be at.',
    'languages_intro' => 'Languages available to users of the system.',

    // ── "How this is used" bullets ─────────────────
    'branch_use_1' => 'Employees, teachers, classes and jamaats are all assigned to a branch.',
    'branch_use_2' => 'Reports can be filtered by branch.',
    'branch_use_3' => 'Inactive branches stay on existing records but cannot be selected again.',
    'department_use_1' => 'Employees are grouped by department across the system.',
    'department_use_2' => 'Reports can be filtered by department.',
    'designation_use_1' => 'Shown on employee records and in reports.',
    'designation_use_2' => 'Inactive designations remain on existing employees.',
    'salah_reason_use_1' => 'Appears in the status dropdown on the Jamaat/Salah attendance sheet.',
    'quran_reason_use_1' => 'Appears in the status dropdown on the Quran attendance sheet.',
    'reason_use_2' => 'The counting rules decide how attendance reports classify the record.',
    'reason_use_3' => 'The colour is used for the badge shown on attendance lists.',
    'quran_department_use_1' => 'Assigned to employees and to every progress record.',
    'quran_department_use_2' => 'Reports can be filtered by Quran department.',
    'quran_status_use_1' => 'Marks how far a student has reached in their study.',
    'quran_status_use_2' => 'The colour is used on progress lists and detail pages.',
    'language_use_1' => 'Users can be assigned a preferred language.',
    'language_use_2' => 'Locale codes must match a translation file shipped with the system.',
];
