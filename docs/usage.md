# Usage Guide

This guide provides practical examples and patterns for using Laravel Fun Lab in your application.

## Table of Contents

- [Setting Up Models](#setting-up-models)
- [Awarding Points](#awarding-points)
- [Achievements](#achievements)
- [Leaderboards](#leaderboards)
- [Profiles](#profiles)
- [Analytics](#analytics)
- [Prizes](#prizes)
- [Badges](#badges)
- [GamedMetrics & Levels](#gamedmetrics--levels)
- [Common Patterns](#common-patterns)

## Setting Up Models

### Add the Awardable Trait

Add the `Awardable` trait to any Eloquent model you want to track:

```php
use LaravelFunLab\Traits\Awardable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Awardable;
    
    // ... your existing code
}
```

The trait provides:
- Relationships: `awards()`, `achievementGrants()`
- Helper methods: `getTotalPoints()`, `hasAchievement()`, `getAchievements()`, etc.

### Using Helper Methods

```php
$user = User::find(1);

// Get total points
$totalPoints = $user->getTotalPoints();

// Check if user has an achievement
if ($user->hasAchievement('first-login')) {
    // ...
}

// Get all achievements
$achievements = $user->getAchievements();

// Get recent awards
$recentAwards = $user->getRecentAwards(10);
```

## Awarding Points

### Fluent API

Use the fluent API for full control:

```php
use LaravelFunLab\Facades\LFL;

$result = LFL::award('points')
    ->to($user)
    ->for('completed task')
    ->from('task-system')
    ->amount(50)
    ->withMeta(['task_id' => 123])
    ->grant();

if ($result->success) {
    echo "Awarded {$result->award->amount} points!";
}
```

### Shorthand Method

For quick point awards:

```php
LFL::awardPoints($user, 100, 'first login', 'auth');
```

### Awarding in Controllers

```php
use LaravelFunLab\Facades\LFL;

class TaskController extends Controller
{
    public function complete(Task $task)
    {
        $task->markAsComplete();
        
        // Award points for completion
        LFL::awardPoints(
            $this->user(),
            50,
            "Completed task: {$task->title}",
            'task-system'
        );
        
        return redirect()->back();
    }
}
```

### Using Default Points

If you don't specify an amount, the default from config is used:

```php
// Uses config('lfl.defaults.points') - default is 10
LFL::awardPoints($user, null, 'daily check-in');
```

## Achievements

### Setting Up Achievements

Define achievements dynamically:

```php
use LaravelFunLab\Facades\LFL;

// Simple achievement
$achievement = LFL::setup(an: 'first-login');

// Full achievement with all options
$achievement = LFL::setup(
    an: 'power-user',
    for: 'User',
    name: 'Power User',
    description: 'Reached 1000 total points',
    icon: 'star',
    metadata: ['threshold' => 1000],
    active: true,
    order: 10
);
```

### Granting Achievements

```php
// Using fluent API
$result = LFL::award('achievement')
    ->to($user)
    ->achievement('first-login')
    ->for('completed first login')
    ->from('auth')
    ->grant();

// Using shorthand
LFL::grantAchievement($user, 'first-login', 'completed first login', 'auth');
```

### Checking Achievements

```php
// Check if user has achievement
if ($user->hasAchievement('first-login')) {
    // User has this achievement
}

// Get all user achievements
$achievements = $user->getAchievements();

// Check achievement by model
$achievement = Achievement::where('slug', 'first-login')->first();
if ($user->hasAchievement($achievement)) {
    // ...
}
```

### Conditional Achievement Granting

```php
// Only grant if user doesn't already have it
if (!$user->hasAchievement('first-purchase')) {
    LFL::grantAchievement($user, 'first-purchase', 'made first purchase', 'store');
}
```

## Leaderboards

### Basic Leaderboard

```php
use LaravelFunLab\Facades\LFL;

// Get top 10 users by points
$leaderboard = LFL::leaderboard()
    ->for(User::class)
    ->by('points')
    ->limit(10)
    ->get();

foreach ($leaderboard as $entry) {
    echo "{$entry->name}: {$entry->points} points";
}
```

### Leaderboard by Period

```php
// Weekly leaderboard
$weekly = LFL::leaderboard()
    ->for(User::class)
    ->by('points')
    ->period('weekly')
    ->limit(20)
    ->get();

// Monthly leaderboard
$monthly = LFL::leaderboard()
    ->for(User::class)
    ->by('points')
    ->period('monthly')
    ->get();
```

### Paginated Leaderboard

```php
$leaderboard = LFL::leaderboard()
    ->for(User::class)
    ->by('points')
    ->paginate(15);

return view('leaderboard', compact('leaderboard'));
```

### Leaderboard by Achievement Count

```php
$achievementLeaders = LFL::leaderboard()
    ->for(User::class)
    ->by('achievements')
    ->limit(10)
    ->get();
```

## Profiles

### Getting or Creating Profiles

```php
use LaravelFunLab\Facades\LFL;

// Get or create profile for a user
$profile = LFL::profile($user);

// Profile provides access to:
$totalPoints = $profile->total_points;
$achievementCount = $profile->achievement_count;
```

### Profile Helper Methods

```php
$user = User::find(1);

// Get profile (creates if doesn't exist)
$profile = $user->profile();

// Access profile data
echo "Total Points: {$profile->total_points}";
echo "Achievements: {$profile->achievement_count}";
```

## Analytics

### Total Points

```php
use LaravelFunLab\Facades\LFL;
use Carbon\Carbon;

// Total points this month
$totalPoints = LFL::analytics()
    ->byType('points')
    ->period('monthly')
    ->total();

// Total points between dates
$totalPoints = LFL::analytics()
    ->byType('points')
    ->between(Carbon::now()->subDays(30), Carbon::now())
    ->total();
```

### Active Users

```php
// Active users in last 7 days
$activeUsers = LFL::analytics()
    ->since(Carbon::now()->subDays(7))
    ->activeUsers();

// Active users this month
$activeUsers = LFL::analytics()
    ->period('monthly')
    ->activeUsers();
```

### Achievement Completion Rates

```php
// Completion rate for specific achievement
$completionRate = LFL::analytics()
    ->forAchievement('first-login')
    ->achievementCompletionRate();

// Returns percentage (0-100)
echo "{$completionRate}% of users have completed first login";
```

### Time-Series Data

```php
// Daily points awarded
$dailyData = LFL::analytics()
    ->byType('points')
    ->timeSeries('day');

// Returns array of periods with counts and totals
foreach ($dailyData as $day) {
    echo "{$day['period']}: {$day['count']} awards, {$day['total']} points";
}
```

### Filtering Analytics

```php
// By source
$taskSystemPoints = LFL::analytics()
    ->byType('points')
    ->fromSource('task-system')
    ->total();

// By awardable type
$userPoints = LFL::analytics()
    ->forAwardableType(User::class)
    ->byType('points')
    ->total();
```

### Exporting Data

```php
// Export recent events
$events = LFL::analytics()
    ->since(Carbon::now()->subDays(30))
    ->export(limit: 1000);

// Returns array ready for CSV export or external analytics
```

## Prizes

### Awarding Prizes

```php
// Using fluent API
$result = LFL::award('prize')
    ->to($user)
    ->prize($prizeId)
    ->for('won contest')
    ->from('contest-system')
    ->grant();

// Using shorthand
LFL::awardPrize($user, 'won contest', 'contest-system');
```

## Badges

### Awarding Badges

```php
// Using fluent API
$result = LFL::award('badge')
    ->to($user)
    ->badge('early-adopter')
    ->for('joined in first week')
    ->from('admin')
    ->grant();

// Using shorthand
LFL::awardBadge($user, 'joined in first week', 'admin');
```

## GamedMetrics & Levels

GamedMetrics allow you to create independent XP categories that track accumulated XP separately. This enables niche leveling systems where users can progress in different areas (e.g., "Combat XP", "Crafting XP", "Social XP").

### Creating GamedMetrics

```php
use LaravelFunLab\Models\GamedMetric;

// Create a new GamedMetric
$combatMetric = GamedMetric::create([
    'name' => 'Combat XP',
    'slug' => 'combat-xp',
    'description' => 'Experience gained from combat activities',
    'icon' => 'sword',
    'active' => true,
]);
```

### Awarding GamedMetric XP

```php
use LaravelFunLab\Facades\LFL;

// Award XP to a specific GamedMetric
$userMetric = LFL::awardGamedMetric($user, 'combat-xp', 100);

// Or using the GamedMetric model directly
$combatMetric = GamedMetric::findBySlug('combat-xp');
$userMetric = LFL::awardGamedMetric($user, $combatMetric, 50);
```

### Defining MetricLevels

MetricLevels define level thresholds for GamedMetrics. When a user reaches a threshold, they automatically progress to that level.

```php
use LaravelFunLab\Models\MetricLevel;

// Create level thresholds for a GamedMetric
MetricLevel::create([
    'gamed_metric_id' => $combatMetric->id,
    'level' => 1,
    'xp_threshold' => 0,
    'name' => 'Novice Fighter',
    'description' => 'Just starting your combat journey',
]);

MetricLevel::create([
    'gamed_metric_id' => $combatMetric->id,
    'level' => 2,
    'xp_threshold' => 100,
    'name' => 'Apprentice Fighter',
    'description' => 'Learning the basics',
]);

MetricLevel::create([
    'gamed_metric_id' => $combatMetric->id,
    'level' => 3,
    'xp_threshold' => 500,
    'name' => 'Experienced Fighter',
    'description' => 'A seasoned warrior',
]);
```

### Checking Level Progression

Level progression is automatically checked when XP is awarded. You can also manually check:

```php
use LaravelFunLab\Services\MetricLevelService;

$levelService = app(MetricLevelService::class);

// Get current level
$currentLevel = $levelService->getCurrentLevel($user, 'combat-xp');

// Get next level threshold
$nextThreshold = $levelService->getNextLevelThreshold($user, 'combat-xp');

// Get progress percentage
$progress = $levelService->getProgressPercentage($user, 'combat-xp');
```

### MetricLevelGroups

MetricLevelGroups combine multiple GamedMetrics to create composite leveling systems. For example, a "Total Player Level" that combines Combat + Crafting + Social XP.

```php
use LaravelFunLab\Models\MetricLevelGroup;
use LaravelFunLab\Models\MetricLevelGroupMetric;
use LaravelFunLab\Models\MetricLevelGroupLevel;

// Create a group
$totalLevelGroup = MetricLevelGroup::create([
    'name' => 'Total Player Level',
    'slug' => 'total-player-level',
    'description' => 'Combined level from all activities',
]);

// Add metrics to the group with weights
MetricLevelGroupMetric::create([
    'metric_level_group_id' => $totalLevelGroup->id,
    'gamed_metric_id' => $combatMetric->id,
    'weight' => 1.0, // Full weight
]);

$craftingMetric = GamedMetric::findBySlug('crafting-xp');
MetricLevelGroupMetric::create([
    'metric_level_group_id' => $totalLevelGroup->id,
    'gamed_metric_id' => $craftingMetric->id,
    'weight' => 0.8, // 80% weight
]);

// Define group levels based on combined XP
MetricLevelGroupLevel::create([
    'metric_level_group_id' => $totalLevelGroup->id,
    'level' => 1,
    'xp_threshold' => 0,
    'name' => 'Beginner',
]);

MetricLevelGroupLevel::create([
    'metric_level_group_id' => $totalLevelGroup->id,
    'level' => 2,
    'xp_threshold' => 1000,
    'name' => 'Intermediate',
]);
```

### Getting Group Level Information

```php
use LaravelFunLab\Services\MetricLevelGroupService;

$groupService = app(MetricLevelGroupService::class);

// Get total combined XP
$totalXp = $groupService->getTotalXp($user, 'total-player-level');

// Get current level
$currentLevel = $groupService->getCurrentLevel($user, 'total-player-level');

// Get level info
$levelInfo = $groupService->getLevelInfo($user, 'total-player-level');
// Returns: ['current_level' => 2, 'total_xp' => 1500, 'next_level_threshold' => 5000, 'progress_percentage' => 30.0]
```

### Example: RPG-Style Leveling System

```php
use LaravelFunLab\Facades\LFL;
use LaravelFunLab\Models\GamedMetric;
use LaravelFunLab\Models\MetricLevel;

// Setup metrics
$combatMetric = GamedMetric::create(['name' => 'Combat XP', 'slug' => 'combat-xp', 'active' => true]);
$magicMetric = GamedMetric::create(['name' => 'Magic XP', 'slug' => 'magic-xp', 'active' => true]);
$stealthMetric = GamedMetric::create(['name' => 'Stealth XP', 'slug' => 'stealth-xp', 'active' => true]);

// Define levels for Combat
for ($level = 1; $level <= 10; $level++) {
    MetricLevel::create([
        'gamed_metric_id' => $combatMetric->id,
        'level' => $level,
        'xp_threshold' => ($level - 1) * 100,
        'name' => "Combat Level {$level}",
    ]);
}

// Award XP when user completes combat action
public function completeCombat(User $user, int $xpGained)
{
    LFL::awardGamedMetric($user, 'combat-xp', $xpGained);
    
    // Level progression is automatically checked
    // You can listen to events or check level after awarding
}
```

## Common Patterns

### Award Points on Model Events

```php
use LaravelFunLab\Facades\LFL;

class Task extends Model
{
    protected static function booted()
    {
        static::created(function ($task) {
            LFL::awardPoints(
                $task->user,
                10,
                "Created task: {$task->title}",
                'task-system'
            );
        });
        
        static::updated(function ($task) {
            if ($task->isDirty('completed_at') && $task->completed_at) {
                LFL::awardPoints(
                    $task->user,
                    50,
                    "Completed task: {$task->title}",
                    'task-system'
                );
            }
        });
    }
}
```

### Conditional Achievement Based on Points

```php
use LaravelFunLab\Facades\LFL;

class User extends Authenticatable
{
    use Awardable;
    
    public function awardPoints($amount, $reason, $source)
    {
        $result = LFL::awardPoints($this, $amount, $reason, $source);
        
        // Check for milestone achievements
        $totalPoints = $this->getTotalPoints();
        
        if ($totalPoints >= 1000 && !$this->hasAchievement('power-user')) {
            LFL::grantAchievement($this, 'power-user', 'reached 1000 points', 'system');
        }
        
        return $result;
    }
}
```

### Daily Streak Tracking

```php
use LaravelFunLab\Facades\LFL;
use Carbon\Carbon;

class DailyCheckIn
{
    public function checkIn(User $user)
    {
        $lastCheckIn = $user->last_check_in_at;
        $streak = $user->current_streak ?? 0;
        
        if ($lastCheckIn && $lastCheckIn->isToday()) {
            return; // Already checked in today
        }
        
        if ($lastCheckIn && $lastCheckIn->isYesterday()) {
            $streak++;
        } else {
            $streak = 1;
        }
        
        $user->update([
            'last_check_in_at' => Carbon::now(),
            'current_streak' => $streak,
        ]);
        
        // Award points with streak bonus
        $basePoints = 10;
        $bonusMultiplier = LFL::getMultiplier('streak_bonus');
        $points = $basePoints * (1 + ($streak - 1) * ($bonusMultiplier - 1));
        
        LFL::awardPoints($user, $points, "Daily check-in (streak: {$streak})", 'check-in');
        
        // Grant streak achievements
        if ($streak === 7 && !$user->hasAchievement('week-streak')) {
            LFL::grantAchievement($user, 'week-streak', '7 day streak', 'check-in');
        }
    }
}
```

### Event Listeners

```php
use LaravelFunLab\Events\PointsAwarded;
use LaravelFunLab\Events\AchievementUnlocked;

class AwardNotificationListener
{
    public function handle(PointsAwarded $event)
    {
        // Send notification when points are awarded
        $event->recipient->notify(
            new PointsAwardedNotification($event->amount, $event->reason)
        );
    }
    
    public function handleAchievement(AchievementUnlocked $event)
    {
        // Send notification when achievement is unlocked
        $event->recipient->notify(
            new AchievementUnlockedNotification($event->achievement)
        );
    }
}
```

### API Integration

```php
use LaravelFunLab\Facades\LFL;

class ApiController extends Controller
{
    public function awardPoints(Request $request)
    {
        $user = $request->user();
        
        $result = LFL::awardPoints(
            $user,
            $request->input('amount'),
            $request->input('reason'),
            'api'
        );
        
        return response()->json([
            'success' => $result->success,
            'points' => $result->award->amount ?? null,
            'total' => $user->getTotalPoints(),
        ]);
    }
}
```

## Next Steps

- [API Reference](api.md) - Complete API documentation
- [Configuration Reference](configuration.md) - All configuration options
- [Extension Guide](extending.md) - Custom implementations and hooks

