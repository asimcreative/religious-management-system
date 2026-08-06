<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A named set of list filters a user wants to come back to.
 *
 * Stored per user rather than in the browser so a filter survives a cache
 * clear and follows the person to whichever machine they sign in from — the
 * whole point of saving one is not having to rebuild it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_filters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('resource_key', 100);
            $table->string('name', 100);
            $table->json('query');
            $table->boolean('is_default')->default(false);

            $table->timestamps();

            // One name per module per person: saving over an existing name
            // replaces it rather than quietly creating a second entry.
            $table->unique(['user_id', 'resource_key', 'name'], 'saved_filters_owner_name_unique');
            $table->index(['company_id', 'user_id', 'resource_key'], 'saved_filters_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_filters');
    }
};
