# Laravel Fun Lab

[![Powered by Tynn](https://img.shields.io/endpoint?url=https%3A%2F%2Ftynn.ai%2Fo%2Fparticle-academy%2Flaravel-fun-lab%2Fbadge.json)](https://tynn.ai/o/particle-academy/laravel-fun-lab)
[![Latest Version](https://img.shields.io/packagist/v/particleacademy/laravel-fun-lab.svg?style=flat-square)](https://packagist.org/packages/particleacademy/laravel-fun-lab)
[![License](https://img.shields.io/packagist/l/particleacademy/laravel-fun-lab.svg?style=flat-square)](https://packagist.org/packages/particleacademy/laravel-fun-lab)
[![Laravel](https://img.shields.io/badge/Laravel-11.x%20%7C%2012.x-red.svg?style=flat-square)](https://laravel.com)

> Analytics disguised as gamification — turn user activity into meaningful engagement insights.

Laravel Fun Lab (LFL) is an analytics-driven gamification layer for Laravel applications. Track user engagement through awards, achievements, and prizes while capturing structured activity data that apps normally never track. The end result: developers get engagement data "for free," users feel recognized and motivated, and product teams gain a clearer picture of what drives long-term adoption.

## Features

- 🎯 **Event-Driven Architecture** - Every action, reward, or update is an observable event
- 🏆 **Flexible Award System** - Points, achievements, badges, and prizes
- 📊 **Built-in Analytics** - Query aggregate engagement data for behavioral insights
- 🎮 **Optional UI Layer** - Drop-in Blade/Livewire components (or use API-only)
- 🔌 **Extensible** - Macros, hooks, and custom implementations
- ⚡ **Drop-In Simple** - Install → Track → Award workflow

## Installation

Install the package via Composer:

```bash
composer require particleacademy/laravel-fun-lab
```

Run the installation command:

```bash
php artisan lfl:install
```

This will:
- Publish the configuration file (`config/lfl.php`)
- Run database migrations
- Optionally install UI components (`--ui` flag)

## Quick Start

### 1. Add the Awardable Trait

Add the `Awardable` trait to your User model (or any model you want to track):

```php
use LaravelFunLab\Traits\Awardable;

class User extends Authenticatable
{
    use Awardable;
    
    // ... your existing code
}
```

### 2. Award Points

Use the fluent API to award points:

```php
use LaravelFunLab\Facades\LFL;

// Award points with full context
$result = LFL::award('points')
    ->to($user)
    ->for('completed task')
    ->from('task-system')
    ->amount(50)
    ->grant();

// Or use the shorthand method
LFL::awardPoints($user, 100, 'first login', 'auth');
```

### 3. Grant Achievements

Set up achievements dynamically and grant them:

```php
// Define an achievement
LFL::setup(
    an: 'first-login',
    for: 'User',
    name: 'First Login',
    description: 'Welcome! You\'ve logged in for the first time.',
    icon: 'star'
);

// Grant the achievement
LFL::grantAchievement($user, 'first-login', 'completed first login', 'auth');
```

### 4. Query Analytics

Get insights into user engagement:

```php
use Carbon\Carbon;

// Total points awarded this month
$totalPoints = LFL::analytics()
    ->byType('points')
    ->period('monthly')
    ->total();

// Active users in the last 7 days
$activeUsers = LFL::analytics()
    ->since(Carbon::now()->subDays(7))
    ->activeUsers();

// Achievement completion rate
$completionRate = LFL::analytics()
    ->forAchievement('first-login')
    ->achievementCompletionRate();
```

## Documentation

- **[Installation Guide](docs/installation.md)** - Detailed installation and configuration
- **[Usage Guide](docs/usage.md)** - Examples and patterns for common use cases
- **[API Reference](docs/api.md)** - Complete API documentation
- **[Configuration Reference](docs/configuration.md)** - All configuration options
- **[Extension Guide](docs/extending.md)** - Custom implementations and hooks

## Requirements

- PHP 8.2 or higher
- Laravel 11.x or 12.x

## License

This package is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Support

For issues, questions, or contributions, please visit the [GitHub repository](https://github.com/particleacademy/laravel-fun-lab).

