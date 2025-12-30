# API Reference

Complete API documentation for Laravel Fun Lab.

## Table of Contents

- [LFL Facade](#lfl-facade)
- [AwardBuilder](#awardbuilder)
- [AnalyticsBuilder](#analyticsbuilder)
- [LeaderboardBuilder](#leaderboardbuilder)
- [Awardable Trait](#awardable-trait)
- [Models](#models)
- [Events](#events)
- [Value Objects](#value-objects)
- [Enums](#enums)

## LFL Facade

The `LFL` facade provides static access to all Laravel Fun Lab functionality.

```php
use LaravelFunLab\Facades\LFL;
```

### Award Methods

#### `award(AwardType|string $type): AwardBuilder`

Start building an award operation with the fluent API.

```php
LFL::award('points')->to($user)->amount(50)->grant();
LFL::award(AwardType::Achievement)->to($user)->achievement('first-login')->grant();
```

**Parameters:**
- `$type` - Award type: `'points'`, `'achievement'`, `'prize'`, `'badge'`, or custom type

**Returns:** `AwardBuilder` instance

#### `awardPoints(Model $recipient, int|float $amount = 1, ?string $reason = null, ?string $source = null, array $meta = []): AwardResult`

Quick method to award points.

```php
LFL::awardPoints($user, 100, 'task completion', 'task-system');
```

**Parameters:**
- `$recipient` - Model instance with `Awardable` trait
- `$amount` - Points to award (defaults to config default)
- `$reason` - Reason for the award
- `$source` - Source identifier
- `$meta` - Additional metadata array

**Returns:** `AwardResult`

#### `grantAchievement(Model $recipient, string $achievementSlug, ?string $reason = null, ?string $source = null, array $meta = []): AwardResult`

Quick method to grant an achievement.

```php
LFL::grantAchievement($user, 'first-login', 'completed first login', 'auth');
```

**Parameters:**
- `$recipient` - Model instance with `Awardable` trait
- `$achievementSlug` - Achievement slug identifier
- `$reason` - Reason for the grant
- `$source` - Source identifier
- `$meta` - Additional metadata array

**Returns:** `AwardResult`

#### `awardPrize(Model $recipient, ?string $reason = null, ?string $source = null, array $meta = []): AwardResult`

Quick method to award a prize.

```php
LFL::awardPrize($user, 'won contest', 'contest-system');
```

**Returns:** `AwardResult`

#### `awardBadge(Model $recipient, ?string $reason = null, ?string $source = null, array $meta = []): AwardResult`

Quick method to award a badge.

```php
LFL::awardBadge($user, 'early adopter', 'admin');
```

**Returns:** `AwardResult`

### Achievement Setup

#### `setup(string $an, ?string $for = null, ?string $name = null, ?string $description = null, ?string $icon = null, array $metadata = [], bool $active = true, int $order = 0): Achievement`

Set up a new achievement dynamically.

```php
LFL::setup(
    an: 'first-login',
    for: 'User',
    name: 'First Login',
    description: 'Welcome!',
    icon: 'star'
);
```

**Parameters:**
- `$an` - Achievement slug (required)
- `$for` - Awardable type (e.g., `'User'`)
- `$name` - Display name (auto-generated from slug if not provided)
- `$description` - Achievement description
- `$icon` - Icon identifier
- `$metadata` - Custom metadata array
- `$active` - Whether achievement is active
- `$order` - Display order

**Returns:** `Achievement` model

### Profile Methods

#### `profile(mixed $awardable): Profile`

Get or create a gamification profile.

```php
$profile = LFL::profile($user);
```

**Returns:** `Profile` model

### Leaderboard Methods

#### `leaderboard(): LeaderboardServiceContract`

Start building a leaderboard query.

```php
LFL::leaderboard()->for(User::class)->by('points')->get();
```

**Returns:** `LeaderboardBuilder` instance

### Analytics Methods

#### `analytics(): AnalyticsServiceContract`

Start building an analytics query.

```php
LFL::analytics()->byType('points')->period('monthly')->total();
```

**Returns:** `AnalyticsBuilder` instance

### Configuration Methods

#### `isFeatureEnabled(string $feature): bool`

Check if a specific feature is enabled.

```php
LFL::isFeatureEnabled('achievements'); // true/false
```

#### `getEnabledFeatures(): array`

Get all enabled features.

```php
$features = LFL::getEnabledFeatures(); // ['achievements', 'leaderboards', ...]
```

#### `getTablePrefix(): string`

Get the configured table prefix.

```php
$prefix = LFL::getTablePrefix(); // 'lfl_'
```

#### `getDefaultPoints(): int|float`

Get the default points amount.

```php
$default = LFL::getDefaultPoints(); // 10
```

#### `getMultiplier(string $name): float`

Get a multiplier value from config.

```php
$streakBonus = LFL::getMultiplier('streak_bonus'); // 1.5
```

#### `isEventLoggingEnabled(): bool`

Check if event logging is enabled.

#### `isEventDispatchEnabled(): bool`

Check if event dispatching is enabled.

#### `getApiPrefix(): string`

Get the configured API prefix.

#### `isApiEnabled(): bool`

Check if the API layer is enabled.

#### `isUiEnabled(): bool`

Check if the UI layer is enabled.

## AwardBuilder

Fluent builder for award operations.

### Methods

#### `to(Model $recipient): self`

Set the recipient of the award.

```php
LFL::award('points')->to($user);
```

#### `for(string $reason): self`

Set the reason for the award.

```php
LFL::award('points')->for('task completion');
```

#### `from(string $source): self`

Set the source/origin of the award.

```php
LFL::award('points')->from('task-system');
```

#### `amount(int|float $amount): self`

Set the amount for cumulative awards.

```php
LFL::award('points')->amount(50);
```

#### `withMeta(array $meta): self`

Add metadata to the award.

```php
LFL::award('points')->withMeta(['task_id' => 123]);
```

#### `achievement(string|Achievement $achievement): self`

Specify which achievement to grant (for achievement type awards).

```php
LFL::award('achievement')->achievement('first-login');
```

#### `grant(): AwardResult`

Execute the award operation and persist to database.

```php
$result = LFL::award('points')->to($user)->amount(50)->grant();
```

**Returns:** `AwardResult`

## AnalyticsBuilder

Fluent builder for analytics queries.

### Filtering Methods

#### `byType(AwardType|string $type): self`

Filter analytics by award type.

```php
LFL::analytics()->byType('points');
```

#### `forAwardableType(string $type): self`

Filter analytics by awardable type.

```php
LFL::analytics()->forAwardableType(User::class);
```

#### `forAwardable(Model $awardable): self`

Filter analytics by specific awardable entity.

```php
LFL::analytics()->forAwardable($user);
```

#### `fromSource(string $source): self`

Filter analytics by source.

```php
LFL::analytics()->fromSource('task-system');
```

#### `forAchievement(string $slug): self`

Filter analytics by achievement slug.

```php
LFL::analytics()->forAchievement('first-login');
```

### Time Period Methods

#### `period(?string $period = null): self`

Filter analytics by time period.

```php
LFL::analytics()->period('weekly'); // 'daily', 'weekly', 'monthly', 'yearly'
```

#### `between(Carbon|string $start, Carbon|string|null $end = null): self`

Filter analytics between two dates.

```php
LFL::analytics()->between($startDate, $endDate);
```

#### `since(Carbon|string $date): self`

Filter analytics since a specific date.

```php
LFL::analytics()->since(Carbon::now()->subDays(7));
```

#### `until(Carbon|string $date): self`

Filter analytics until a specific date.

```php
LFL::analytics()->until($endDate);
```

### Aggregation Methods

#### `count(): int`

Get the total count of events.

```php
$count = LFL::analytics()->byType('points')->count();
```

#### `total(): float`

Get the total amount (sum) for cumulative award types.

```php
$total = LFL::analytics()->byType('points')->total();
```

#### `average(): float`

Get the average amount.

```php
$avg = LFL::analytics()->byType('points')->average();
```

#### `min(): ?float`

Get the minimum amount.

```php
$min = LFL::analytics()->byType('points')->min();
```

#### `max(): ?float`

Get the maximum amount.

```php
$max = LFL::analytics()->byType('points')->max();
```

#### `activeUsers(): int`

Get distinct active users within the filtered period.

```php
$active = LFL::analytics()->since(Carbon::now()->subDays(7))->activeUsers();
```

#### `achievementCompletionRate(?string $achievementSlug = null): float`

Get achievement completion rate as percentage (0-100).

```php
$rate = LFL::analytics()->forAchievement('first-login')->achievementCompletionRate();
```

### Grouping Methods

#### `timeSeries(string $interval = 'day'): array`

Get time-series data grouped by interval.

```php
$data = LFL::analytics()->timeSeries('day'); // 'hour', 'day', 'week', 'month', 'year'
```

**Returns:** Array of periods with `period`, `count`, and `total` keys.

#### `byAwardType(): array`

Get aggregated data grouped by award type.

```php
$byType = LFL::analytics()->byAwardType();
```

#### `byAwardableType(): array`

Get aggregated data grouped by awardable type.

```php
$byType = LFL::analytics()->byAwardableType();
```

#### `bySource(): array`

Get aggregated data grouped by source.

```php
$bySource = LFL::analytics()->bySource();
```

### Export Methods

#### `export(?int $limit = null): array`

Get export-ready data as an array.

```php
$events = LFL::analytics()->export(limit: 1000);
```

#### `query(): Builder`

Get the underlying query builder for custom queries.

```php
$query = LFL::analytics()->query()->where('amount', '>', 100);
```

## LeaderboardBuilder

Fluent builder for leaderboard queries.

### Methods

#### `for(string $type): self`

Filter leaderboard by awardable type.

```php
LFL::leaderboard()->for(User::class);
```

#### `by(string $metric): self`

Sort leaderboard by metric.

```php
LFL::leaderboard()->by('points'); // 'points', 'achievements', 'prizes'
```

#### `period(?string $period = null): self`

Filter leaderboard by time period.

```php
LFL::leaderboard()->period('weekly'); // 'daily', 'weekly', 'monthly', 'all-time'
```

#### `limit(int $limit): self`

Set the maximum number of results.

```php
LFL::leaderboard()->limit(10);
```

#### `perPage(int $perPage): self`

Set pagination per page.

```php
LFL::leaderboard()->perPage(15);
```

#### `page(int $page): self`

Set the current page number.

```php
LFL::leaderboard()->page(2);
```

#### `includeOptedOut(bool $include = true): self`

Include opted-out users in leaderboard.

```php
LFL::leaderboard()->includeOptedOut();
```

#### `get(): Collection`

Get leaderboard results as a collection.

```php
$leaderboard = LFL::leaderboard()->for(User::class)->by('points')->get();
```

#### `paginate(?int $perPage = null): LengthAwarePaginator`

Get paginated leaderboard results.

```php
$leaderboard = LFL::leaderboard()->for(User::class)->by('points')->paginate(15);
```

## Awardable Trait

Trait to add to Eloquent models for gamification features.

### Relationships

#### `awards(): MorphMany`

Get all awards (points/badges) granted to this model.

```php
$user->awards;
```

#### `achievementGrants(): MorphMany`

Get all achievement grants for this model.

```php
$user->achievementGrants;
```

### Helper Methods

#### `getTotalPoints(?string $type = 'points'): int|float`

Get total points accumulated by this model.

```php
$total = $user->getTotalPoints();
```

#### `hasAchievement(string|Achievement $achievement): bool`

Check if this model has a specific achievement.

```php
if ($user->hasAchievement('first-login')) {
    // ...
}
```

#### `getAchievements(): Collection`

Get all achievements unlocked by this model.

```php
$achievements = $user->getAchievements();
```

#### `getAwardCount(string $type): int`

Get award count by type.

```php
$count = $user->getAwardCount('points');
```

#### `getRecentAwards(int $limit = 10): Collection`

Get recent awards.

```php
$recent = $user->getRecentAwards(10);
```

#### `getRecentAchievements(int $limit = 10): Collection`

Get recent achievements.

```php
$recent = $user->getRecentAchievements(10);
```

## Models

### Award

Represents a point or badge award.

**Properties:**
- `id` - Award ID
- `type` - Award type (`'points'`, `'badge'`, etc.)
- `amount` - Award amount
- `reason` - Reason for award
- `source` - Source identifier
- `meta` - Metadata JSON
- `awardable_type` - Recipient model type
- `awardable_id` - Recipient model ID
- `created_at` - Creation timestamp

### Achievement

Represents an achievement definition.

**Properties:**
- `id` - Achievement ID
- `slug` - Unique slug identifier
- `name` - Display name
- `description` - Achievement description
- `icon` - Icon identifier
- `awardable_type` - Target awardable type
- `metadata` - Custom metadata JSON
- `is_active` - Whether achievement is active
- `order` - Display order

### AchievementGrant

Represents a granted achievement to a user.

**Properties:**
- `id` - Grant ID
- `achievement_id` - Achievement ID
- `awardable_type` - Recipient model type
- `awardable_id` - Recipient model ID
- `reason` - Reason for grant
- `source` - Source identifier
- `meta` - Metadata JSON
- `granted_at` - Grant timestamp

### Profile

Represents a gamification profile for an awardable.

**Properties:**
- `id` - Profile ID
- `awardable_type` - Model type
- `awardable_id` - Model ID
- `total_points` - Total points accumulated
- `achievement_count` - Number of achievements
- `prize_count` - Number of prizes
- `opt_out` - Whether user opted out
- `updated_at` - Last update timestamp

### EventLog

Represents an event log entry for analytics.

**Properties:**
- `id` - Log ID
- `event_type` - Event type (`'points_awarded'`, `'achievement_unlocked'`, etc.)
- `award_type` - Award type
- `awardable_type` - Recipient model type
- `awardable_id` - Recipient model ID
- `achievement_slug` - Achievement slug (if applicable)
- `amount` - Award amount
- `reason` - Reason
- `source` - Source identifier
- `context` - Context JSON
- `occurred_at` - Event timestamp

## Events

### PointsAwarded

Dispatched when points are awarded.

**Properties:**
- `$recipient` - Award recipient
- `$award` - Award model
- `$amount` - Points amount
- `$reason` - Reason
- `$source` - Source
- `$previousTotal` - Previous total points
- `$newTotal` - New total points
- `$meta` - Metadata

### AchievementUnlocked

Dispatched when an achievement is unlocked.

**Properties:**
- `$recipient` - Award recipient
- `$achievement` - Achievement model
- `$grant` - AchievementGrant model
- `$reason` - Reason
- `$source` - Source
- `$meta` - Metadata

### PrizeAwarded

Dispatched when a prize is awarded.

### BadgeAwarded

Dispatched when a badge is awarded.

### AwardGranted

Generic event dispatched for any award.

### AwardFailed

Dispatched when an award operation fails.

## Value Objects

### AwardResult

Result of an award operation.

**Properties:**
- `$success` - Whether operation succeeded
- `$type` - Award type
- `$award` - Award model (if successful)
- `$message` - Success/error message
- `$errors` - Validation errors (if failed)
- `$meta` - Additional metadata

**Methods:**
- `successful(): bool` - Check if successful
- `failed(): bool` - Check if failed

## Enums

### AwardType

Award type enumeration.

**Values:**
- `Points` - Points award
- `Achievement` - Achievement grant
- `Prize` - Prize award
- `Badge` - Badge award

```php
use LaravelFunLab\Enums\AwardType;

AwardType::Points;
AwardType::Achievement;
```

## Next Steps

- [Usage Guide](usage.md) - Practical examples and patterns
- [Configuration Reference](configuration.md) - All configuration options
- [Extension Guide](extending.md) - Custom implementations

