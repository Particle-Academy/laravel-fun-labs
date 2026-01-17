<?php

declare(strict_types=1);

namespace LaravelFunLab\Contracts;

use Illuminate\Database\Eloquent\Model;
use LaravelFunLab\Models\Profile;
use LaravelFunLab\Services\AwardXpBuilder;
use LaravelFunLab\Services\GrantBuilder;

/**
 * AwardEngine Contract
 *
 * Defines the public API surface for the AwardEngine service.
 * This contract allows developers to swap implementations while maintaining
 * compatibility with the LFL facade and package internals.
 *
 * The API is minimal and focused:
 * - setup() - Create any entity (GamedMetric, MetricLevel, Achievement, Prize, etc.)
 * - award() - Award XP to a GamedMetric
 * - grant() - Grant an Achievement or Prize
 * - hasLevel() - Check if a Profile has reached a level
 */
interface AwardEngineContract
{
    /**
     * Set up a new entity dynamically at runtime.
     *
     * Creates GamedMetrics, MetricLevels, MetricLevelGroups, Achievements, or Prizes.
     * Uses upsert logic to create new entities or update existing ones by slug.
     *
     * @param  string  $a  Entity type: 'gamed-metric', 'metric-level', 'metric-level-group', 'metric-level-group-level', 'achievement', 'prize'
     * @param  array<string, mixed>  $with  Configuration array with entity-specific fields
     * @return Model The created or updated entity instance
     */
    public function setup(string $a, array $with = []): Model;

    /**
     * Award XP to a GamedMetric for an awardable entity.
     *
     * @param  string  $metricSlug  The GamedMetric slug to award XP to
     * @return AwardXpBuilder Fluent builder for chaining
     */
    public function award(string $metricSlug): AwardXpBuilder;

    /**
     * Grant an Achievement or Prize to an awardable entity.
     *
     * @param  string  $slug  The Achievement or Prize slug
     * @return GrantBuilder Fluent builder for chaining
     */
    public function grant(string $slug): GrantBuilder;

    /**
     * Check if a Profile has reached a specific level in a metric or metric group.
     *
     * @param  Model  $awardable  The awardable entity
     * @param  int  $level  The level number to check
     * @param  string|null  $metric  GamedMetric slug (mutually exclusive with $group)
     * @param  string|null  $group  MetricLevelGroup slug (mutually exclusive with $metric)
     * @return bool Whether the profile has reached the specified level
     */
    public function hasLevel(Model $awardable, int $level, ?string $metric = null, ?string $group = null): bool;

    /**
     * Get or create a gamification profile for an awardable entity.
     *
     * @param  Model  $awardable  The entity to get the profile for
     * @return Profile|null The profile instance
     */
    public function profile(Model $awardable): ?Profile;

    /**
     * Start building a leaderboard query with the fluent API.
     *
     * @return LeaderboardServiceContract Fluent builder for chaining
     */
    public function leaderboard(): LeaderboardServiceContract;

    /**
     * Start building an analytics query with the fluent API.
     *
     * @return AnalyticsServiceContract Fluent builder for chaining
     */
    public function analytics(): AnalyticsServiceContract;

    /**
     * Check if a specific LFL feature is enabled.
     *
     * @param  string  $feature  Feature name
     * @return bool Whether the feature is enabled
     */
    public function isFeatureEnabled(string $feature): bool;

    /**
     * Get all enabled features.
     *
     * @return array<string, bool> Array of feature names and their enabled status
     */
    public function getEnabledFeatures(): array;

    /**
     * Get the configured table prefix.
     *
     * @return string The table prefix
     */
    public function getTablePrefix(): string;

    /**
     * Get the default points amount from configuration.
     *
     * @return int|float The default points amount
     */
    public function getDefaultPoints(): int|float;

    /**
     * Get a multiplier value from configuration.
     *
     * @param  string  $name  Multiplier name
     * @return float The multiplier value
     */
    public function getMultiplier(string $name): float;

    /**
     * Check if event logging is enabled.
     *
     * @return bool Whether event logging is enabled
     */
    public function isEventLoggingEnabled(): bool;

    /**
     * Check if event dispatching is enabled.
     *
     * @return bool Whether event dispatching is enabled
     */
    public function isEventDispatchEnabled(): bool;

    /**
     * Get the configured API prefix.
     *
     * @return string The API route prefix
     */
    public function getApiPrefix(): string;

    /**
     * Check if the API layer is enabled.
     *
     * @return bool Whether the API is enabled
     */
    public function isApiEnabled(): bool;

    /**
     * Check if the UI layer is enabled.
     *
     * @return bool Whether the UI is enabled
     */
    public function isUiEnabled(): bool;
}
