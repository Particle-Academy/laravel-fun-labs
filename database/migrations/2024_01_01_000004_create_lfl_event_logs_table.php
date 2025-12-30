<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the event_logs table for capturing all LFL events.
 * Table name uses configurable prefix from lfl.table_prefix config.
 *
 * This table is optimized for analytics queries with strategic indexes.
 */
return new class extends Migration
{
    /**
     * Get the table name with configurable prefix.
     */
    protected function tableName(): string
    {
        return config('lfl.table_prefix', 'lfl_').'event_logs';
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create($this->tableName(), function (Blueprint $table) {
            $table->id();

            // Event identification
            $table->string('event_type', 50)->index();
            $table->string('award_type', 20)->index();

            // Polymorphic relationship to recipient
            $table->string('awardable_type');
            $table->unsignedBigInteger('awardable_id');

            // Award reference (optional, points to awards or achievement_grants)
            $table->unsignedBigInteger('award_id')->nullable();

            // Achievement tracking (for quick achievement-specific queries)
            $table->string('achievement_slug')->nullable()->index();

            // Award details
            $table->decimal('amount', 15, 2)->nullable();
            $table->string('reason')->nullable();
            $table->string('source')->nullable()->index();

            // Full event context as JSON for detailed analytics
            $table->json('context')->nullable();

            // Timestamps
            $table->timestamp('occurred_at')->useCurrent()->index();
            $table->timestamp('created_at')->useCurrent();

            // Composite indexes for common analytics queries
            $table->index(['awardable_type', 'awardable_id'], 'event_logs_awardable_index');
            $table->index(['award_type', 'occurred_at'], 'event_logs_type_time_index');
            $table->index(['source', 'occurred_at'], 'event_logs_source_time_index');

            // Additional composite indexes optimized for analytics queries
            $table->index(['awardable_type', 'occurred_at'], 'event_logs_awardable_time_index');
            $table->index(['achievement_slug', 'occurred_at'], 'event_logs_achievement_time_index');
            $table->index(['award_type', 'awardable_type', 'occurred_at'], 'event_logs_type_awardable_time_index');
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
