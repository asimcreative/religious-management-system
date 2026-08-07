<?php

return [
    'title' => 'رپورٹ تجزیہ',
    'subtitle' => 'حاضری اور پیشرفت کو جس طرح چاہیں تقسیم کریں — پھر مزید چھان لیں۔',
    'open' => 'کھولیں',
    'datasets' => 'ڈیٹا سیٹ',
    'tip_title' => 'ایک رپورٹ، بے شمار سوال۔',
    'tip_text' => 'منتخب کریں کہ اعداد کس حساب سے تقسیم ہوں، جتنے چاہیں فلٹر لگائیں، پھر جو نظر آ رہا ہے وہی ایکسپورٹ یا پرنٹ کریں۔',

    'n_breakdowns' => ':count تقسیمیں',
    'n_filters' => ':count فلٹر',

    // ── Datasets ────────────────────────────────────────────────────
    'salah_attendance' => 'نماز کی حاضری',
    'salah_attendance_description' => 'نماز کی حاضری — نماز، جماعت، امام، مقام، شعبے یا وقت کے حساب سے۔',
    'quran_attendance' => 'قرآن کی حاضری',
    'quran_attendance_description' => 'کلاس کی حاضری — قاری، کلاس، مقام، شعبے یا وقت کے حساب سے۔',
    'quran_progress' => 'قرآن کی پیشرفت',
    'quran_progress_description' => 'ہر طالبِ علم کہاں تک پہنچا — قاری، شعبے، حیثیت یا مقام کے حساب سے۔',

    // ── Builder ─────────────────────────────────────────────────────
    'build_report' => 'اپنی رپورٹ بنائیں',
    'build_hint' => 'منتخب کریں کہ اعداد کس حساب سے تقسیم ہوں، پھر جو فلٹر چاہیں لگائیں۔',
    'group_by' => 'کس حساب سے تقسیم',
    'then_by' => 'پھر کس حساب سے',
    'then_by_none' => 'مزید کچھ نہیں',
    'by' => ':dimension کے حساب سے',
    'and_then' => 'پھر :dimension کے حساب سے',
    'any' => 'کوئی بھی',
    'total' => 'کل',
    'not_set' => 'مقرر نہیں',

    // ── Dimension / filter groupings ────────────────────────────────
    'group_time' => 'وقت',
    'group_record' => 'ریکارڈ',
    'group_person' => 'فرد',
    'group_salah' => 'نماز',
    'group_quran' => 'قرآن',
    'group_other' => 'دیگر',

    // ── Dimensions ──────────────────────────────────────────────────
    'dim_day' => 'دن',
    'dim_week' => 'ہفتہ',
    'dim_month' => 'مہینہ',
    'dim_year' => 'سال',
    'dim_weekday' => 'ہفتے کا دن',
    'dim_status' => 'حاضر / غیر حاضر',
    'dim_reason' => 'غیر حاضری کی وجہ',
    'dim_recorded_by' => 'درج کرنے والا',

    'dim_prayer' => 'نماز',
    'dim_jamaat' => 'جماعت',
    'dim_jamaat_branch' => 'جماعت کا مقام',
    'dim_leader' => 'امامِ جماعت',

    'dim_teacher' => 'قاری / استاد',
    'dim_class' => 'قرآن کلاس',
    'dim_class_branch' => 'کلاس کا مقام',
    'dim_progress_department' => 'قرآن شعبہ (پیشرفت)',
    'dim_progress_status' => 'قرآن حیثیت (پیشرفت)',
    'dim_sipara' => 'سپارہ',
    'dim_completion_band' => 'تکمیل کا درجہ',
    'band_complete' => 'مکمل',

    'dim_branch' => 'مقام / برانچ',
    'dim_department' => 'شعبہ',
    'dim_designation' => 'عہدہ',
    'dim_employee' => 'ملازم',
    'dim_gender' => 'جنس',
    'dim_employment_status' => 'ملازمت کی حیثیت',
    'dim_quran_department' => 'قرآن شعبہ',
    'dim_quran_status' => 'قرآن حیثیت',

    // ── Filters ─────────────────────────────────────────────────────
    'filter_date_from' => 'سے',
    'filter_date_to' => 'تک',
    'filter_employee' => 'ملازم',
    'filter_employee_placeholder' => 'نام یا کوڈ…',
    'filter_remarks' => 'تبصرہ',
    'filter_remarks_yes' => 'تبصرہ موجود',
    'filter_remarks_no' => 'تبصرہ نہیں',
    'filter_completion_min' => 'تکمیل % سے',
    'filter_completion_max' => 'تکمیل % تک',
    'filter_updated_from' => 'اپ ڈیٹ سے',
    'filter_updated_to' => 'اپ ڈیٹ تک',

    // ── Measures ────────────────────────────────────────────────────
    'measure_records' => 'ریکارڈ',
    'measure_present' => 'حاضر',
    'measure_absent' => 'غیر حاضر',
    'measure_rate' => 'حاضری %',
    'measure_students' => 'طلبہ',
    'measure_average_completion' => 'اوسط تکمیل',
    'measure_completed' => 'مکمل',
    'measure_not_started' => 'شروع نہیں ہوا',
    'measure_completed_rate' => 'مکمل %',

    'status_present' => 'حاضر',
    'status_absent' => 'غیر حاضر',
    'present_no_reason' => 'حاضر (کوئی وجہ درج نہیں)',

    // ── States ──────────────────────────────────────────────────────
    'empty_title' => 'دکھانے کو کچھ نہیں',
    'empty_text' => 'ان فلٹرز پر کوئی ریکارڈ نہیں ملا۔ تاریخ کا دورانیہ بڑھائیں یا کوئی فلٹر ہٹائیں۔',
    'truncated' => 'صرف پہلے :count گروپ دکھائے جا رہے ہیں۔ فلٹر کم کریں یا وسیع تقسیم منتخب کریں۔',

    // ── Provenance ──────────────────────────────────────────────────
    'about_this_report' => 'اس رپورٹ کے بارے میں',
    'meta_company' => 'کمپنی',
    'meta_generated_by' => 'تیار کرنے والا',
    'meta_generated_at' => 'تیاری کا وقت',
    'meta_breakdown' => 'تقسیم بلحاظ',
    'meta_rows' => 'دکھائے گئے گروپ',
    'meta_filters' => 'لاگو فلٹر',
    'meta_no_filters' => 'کوئی نہیں — یہ سب کچھ شامل ہے جو آپ دیکھ سکتے ہیں۔',
];
