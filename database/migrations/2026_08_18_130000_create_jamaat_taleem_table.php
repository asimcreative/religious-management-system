<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jamaat_taleem', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->date('attendance_date');
            $table->foreignId('jamaat_id')->constrained('jamaats')->cascadeOnDelete();
            $table->foreignId('leader_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->boolean('held')->default(true);
            $table->foreignId('attendance_reason_id')->nullable()->constrained()->nullOnDelete();
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['attendance_date', 'jamaat_id'], 'jamaat_taleem_date_jamaat_unique');
            $table->index('company_id');
            $table->index('attendance_date');
            $table->index('jamaat_id');
            $table->index('leader_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jamaat_taleem');
    }
};
