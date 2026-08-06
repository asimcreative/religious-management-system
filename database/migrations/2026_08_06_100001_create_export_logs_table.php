<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Record of every export taken out of a company.
 *
 * docs/38_BUSINESS_RULES_MASTER.md requires exports to be logged. The filters
 * column stores exactly what narrowed the export, so "who took what data out"
 * can be answered precisely rather than approximately.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('export_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('resource_key', 100);
            $table->string('module_label', 150);

            $table->string('format', 10);
            $table->string('scope', 20);
            $table->json('filters')->nullable();

            $table->unsignedInteger('record_count')->default(0);
            $table->boolean('was_truncated')->default(false);

            $table->string('file_name');
            $table->string('file_path')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();

            $table->string('status', 30)->default('completed');
            $table->unsignedInteger('duration_ms')->nullable();
            $table->text('exception')->nullable();

            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['company_id', 'resource_key', 'created_at'], 'export_logs_tenant_module_idx');
            $table->index(['company_id', 'user_id'], 'export_logs_tenant_user_idx');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('export_logs');
    }
};
