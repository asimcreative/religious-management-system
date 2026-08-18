<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quran_teacher_attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->date('attendance_date');
            $table->foreignId('class_id')->constrained('quran_classes')->cascadeOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained('teachers')->nullOnDelete();
            $table->foreignId('attendance_reason_id')->nullable()->constrained()->nullOnDelete();
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['attendance_date', 'class_id'], 'quran_teacher_att_date_class_unique');
            $table->index('company_id');
            $table->index('attendance_date');
            $table->index('class_id');
            $table->index('teacher_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quran_teacher_attendance');
    }
};
