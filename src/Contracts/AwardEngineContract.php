<?php

declare(strict_types=1);

namespace LaravelFunLab\Contracts;

use Illuminate\Database\Eloquent\Model;
use LaravelFunLab\Builders\AwardBuilder;
use LaravelFunLab\Enums\AwardType;
use LaravelFunLab\Models\Achievement;
use LaravelFunLab\ValueObjects\AwardResult;

/**
 * AwardEngine Contract
 *
 * Defines the public API surface for the AwardEngine service.
 * This contract allows developers to swap implementations while maintaining
 * compatibility with the LFL facade and package internals.
 */
interface AwardEngineContract
{
    /**
     * Start building an award operation with the fluent API.
     *
     * @param  AwardType|string  $type  The type of award (points, achievement, prize, badge)
     * @return AwardBuilder Fluent builder for chaining
     */
    public function award(AwardType|string $type): AwardBuilder;

    /**
     * Quick method to award points to an entity.
     *
     * @param  Model  $recipient  The entity receiving points
     * @param  int|float  $amount  Number of points to award
     * @param  string|null  $reason  Why points are being awarded
     * @param  string|null  $source  Where the points came from
     * @param  array<string, mixed>  $meta  Additional metadata
     */
    public function awardPoints(
        Model $recipient,
        int|float $amount = 1,
        ?string $reason = null,
        ?string $source = null,
        array $meta = [],
    ): AwardResult;

    /**
     * Quick method to grant an achievement to an entity.
     *
     * @param  Model  $recipient  The entity receiving the achievement
     * @param  string  $achievementSlug  The achievement identifier
     * @param  string|null  $reason  Optional reason for the grant
     * @param  string|null  $source  Where the grant originated
     * @param  array<string, mixed>  $meta  Additional metadata
     */
    public function grantAchievement(
        Model $recipient,
        string $achievementSlug,
        ?string $reason = null,
        ?string $source = null,
        array $meta = [],
    ): AwardResult;

    /**
     * Quick method to award a prize to an entity.
     *
     * @param  Model  $recipient  The entity receiving the prize
     * @param  string|null  $reason  Why the prize is being awarded
     * @param  string|null  $source  Where the prize came from
     * @param  array<string, mixed>  $meta  Additional metadata
     */
    public function awardPrize(
        Model $recipient,
        ?string $reason = null,
        ?string $source = null,
        array $meta = [],
    ): AwardResult;

    /**
     * Quick method to award a badge to an entity.
     *
     * @param  Model  $recipient  The entity receiving the badge
     * @param  string|null  $reason  Badge identifier or reason
     * @param  string|null  $source  Where the badge came from
     * @param  array<string, mixed>  $meta  Additional metadata (e.g., badge details)
     */
    public function awardBadge(
        Model $recipient,
        ?string $reason = null,
        ?string $source = null,
        array $meta = [],
    ): AwardResult;

    /**
     * Set up a new achievement dynamically at runtime.
     *
     * @param  string  $an  Achievement name/slug identifier (required)
     * @param  string|null  $for  Awardable type restriction (e.g., 'User', 'App\Models\User')
     * @param  string|null  $name  Human-readable display name (defaults to formatted slug)
     * @param  string|null  $description  Achievement description
     * @param  string|null  $icon  Icon identifier for UI display
     * @param  array<string, mixed>  $metadata  Flexible JSON metadata for custom attributes
     * @param  bool  $active  Whether the achievement is active (default: true)
     * @param  int  $order  Sort order for display (default: 0)
     * @return Achievement The created or updated achievement instance
     */
    public function setup(
        string $an,
        ?string $for = null,
        ?string $name = null,
        ?string $description = null,
        ?string $icon = null,
        array $metadata = [],
        bool $active = true,
        int $order = 0,
    ): Achievement;

    /**
     * Get or create a gamification profile for an awardable entity.
     *
     * @param  mixed  $awardable  The entity to get the profile for
     * @return mixed The profile instance
     */
    public function profile(mixed $awardable): mixed;

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
     * @param  string  $feature  Feature name (achievements, leaderboards, prizes, profiles, analytics)
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
     * @return string The table prefix (default: 'lfl_')
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
     * @param  string  $name  Multiplier name (e.g., 'streak_bonus', 'first_time_bonus')
     * @return float The multiplier value (default: 1.0)
     */
    public function getMultiplier(string $name): float;

    /**
     * Check if event logging is enabled.
     *
     * @return bool Whether event logging to database is enabled
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
