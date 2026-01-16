<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the prize_grants table for tracking awarded prizes.
 * Links prizes to profiles (not directly to awardable models).
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
     * Get the profiles table name with prefix.
     */
    protected function profilesTable(): string
    {
        return config('lfl.table_prefix', 'lfl_').'profiles';
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create($this->tableName(), function (Blueprint $table) {
            $table->id();

            // Foreign key to profile
            $table->foreignId('profile_id')
                ->constrained($this->profilesTable())
                ->cascadeOnDelete();

            // Foreign key to prize
            $table->foreignId('prize_id')
                ->constrained($this->prizesTable())
                ->cascadeOnDelete();

            // Grant details
            $table->string('reason')->nullable();
            $table->string('source')->nullable();

            // Redemption status (pending, claimed, fulfilled, cancelled, granted)
            $table->string('status')->default('pending');

            // Grant and redemption timestamps
            $table->timestamp('granted_at')->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamp('fulfilled_at')->nullable();

            // Metadata for custom attributes and external integrations
            $table->json('meta')->nullable();

            $table->timestamps();

            // Indexes
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
