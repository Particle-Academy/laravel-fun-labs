# Configuration Reference

This document describes all configuration options available in Laravel Fun Lab.

## Publishing Configuration

To customize the configuration, publish it first:

```bash
php artisan vendor:publish --tag=lfl-config
```

This creates `config/lfl.php` in your application.

## Configuration Options

### Table Prefix

**Key:** `table_prefix`

**Default:** `'lfl_'`

**Environment Variable:** `LFL_TABLE_PREFIX`

All LFL database tables will be prefixed with this value. This helps avoid naming collisions with your application's existing tables.

```php
'table_prefix' => env('LFL_TABLE_PREFIX', 'lfl_'),
```

**Example:**
```php
'table_prefix' => 'game_', // Creates: game_awards, game_achievements, etc.
```

### Migrations

**Key:** `migrations`

**Default:** `true`

Set this to `false` if you want to publish and customize migrations instead of using the package's migrations directly.

```php
'migrations' => true,
```

When set to `false`, you can publish migrations with:

```bash
php artisan vendor:publish --tag=lfl-migrations
```

### API Configuration

**Key:** `api`

**Default:**
```php
'api' => [
    'enabled' => true,
    'prefix' => 'api/lfl',
    'middleware' => ['api'],
    'auth' => [
        'guard' => env('LFL_API_GUARD', 'sanctum'),
        'middleware' => env('LFL_API_AUTH_MIDDLEWARE', null),
    ],
],
```

Configure the REST API layer for LFL. You can disable the API entirely if you only want to use LFL through the facade.

#### `api.enabled`

**Type:** `bool`

**Default:** `true`

Enable or disable the API routes entirely.

#### `api.prefix`

**Type:** `string`

**Default:** `'api/lfl'`

The URL prefix for all API routes. Routes will be available at `/api/lfl/*`.

#### `api.middleware`

**Type:** `array`

**Default:** `['api']`

Middleware to apply to all API routes.

#### `api.auth.guard`

**Type:** `string`

**Default:** `'sanctum'` (or value from `LFL_API_GUARD` env var)

Authentication guard to use for API routes.

#### `api.auth.middleware`

**Type:** `string|null`

**Default:** `null` (or value from `LFL_API_AUTH_MIDDLEWARE` env var)

Additional authentication middleware to apply. Set to `'auth:sanctum'` to require authentication.

### UI Configuration

**Key:** `ui`

**Default:**
```php
'ui' => [
    'enabled' => false,
    'prefix' => 'lfl',
    'middleware' => ['web'],
],
```

The UI layer is optional and disabled by default. Enable it to use the built-in Blade/Livewire components.

#### `ui.enabled`

**Type:** `bool`

**Default:** `false`

Enable or disable the UI layer. When enabled, routes and views become available.

#### `ui.prefix`

**Type:** `string`

**Default:** `'lfl'`

The URL prefix for all UI routes. Routes will be available at `/lfl/*`.

#### `ui.middleware`

**Type:** `array`

**Default:** `['web']`

Middleware to apply to all UI routes.

### Feature Flags

**Key:** `features`

**Default:**
```php
'features' => [
    'achievements' => true,
    'leaderboards' => true,
    'prizes' => true,
    'profiles' => true,
    'analytics' => true,
],
```

Enable or disable specific LFL features. This allows you to only use the parts of the package that your application needs.

All features are enabled by default. Disable features you don't need:

```php
'features' => [
    'achievements' => true,
    'leaderboards' => false, // Disable leaderboards
    'prizes' => true,
    'profiles' => true,
    'analytics' => false, // Disable analytics
],
```

### Default Point Values

**Key:** `defaults`

**Default:**
```php
'defaults' => [
    'points' => 10,
    'multipliers' => [
        'streak_bonus' => 1.5,
        'first_time_bonus' => 2.0,
    ],
    'max_points_per_action' => 0,
],
```

Define default point amounts for common actions. These can be overridden when calling `LFL::award()`.

#### `defaults.points`

**Type:** `int|float`

**Default:** `10`

Default points when no amount is specified in `LFL::award()`.

#### `defaults.multipliers`

**Type:** `array`

**Default:**
```php
'multipliers' => [
    'streak_bonus' => 1.5,
    'first_time_bonus' => 2.0,
],
```

Multipliers for different contexts. These are available via `LFL::getMultiplier($name)` but are not automatically applied - you control when to use them.

#### `defaults.max_points_per_action`

**Type:** `int`

**Default:** `0` (unlimited)

Maximum points per action. Set to `0` for unlimited points.

### Default Award Types

**Key:** `award_types`

**Default:**
```php
'award_types' => [
    'points' => [
        'name' => 'Points',
        'icon' => 'star',
        'cumulative' => true,
        'default_amount' => 10,
    ],
    'badge' => [
        'name' => 'Badge',
        'icon' => 'badge',
        'cumulative' => false,
        'default_amount' => 1,
    ],
    'achievement' => [
        'name' => 'Achievement',
        'icon' => 'trophy',
        'cumulative' => false,
        'default_amount' => 1,
    ],
],
```

Pre-configured award types that are available out of the box. You can add custom types or modify existing ones.

Each award type has:
- **`name`**: Display name
- **`icon`**: Icon identifier (for UI display)
- **`cumulative`**: Whether awards accumulate (`true` for points) or are unique (`false` for badges/achievements)
- **`default_amount`**: Default amount when awarding this type

### Event Configuration

**Key:** `events`

**Default:**
```php
'events' => [
    'dispatch' => true,
    'log_to_database' => true,
    'queue' => null,
],
```

Configure how LFL events are dispatched. Events are useful for triggering notifications, analytics, or custom business logic.

#### `events.dispatch`

**Type:** `bool`

**Default:** `true`

Master switch for event dispatching. Set to `false` to disable all events.

#### `events.log_to_database`

**Type:** `bool`

**Default:** `true`

Log all award events to the database for analytics. Events are stored in the `lfl_event_logs` table.

#### `events.queue`

**Type:** `string|null`

**Default:** `null` (synchronous)

Queue name for async event processing. Set to a queue name (e.g., `'default'`) to process events asynchronously, or `null` for synchronous processing.

### Service Bindings

**Key:** `services`

**Default:**
```php
'services' => [
    'award_engine' => \LaravelFunLab\Services\AwardEngine::class,
    'leaderboard' => \LaravelFunLab\Builders\LeaderboardBuilder::class,
    'analytics' => \LaravelFunLab\Builders\AnalyticsBuilder::class,
],
```

Configure custom implementations for LFL services. This allows you to swap out the default implementations with your own custom classes.

**Example:**
```php
'services' => [
    'award_engine' => \App\Services\CustomAwardEngine::class,
    'leaderboard' => \App\Services\CustomLeaderboardService::class,
    'analytics' => \App\Services\CustomAnalyticsService::class,
],
```

Custom services must implement the corresponding contracts:
- `AwardEngine` → `LaravelFunLab\Contracts\AwardEngineContract`
- `Leaderboard` → `LaravelFunLab\Contracts\LeaderboardServiceContract`
- `Analytics` → `LaravelFunLab\Contracts\AnalyticsServiceContract`

## Environment Variables

You can override configuration values using environment variables:

```env
LFL_TABLE_PREFIX=custom_
LFL_API_GUARD=sanctum
LFL_API_AUTH_MIDDLEWARE=auth:sanctum
```

## Helper Methods

The LFL facade provides helper methods to access configuration:

```php
use LaravelFunLab\Facades\LFL;

// Feature flags
LFL::isFeatureEnabled('achievements');
LFL::getEnabledFeatures();

// Configuration
LFL::getTablePrefix();
LFL::getDefaultPoints();
LFL::getMultiplier('streak_bonus');

// Event configuration
LFL::isEventLoggingEnabled();
LFL::isEventDispatchEnabled();

// API/UI configuration
LFL::isApiEnabled();
LFL::getApiPrefix();
LFL::isUiEnabled();
```

## Next Steps

- [Installation Guide](installation.md) - Learn how to install and configure LFL
- [Usage Guide](usage.md) - See examples and common patterns
- [Extension Guide](extending.md) - Learn how to extend LFL with custom implementations

