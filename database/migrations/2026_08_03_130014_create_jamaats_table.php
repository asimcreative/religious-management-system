<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jamaats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('jamaat_number', 50);
            $table->string('jamaat_name');
            $table->foreignId('leader_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('vice_leader_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->tinyInteger('status')->default(1);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'jamaat_number']);
            $table->index('company_id');
            $table->index('branch_id');
            $table->index('leader_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jamaats');
    }
};
