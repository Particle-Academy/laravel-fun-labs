# Extension Guide

This guide covers extending Laravel Fun Lab with custom implementations, award types, validation, and more.

## Table of Contents

- [Custom Award Types](#custom-award-types)
- [Service Swapping](#service-swapping)
- [Macros](#macros)
- [Validation Pipelines](#validation-pipelines)
- [Event Listeners](#event-listeners)
- [Custom Models](#custom-models)

## Custom Award Types

LFL supports custom award types beyond the built-in `points`, `achievement`, `prize`, and `badge` types.

### Registering Custom Types

#### Via Service Provider

Register custom types in your `AppServiceProvider`:

```php
use LaravelFunLab\Registries\AwardTypeRegistry;

public function boot(): void
{
    // Register a single type
    AwardTypeRegistry::register(
        type: 'coins',
        name: 'Coins',
        icon: 'coin',
        cumulative: true,
        defaultAmount: 100
    );
    
    // Register multiple types
    AwardTypeRegistry::registerMany([
        'coins' => [
            'name' => 'Coins',
            'icon' => 'coin',
            'cumulative' => true,
            'default_amount' => 100,
        ],
        'stars' => [
            'name' => 'Stars',
            'icon' => 'star',
            'cumulative' => true,
            'default_amount' => 1,
        ],
    ]);
}
```

#### Via Configuration

Add custom types to `config/lfl.php`:

```php
'award_types' => [
    'points' => [
        'name' => 'Points',
        'icon' => 'star',
        'cumulative' => true,
        'default_amount' => 10,
    ],
    'coins' => [
        'name' => 'Coins',
        'icon' => 'coin',
        'cumulative' => true,
        'default_amount' => 100,
    ],
],
```

### Using Custom Types

Once registered, use custom types with the same API:

```php
use LaravelFunLab\Facades\LFL;

// Award coins using fluent API
$result = LFL::award('coins')
    ->to($user)
    ->amount(50)
    ->for('purchase')
    ->grant();

// Award coins with default amount
$result = LFL::award('coins')
    ->to($user)
    ->for('daily bonus')
    ->grant(); // Uses default_amount from registry
```

### Checking Registration

```php
use LaravelFunLab\Registries\AwardTypeRegistry;

if (AwardTypeRegistry::isRegistered('coins')) {
    // Type is registered
}

$metadata = AwardTypeRegistry::getMetadata('coins');
// Returns: ['name' => 'Coins', 'icon' => 'coin', 'cumulative' => true, 'default_amount' => 100]
```

## Service Swapping

LFL uses contracts for all major services, allowing you to swap implementations.

### Available Contracts

- `AwardEngineContract` - Award engine service
- `LeaderboardServiceContract` - Leaderboard builder
- `AnalyticsServiceContract` - Analytics builder

### Creating Custom Implementations

#### Custom Award Engine

```php
namespace App\Services;

use LaravelFunLab\Contracts\AwardEngineContract;
use LaravelFunLab\Builders\AwardBuilder;
use LaravelFunLab\Enums\AwardType;
use LaravelFunLab\Models\Achievement;
use LaravelFunLab\ValueObjects\AwardResult;

class CustomAwardEngine implements AwardEngineContract
{
    public function award(AwardType|string $type): AwardBuilder
    {
        // Custom implementation
        return new AwardBuilder($type);
    }
    
    // Implement other required methods...
}
```

#### Custom Leaderboard Service

```php
namespace App\Services;

use LaravelFunLab\Contracts\LeaderboardServiceContract;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class CustomLeaderboardService implements LeaderboardServiceContract
{
    public function for(string $type): self
    {
        // Custom implementation
        return $this;
    }
    
    // Implement other required methods...
}
```

### Binding Custom Services

Bind your custom implementations in `AppServiceProvider`:

```php
use LaravelFunLab\Contracts\AwardEngineContract;
use App\Services\CustomAwardEngine;

public function register(): void
{
    $this->app->singleton(AwardEngineContract::class, CustomAwardEngine::class);
}
```

Or configure via `config/lfl.php`:

```php
'services' => [
    'award_engine' => \App\Services\CustomAwardEngine::class,
    'leaderboard' => \App\Services\CustomLeaderboardService::class,
    'analytics' => \App\Services\CustomAnalyticsService::class,
],
```

## Macros

Add custom methods to the LFL facade using macros.

### Registering Macros

Register macros in your `AppServiceProvider`:

```php
use LaravelFunLab\Services\AwardEngine;

public function boot(): void
{
    // Simple macro
    AwardEngine::macro('awardBonusPoints', function ($user, $multiplier = 1.5) {
        $basePoints = config('lfl.defaults.points', 10);
        $bonusPoints = $basePoints * $multiplier;
        
        return LFL::awardPoints($user, $bonusPoints, 'bonus', 'system');
    });
    
    // Macro with instance context
    AwardEngine::macro('getConfigValue', function ($key) {
        return config("lfl.{$key}");
    });
}
```

### Using Macros

```php
use LaravelFunLab\Facades\LFL;

// Call macro via facade
$result = LFL::awardBonusPoints($user, 2.0);

$value = LFL::getConfigValue('defaults.points');
```

### Checking for Macros

```php
use LaravelFunLab\Services\AwardEngine;

if (AwardEngine::hasMacro('awardBonusPoints')) {
    // Macro exists
}
```

## Validation Pipelines

Add custom validation logic to award operations using the validation pipeline.

### Adding Validation Steps

Register validation steps in your `AppServiceProvider`:

```php
use LaravelFunLab\Pipelines\AwardValidationPipeline;
use LaravelFunLab\Enums\AwardType;

public function boot(): void
{
    // Maximum points per award
    AwardValidationPipeline::addStep(function ($awardable, $type, $amount) {
        if ($type === AwardType::Points && $amount > 1000) {
            return [
                'valid' => false,
                'message' => 'Maximum points per award is 1000',
                'errors' => ['amount' => ['Amount cannot exceed 1000']],
            ];
        }
        
        return ['valid' => true];
    });
    
    // Rate limiting
    AwardValidationPipeline::addStep(function ($awardable, $type, $amount, $reason, $source) {
        $recentAwards = $awardable->awards()
            ->where('source', $source)
            ->where('created_at', '>=', now()->subHour())
            ->count();
        
        if ($recentAwards >= 10) {
            return [
                'valid' => false,
                'message' => 'Too many awards from this source',
                'errors' => ['rate_limit' => ['Maximum 10 awards per hour from this source']],
            ];
        }
        
        return ['valid' => true];
    });
    
    // User-specific validation
    AwardValidationPipeline::addStep(function ($awardable, $type, $amount) {
        // Example: Premium users can receive more points
        if ($type === AwardType::Points && $awardable->isPremium()) {
            // Premium users have no limit
            return ['valid' => true];
        }
        
        // Regular users limited to 500 points per award
        if ($type === AwardType::Points && $amount > 500) {
            return [
                'valid' => false,
                'message' => 'Regular users limited to 500 points per award',
                'errors' => ['amount' => ['Upgrade to premium for higher limits']],
            ];
        }
        
        return ['valid' => true];
    });
}
```

### Validation Step Parameters

Each validation step receives:

- `$awardable` - The recipient model (with `Awardable` trait)
- `$type` - Award type (`AwardType` enum or string)
- `$amount` - Award amount (int|float)
- `$reason` - Reason for award (string|null)
- `$source` - Source identifier (string|null)
- `$meta` - Additional metadata (array)

### Validation Return Values

Return `['valid' => true]` to pass validation, or:

```php
return [
    'valid' => false,
    'message' => 'Human-readable error message',
    'errors' => [
        'field' => ['Error message for field'],
    ],
];
```

### Pipeline Behavior

- Steps execute in registration order
- First failure halts the pipeline
- All steps must pass for award to succeed
- Failed validation prevents award from being granted

## Event Listeners

Listen to LFL events to add custom behavior.

### Available Events

- `PointsAwarded` - When points are awarded
- `AchievementUnlocked` - When achievement is unlocked
- `PrizeAwarded` - When prize is awarded
- `BadgeAwarded` - When badge is awarded
- `AwardGranted` - Generic award event
- `AwardFailed` - When award operation fails

### Creating Event Listeners

```php
namespace App\Listeners;

use LaravelFunLab\Events\PointsAwarded;
use LaravelFunLab\Events\AchievementUnlocked;

class AwardNotificationListener
{
    public function handle(PointsAwarded $event): void
    {
        // Send notification
        $event->recipient->notify(
            new PointsAwardedNotification($event->amount, $event->reason)
        );
        
        // Log to external analytics
        Analytics::track('points_awarded', [
            'user_id' => $event->recipient->id,
            'amount' => $event->amount,
            'total' => $event->newTotal,
        ]);
    }
    
    public function handleAchievement(AchievementUnlocked $event): void
    {
        // Send achievement notification
        $event->recipient->notify(
            new AchievementUnlockedNotification($event->achievement)
        );
        
        // Grant bonus points for achievement
        if ($event->achievement->slug === 'first-login') {
            LFL::awardPoints($event->recipient, 50, 'first login bonus', 'system');
        }
    }
}
```

### Registering Event Listeners

In `AppServiceProvider`:

```php
use LaravelFunLab\Events\PointsAwarded;
use LaravelFunLab\Events\AchievementUnlocked;
use App\Listeners\AwardNotificationListener;

public function boot(): void
{
    Event::listen(PointsAwarded::class, AwardNotificationListener::class);
    Event::listen(AchievementUnlocked::class, [AwardNotificationListener::class, 'handleAchievement']);
}
```

Or use auto-discovery in `EventServiceProvider`:

```php
protected $listen = [
    PointsAwarded::class => [
        AwardNotificationListener::class,
    ],
    AchievementUnlocked::class => [
        AwardNotificationListener::class,
    ],
];
```

## Custom Models

Extend LFL models to add custom functionality.

### Extending Models

```php
namespace App\Models;

use LaravelFunLab\Models\Award as BaseAward;

class Award extends BaseAward
{
    /**
     * Get formatted amount with currency symbol.
     */
    public function getFormattedAmountAttribute(): string
    {
        return '$' . number_format($this->amount, 2);
    }
    
    /**
     * Scope for high-value awards.
     */
    public function scopeHighValue($query)
    {
        return $query->where('amount', '>', 1000);
    }
}
```

### Binding Custom Models

Update model bindings in `AppServiceProvider`:

```php
use LaravelFunLab\Models\Award;
use App\Models\Award as CustomAward;

public function boot(): void
{
    // Note: This requires modifying LFL's internal model resolution
    // Consider using model events or observers instead for most use cases
}
```

### Using Model Observers

```php
namespace App\Observers;

use LaravelFunLab\Models\Award;

class AwardObserver
{
    public function created(Award $award): void
    {
        // Custom logic when award is created
        if ($award->amount > 1000) {
            // Notify admin of high-value award
        }
    }
    
    public function updated(Award $award): void
    {
        // Custom logic when award is updated
    }
}
```

Register observer in `AppServiceProvider`:

```php
use LaravelFunLab\Models\Award;
use App\Observers\AwardObserver;

public function boot(): void
{
    Award::observe(AwardObserver::class);
}
```

## Advanced Examples

### Custom Award Type with Business Logic

```php
// Register custom type
AwardTypeRegistry::register('experience', 'Experience Points', 'xp', true, 10);

// Add validation
AwardValidationPipeline::addStep(function ($awardable, $type, $amount) {
    if ($type === 'experience') {
        // Level up check
        $currentXP = $awardable->getTotalPoints('experience');
        $newXP = $currentXP + $amount;
        
        $level = floor($newXP / 1000) + 1;
        $currentLevel = floor($currentXP / 1000) + 1;
        
        if ($level > $currentLevel) {
            // Level up! Grant achievement
            LFL::grantAchievement($awardable, "level-{$level}", "Reached level {$level}", 'system');
        }
    }
    
    return ['valid' => true];
});
```

### Custom Analytics Service

```php
namespace App\Services;

use LaravelFunLab\Contracts\AnalyticsServiceContract;
use LaravelFunLab\Builders\AnalyticsBuilder;

class CustomAnalyticsService extends AnalyticsBuilder
{
    public function total(): float
    {
        $total = parent::total();
        
        // Add custom caching
        return cache()->remember(
            "analytics.total.{$this->getCacheKey()}",
            now()->addHour(),
            fn() => $total
        );
    }
    
    protected function getCacheKey(): string
    {
        return md5(serialize([
            $this->awardType,
            $this->awardableType,
            $this->startDate,
            $this->endDate,
        ]));
    }
}
```

## Next Steps

- [Usage Guide](usage.md) - Practical examples and patterns
- [API Reference](api.md) - Complete API documentation
- [Configuration Reference](configuration.md) - All configuration options

