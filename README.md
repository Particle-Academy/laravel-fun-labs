# Laravel Fun Lab

[![Powered by Tynn](https://img.shields.io/endpoint?url=https%3A%2F%2Ftynn.ai%2Fo%2Fparticle-academy%2Flaravel-fun-lab%2Fbadge.json)](https://tynn.ai/o/particle-academy/laravel-fun-lab)
[![Latest Version](https://img.shields.io/packagist/v/particleacademy/laravel-fun-lab.svg?style=flat-square)](https://packagist.org/packages/particleacademy/laravel-fun-lab)
[![License](https://img.shields.io/packagist/l/particleacademy/laravel-fun-lab.svg?style=flat-square)](https://packagist.org/packages/particleacademy/laravel-fun-lab)
[![Laravel](https://img.shields.io/badge/Laravel-11.x%20%7C%2012.x-red.svg?style=flat-square)](https://laravel.com)

> Analytics disguised as gamification — turn user activity into meaningful engagement insights.

Laravel Fun Lab (LFL) is an analytics-driven gamification layer for Laravel applications. Track user engagement through awards, achievements, and prizes while capturing structured activity data that apps normally never track. The end result: developers get engagement data "for free," users feel recognized and motivated, and product teams gain a clearer picture of what drives long-term adoption.

## Features

- 🎯 **Event-Driven Architecture** - Every action, reward, or update is an observable event
- 🎮 **GamedMetrics XP System** - Create independent XP buckets with automatic level progression
- 🏆 **Achievements & Badges** - Define and grant achievements with auto-unlock on level-up
- 🎁 **Prizes** - Reward users with virtual or physical prizes
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

Add the `Awardable` trait to any model you want to gamify (User, Team, Organization, etc.):

```php
use LaravelFunLab\Traits\Awardable;

class User extends Authenticatable
{
    use Awardable;
    
    // ... your existing code
}

// Works with any model!
class Team extends Model
{
    use Awardable;
}
```

The `Awardable` trait gives your model a **Profile** that tracks XP, achievements, and prizes. Profiles are automatically created when needed.

### 2. Create GamedMetrics (XP Categories)

GamedMetrics are independent XP buckets. Each metric tracks its own XP and levels:

```php
use LaravelFunLab\Facades\LFL;

// Create XP categories via setup()
LFL::setup(
    a: 'gamed-metric',
    slug: 'combat-xp',
    name: 'Combat XP',
    description: 'Experience from combat activities'
);

LFL::setup(
    a: 'gamed-metric',
    slug: 'crafting-xp',
    name: 'Crafting XP',
    description: 'Experience from crafting items'
);
```

### 3. Award XP

Award XP using the `awardGamedMetric()` method:

```php
use LaravelFunLab\Facades\LFL;

// Award XP to a specific GamedMetric
$profileMetric = LFL::awardGamedMetric($user, 'combat-xp', 50);

// XP accumulates per metric and on the profile
echo $profileMetric->total_xp; // 50
echo $user->getProfile()->total_xp; // Total across all metrics
```

### 4. Set Up and Grant Achievements

Create achievements that can be granted manually or auto-unlock on level-up:

```php
// Define an achievement
LFL::setup(
    an: 'first-login',
    name: 'First Login',
    description: 'Welcome! You\'ve logged in for the first time.',
    icon: 'star'
);

// Grant the achievement
$result = LFL::grantAchievement($user, 'first-login', 'completed first login', 'auth');

// Check if user has an achievement
if ($user->hasAchievement('first-login')) {
    // ...
}
```

### 5. Award Prizes

Prizes are special rewards that can be tracked and redeemed:

```php
// Create a prize
LFL::setup(
    a: 'prize',
    slug: 'premium-access',
    name: '1 Month Premium Access',
    type: 'virtual'
);

// Award the prize
LFL::award('prize')
    ->to($user)
    ->for('won monthly contest')
    ->withMeta(['prize_slug' => 'premium-access'])
    ->grant();
```

### 6. Query Profiles and Leaderboards

```php
// Get user's profile
$profile = $user->getProfile();
echo $profile->total_xp;
echo $profile->achievement_count;
echo $profile->prize_count;

// Leaderboard by XP
$leaders = LFL::leaderboard()
    ->for(User::class)
    ->by('xp')
    ->take(10);
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

