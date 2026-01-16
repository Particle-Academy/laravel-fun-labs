<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use LaravelFunLab\Enums\AwardType;
use LaravelFunLab\Events\AchievementUnlocked;
use LaravelFunLab\Events\PrizeAwarded;
use LaravelFunLab\Events\XpAwarded;
use LaravelFunLab\Facades\LFL;
use LaravelFunLab\Models\Achievement;
use LaravelFunLab\Models\GamedMetric;
use LaravelFunLab\Models\Prize;
use LaravelFunLab\Services\AwardXpBuilder;
use LaravelFunLab\Services\GrantBuilder;
use LaravelFunLab\Tests\Fixtures\User;

/*
|--------------------------------------------------------------------------
| Award Engine Tests
|--------------------------------------------------------------------------
|
| Tests for the unified award API:
| - LFL::award($metric) - Award XP to a GamedMetric
| - LFL::grant($slug) - Grant an Achievement or Prize
| - LFL::hasLevel($user, $level, metric: or group:) - Check level
|
*/

describe('AwardEngine Facade', function () {

    it('can access the LFL facade', function () {
        expect(LFL::getFacadeRoot())->toBeInstanceOf(\LaravelFunLab\Services\AwardEngine::class);
    });

    it('returns an AwardXpBuilder when calling award() with a metric slug', function () {
        GamedMetric::create([
            'slug' => 'test-metric',
            'name' => 'Test Metric',
            'active' => true,
        ]);

        $builder = LFL::award('test-metric');

        expect($builder)->toBeInstanceOf(AwardXpBuilder::class);
    });

    it('returns a GrantBuilder when calling grant() with a slug', function () {
        Achievement::create([
            'slug' => 'test-achievement',
            'name' => 'Test Achievement',
            'is_active' => true,
        ]);

        $builder = LFL::grant('test-achievement');

        expect($builder)->toBeInstanceOf(GrantBuilder::class);
    });

});

describe('GamedMetric XP Awards', function () {

    it('can award XP to a GamedMetric using fluent API', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'gamed-metric-test@example.com']);

        GamedMetric::create([
            'slug' => 'test-combat-xp',
            'name' => 'Combat XP',
            'description' => 'XP from combat',
            'active' => true,
        ]);

        $profileMetric = LFL::award('test-combat-xp')
            ->to($user)
            ->because('defeated enemy')
            ->amount(50)
            ->save();

        expect($profileMetric->total_xp)->toBe(50)
            ->and($profileMetric->current_level)->toBe(1);
    });

    it('accumulates XP correctly across multiple awards', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'gamed-metric-accumulate@example.com']);

        GamedMetric::create([
            'slug' => 'test-crafting-xp',
            'name' => 'Crafting XP',
            'active' => true,
        ]);

        LFL::award('test-crafting-xp')->to($user)->amount(30)->save();
        $profileMetric = LFL::award('test-crafting-xp')->to($user)->amount(20)->save();

        expect($profileMetric->total_xp)->toBe(50);
    });

    it('updates profile total XP', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'profile-xp@example.com']);

        GamedMetric::create([
            'slug' => 'test-profile-xp',
            'name' => 'Profile XP',
            'active' => true,
        ]);

        LFL::award('test-profile-xp')->to($user)->amount(100)->save();

        $user->refresh();
        expect($user->getTotalXp())->toBe(100);
    });

    it('throws exception when GamedMetric is inactive', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'gamed-metric-inactive@example.com']);

        GamedMetric::create([
            'slug' => 'inactive-xp',
            'name' => 'Inactive XP',
            'active' => false,
        ]);

        LFL::award('inactive-xp')->to($user)->amount(50)->save();
    })->throws(\InvalidArgumentException::class, 'not active');

    it('throws exception for non-existent GamedMetric slug', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'non-existent@example.com']);

        LFL::award('non-existent-metric')->to($user)->amount(50)->save();
    })->throws(\InvalidArgumentException::class, 'not found');

    it('throws exception when no recipient is specified', function () {
        GamedMetric::create([
            'slug' => 'no-recipient-test',
            'name' => 'No Recipient Test',
            'active' => true,
        ]);

        LFL::award('no-recipient-test')->amount(50)->save();
    })->throws(\InvalidArgumentException::class, 'Recipient is required');

    it('throws exception when amount is zero or negative', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'zero-amount@example.com']);

        GamedMetric::create([
            'slug' => 'zero-amount-test',
            'name' => 'Zero Amount Test',
            'active' => true,
        ]);

        LFL::award('zero-amount-test')->to($user)->amount(0)->save();
    })->throws(\InvalidArgumentException::class, 'Amount must be greater than 0');

});

describe('Achievement Grants', function () {

    it('can grant an achievement using fluent API', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);
        Achievement::create([
            'slug' => 'first-login',
            'name' => 'First Login',
            'is_active' => true,
        ]);

        $result = LFL::grant('first-login')
            ->to($user)
            ->because('completed first login')
            ->save();

        expect($result->succeeded())->toBeTrue()
            ->and($user->hasAchievement('first-login'))->toBeTrue();
    });

    it('fails when achievement does not exist', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        $result = LFL::grant('non-existent')
            ->to($user)
            ->save();

        expect($result->failed())->toBeTrue()
            ->and($result->message)->toContain('found');
    });

    it('fails when achievement is inactive', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);
        Achievement::create([
            'slug' => 'inactive-achievement',
            'name' => 'Inactive',
            'is_active' => false,
        ]);

        $result = LFL::grant('inactive-achievement')
            ->to($user)
            ->save();

        expect($result->failed())->toBeTrue()
            ->and($result->message)->toContain('not active');
    });

    it('fails when achievement already granted', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);
        Achievement::create([
            'slug' => 'one-time',
            'name' => 'One Time Only',
            'is_active' => true,
        ]);

        // Grant first time
        LFL::grant('one-time')->to($user)->save();

        // Try to grant again
        $result = LFL::grant('one-time')->to($user)->save();

        expect($result->failed())->toBeTrue()
            ->and($result->message)->toContain('already granted');
    });

});

describe('Prize Grants', function () {

    it('can grant a prize using fluent API', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);
        Prize::create([
            'slug' => 'gold-medal',
            'name' => 'Gold Medal',
            'type' => 'virtual',
            'is_active' => true,
        ]);

        $result = LFL::grant('gold-medal')
            ->to($user)
            ->because('winning competition')
            ->save();

        expect($result->succeeded())->toBeTrue();
    });

    it('fails when prize does not exist', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        $result = LFL::grant('non-existent-prize')
            ->to($user)
            ->save();

        expect($result->failed())->toBeTrue()
            ->and($result->message)->toContain('found');
    });

    it('increments profile prize count', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);
        Prize::create([
            'slug' => 'bronze-medal',
            'name' => 'Bronze Medal',
            'type' => 'virtual',
            'is_active' => true,
        ]);

        LFL::grant('bronze-medal')->to($user)->save();

        $user->refresh();
        expect($user->getPrizeCount())->toBe(1);
    });

});

describe('Validation', function () {

    it('fails grant when no recipient is specified', function () {
        Achievement::create([
            'slug' => 'validation-test',
            'name' => 'Validation Test',
            'is_active' => true,
        ]);

        LFL::grant('validation-test')->save();
    })->throws(\InvalidArgumentException::class, 'Recipient is required');

});

describe('Events', function () {

    it('dispatches XpAwarded event on successful XP award', function () {
        Event::fake([XpAwarded::class]);

        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);
        GamedMetric::create([
            'slug' => 'event-test',
            'name' => 'Event Test',
            'active' => true,
        ]);

        LFL::award('event-test')->to($user)->amount(50)->save();

        Event::assertDispatched(XpAwarded::class);
    });

    it('dispatches AchievementUnlocked event on successful achievement grant', function () {
        Event::fake([AchievementUnlocked::class]);

        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);
        Achievement::create([
            'slug' => 'achievement-event-test',
            'name' => 'Achievement Event Test',
            'is_active' => true,
        ]);

        LFL::grant('achievement-event-test')->to($user)->save();

        Event::assertDispatched(AchievementUnlocked::class);
    });

    it('dispatches PrizeAwarded event on successful prize grant', function () {
        Event::fake([PrizeAwarded::class]);

        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);
        Prize::create([
            'slug' => 'prize-event-test',
            'name' => 'Prize Event Test',
            'type' => 'virtual',
            'is_active' => true,
        ]);

        LFL::grant('prize-event-test')->to($user)->save();

        Event::assertDispatched(PrizeAwarded::class);
    });

    it('does not dispatch events when disabled in config', function () {
        Event::fake([XpAwarded::class]);
        config(['lfl.events.dispatch' => false]);

        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);
        GamedMetric::create([
            'slug' => 'no-event-test',
            'name' => 'No Event Test',
            'active' => true,
        ]);

        LFL::award('no-event-test')->to($user)->amount(50)->save();

        Event::assertNotDispatched(XpAwarded::class);

        // Reset config
        config(['lfl.events.dispatch' => true]);
    });

});

describe('hasLevel', function () {

    it('checks if user has reached a level in a metric', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'level-test@example.com']);

        $metric = GamedMetric::create([
            'slug' => 'level-test-metric',
            'name' => 'Level Test Metric',
            'active' => true,
        ]);

        // Create levels
        \LaravelFunLab\Models\MetricLevel::create([
            'gamed_metric_id' => $metric->id,
            'level' => 1,
            'xp_threshold' => 0,
            'name' => 'Level 1',
        ]);
        \LaravelFunLab\Models\MetricLevel::create([
            'gamed_metric_id' => $metric->id,
            'level' => 2,
            'xp_threshold' => 100,
            'name' => 'Level 2',
        ]);

        // Award XP
        LFL::award('level-test-metric')->to($user)->amount(150)->save();

        expect(LFL::hasLevel($user, 1, metric: 'level-test-metric'))->toBeTrue()
            ->and(LFL::hasLevel($user, 2, metric: 'level-test-metric'))->toBeTrue()
            ->and(LFL::hasLevel($user, 3, metric: 'level-test-metric'))->toBeFalse();
    });

    it('throws exception when neither metric nor group is specified', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'level-error@example.com']);

        LFL::hasLevel($user, 1);
    })->throws(\InvalidArgumentException::class, "Either 'metric' or 'group' must be specified");

    it('throws exception when both metric and group are specified', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'level-error2@example.com']);

        LFL::hasLevel($user, 1, metric: 'test', group: 'test');
    })->throws(\InvalidArgumentException::class, "Only one of 'metric' or 'group' can be specified");

});

describe('AwardType Enum', function () {

    it('has correct labels', function () {
        expect(AwardType::Points->label())->toBe('Points')
            ->and(AwardType::Achievement->label())->toBe('Achievement')
            ->and(AwardType::Prize->label())->toBe('Prize')
            ->and(AwardType::Badge->label())->toBe('Badge');
    });

    it('correctly identifies cumulative types', function () {
        expect(AwardType::Points->isCumulative())->toBeTrue()
            ->and(AwardType::Achievement->isCumulative())->toBeFalse()
            ->and(AwardType::Prize->isCumulative())->toBeFalse()
            ->and(AwardType::Badge->isCumulative())->toBeFalse();
    });

    it('has icons for each type', function () {
        expect(AwardType::Points->icon())->toBe('star')
            ->and(AwardType::Achievement->icon())->toBe('trophy')
            ->and(AwardType::Prize->icon())->toBe('gift')
            ->and(AwardType::Badge->icon())->toBe('badge');
    });

});
