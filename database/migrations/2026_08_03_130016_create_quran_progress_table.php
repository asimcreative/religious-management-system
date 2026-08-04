<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quran_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('quran_department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('quran_status_id')->nullable()->constrained()->nullOnDelete();
            $table->string('current_lesson')->nullable();
            $table->string('current_surah')->nullable();
            $table->unsignedTinyInteger('current_sipara')->nullable();
            $table->unsignedSmallInteger('current_page')->nullable();
            $table->decimal('completion_percentage', 5, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'employee_id']);
            $table->index('company_id');
            $table->index('employee_id');
            $table->index('teacher_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quran_progress');
    }
};
