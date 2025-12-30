<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the prize_grants table for tracking awarded prizes.
 * Table name uses configurable prefix from lfl.table_prefix config.
 */
return new class extends Migration
{
    /**
     * Get the table name with configurable prefix.
     */
    protected function tableName(): string
    {
        return config('lfl.table_prefix', 'lfl_').'prize_grants';
    }

    /**
     * Get the prizes table name with prefix.
     */
    protected function prizesTable(): string
    {
        return config('lfl.table_prefix', 'lfl_').'prizes';
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create($this->tableName(), function (Blueprint $table) {
            $table->id();

            // Foreign key to prize
            $table->foreignId('prize_id')
                ->constrained($this->prizesTable())
                ->cascadeOnDelete();

            // Polymorphic relationship to any awardable model
            $table->morphs('awardable');

            // Redemption status (pending, claimed, fulfilled, cancelled)
            $table->string('status')->default('pending');

            // Grant and redemption timestamps
            $table->timestamp('granted_at')->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamp('fulfilled_at')->nullable();

            // Metadata for custom attributes and external integrations
            $table->json('meta')->nullable();

            $table->timestamps();

            // Indexes
            // Note: morphs() already creates an index on awardable_type and awardable_id
            $table->index('status');
            $table->index('granted_at');
            $table->index('claimed_at');
            $table->index('fulfilled_at');
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
