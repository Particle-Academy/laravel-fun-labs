<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the prizes table for defining prize types.
 * Table name uses configurable prefix from lfl.table_prefix config.
 */
return new class extends Migration
{
    /**
     * Get the table name with configurable prefix.
     */
    protected function tableName(): string
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

            // Prize identification
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description')->nullable();

            // Prize type (virtual, physical, feature_unlock, custom)
            $table->string('type');

            // Cost in points (for point-based redemption)
            $table->decimal('cost_in_points', 12, 2)->default(0);

            // Inventory tracking (null = unlimited)
            $table->unsignedInteger('inventory_quantity')->nullable();

            // Metadata for custom attributes and external integrations
            $table->json('meta')->nullable();

            // Status and ordering
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            // Indexes
            $table->index('is_active');
            $table->index('type');
            $table->index('sort_order');
            $table->index('cost_in_points');
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
