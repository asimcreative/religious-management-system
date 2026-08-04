<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quran_class_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('quran_classes')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->date('joined_at');
            $table->date('left_at')->nullable();

            $table->unique(['class_id', 'employee_id']);
            $table->index('employee_id');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quran_class_members');
    }
};
