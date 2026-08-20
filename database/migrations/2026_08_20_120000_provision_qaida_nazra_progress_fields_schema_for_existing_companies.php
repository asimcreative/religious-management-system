<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Gives every existing company's "Qaida Department" and "Nazra Department"
 * their real progress-tracking fields, instead of the generic Lesson/Surah/
 * Sipara/Page columns every Quran department was forced through before.
 *
 * Matches on the confirmed real department names (case-insensitive,
 * trimmed) rather than a guessed bare "qaida"/"nazra" — a company that named
 * its departments something else gets nothing here and configures its own
 * schema through the new Progress Fields builder instead, which is exactly
 * the point of making this data-driven.
 *
 * Only touches rows where progress_fields_schema is still NULL, so this
 * never overwrites a company that has already customised it — the same
 * non-destructive, idempotent rule as every other provisioning migration in
 * this app. "Hifz Department" is deliberately left alone: no template was
 * given for it, so it stays unconfigured until set up through the UI.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $qaidaSchema = [
            ['key' => 'takhtis_completed_this_month', 'label' => 'Takhtis Completed This Month', 'type' => 'number', 'min' => 1, 'max' => 17, 'required' => false],
            ['key' => 'current_takhti', 'label' => 'Current Takhti', 'type' => 'number', 'min' => 1, 'max' => 17, 'required' => false],
            ['key' => 'letter_recognition', 'label' => 'Letter Recognition', 'type' => 'select', 'options' => ['Excellent', 'Average', 'Weak'], 'required' => false],
            ['key' => 'lesson_preparation', 'label' => 'Lesson Preparation', 'type' => 'select', 'options' => ['Usually Complete', 'Sometimes Complete', 'Weak'], 'required' => false],
            ['key' => 'class_interest_engagement', 'label' => 'Class Interest & Engagement', 'type' => 'select', 'options' => ['Excellent', 'Average', 'Weak'], 'required' => false],
            ['key' => 'overall_assessment', 'label' => "Overall Teacher's Assessment", 'type' => 'select', 'options' => ['Excellent', 'Average', 'Weak'], 'required' => false],
        ];

        $nazraSchema = [
            ['key' => 'completed_juz_this_month', 'label' => 'Completed Juz This Month', 'type' => 'number', 'min' => 0, 'max' => 30, 'required' => false],
            ['key' => 'current_juz', 'label' => 'Current Juz', 'type' => 'number', 'min' => 1, 'max' => 30, 'required' => false],
            ['key' => 'rukus_completed', 'label' => 'Number of Rukus Completed in Current Juz', 'type' => 'number', 'min' => 1, 'max' => 20, 'required' => false],
            ['key' => 'lesson_preparation', 'label' => 'Lesson Preparation', 'type' => 'select', 'options' => ['Usually Complete', 'Sometimes Complete', 'Weak'], 'required' => false],
            ['key' => 'interest_seriousness', 'label' => 'Interest & Seriousness', 'type' => 'select', 'options' => ['Excellent', 'Average', 'Weak'], 'required' => false],
            ['key' => 'overall_assessment', 'label' => "Overall Teacher's Assessment", 'type' => 'select', 'options' => ['Excellent', 'Average', 'Weak'], 'required' => false],
        ];

        DB::table('quran_departments')
            ->whereNull('deleted_at')
            ->whereNull('progress_fields_schema')
            ->whereRaw('LOWER(TRIM(department_name)) = ?', ['qaida department'])
            ->update(['progress_fields_schema' => json_encode($qaidaSchema), 'updated_at' => $now]);

        DB::table('quran_departments')
            ->whereNull('deleted_at')
            ->whereNull('progress_fields_schema')
            ->whereRaw('LOWER(TRIM(department_name)) = ?', ['nazra department'])
            ->update(['progress_fields_schema' => json_encode($nazraSchema), 'updated_at' => $now]);
    }

    public function down(): void
    {
        // A provisioned schema becomes company data the moment it exists —
        // by the time anyone rolls back, an admin may have already edited it
        // or recorded progress against one of its fields. There is no safe
        // way to tell an untouched default apart from a since-edited real
        // schema, so a rollback leaves every row in place — matching the
        // established rollback rule for every sibling provisioning migration.
    }
};
