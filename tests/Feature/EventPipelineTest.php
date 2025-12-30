<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use LaravelFunLab\Events\AchievementUnlocked;
use LaravelFunLab\Events\AwardGranted;
use LaravelFunLab\Events\BadgeAwarded;
use LaravelFunLab\Events\PointsAwarded;
use LaravelFunLab\Events\PrizeAwarded;
use LaravelFunLab\Facades\LFL;
use LaravelFunLab\Models\Achievement;
use LaravelFunLab\Models\EventLog;
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

describe('PointsAwarded Event', function () {

    it('dispatches PointsAwarded when points are awarded', function () {
        Event::fake([PointsAwarded::class]);

        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        LFL::awardPoints($user, 50, 'task completion', 'task-system');

        Event::assertDispatched(PointsAwarded::class, function ($event) use ($user) {
            return $event->recipient->is($user)
                && $event->amount === 50.0
                && $event->reason === 'task completion'
                && $event->source === 'task-system';
        });
    });

    it('includes previous and new totals in PointsAwarded event', function () {
        Event::fake([PointsAwarded::class]);

        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        // First award (manually to set up state)
        config(['lfl.events.dispatch' => false]);
        LFL::awardPoints($user, 100);
        config(['lfl.events.dispatch' => true]);

        // Second award - this will dispatch the event
        LFL::awardPoints($user, 50);

        Event::assertDispatched(PointsAwarded::class, function ($event) {
            return $event->previousTotal === 100.0
                && $event->newTotal === 150.0;
        });
    });

    it('converts PointsAwarded to log array correctly', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        $result = LFL::awardPoints($user, 25, 'test reason', 'test-source');

        $event = new PointsAwarded(
            recipient: $user,
            award: $result->award,
            amount: 25.0,
            reason: 'test reason',
            source: 'test-source',
            previousTotal: 0,
            newTotal: 25.0,
        );

        $logArray = $event->toLogArray();

        expect($logArray)
            ->toHaveKey('event_type', 'points_awarded')
            ->toHaveKey('award_type', 'points')
            ->toHaveKey('recipient_id', $user->id)
            ->toHaveKey('amount', 25.0)
            ->toHaveKey('reason', 'test reason')
            ->toHaveKey('source', 'test-source');
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

        LFL::grantAchievement($user, 'first-login', 'user logged in', 'auth-system');

        Event::assertDispatched(AchievementUnlocked::class, function ($event) use ($user) {
            return $event->recipient->is($user)
                && $event->achievement->slug === 'first-login'
                && $event->reason === 'user logged in'
                && $event->source === 'auth-system';
        });
    });

    it('includes achievement details in AchievementUnlocked event', function () {
        Event::fake([AchievementUnlocked::class]);

        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        LFL::grantAchievement($user, 'first-login');

        Event::assertDispatched(AchievementUnlocked::class, function ($event) {
            return $event->getAchievementSlug() === 'first-login'
                && $event->getAchievementName() === 'First Login';
        });
    });

    it('does not dispatch AchievementUnlocked when achievement already granted', function () {
        Event::fake([AchievementUnlocked::class]);

        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        // Grant once
        config(['lfl.events.dispatch' => false]);
        LFL::grantAchievement($user, 'first-login');
        config(['lfl.events.dispatch' => true]);

        Event::fake([AchievementUnlocked::class]);

        // Try to grant again
        LFL::grantAchievement($user, 'first-login');

        Event::assertNotDispatched(AchievementUnlocked::class);
    });

});

describe('PrizeAwarded Event', function () {

    it('dispatches PrizeAwarded when prize is awarded', function () {
        Event::fake([PrizeAwarded::class]);

        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        LFL::awardPrize($user, 'contest winner', 'contest-system');

        Event::assertDispatched(PrizeAwarded::class, function ($event) use ($user) {
            return $event->recipient->is($user)
                && $event->reason === 'contest winner'
                && $event->source === 'contest-system';
        });
    });

    it('includes metadata in PrizeAwarded event', function () {
        Event::fake([PrizeAwarded::class]);

        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        LFL::award('prize')
            ->to($user)
            ->for('grand prize')
            ->withMeta(['prize_type' => 'gift_card', 'value' => 100])
            ->grant();

        Event::assertDispatched(PrizeAwarded::class, function ($event) {
            return $event->meta['prize_type'] === 'gift_card'
                && $event->meta['value'] === 100;
        });
    });

});

describe('BadgeAwarded Event', function () {

    it('dispatches BadgeAwarded when badge is awarded', function () {
        Event::fake([BadgeAwarded::class]);

        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        LFL::awardBadge($user, 'early-adopter', 'onboarding');

        Event::assertDispatched(BadgeAwarded::class, function ($event) use ($user) {
            return $event->recipient->is($user)
                && $event->reason === 'early-adopter'
                && $event->source === 'onboarding';
        });
    });

    it('provides badge identifier via helper method', function () {
        Event::fake([BadgeAwarded::class]);

        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        LFL::awardBadge($user, 'verified-user');

        Event::assertDispatched(BadgeAwarded::class, function ($event) {
            return $event->getBadgeIdentifier() === 'verified-user';
        });
    });

});

describe('Generic AwardGranted Event', function () {

    it('dispatches both generic and specific events for points', function () {
        Event::fake([AwardGranted::class, PointsAwarded::class]);

        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        LFL::awardPoints($user, 50);

        Event::assertDispatched(AwardGranted::class);
        Event::assertDispatched(PointsAwarded::class);
    });

    it('dispatches both generic and specific events for achievements', function () {
        Achievement::create([
            'slug' => 'test-achievement',
            'name' => 'Test Achievement',
            'is_active' => true,
        ]);

        Event::fake([AwardGranted::class, AchievementUnlocked::class]);

        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        LFL::grantAchievement($user, 'test-achievement');

        Event::assertDispatched(AwardGranted::class);
        Event::assertDispatched(AchievementUnlocked::class);
    });

});

describe('EventLog Model', function () {

    it('logs points awarded events to database', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        LFL::awardPoints($user, 75, 'test award', 'test-source');

        $log = EventLog::latest()->first();

        expect($log)
            ->not->toBeNull()
            ->event_type->toBe('points_awarded')
            ->award_type->toBe('points')
            ->awardable_id->toBe($user->id)
            ->amount->toBe(75.0)
            ->reason->toBe('test award')
            ->source->toBe('test-source');
    });

    it('logs achievement unlocked events to database', function () {
        Achievement::create([
            'slug' => 'logged-achievement',
            'name' => 'Logged Achievement',
            'is_active' => true,
        ]);

        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        LFL::grantAchievement($user, 'logged-achievement');

        $log = EventLog::ofEventType('achievement_unlocked')->first();

        expect($log)
            ->not->toBeNull()
            ->event_type->toBe('achievement_unlocked')
            ->award_type->toBe('achievement')
            ->achievement_slug->toBe('logged-achievement');
    });

    it('logs badge awarded events to database', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        LFL::awardBadge($user, 'test-badge', 'badge-system');

        $log = EventLog::ofEventType('badge_awarded')->first();

        expect($log)
            ->not->toBeNull()
            ->event_type->toBe('badge_awarded')
            ->award_type->toBe('badge')
            ->source->toBe('badge-system');
    });

    it('logs prize awarded events to database', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        LFL::awardPrize($user, 'prize reason', 'prize-source');

        $log = EventLog::ofEventType('prize_awarded')->first();

        expect($log)
            ->not->toBeNull()
            ->event_type->toBe('prize_awarded')
            ->award_type->toBe('prize')
            ->reason->toBe('prize reason');
    });

    it('stores full event context as JSON', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        LFL::awardPoints($user, 100, 'bonus points', 'admin');

        $log = EventLog::latest()->first();

        expect($log->context)
            ->toBeArray()
            ->toHaveKey('event_type', 'points_awarded')
            ->toHaveKey('amount', 100.0)
            ->toHaveKey('occurred_at');
    });

    it('can filter logs by award type', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        LFL::awardPoints($user, 50);
        LFL::awardBadge($user, 'test-badge');
        LFL::awardPoints($user, 25);

        $pointsLogs = EventLog::ofAwardType('points')->count();
        $badgeLogs = EventLog::ofAwardType('badge')->count();

        expect($pointsLogs)->toBe(2)
            ->and($badgeLogs)->toBe(1);
    });

    it('can filter logs by awardable', function () {
        $user1 = User::create(['name' => 'User 1', 'email' => 'user1@example.com']);
        $user2 = User::create(['name' => 'User 2', 'email' => 'user2@example.com']);

        LFL::awardPoints($user1, 50);
        LFL::awardPoints($user1, 25);
        LFL::awardPoints($user2, 100);

        $user1Logs = EventLog::forAwardable($user1)->count();
        $user2Logs = EventLog::forAwardable($user2)->count();

        expect($user1Logs)->toBe(2)
            ->and($user2Logs)->toBe(1);
    });

    it('can filter logs by source', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        LFL::awardPoints($user, 50, 'reason1', 'source-a');
        LFL::awardPoints($user, 25, 'reason2', 'source-b');
        LFL::awardPoints($user, 75, 'reason3', 'source-a');

        $sourceALogs = EventLog::fromSource('source-a')->count();
        $sourceBLogs = EventLog::fromSource('source-b')->count();

        expect($sourceALogs)->toBe(2)
            ->and($sourceBLogs)->toBe(1);
    });

    it('can filter recent logs', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        LFL::awardPoints($user, 50);

        $recentLogs = EventLog::recent(7)->count();
        $oldLogs = EventLog::recent(0)->count();

        expect($recentLogs)->toBe(1)
            ->and($oldLogs)->toBe(0);
    });

    it('does not log events when disabled in config', function () {
        config(['lfl.events.log_to_database' => false]);

        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        LFL::awardPoints($user, 50);

        expect(EventLog::count())->toBe(0);

        // Re-enable for other tests
        config(['lfl.events.log_to_database' => true]);
    });

});

describe('Event Pipeline Configuration', function () {

    it('does not dispatch any events when dispatch is disabled', function () {
        Event::fake([AwardGranted::class, PointsAwarded::class]);

        config(['lfl.events.dispatch' => false]);

        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        LFL::awardPoints($user, 50);

        Event::assertNotDispatched(AwardGranted::class);
        Event::assertNotDispatched(PointsAwarded::class);

        // Re-enable for other tests
        config(['lfl.events.dispatch' => true]);
    });

});

describe('LflEvent Contract', function () {

    it('all specific events implement LflEvent contract', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);
        $result = LFL::awardPoints($user, 50, 'test', 'source');

        $pointsEvent = new PointsAwarded($user, $result->award, 50, 'test', 'source');
        $badgeEvent = new BadgeAwarded($user, $result->award, 'badge', 'source');
        $prizeEvent = new PrizeAwarded($user, $result->award, 'prize', 'source');

        expect($pointsEvent)->toBeInstanceOf(\LaravelFunLab\Contracts\LflEvent::class)
            ->and($badgeEvent)->toBeInstanceOf(\LaravelFunLab\Contracts\LflEvent::class)
            ->and($prizeEvent)->toBeInstanceOf(\LaravelFunLab\Contracts\LflEvent::class);
    });

    it('events provide consistent interface methods', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);
        $result = LFL::awardPoints($user, 50, 'test reason', 'test source');

        $event = new PointsAwarded($user, $result->award, 50, 'test reason', 'test source');

        expect($event->getAwardType()->value)->toBe('points')
            ->and($event->getRecipient())->toBe($user)
            ->and($event->getReason())->toBe('test reason')
            ->and($event->getSource())->toBe('test source')
            ->and($event->toLogArray())->toBeArray();
    });

});
