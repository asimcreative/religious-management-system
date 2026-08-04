<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prayers', function (Blueprint $table) {
            $table->id();
            $table->string('prayer_name');
            $table->string('prayer_name_ur')->nullable();
            $table->unsignedTinyInteger('prayer_order');
            $table->tinyInteger('status')->default(1);
            $table->timestamps();

            $table->unique('prayer_order');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prayers');
    }
};
