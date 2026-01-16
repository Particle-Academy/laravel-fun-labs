<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the profile_metrics table for tracking accumulated XP per profile per metric.
 * Table name uses configurable prefix from lfl.table_prefix config.
 */
return new class extends Migration
{
    /**
     * Get the table name with configurable prefix.
     */
    protected function tableName(): string
    {
        return config('lfl.table_prefix', 'lfl_').'profile_metrics';
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

            // Foreign key to GamedMetric
            $table->foreignId('gamed_metric_id')->constrained(
                config('lfl.table_prefix', 'lfl_').'gamed_metrics'
            )->cascadeOnDelete();

            // XP tracking
            $table->unsignedBigInteger('total_xp')->default(0);
            $table->unsignedInteger('current_level')->default(1);

            $table->timestamps();

            // Indexes
            $table->unique(['profile_id', 'gamed_metric_id'], 'unique_profile_metric');
            $table->index('total_xp');
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
