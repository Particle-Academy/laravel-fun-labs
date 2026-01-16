# Usage Guide

This guide provides practical examples and patterns for using Laravel Fun Lab in your application.

## Table of Contents

- [Setting Up Models](#setting-up-models)
- [Profiles](#profiles)
- [GamedMetrics (XP System)](#gamedmetrics-xp-system)
- [Achievements](#achievements)
- [Prizes](#prizes)
- [Leaderboards](#leaderboards)
- [Analytics](#analytics)
- [Common Patterns](#common-patterns)

## Setting Up Models

### Add the Awardable Trait

Add the `Awardable` trait to any Eloquent model you want to gamify:

```php
use LaravelFunLab\Traits\Awardable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Awardable;
    
    // ... your existing code
}
```

The trait works with any model - Users, Teams, Organizations, etc.:

```php
class Team extends Model
{
    use Awardable;
}

class Organization extends Model
{
    use Awardable;
}
```

### What the Awardable Trait Provides

The trait provides:
- **Profile relationship**: Each awardable model gets a Profile that tracks XP, achievements, and prizes
- **Achievement grants relationship**: `achievementGrants()`
- **Prize grants relationship**: `prizeGrants()`
- **Helper methods**: `hasAchievement()`, `getProfile()`, `isOptedIn()`, etc.

### Using Helper Methods

```php
$user = User::find(1);

// Get or create the user's profile
$profile = $user->getProfile();
echo $profile->total_xp;
echo $profile->achievement_count;
echo $profile->prize_count;

// Check if user has an achievement
if ($user->hasAchievement('first-login')) {
    // ...
}

// Check opt-in status
if ($user->isOptedIn()) {
    // User participates in gamification
}
```

## Profiles

Every awardable model has a Profile that tracks:
- **Total XP**: Sum of all XP across all GamedMetrics
- **Achievement count**: Number of achievements earned
- **Prize count**: Number of prizes awarded
- **Opt-in status**: Whether the user participates in gamification
- **Display preferences**: User preferences for how their profile is shown
- **Visibility settings**: What parts of their profile are public

### Getting a Profile

```php
// Get or create profile
$profile = $user->getProfile();

// Check if profile exists
if ($user->hasProfile()) {
    // ...
}

// Access profile data
echo $profile->total_xp;
echo $profile->achievement_count;
echo $profile->prize_count;
echo $profile->last_activity_at;
```

### Opt-In/Opt-Out

Users can opt out of gamification:

```php
// Opt out
$user->optOut();

// Opt back in
$user->optIn();

// Check status
if ($user->isOptedIn()) {
    // User participates
}

if ($user->isOptedOut()) {
    // User has opted out - they won't appear on leaderboards
}
```

## GamedMetrics (XP System)

GamedMetrics are independent XP categories. Each metric tracks its own XP and can have its own level progression.

### Creating GamedMetrics

```php
use LaravelFunLab\Facades\LFL;

// Create via setup()
LFL::setup(
    a: 'gamed-metric',
    slug: 'combat-xp',
    name: 'Combat XP',
    description: 'Experience from combat activities'
);

// Or create directly via model
use LaravelFunLab\Models\GamedMetric;

GamedMetric::create([
    'slug' => 'crafting-xp',
    'name' => 'Crafting XP',
    'description' => 'Experience from crafting items',
    'active' => true,
]);
```

### Awarding XP

```php
use LaravelFunLab\Facades\LFL;

// Award XP to a specific GamedMetric
$profileMetric = LFL::awardGamedMetric($user, 'combat-xp', 50);

// Check the result
echo $profileMetric->total_xp; // XP for this specific metric
echo $profileMetric->current_level; // Current level in this metric

// Total XP is automatically updated on the profile
echo $user->getProfile()->fresh()->total_xp;
```

### Querying XP

```php
$profile = $user->getProfile();

// Get XP for a specific metric
$combatXp = $profile->getXpFor('combat-xp');

// Get level for a specific metric
$combatLevel = $profile->getLevelFor('combat-xp');

// Get total XP across all metrics
$totalXp = $profile->total_xp;

// Get all profile metrics
$metrics = $profile->metrics; // Collection of ProfileMetric
```

### Level Progression

Define levels for GamedMetrics using MetricLevels:

```php
use LaravelFunLab\Models\MetricLevel;

// Create levels for a GamedMetric
MetricLevel::create([
    'gamed_metric_id' => $combatMetric->id,
    'level' => 1,
    'xp_required' => 0,
    'name' => 'Novice',
]);

MetricLevel::create([
    'gamed_metric_id' => $combatMetric->id,
    'level' => 2,
    'xp_required' => 100,
    'name' => 'Apprentice',
]);

MetricLevel::create([
    'gamed_metric_id' => $combatMetric->id,
    'level' => 3,
    'xp_required' => 500,
    'name' => 'Journeyman',
]);
```

Level progression happens automatically when XP is awarded.

## Achievements

### Creating Achievements

```php
use LaravelFunLab\Facades\LFL;

// Via setup()
$achievement = LFL::setup(
    an: 'first-login',
    for: 'User', // Optional: restrict to specific model type
    name: 'First Login',
    description: 'Welcome! You\'ve logged in for the first time.',
    icon: 'star'
);

// Or directly via model
use LaravelFunLab\Models\Achievement;

Achievement::create([
    'slug' => 'task-master',
    'name' => 'Task Master',
    'description' => 'Complete 100 tasks',
    'icon' => 'trophy',
    'is_active' => true,
]);
```

### Granting Achievements

```php
use LaravelFunLab\Facades\LFL;

// Grant an achievement
$result = LFL::grantAchievement($user, 'first-login', 'completed first login', 'auth');

if ($result->succeeded()) {
    echo "Achievement unlocked!";
}

// Check if already granted
if ($user->hasAchievement('first-login')) {
    // Already has this achievement
}
```

### Fluent API for Achievements

```php
$result = LFL::award('achievement')
    ->to($user)
    ->achievement('first-login')
    ->for('completed first login')
    ->from('auth-system')
    ->grant();
```

## Prizes

### Creating Prizes

```php
use LaravelFunLab\Models\Prize;
use LaravelFunLab\Enums\PrizeType;

Prize::create([
    'slug' => 'premium-month',
    'name' => '1 Month Premium Access',
    'description' => 'One month of premium features',
    'type' => PrizeType::Virtual,
    'meta' => ['duration_days' => 30],
]);

Prize::create([
    'slug' => 'tshirt',
    'name' => 'LFL T-Shirt',
    'description' => 'Exclusive branded t-shirt',
    'type' => PrizeType::Physical,
]);
```

### Awarding Prizes

```php
use LaravelFunLab\Facades\LFL;

$result = LFL::award('prize')
    ->to($user)
    ->for('won monthly contest')
    ->from('contest-system')
    ->withMeta(['prize_slug' => 'premium-month'])
    ->grant();

if ($result->succeeded()) {
    echo "Prize awarded!";
}
```

## Leaderboards

### Basic Leaderboard

```php
use LaravelFunLab\Facades\LFL;

// Get top 10 users by XP
$leaderboard = LFL::leaderboard()
    ->for(User::class)
    ->by('xp')
    ->take(10);

foreach ($leaderboard as $profile) {
    echo "{$profile->rank}. {$profile->awardable->name}: {$profile->total_xp} XP";
}
```

### Sorting Options

```php
// By XP (default)
->by('xp')

// By achievements
->by('achievements')

// By prizes
->by('prizes')
```

### Time-Based Leaderboards

```php
// Daily leaderboard
$daily = LFL::leaderboard()
    ->for(User::class)
    ->period('daily')
    ->take(10);

// Weekly leaderboard
$weekly = LFL::leaderboard()
    ->for(User::class)
    ->period('weekly')
    ->take(10);

// Monthly leaderboard
$monthly = LFL::leaderboard()
    ->for(User::class)
    ->period('monthly')
    ->take(10);

// All-time
$allTime = LFL::leaderboard()
    ->for(User::class)
    ->period('all-time')
    ->take(10);
```

### Pagination

```php
$paginator = LFL::leaderboard()
    ->for(User::class)
    ->perPage(20)
    ->page(1)
    ->paginate();
```

### Include Opted-Out Users

By default, opted-out users are excluded:

```php
// Include everyone
$leaderboard = LFL::leaderboard()
    ->for(User::class)
    ->excludeOptedOut(false)
    ->get();
```

## Analytics

### Profile Statistics

```php
use LaravelFunLab\Models\Profile;

// Count profiles with XP
$activeProfiles = Profile::where('total_xp', '>', 0)->count();

// Average XP
$avgXp = Profile::avg('total_xp');

// Top earners
$topEarners = Profile::orderByDesc('total_xp')
    ->take(10)
    ->get();
```

### Achievement Completion Rate

```php
$totalProfiles = Profile::count();
$profilesWithAchievement = Profile::where('achievement_count', '>', 0)->count();
$completionRate = ($profilesWithAchievement / $totalProfiles) * 100;
```

## Common Patterns

### Award XP on Task Completion

```php
class TaskController extends Controller
{
    public function complete(Task $task)
    {
        $task->markAsComplete();
        
        // Award XP based on task difficulty
        $xp = match($task->difficulty) {
            'easy' => 10,
            'medium' => 25,
            'hard' => 50,
            default => 10,
        };
        
        LFL::awardGamedMetric(auth()->user(), 'task-xp', $xp);
        
        return back()->with('success', "Task completed! +{$xp} XP");
    }
}
```

### Grant Achievement on First Action

```php
class LoginController extends Controller
{
    public function login(Request $request)
    {
        // ... authentication logic
        
        $user = auth()->user();
        
        // Grant first-login achievement if not already granted
        if (!$user->hasAchievement('first-login')) {
            LFL::grantAchievement($user, 'first-login', 'First successful login', 'auth');
        }
        
        return redirect()->intended();
    }
}
```

### Daily Login Bonus

```php
class DailyBonusService
{
    public function checkAndAwardBonus(User $user): void
    {
        $profile = $user->getProfile();
        
        // Check if already awarded today
        if ($profile->last_activity_at?->isToday()) {
            return;
        }
        
        // Award daily bonus XP
        LFL::awardGamedMetric($user, 'daily-xp', 10);
    }
}
```

### Event-Driven Awards

```php
// In EventServiceProvider
Event::listen(OrderCompleted::class, function ($event) {
    $xp = (int) ($event->order->total / 10); // 1 XP per $10 spent
    
    LFL::awardGamedMetric($event->order->user, 'shopping-xp', $xp);
});

Event::listen(ReviewSubmitted::class, function ($event) {
    LFL::awardGamedMetric($event->review->user, 'community-xp', 5);
    
    // Grant achievement for first review
    if (!$event->review->user->hasAchievement('first-review')) {
        LFL::grantAchievement($event->review->user, 'first-review');
    }
});
```
