<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the achievement_prizes pivot table for linking prizes to achievements.
 * Prizes can be attached to achievements and awarded when achievements are unlocked.
 * Table name uses configurable prefix from lfl.table_prefix config.
 */
return new class extends Migration
{
    /**
     * Get the table name with configurable prefix.
     */
    protected function tableName(): string
    {
        return config('lfl.table_prefix', 'lfl_').'achievement_prizes';
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

            // Foreign key to Prize
            $table->foreignId('prize_id')->constrained(
                config('lfl.table_prefix', 'lfl_').'prizes'
            )->cascadeOnDelete();

            $table->timestamps();

            // Ensure unique achievement-prize combination
            $table->unique(['achievement_id', 'prize_id'], 'unique_achievement_prize');
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

