<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('company_code', 50)->unique();
            $table->string('company_name');
            $table->string('logo')->nullable();
            $table->string('email')->unique();
            $table->string('phone', 50)->nullable();
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('country', 100)->nullable();
            $table->string('timezone', 100)->default('Asia/Karachi');
            $table->string('default_language', 10)->default('en');
            $table->string('subscription_plan', 100)->nullable();
            $table->date('subscription_expiry')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
