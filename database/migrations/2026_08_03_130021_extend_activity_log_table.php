<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(config('activitylog.table_name'), function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable()->after('id');
            $table->string('ip_address', 45)->nullable()->after('batch_uuid');
            $table->string('browser')->nullable()->after('ip_address');
            $table->string('operating_system')->nullable()->after('browser');

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->index('company_id');
        });
    }

    public function down(): void
    {
        Schema::table(config('activitylog.table_name'), function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn(['company_id', 'ip_address', 'browser', 'operating_system']);
        });
    }
};
