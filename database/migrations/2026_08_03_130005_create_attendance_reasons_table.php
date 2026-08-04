<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_reasons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('reason_name');
            $table->string('color', 30)->nullable();
            $table->string('icon', 50)->nullable();
            $table->boolean('counts_as_absent')->default(false);
            $table->boolean('counts_as_leave')->default(false);
            $table->tinyInteger('status')->default(1);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('company_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_reasons');
    }
};
