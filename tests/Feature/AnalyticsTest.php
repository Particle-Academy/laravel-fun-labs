<?php

declare(strict_types=1);

use Carbon\Carbon;
use LaravelFunLab\Enums\AwardType;
use LaravelFunLab\Facades\LFL;
use LaravelFunLab\Models\Achievement;
use LaravelFunLab\Models\EventLog;
use LaravelFunLab\Tests\Fixtures\User;

/*
|--------------------------------------------------------------------------
| Analytics Tests
|--------------------------------------------------------------------------
|
| Tests for the analytics builder and query methods.
| Covers aggregations, time-series queries, filtering, and export functionality.
|
*/

describe('AnalyticsBuilder', function () {

    it('can access analytics via LFL facade', function () {
        $builder = LFL::analytics();

        expect($builder)->toBeInstanceOf(\LaravelFunLab\Builders\AnalyticsBuilder::class);
    });

    it('returns a query builder instance', function () {
        $query = LFL::analytics()->query();

        expect($query)->toBeInstanceOf(\Illuminate\Database\Eloquent\Builder::class);
    });

});

describe('Analytics Filtering', function () {

    beforeEach(function () {
        $this->user1 = User::create(['name' => 'User 1', 'email' => 'user1@example.com']);
        $this->user2 = User::create(['name' => 'User 2', 'email' => 'user2@example.com']);

        // Create some awards with different types and sources
        LFL::awardPoints($this->user1, 50, 'task completion', 'task-system');
        LFL::awardPoints($this->user2, 30, 'task completion', 'task-system');
        LFL::awardPoints($this->user1, 20, 'bonus', 'admin');
    });

    it('can filter by award type', function () {
        $count = LFL::analytics()
            ->byType(AwardType::Points)
            ->count();

        expect($count)->toBe(3);
    });

    it('can filter by awardable type', function () {
        $count = LFL::analytics()
            ->forAwardableType(User::class)
            ->count();

        expect($count)->toBe(3);
    });

    it('can filter by source', function () {
        $count = LFL::analytics()
            ->fromSource('task-system')
            ->count();

        expect($count)->toBe(2);
    });

    it('can filter by date period', function () {
        // Create an old award
        $oldDate = Carbon::now()->subDays(10);
        EventLog::create([
            'event_type' => 'points_awarded',
            'award_type' => 'points',
            'awardable_type' => User::class,
            'awardable_id' => $this->user1->id,
            'amount' => 100,
            'occurred_at' => $oldDate,
        ]);

        $recentCount = LFL::analytics()
            ->period('weekly')
            ->count();

        expect($recentCount)->toBe(3); // Only recent awards
    });

    it('can filter between specific dates', function () {
        $start = Carbon::now()->subDays(5);
        $end = Carbon::now()->subDays(2);

        // Create awards in the date range
        EventLog::create([
            'event_type' => 'points_awarded',
            'award_type' => 'points',
            'awardable_type' => User::class,
            'awardable_id' => $this->user1->id,
            'amount' => 25,
            'occurred_at' => Carbon::now()->subDays(3),
        ]);

        $count = LFL::analytics()
            ->between($start, $end)
            ->count();

        expect($count)->toBe(1);
    });

    it('can filter by achievement slug', function () {
        $achievement = LFL::setup('first-login', for: User::class);
        LFL::grantAchievement($this->user1, 'first-login');

        $count = LFL::analytics()
            ->forAchievement('first-login')
            ->count();

        expect($count)->toBe(1);
    });

    it('can chain multiple filters', function () {
        $total = LFL::analytics()
            ->byType(AwardType::Points)
            ->fromSource('task-system')
            ->forAwardableType(User::class)
            ->total();

        expect($total)->toBe(80.0); // 50 + 30
    });

});

describe('Analytics Aggregations', function () {

    beforeEach(function () {
        $this->user1 = User::create(['name' => 'User 1', 'email' => 'user1@example.com']);
        $this->user2 = User::create(['name' => 'User 2', 'email' => 'user2@example.com']);

        LFL::awardPoints($this->user1, 50);
        LFL::awardPoints($this->user1, 30);
        LFL::awardPoints($this->user2, 20);
    });

    it('can count total events', function () {
        $count = LFL::analytics()
            ->byType(AwardType::Points)
            ->count();

        expect($count)->toBe(3);
    });

    it('can calculate total amount', function () {
        $total = LFL::analytics()
            ->byType(AwardType::Points)
            ->total();

        expect($total)->toBe(100.0);
    });

    it('can calculate average amount', function () {
        $average = LFL::analytics()
            ->byType(AwardType::Points)
            ->average();

        expect($average)->toBeGreaterThan(33.0)
            ->and($average)->toBeLessThan(34.0); // 100 / 3 ≈ 33.33
    });

    it('can find minimum amount', function () {
        $min = LFL::analytics()
            ->byType(AwardType::Points)
            ->min();

        expect($min)->toBe(20.0);
    });

    it('can find maximum amount', function () {
        $max = LFL::analytics()
            ->byType(AwardType::Points)
            ->max();

        expect($max)->toBe(50.0);
    });

});

describe('Analytics Grouping', function () {

    beforeEach(function () {
        $this->user1 = User::create(['name' => 'User 1', 'email' => 'user1@example.com']);
        $this->user2 = User::create(['name' => 'User 2', 'email' => 'user2@example.com']);

        LFL::awardPoints($this->user1, 50, 'task', 'system1');
        LFL::awardPoints($this->user2, 30, 'task', 'system1');
        LFL::awardPoints($this->user1, 20, 'bonus', 'system2');
    });

    it('can group by award type', function () {
        $results = LFL::analytics()
            ->byAwardType();

        expect($results)->toHaveKey('points')
            ->and($results['points']['count'])->toBe(3)
            ->and($results['points']['total'])->toBe(100.0);
    });

    it('can group by awardable type', function () {
        $results = LFL::analytics()
            ->byAwardableType();

        expect($results)->toHaveKey(User::class)
            ->and($results[User::class]['count'])->toBe(3)
            ->and($results[User::class]['unique_awardables'])->toBe(2);
    });

    it('can group by source', function () {
        $results = LFL::analytics()
            ->bySource();

        expect($results)->toHaveKey('system1')
            ->and($results['system1']['count'])->toBe(2)
            ->and($results['system1']['total'])->toBe(80.0)
            ->and($results)->toHaveKey('system2')
            ->and($results['system2']['count'])->toBe(1)
            ->and($results['system2']['total'])->toBe(20.0);
    });

});

describe('Time-Series Analytics', function () {

    beforeEach(function () {
        $this->user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        // Create events at different times
        EventLog::create([
            'event_type' => 'points_awarded',
            'award_type' => 'points',
            'awardable_type' => User::class,
            'awardable_id' => $this->user->id,
            'amount' => 10,
            'occurred_at' => Carbon::now()->subDays(2),
        ]);

        EventLog::create([
            'event_type' => 'points_awarded',
            'award_type' => 'points',
            'awardable_type' => User::class,
            'awardable_id' => $this->user->id,
            'amount' => 20,
            'occurred_at' => Carbon::now()->subDays(1),
        ]);

        EventLog::create([
            'event_type' => 'points_awarded',
            'award_type' => 'points',
            'awardable_type' => User::class,
            'awardable_id' => $this->user->id,
            'amount' => 30,
            'occurred_at' => Carbon::now(),
        ]);
    });

    it('can generate time-series data by day', function () {
        $series = LFL::analytics()
            ->byType(AwardType::Points)
            ->timeSeries('day');

        expect($series)->toBeArray()
            ->and(count($series))->toBeGreaterThanOrEqual(3);
    });

    it('can generate time-series data by hour', function () {
        $series = LFL::analytics()
            ->byType(AwardType::Points)
            ->timeSeries('hour');

        expect($series)->toBeArray();
    });

    it('can generate time-series data by month', function () {
        $series = LFL::analytics()
            ->byType(AwardType::Points)
            ->timeSeries('month');

        expect($series)->toBeArray();
    });

    it('returns time-series data in correct format', function () {
        $series = LFL::analytics()
            ->byType(AwardType::Points)
            ->timeSeries('day');

        if (count($series) > 0) {
            expect($series[0])->toHaveKeys(['period', 'count', 'total']);
        }
    });

});

describe('Active Users Analytics', function () {

    beforeEach(function () {
        $this->user1 = User::create(['name' => 'User 1', 'email' => 'user1@example.com']);
        $this->user2 = User::create(['name' => 'User 2', 'email' => 'user2@example.com']);
        $this->user3 = User::create(['name' => 'User 3', 'email' => 'user3@example.com']);

        LFL::awardPoints($this->user1, 50);
        LFL::awardPoints($this->user2, 30);
        LFL::awardPoints($this->user1, 20); // Same user again
    });

    it('can count active users', function () {
        $activeUsers = LFL::analytics()
            ->byType(AwardType::Points)
            ->activeUsers();

        expect($activeUsers)->toBe(2); // user1 and user2, not user3
    });

    it('can count active users in a period', function () {
        // Create an old award
        EventLog::create([
            'event_type' => 'points_awarded',
            'award_type' => 'points',
            'awardable_type' => User::class,
            'awardable_id' => $this->user3->id,
            'amount' => 10,
            'occurred_at' => Carbon::now()->subDays(10),
        ]);

        $recentActive = LFL::analytics()
            ->byType(AwardType::Points)
            ->period('weekly')
            ->activeUsers();

        expect($recentActive)->toBe(2); // Only user1 and user2 in recent period
    });

});

describe('Achievement Completion Rate', function () {

    beforeEach(function () {
        $this->user1 = User::create(['name' => 'User 1', 'email' => 'user1@example.com']);
        $this->user2 = User::create(['name' => 'User 2', 'email' => 'user2@example.com']);
        $this->user3 = User::create(['name' => 'User 3', 'email' => 'user3@example.com']);

        $achievement = LFL::setup('first-login', for: User::class);

        // Grant achievement to 2 out of 3 users
        LFL::grantAchievement($this->user1, 'first-login');
        LFL::grantAchievement($this->user2, 'first-login');

        // Create some other activity for user3
        LFL::awardPoints($this->user3, 10);
    });

    it('can calculate achievement completion rate', function () {
        $rate = LFL::analytics()
            ->forAchievement('first-login')
            ->achievementCompletionRate();

        // Should be 2 out of 3 users (66.67%)
        expect($rate)->toBeGreaterThan(60.0)
            ->and($rate)->toBeLessThan(70.0);
    });

    it('can calculate completion rate with slug parameter', function () {
        $rate = LFL::analytics()
            ->achievementCompletionRate('first-login');

        expect($rate)->toBeGreaterThan(0);
    });

});

describe('Export Functionality', function () {

    beforeEach(function () {
        $this->user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        LFL::awardPoints($this->user, 50, 'task completion', 'task-system');
    });

    it('can export analytics data', function () {
        $export = LFL::analytics()
            ->byType(AwardType::Points)
            ->export();

        expect($export)->toBeArray()
            ->and(count($export))->toBeGreaterThan(0)
            ->and($export[0])->toHaveKeys([
                'id',
                'event_type',
                'award_type',
                'awardable_type',
                'awardable_id',
                'achievement_slug',
                'amount',
                'reason',
                'source',
                'occurred_at',
                'context',
            ]);
    });

    it('can limit export results', function () {
        // Create multiple events
        for ($i = 0; $i < 5; $i++) {
            LFL::awardPoints($this->user, 10);
        }

        $export = LFL::analytics()
            ->byType(AwardType::Points)
            ->export(limit: 3);

        expect(count($export))->toBeLessThanOrEqual(3);
    });

    it('exports data in ISO8601 format for dates', function () {
        $export = LFL::analytics()
            ->byType(AwardType::Points)
            ->export();

        if (count($export) > 0 && $export[0]['occurred_at'] !== null) {
            // Should be ISO8601 format
            expect($export[0]['occurred_at'])->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/');
        }
    });

});
