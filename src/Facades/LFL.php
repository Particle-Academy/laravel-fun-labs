<?php

declare(strict_types=1);

namespace LaravelFunLab\Facades;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Facade;
use LaravelFunLab\Builders\AnalyticsBuilder;
use LaravelFunLab\Builders\AwardBuilder;
use LaravelFunLab\Enums\AwardType;
use LaravelFunLab\Models\Achievement;
use LaravelFunLab\ValueObjects\AwardResult;

/**
 * LFL Facade
 *
 * Provides static access to the AwardEngine service, enabling clean API calls
 * for gamification operations like awarding points, granting achievements, and more.
 *
 * Award Methods:
 *
 * @method static AwardBuilder award(AwardType|string $type) Start building an award operation
 * @method static AwardResult awardPoints(Model $recipient, int|float $amount = 1, ?string $reason = null, ?string $source = null, array $meta = []) Quick method to award points
 * @method static AwardResult grantAchievement(Model $recipient, string $achievementSlug, ?string $reason = null, ?string $source = null, array $meta = []) Quick method to grant an achievement
 * @method static AwardResult awardPrize(Model $recipient, ?string $reason = null, ?string $source = null, array $meta = []) Quick method to award a prize
 * @method static AwardResult awardBadge(Model $recipient, ?string $reason = null, ?string $source = null, array $meta = []) Quick method to award a badge
 * @method static Achievement setup(string $an, ?string $for = null, ?string $name = null, ?string $description = null, ?string $icon = null, array $metadata = [], bool $active = true, int $order = 0) Set up a new achievement dynamically
 * @method static mixed profile(mixed $awardable) Get or create a gamification profile
 * @method static mixed leaderboard(string $type, array $options = []) Generate a leaderboard
 * @method static AnalyticsBuilder analytics() Start building an analytics query
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
