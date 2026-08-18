<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quran_attendance', function (Blueprint $table) {
            $table->boolean('class_held')->default(true)->after('attendance_reason_id');
        });
    }

    public function down(): void
    {
        Schema::table('quran_attendance', function (Blueprint $table) {
            $table->dropColumn('class_held');
        });
    }
};
