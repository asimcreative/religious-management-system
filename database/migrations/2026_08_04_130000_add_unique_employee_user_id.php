<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $hasDuplicateUserLinks = DB::table('employees')
            ->select('user_id')
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($hasDuplicateUserLinks) {
            throw new RuntimeException(
                'Cannot add the employees.user_id unique constraint because one or more users are linked to multiple employees. Resolve duplicate non-null user_id values and retry the migration.'
            );
        }

        Schema::table('employees', function (Blueprint $table): void {
            $table->unique('user_id', 'employees_user_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table): void {
            $table->dropUnique('employees_user_id_unique');
        });
    }
};
