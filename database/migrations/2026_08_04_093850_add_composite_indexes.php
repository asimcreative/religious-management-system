<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Composite indexes for common multi-column query patterns.
 *
 * Based on PERFORMANCE_REVIEW.md PERF-04.
 * These are in addition to the single-column indexes already added
 * in the individual table migrations.
 */
return new class extends Migration
{
    public function up(): void
    {
        // quran_attendance — daily attendance view, class+date, employee history
        Schema::table('quran_attendance', function (Blueprint $table) {
            $table->index(['company_id', 'attendance_date'], 'qa_company_date_idx');
            $table->index(['company_id', 'class_id', 'attendance_date'], 'qa_company_class_date_idx');
            $table->index(['employee_id', 'attendance_date'], 'qa_employee_date_idx');
        });

        // salah_attendance — daily prayer view, jamaat+date, employee prayer history
        Schema::table('salah_attendance', function (Blueprint $table) {
            $table->index(['company_id', 'attendance_date'], 'sa_company_date_idx');
            $table->index(['company_id', 'jamaat_id', 'attendance_date'], 'sa_company_jamaat_date_idx');
            $table->index(['employee_id', 'prayer_id', 'attendance_date'], 'sa_employee_prayer_date_idx');
        });

        // employees — branch/department/status filtering
        Schema::table('employees', function (Blueprint $table) {
            $table->index(['company_id', 'branch_id'], 'emp_company_branch_idx');
            $table->index(['company_id', 'department_id'], 'emp_company_dept_idx');
            $table->index(['company_id', 'employment_status'], 'emp_company_status_idx');
        });

        // audit_logs — company-scoped listing by date
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->index(['company_id', 'created_at'], 'al_company_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('quran_attendance', function (Blueprint $table) {
            $table->dropIndex('qa_company_date_idx');
            $table->dropIndex('qa_company_class_date_idx');
            $table->dropIndex('qa_employee_date_idx');
        });

        Schema::table('salah_attendance', function (Blueprint $table) {
            $table->dropIndex('sa_company_date_idx');
            $table->dropIndex('sa_company_jamaat_date_idx');
            $table->dropIndex('sa_employee_prayer_date_idx');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex('emp_company_branch_idx');
            $table->dropIndex('emp_company_dept_idx');
            $table->dropIndex('emp_company_status_idx');
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex('al_company_created_idx');
        });
    }
};
