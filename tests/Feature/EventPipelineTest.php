<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use LaravelFunLab\Events\AchievementUnlocked;
use LaravelFunLab\Events\AwardGranted;
use LaravelFunLab\Events\PrizeAwarded;
use LaravelFunLab\Facades\LFL;
use LaravelFunLab\Models\Achievement;
use LaravelFunLab\Models\EventLog;
use LaravelFunLab\Models\GamedMetric;
use LaravelFunLab\Tests\Fixtures\User;

/*
|--------------------------------------------------------------------------
| Event Pipeline Tests
|--------------------------------------------------------------------------
|
| Tests for the LFL event pipeline including specific event classes,
| event dispatching, and EventLog model for analytics.
|
*/

describe('XP Awarded Events', function () {

    beforeEach(function () {
        // Create a GamedMetric for XP tests
        GamedMetric::create([
            'slug' => 'general-xp',
            'name' => 'General XP',
            'description' => 'General experience points',
            'active' => true,
        ]);
    });

    it('can award XP to a user', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        $result = LFL::awardGamedMetric($user, 'general-xp', 50);

        expect($result->total_xp)->toBe(50);
    });

    it('accumulates XP correctly', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        LFL::awardGamedMetric($user, 'general-xp', 50);
        LFL::awardGamedMetric($user, 'general-xp', 30);

        $profile = $user->getProfile()->fresh();

        expect($profile->total_xp)->toBe(80);
    });

});

describe('AchievementUnlocked Event', function () {

    beforeEach(function () {
        Achievement::create([
            'slug' => 'first-login',
            'name' => 'First Login',
            'description' => 'Logged in for the first time',
            'is_active' => true,
        ]);
    });

    it('dispatches AchievementUnlocked when achievement is granted', function () {
        Event::fake([AchievementUnlocked::class]);

        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        LFL::grantAchievement($user, 'first-login', 'first time login', 'auth');

        Event::assertDispatched(AchievementUnlocked::class, function ($event) use ($user) {
            return $event->recipient->is($user)
                && $event->achievement->slug === 'first-login';
        });
    });

    it('includes achievement details in AchievementUnlocked event', function () {
        Event::fake([AchievementUnlocked::class]);

        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        LFL::grantAchievement($user, 'first-login');

        Event::assertDispatched(AchievementUnlocked::class, function ($event) {
            return $event->achievement->name === 'First Login'
                && $event->achievement->description === 'Logged in for the first time';
        });
    });

    it('provides achievement via property', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);
        $achievement = Achievement::where('slug', 'first-login')->first();

        $result = LFL::grantAchievement($user, 'first-login');

        $event = new AchievementUnlocked(
            recipient: $user,
            achievement: $achievement,
            grant: $result->award,
        );

        expect($event->achievement->slug)->toBe('first-login');
    });

    it('converts AchievementUnlocked to log array correctly', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);
        $achievement = Achievement::where('slug', 'first-login')->first();

        $result = LFL::grantAchievement($user, 'first-login');

        $event = new AchievementUnlocked(
            recipient: $user,
            achievement: $achievement,
            grant: $result->award,
        );

        $logArray = $event->toLogArray();

        expect($logArray)
            ->toHaveKey('event_type', 'achievement_unlocked')
            ->toHaveKey('award_type', 'achievement')
            ->toHaveKey('recipient_id', $user->id)
            ->toHaveKey('achievement_slug', 'first-login');
    });

});

describe('PrizeAwarded Event', function () {

    beforeEach(function () {
        \LaravelFunLab\Models\Prize::create([
            'slug' => 'test-prize',
            'name' => 'Test Prize',
            'type' => \LaravelFunLab\Enums\PrizeType::Virtual,
        ]);
    });

    it('dispatches PrizeAwarded when prize is awarded', function () {
        Event::fake([PrizeAwarded::class]);

        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);
        $user->getProfile();

        LFL::award('prize')
            ->to($user)
            ->for('won a contest')
            ->from('contest-system')
            ->withMeta(['prize_slug' => 'test-prize'])
            ->grant();

        Event::assertDispatched(PrizeAwarded::class, function ($event) use ($user) {
            return $event->recipient->is($user);
        });
    });

    it('includes metadata in PrizeAwarded event', function () {
        Event::fake([PrizeAwarded::class]);

        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);
        $user->getProfile();

        LFL::award('prize')
            ->to($user)
            ->for('won a contest')
            ->from('contest-system')
            ->withMeta([
                'prize_slug' => 'test-prize',
                'contest_id' => 123,
            ])
            ->grant();

        Event::assertDispatched(PrizeAwarded::class, function ($event) {
            $meta = $event->award->meta ?? [];

            return isset($meta['contest_id']) && $meta['contest_id'] === 123;
        });
    });

});

describe('Generic AwardGranted Event', function () {

    beforeEach(function () {
        Achievement::create([
            'slug' => 'test-achievement',
            'name' => 'Test Achievement',
            'is_active' => true,
        ]);
    });

    it('dispatches both generic and specific events for achievements', function () {
        Event::fake([AwardGranted::class, AchievementUnlocked::class]);

        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        LFL::grantAchievement($user, 'test-achievement');

        Event::assertDispatched(AwardGranted::class);
        Event::assertDispatched(AchievementUnlocked::class);
    });

    it('includes award type in generic event', function () {
        Event::fake([AwardGranted::class]);

        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        LFL::grantAchievement($user, 'test-achievement');

        Event::assertDispatched(AwardGranted::class, function ($event) {
            return $event->type === 'achievement';
        });
    });

});

describe('EventLog Model', function () {

    beforeEach(function () {
        // Create a GamedMetric for XP tests
        GamedMetric::create([
            'slug' => 'general-xp',
            'name' => 'General XP',
            'description' => 'General experience points',
            'active' => true,
        ]);

        Achievement::create([
            'slug' => 'test-achievement',
            'name' => 'Test Achievement',
            'is_active' => true,
        ]);

        \LaravelFunLab\Models\Prize::create([
            'slug' => 'test-prize',
            'name' => 'Test Prize',
            'type' => \LaravelFunLab\Enums\PrizeType::Virtual,
        ]);
    });

    it('logs achievement granted events to database', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        LFL::grantAchievement($user, 'test-achievement');

        $log = EventLog::where('event_type', 'achievement_unlocked')->first();

        expect($log)->not->toBeNull()
            ->and($log->award_type)->toBe('achievement')
            ->and($log->awardable_id)->toBe($user->id);
    });

    it('logs prize awarded events to database', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);
        $user->getProfile();

        LFL::award('prize')
            ->to($user)
            ->for('test prize')
            ->withMeta(['prize_slug' => 'test-prize'])
            ->grant();

        $log = EventLog::where('event_type', 'prize_awarded')->first();

        expect($log)->not->toBeNull()
            ->and($log->award_type)->toBe('prize')
            ->and($log->awardable_id)->toBe($user->id);
    });

    it('stores full event context as JSON', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        LFL::grantAchievement($user, 'test-achievement', 'completed task', 'task-system');

        $log = EventLog::where('event_type', 'achievement_unlocked')->first();

        expect($log)->not->toBeNull()
            ->and($log->context)->toBeArray()
            ->and($log->context)->toHaveKey('reason', 'completed task')
            ->and($log->context)->toHaveKey('source', 'task-system');
    });

    it('can filter logs by award type', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);
        $user->getProfile();

        LFL::grantAchievement($user, 'test-achievement');
        LFL::award('prize')
            ->to($user)
            ->for('test prize')
            ->withMeta(['prize_slug' => 'test-prize'])
            ->grant();

        $achievementLogs = EventLog::where('award_type', 'achievement')->count();
        $prizeLogs = EventLog::where('award_type', 'prize')->count();

        expect($achievementLogs)->toBe(1)
            ->and($prizeLogs)->toBe(1);
    });

    it('can filter logs by awardable', function () {
        $user1 = User::create(['name' => 'User 1', 'email' => 'user1@example.com']);
        $user2 = User::create(['name' => 'User 2', 'email' => 'user2@example.com']);

        LFL::grantAchievement($user1, 'test-achievement');

        Achievement::create([
            'slug' => 'another-achievement',
            'name' => 'Another Achievement',
            'is_active' => true,
        ]);
        LFL::grantAchievement($user2, 'another-achievement');

        $user1Logs = EventLog::where('awardable_id', $user1->id)->count();
        $user2Logs = EventLog::where('awardable_id', $user2->id)->count();

        expect($user1Logs)->toBe(1)
            ->and($user2Logs)->toBe(1);
    });

});
