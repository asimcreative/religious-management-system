<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->index(['company_id', 'user_id', 'created_at'], 'notifications_company_user_created_idx');
            $table->index(['company_id', 'user_id', 'read_at'], 'notifications_company_user_read_idx');
            $table->index('created_at', 'notifications_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('notifications_company_user_created_idx');
            $table->dropIndex('notifications_company_user_read_idx');
            $table->dropIndex('notifications_created_idx');
        });
    }
};
