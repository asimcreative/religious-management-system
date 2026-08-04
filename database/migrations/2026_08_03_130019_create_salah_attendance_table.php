<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salah_attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->date('attendance_date');
            $table->foreignId('prayer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('jamaat_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('leader_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attendance_reason_id')->nullable()->constrained()->nullOnDelete();
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['attendance_date', 'prayer_id', 'employee_id'], 'salah_att_date_prayer_employee_unique');
            $table->index('company_id');
            $table->index('attendance_date');
            $table->index('prayer_id');
            $table->index('jamaat_id');
            $table->index('employee_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salah_attendance');
    }
};
