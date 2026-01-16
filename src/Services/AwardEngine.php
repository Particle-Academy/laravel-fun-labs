<?php

declare(strict_types=1);

namespace LaravelFunLab\Services;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;
use LaravelFunLab\Builders\AwardBuilder;
use LaravelFunLab\Contracts\AnalyticsServiceContract;
use LaravelFunLab\Contracts\AwardEngineContract;
use LaravelFunLab\Contracts\LeaderboardServiceContract;
use LaravelFunLab\Enums\AwardType;
use LaravelFunLab\Models\Achievement;
use LaravelFunLab\Models\GamedMetric;
use LaravelFunLab\Models\ProfileMetric;
use LaravelFunLab\ValueObjects\AwardResult;

/**
 * AwardEngine Service
 *
 * The core service that powers the LFL facade. Handles award distribution,
 * achievement setup, profile management, and leaderboard generation.
 * This is the main entry point for all gamification operations.
 *
 * Usage examples:
 * - LFL::award('points')->to($user)->for('task completion')->amount(10)->grant()
 * - LFL::award('achievement')->to($user)->achievement('first-login')->grant()
 * - LFL::awardPoints($user, 50, 'daily bonus', 'scheduler')
 *
 * Macros can be registered to extend functionality:
 * - AwardEngine::macro('customMethod', fn() => ...)
 * - LFL::customMethod()
 */
class AwardEngine implements AwardEngineContract
{
    use Macroable;

    public function __construct(
        protected Application $app,
        protected GamedMetricService $gamedMetricService
    ) {}

    /**
     * Start building an award operation with the fluent API.
     *
     * @param  AwardType|string  $type  The type of award (points, achievement, prize, badge)
     * @return AwardBuilder Fluent builder for chaining
     *
     * @example LFL::award('points')->to($user)->for('purchase')->amount(100)->grant()
     */
    public function award(AwardType|string $type): AwardBuilder
    {
        return new AwardBuilder($type);
    }

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
    ): AwardResult {
        $builder = $this->award(AwardType::Points)
            ->to($recipient)
            ->amount($amount);

        if ($reason !== null) {
            $builder->for($reason);
        }

        if ($source !== null) {
            $builder->from($source);
        }

        if (! empty($meta)) {
            $builder->withMeta($meta);
        }

        return $builder->grant();
    }

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
    ): AwardResult {
        $builder = $this->award(AwardType::Achievement)
            ->to($recipient)
            ->achievement($achievementSlug);

        if ($reason !== null) {
            $builder->for($reason);
        }

        if ($source !== null) {
            $builder->from($source);
        }

        if (! empty($meta)) {
            $builder->withMeta($meta);
        }

        return $builder->grant();
    }

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
    ): AwardResult {
        $builder = $this->award(AwardType::Prize)
            ->to($recipient);

        if ($reason !== null) {
            $builder->for($reason);
        }

        if ($source !== null) {
            $builder->from($source);
        }

        if (! empty($meta)) {
            $builder->withMeta($meta);
        }

        return $builder->grant();
    }

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
    ): AwardResult {
        $builder = $this->award(AwardType::Badge)
            ->to($recipient)
            ->amount(1);

        if ($reason !== null) {
            $builder->for($reason);
        }

        if ($source !== null) {
            $builder->from($source);
        }

        if (! empty($meta)) {
            $builder->withMeta($meta);
        }

        return $builder->grant();
    }

    /**
     * Set up a new achievement dynamically at runtime.
     *
     * This method allows developers to define achievements programmatically
     * without requiring database seeding or migrations. Uses upsert logic
     * to create new achievements or update existing ones by slug.
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
     *
     * @example LFL::setup(an: 'first-login', description: 'Logged in for the first time')
     * @example LFL::setup(an: 'power-user', for: 'User', icon: 'bolt', metadata: ['tier' => 'gold'])
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
    ): Achievement {
        // Generate slug from the "an" (achievement name) parameter
        $slug = Str::slug($an);

        // Use provided name or generate a human-readable one from the slug
        $displayName = $name ?? Str::headline($an);

        // Normalize the awardable type (support short class names)
        $awardableType = $this->normalizeAwardableType($for);

        // Upsert the achievement - create or update by slug
        return Achievement::updateOrCreate(
            ['slug' => $slug],
            [
                'name' => $displayName,
                'description' => $description,
                'icon' => $icon,
                'awardable_type' => $awardableType,
                'meta' => ! empty($metadata) ? $metadata : null,
                'is_active' => $active,
                'sort_order' => $order,
            ]
        );
    }

    /**
     * Normalize an awardable type string to a fully qualified class name or null.
     *
     * Handles short class names like 'User' by checking common namespaces.
     *
     * @param  string|null  $type  The awardable type to normalize
     * @return string|null The normalized class name or null
     */
    protected function normalizeAwardableType(?string $type): ?string
    {
        if ($type === null) {
            return null;
        }

        // If it already looks like a fully qualified class name, use it as-is
        if (str_contains($type, '\\')) {
            return $type;
        }

        // Check common Laravel model namespaces
        $commonNamespaces = [
            'App\\Models\\',
            'App\\',
        ];

        foreach ($commonNamespaces as $namespace) {
            $fullClass = $namespace.$type;
            if (class_exists($fullClass)) {
                return $fullClass;
            }
        }

        // Return as-is (allows storing without validation for flexibility)
        return $type;
    }

    /**
     * Get or create a gamification profile for an awardable entity.
     *
     * @param  mixed  $awardable  The entity to get the profile for
     * @return mixed The profile instance
     */
    public function profile(mixed $awardable): mixed
    {
        // TODO: Implement profile logic in Story 7 (Profiles & Opt-In)
        return null;
    }

    /**
     * Start building a leaderboard query with the fluent API.
     *
     * @return LeaderboardServiceContract Fluent builder for chaining
     *
     * @example LFL::leaderboard()->for(User::class)->by('points')->period('weekly')->get()
     * @example LFL::leaderboard()->for(User::class)->by('achievements')->paginate()
     */
    public function leaderboard(): LeaderboardServiceContract
    {
        return $this->app->make(LeaderboardServiceContract::class);
    }

    /**
     * Start building an analytics query with the fluent API.
     *
     * Provides access to engagement analytics, aggregate queries, and time-series data
     * from the event log. Enables developers to extract behavioral insights.
     *
     * @return AnalyticsServiceContract Fluent builder for chaining
     *
     * @example LFL::analytics()->byType('points')->period('weekly')->total()
     * @example LFL::analytics()->activeUsers()->between($start, $end)->count()
     * @example LFL::analytics()->forAchievement('first-login')->achievementCompletionRate()
     */
    public function analytics(): AnalyticsServiceContract
    {
        return $this->app->make(AnalyticsServiceContract::class);
    }

    /**
     * Check if a specific LFL feature is enabled.
     *
     * Feature flags allow developers to selectively enable/disable
     * parts of the package based on their application's needs.
     *
     * @param  string  $feature  Feature name (achievements, leaderboards, prizes, profiles, analytics)
     * @return bool Whether the feature is enabled
     *
     * @example LFL::isFeatureEnabled('achievements') // true by default
     * @example LFL::isFeatureEnabled('profiles') // true by default
     */
    public function isFeatureEnabled(string $feature): bool
    {
        return (bool) config("lfl.features.{$feature}", false);
    }

    /**
     * Get all enabled features.
     *
     * @return array<string, bool> Array of feature names and their enabled status
     */
    public function getEnabledFeatures(): array
    {
        $features = config('lfl.features', []);

        return array_filter($features, fn ($enabled) => $enabled === true);
    }

    /**
     * Get the configured table prefix.
     *
     * @return string The table prefix (default: 'lfl_')
     */
    public function getTablePrefix(): string
    {
        return config('lfl.table_prefix', 'lfl_');
    }

    /**
     * Get the default points amount from configuration.
     *
     * @return int|float The default points amount
     */
    public function getDefaultPoints(): int|float
    {
        return config('lfl.defaults.points', 10);
    }

    /**
     * Get a multiplier value from configuration.
     *
     * @param  string  $name  Multiplier name (e.g., 'streak_bonus', 'first_time_bonus')
     * @return float The multiplier value (default: 1.0)
     */
    public function getMultiplier(string $name): float
    {
        return (float) config("lfl.defaults.multipliers.{$name}", 1.0);
    }

    /**
     * Check if event logging is enabled.
     *
     * @return bool Whether event logging to database is enabled
     */
    public function isEventLoggingEnabled(): bool
    {
        return (bool) config('lfl.events.log_to_database', true);
    }

    /**
     * Check if event dispatching is enabled.
     *
     * @return bool Whether event dispatching is enabled
     */
    public function isEventDispatchEnabled(): bool
    {
        return (bool) config('lfl.events.dispatch', true);
    }

    /**
     * Get the configured API prefix.
     *
     * @return string The API route prefix
     */
    public function getApiPrefix(): string
    {
        return config('lfl.api.prefix', 'api/lfl');
    }

    /**
     * Check if the API layer is enabled.
     *
     * @return bool Whether the API is enabled
     */
    public function isApiEnabled(): bool
    {
        return (bool) config('lfl.api.enabled', true);
    }

    /**
     * Check if the UI layer is enabled.
     *
     * @return bool Whether the UI is enabled
     */
    public function isUiEnabled(): bool
    {
        return (bool) config('lfl.ui.enabled', false);
    }

    /**
     * Quick method to award XP to a GamedMetric.
     *
     * @param  Model  $recipient  The entity receiving XP
     * @param  string|GamedMetric  $gamedMetric  GamedMetric slug or model instance
     * @param  int  $amount  Amount of XP to award
     * @return ProfileMetric The updated ProfileMetric record
     *
     * @example LFL::awardGamedMetric($user, 'combat-xp', 100)
     * @example LFL::awardGamedMetric($user, $combatMetric, 50)
     */
    public function awardGamedMetric(
        Model $recipient,
        string|GamedMetric $gamedMetric,
        int $amount
    ): ProfileMetric {
        return $this->gamedMetricService->awardXp($recipient, $gamedMetric, $amount);
    }
}
