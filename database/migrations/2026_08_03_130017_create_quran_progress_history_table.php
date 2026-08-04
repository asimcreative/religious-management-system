<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quran_progress_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('progress_id')->constrained('quran_progress')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('quran_department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('quran_status_id')->nullable()->constrained()->nullOnDelete();
            $table->string('lesson')->nullable();
            $table->string('surah')->nullable();
            $table->unsignedTinyInteger('sipara')->nullable();
            $table->unsignedSmallInteger('page')->nullable();
            $table->decimal('percentage', 5, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->index('company_id');
            $table->index('employee_id');
            $table->index('progress_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quran_progress_history');
    }
};
