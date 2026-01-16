<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the profile_metric_groups table for tracking level progression per profile per metric group.
 * Table name uses configurable prefix from lfl.table_prefix config.
 */
return new class extends Migration
{
    /**
     * Get the table name with configurable prefix.
     */
    protected function tableName(): string
    {
        return config('lfl.table_prefix', 'lfl_').'profile_metric_groups';
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create($this->tableName(), function (Blueprint $table) {
            $table->id();

            // Foreign key to Profile
            $table->foreignId('profile_id')->constrained(
                config('lfl.table_prefix', 'lfl_').'profiles'
            )->cascadeOnDelete();

            // Foreign key to MetricLevelGroup
            $table->foreignId('metric_level_group_id')->constrained(
                config('lfl.table_prefix', 'lfl_').'metric_level_groups'
            )->cascadeOnDelete();

            // Level tracking
            $table->unsignedInteger('current_level')->default(1);

            $table->timestamps();

            // Indexes
            $table->unique(['profile_id', 'metric_level_group_id'], 'unique_profile_metric_group');
            $table->index('current_level');
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
