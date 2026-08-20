<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quran_departments', function (Blueprint $table) {
            $table->json('progress_fields_schema')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('quran_departments', function (Blueprint $table) {
            $table->dropColumn('progress_fields_schema');
        });
    }
};
