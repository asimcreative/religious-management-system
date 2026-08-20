<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Optional admission details for one Quran Class membership.
 *
 * A membership can exist without one of these rows — adding a member never
 * requires filling this in, "Skip for now" is a real, supported outcome —
 * so the row's mere presence is the "admission form submitted" flag; no
 * separate boolean is needed for that.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quran_class_admissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quran_class_member_id')->unique()->constrained('quran_class_members')->cascadeOnDelete();
            $table->unsignedTinyInteger('current_reading_level');
            $table->boolean('previously_completed_quran')->default(false);
            $table->date('admission_date');
            $table->unsignedTinyInteger('classes_per_week');
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quran_class_admissions');
    }
};
