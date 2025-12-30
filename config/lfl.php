<?php

declare(strict_types=1);

/**
 * Laravel Fun Lab Configuration
 *
 * This file defines the configuration options for the LFL gamification package.
 * Publish this file to customize behavior: php artisan vendor:publish --tag=lfl-config
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Table Prefix
    |--------------------------------------------------------------------------
    |
    | All LFL database tables will be prefixed with this value. This helps
    | avoid naming collisions with your application's existing tables.
    |
    */

    'table_prefix' => env('LFL_TABLE_PREFIX', 'lfl_'),

    /*
    |--------------------------------------------------------------------------
    | Migrations
    |--------------------------------------------------------------------------
    |
    | Set this to false if you want to publish and customize migrations
    | instead of using the package's migrations directly.
    |
    */

    'migrations' => true,

    /*
    |--------------------------------------------------------------------------
    | API Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the REST API layer for LFL. You can disable the API entirely
    | if you only want to use LFL through the facade.
    |
    */

    'api' => [
        'enabled' => true,
        'prefix' => 'api/lfl',
        'middleware' => ['api'],
        'auth' => [
            'guard' => env('LFL_API_GUARD', 'sanctum'),
            'middleware' => env('LFL_API_AUTH_MIDDLEWARE', null),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | UI Configuration
    |--------------------------------------------------------------------------
    |
    | The UI layer is optional and disabled by default. Enable it to use
    | the built-in Blade/Livewire components for displaying awards,
    | achievements, leaderboards, and profiles.
    |
    */

    'ui' => [
        'enabled' => false,
        'prefix' => 'lfl',
        'middleware' => ['web'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    |
    | Enable or disable specific LFL features. This allows you to only
    | use the parts of the package that your application needs.
    |
    */

    'features' => [
        'achievements' => true,
        'leaderboards' => true,
        'prizes' => true,
        'profiles' => true,
        'analytics' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Point Values
    |--------------------------------------------------------------------------
    |
    | Define default point amounts for common actions. These can be overridden
    | when calling LFL::award() by specifying an explicit amount.
    |
    */

    'defaults' => [
        // Default points when no amount is specified
        'points' => 10,

        // Multipliers for different contexts (e.g., streaks, bonuses)
        'multipliers' => [
            'streak_bonus' => 1.5,
            'first_time_bonus' => 2.0,
        ],

        // Maximum points per action (0 = unlimited)
        'max_points_per_action' => 0,
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Award Types
    |--------------------------------------------------------------------------
    |
    | Pre-configured award types that are available out of the box.
    | You can add custom types via LFL::setup() or by extending this array.
    |
    */

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

    /*
    |--------------------------------------------------------------------------
    | Event Configuration
    |--------------------------------------------------------------------------
    |
    | Configure how LFL events are dispatched. Events are useful for
    | triggering notifications, analytics, or custom business logic.
    |
    */

    'events' => [
        // Master switch for event dispatching
        'dispatch' => true,

        // Log all award events to the database for analytics
        'log_to_database' => true,

        // Queue name for async event processing (null = sync)
        'queue' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Service Bindings
    |--------------------------------------------------------------------------
    |
    | Configure custom implementations for LFL services. This allows you to
    | swap out the default implementations with your own custom classes.
    |
    | Example:
    | 'services' => [
    |     'award_engine' => \App\Services\CustomAwardEngine::class,
    |     'leaderboard' => \App\Services\CustomLeaderboardService::class,
    |     'analytics' => \App\Services\CustomAnalyticsService::class,
    | ],
    |
    */

    'services' => [
        'award_engine' => \LaravelFunLab\Services\AwardEngine::class,
        'leaderboard' => \LaravelFunLab\Builders\LeaderboardBuilder::class,
        'analytics' => \LaravelFunLab\Builders\AnalyticsBuilder::class,
    ],

];
