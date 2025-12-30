<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the metric_levels table for defining level thresholds based on GamedMetric XP.
 * Table name uses configurable prefix from lfl.table_prefix config.
 */
return new class extends Migration
{
    /**
     * Get the table name with configurable prefix.
     */
    protected function tableName(): string
    {
        return config('lfl.table_prefix', 'lfl_').'metric_levels';
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create($this->tableName(), function (Blueprint $table) {
            $table->id();

            // Foreign key to GamedMetric
            $table->foreignId('gamed_metric_id')->constrained(
                config('lfl.table_prefix', 'lfl_').'gamed_metrics'
            )->cascadeOnDelete();

            // Level definition
            $table->unsignedInteger('level');
            $table->unsignedInteger('xp_threshold');
            $table->string('name');
            $table->text('description')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('gamed_metric_id');
            $table->index('level');
            $table->index('xp_threshold');
            // Ensure unique level per metric
            $table->unique(['gamed_metric_id', 'level'], 'unique_metric_level');
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

