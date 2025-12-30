<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the metric_level_group_metrics pivot table linking groups to GamedMetrics with weights.
 * Table name uses configurable prefix from lfl.table_prefix config.
 */
return new class extends Migration
{
    /**
     * Get the table name with configurable prefix.
     */
    protected function tableName(): string
    {
        return config('lfl.table_prefix', 'lfl_').'metric_level_group_metrics';
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create($this->tableName(), function (Blueprint $table) {
            $table->id();

            // Foreign keys
            $table->foreignId('metric_level_group_id')->constrained(
                config('lfl.table_prefix', 'lfl_').'metric_level_groups'
            )->cascadeOnDelete();
            $table->foreignId('gamed_metric_id')->constrained(
                config('lfl.table_prefix', 'lfl_').'gamed_metrics'
            )->cascadeOnDelete();

            // Weight for this metric in the group (default 1.0)
            $table->decimal('weight', 8, 2)->default(1.0);

            $table->timestamps();

            // Indexes
            $table->index('metric_level_group_id');
            $table->index('gamed_metric_id');
            // Ensure unique metric per group
            $table->unique(['metric_level_group_id', 'gamed_metric_id'], 'unique_group_metric');
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

