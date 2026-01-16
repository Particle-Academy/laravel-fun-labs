<?php

declare(strict_types=1);

namespace LaravelFunLab\Facades;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Facade;
use LaravelFunLab\Contracts\AnalyticsServiceContract;
use LaravelFunLab\Contracts\LeaderboardServiceContract;
use LaravelFunLab\Models\Profile;
use LaravelFunLab\Services\AwardXpBuilder;
use LaravelFunLab\Services\GrantBuilder;

/**
 * LFL Facade
 *
 * Provides a minimal, focused API for all gamification operations:
 *
 * Core Methods:
 *
 * @method static Model setup(?string $a = null, ?string $an = null, ?string $slug = null, ?string $name = null, ?string $description = null, ?string $icon = null, ?string $for = null, ?string $metric = null, ?string $group = null, ?int $level = null, ?int $xp = null, ?float $weight = null, ?string $type = null, int|float|null $cost = null, ?int $inventory = null, array $metadata = [], bool $active = true, int $order = 0) Set up any entity (GamedMetric, MetricLevel, MetricLevelGroup, Achievement, Prize)
 * @method static AwardXpBuilder award(string $metricSlug) Award XP to a GamedMetric (returns fluent builder)
 * @method static GrantBuilder grant(string $slug) Grant an Achievement or Prize (returns fluent builder)
 * @method static bool hasLevel(Model $awardable, int $level, ?string $metric = null, ?string $group = null) Check if Profile has reached a level in a metric or group
 * @method static Profile|null profile(Model $awardable) Get or create a gamification profile
 * @method static LeaderboardServiceContract leaderboard() Start building a leaderboard query
 * @method static AnalyticsServiceContract analytics() Start building an analytics query
 *
 * Configuration & Feature Flag Methods:
 * @method static bool isFeatureEnabled(string $feature) Check if a specific feature is enabled
 * @method static array getEnabledFeatures() Get all enabled features
 * @method static string getTablePrefix() Get the configured table prefix
 * @method static int|float getDefaultPoints() Get the default points amount
 * @method static float getMultiplier(string $name) Get a multiplier value from config
 * @method static bool isEventLoggingEnabled() Check if event logging is enabled
 * @method static bool isEventDispatchEnabled() Check if event dispatching is enabled
 * @method static string getApiPrefix() Get the configured API prefix
 * @method static bool isApiEnabled() Check if the API layer is enabled
 * @method static bool isUiEnabled() Check if the UI layer is enabled
 *
 * @see \LaravelFunLab\Services\AwardEngine
 */
class LFL extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'lfl';
    }
}
