<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Record of every file uploaded into a company.
 *
 * company_id is nullable on delete rather than cascading: the record of who
 * moved data into the system outlives the tenant, matching the treatment of
 * audit_logs. module_label snapshots the translated module name so a historic
 * entry still reads correctly after a module is renamed or retired.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('resource_key', 100);
            $table->string('module_label', 150);

            $table->string('file_name');
            $table->string('file_path')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('format', 10)->nullable();

            $table->string('mode', 20);
            $table->string('duplicate_strategy', 20);

            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('imported_rows')->default(0);
            $table->unsignedInteger('updated_rows')->default(0);
            $table->unsignedInteger('skipped_rows')->default(0);
            $table->unsignedInteger('failed_rows')->default(0);

            $table->string('error_file_path')->nullable();
            $table->json('error_summary')->nullable();

            $table->string('status', 30)->default('pending');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->text('exception')->nullable();

            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['company_id', 'resource_key', 'created_at'], 'import_logs_tenant_module_idx');
            $table->index(['company_id', 'user_id'], 'import_logs_tenant_user_idx');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_logs');
    }
};
