<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Anonymous security events do not have a tenant or model record.
     */
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->foreignId('company_id')->nullable()->change();
            $table->unsignedBigInteger('record_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->foreignId('company_id')->nullable(false)->change();
            $table->unsignedBigInteger('record_id')->nullable(false)->change();
        });
    }
};
