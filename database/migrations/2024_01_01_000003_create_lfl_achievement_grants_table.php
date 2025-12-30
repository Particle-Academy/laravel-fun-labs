<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the achievement_grants table for tracking awarded achievements.
 * Table name uses configurable prefix from lfl.table_prefix config.
 */
return new class extends Migration
{
    /**
     * Get the table name with configurable prefix.
     */
    protected function tableName(): string
    {
        return config('lfl.table_prefix', 'lfl_').'achievement_grants';
    }

    /**
     * Get the achievements table name with prefix.
     */
    protected function achievementsTable(): string
    {
        return config('lfl.table_prefix', 'lfl_').'achievements';
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create($this->tableName(), function (Blueprint $table) {
            $table->id();

            // Foreign key to achievement
            $table->foreignId('achievement_id')
                ->constrained($this->achievementsTable())
                ->cascadeOnDelete();

            // Polymorphic relationship to any awardable model
            $table->morphs('awardable');

            // Grant details
            $table->json('meta')->nullable();
            $table->timestamp('granted_at');

            $table->timestamps();

            // Unique constraint: each awardable can only have one grant per achievement
            $table->unique(['achievement_id', 'awardable_type', 'awardable_id'], 'unique_achievement_grant');

            // Indexes
            $table->index('granted_at');
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
