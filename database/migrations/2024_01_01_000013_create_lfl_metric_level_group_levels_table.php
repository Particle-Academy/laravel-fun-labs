<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the metric_level_group_levels table for defining levels based on combined group XP.
 * Table name uses configurable prefix from lfl.table_prefix config.
 */
return new class extends Migration
{
    /**
     * Get the table name with configurable prefix.
     */
    protected function tableName(): string
    {
        return config('lfl.table_prefix', 'lfl_').'metric_level_group_levels';
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create($this->tableName(), function (Blueprint $table) {
            $table->id();

            // Foreign key to MetricLevelGroup
            $table->foreignId('metric_level_group_id')->constrained(
                config('lfl.table_prefix', 'lfl_').'metric_level_groups'
            )->cascadeOnDelete();

            // Level definition
            $table->unsignedInteger('level');
            $table->unsignedInteger('xp_threshold');
            $table->string('name');
            $table->text('description')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('metric_level_group_id');
            $table->index('level');
            $table->index('xp_threshold');
            // Ensure unique level per group
            $table->unique(['metric_level_group_id', 'level'], 'unique_group_level');
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

