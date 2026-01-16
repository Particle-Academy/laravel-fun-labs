<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the profiles table for storing engagement profiles with opt-in/opt-out logic.
 * Table name uses configurable prefix from lfl.table_prefix config.
 */
return new class extends Migration
{
    /**
     * Get the table name with configurable prefix.
     */
    protected function tableName(): string
    {
        return config('lfl.table_prefix', 'lfl_').'profiles';
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create($this->tableName(), function (Blueprint $table) {
            $table->id();

            // Polymorphic relationship to any awardable model
            $table->morphs('awardable');

            // Opt-in/opt-out status
            $table->boolean('is_opted_in')->default(true);

            // Display preferences
            $table->json('display_preferences')->nullable();

            // Visibility settings
            $table->json('visibility_settings')->nullable();

            // Aggregated values (cached for performance)
            $table->unsignedBigInteger('total_xp')->default(0);
            $table->unsignedInteger('achievement_count')->default(0);
            $table->unsignedInteger('prize_count')->default(0);

            // Engagement tracking
            $table->timestamp('last_activity_at')->nullable();

            $table->timestamps();

            // Unique constraint: each awardable can only have one profile
            $table->unique(['awardable_type', 'awardable_id'], 'unique_awardable_profile');

            // Indexes for common queries
            $table->index('is_opted_in');
            $table->index('total_xp');
            $table->index('last_activity_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->tableName());
    }
};
