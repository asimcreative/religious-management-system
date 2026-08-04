<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('employee_code', 50);
            $table->string('employee_name');
            $table->text('cnic')->nullable();
            $table->string('cnic_hash', 64)->nullable();
            $table->string('mobile', 30)->nullable();
            $table->string('email')->nullable();
            $table->date('dob')->nullable();
            $table->enum('gender', ['male', 'female'])->nullable();
            $table->string('photo')->nullable();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('designation_id')->nullable()->constrained()->nullOnDelete();
            $table->tinyInteger('employment_status')->default(1);
            $table->foreignId('quran_department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('quran_status_id')->nullable()->constrained()->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'employee_code']);
            $table->unique(['company_id', 'cnic_hash']);
            $table->index('branch_id');
            $table->index('department_id');
            $table->index('designation_id');
            $table->index('employment_status');
            $table->index('user_id');
            $table->index('cnic_hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
