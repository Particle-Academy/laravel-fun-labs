<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the achievement_metric_level_group_levels pivot table for linking achievements to MetricLevelGroupLevels.
 * When a user reaches a MetricLevelGroupLevel threshold, associated achievements are automatically awarded.
 * Table name uses configurable prefix from lfl.table_prefix config.
 */
return new class extends Migration
{
    /**
     * Get the table name with configurable prefix.
     */
    protected function tableName(): string
    {
        return config('lfl.table_prefix', 'lfl_').'achievement_metric_level_group_levels';
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create($this->tableName(), function (Blueprint $table) {
            $table->id();

            // Foreign key to Achievement
            $table->foreignId('achievement_id')->constrained(
                config('lfl.table_prefix', 'lfl_').'achievements'
            )->cascadeOnDelete();

            // Foreign key to MetricLevelGroupLevel
            $table->foreignId('metric_level_group_level_id')->constrained(
                config('lfl.table_prefix', 'lfl_').'metric_level_group_levels'
            )->cascadeOnDelete();

            $table->timestamps();

            // Ensure unique achievement-group-level combination
            $table->unique(['achievement_id', 'metric_level_group_level_id'], 'unique_achievement_group_level');
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

