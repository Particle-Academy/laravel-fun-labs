<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use LaravelFunLab\Enums\AwardType;
use LaravelFunLab\Events\AwardFailed;
use LaravelFunLab\Events\AwardGranted;
use LaravelFunLab\Facades\LFL;
use LaravelFunLab\Models\Achievement;
use LaravelFunLab\Models\AchievementGrant;
use LaravelFunLab\Models\GamedMetric;
use LaravelFunLab\Models\Prize;
use LaravelFunLab\Models\PrizeGrant;
use LaravelFunLab\Tests\Fixtures\NonAwardableModel;
use LaravelFunLab\Tests\Fixtures\User;

/*
|--------------------------------------------------------------------------
| Award Engine Tests
|--------------------------------------------------------------------------
|
| Tests for the unified award API: LFL::award()
| Covers GamedMetric XP, achievements, and prizes.
|
*/

describe('AwardEngine Facade', function () {

    it('can access the LFL facade', function () {
        expect(LFL::getFacadeRoot())->toBeInstanceOf(\LaravelFunLab\Services\AwardEngine::class);
    });

    it('returns an AwardBuilder when calling award() with valid type', function () {
        // Create a GamedMetric first
        GamedMetric::create([
            'slug' => 'test-metric',
            'name' => 'Test Metric',
            'active' => true,
        ]);

        $builder = LFL::award('test-metric');

        expect($builder)->toBeInstanceOf(\LaravelFunLab\Builders\AwardBuilder::class);
    });

});

describe('Deprecated Points Awards', function () {

    it('fails when trying to use deprecated points type', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        $result = LFL::award('points')
            ->to($user)
            ->amount(50)
            ->grant();

        expect($result->failed())->toBeTrue()
            ->and($result->message)->toContain('deprecated');
    });

    it('fails when trying to use deprecated badge type', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        $result = LFL::award('badge')
            ->to($user)
            ->amount(1)
            ->grant();

        expect($result->failed())->toBeTrue()
            ->and($result->message)->toContain('deprecated');
    });

});

describe('GamedMetric XP Awards', function () {

    it('can award XP to a GamedMetric using fluent API', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'gamed-metric-test@example.com']);

        // Create a GamedMetric
        $metric = GamedMetric::create([
            'slug' => 'test-combat-xp',
            'name' => 'Combat XP',
            'description' => 'XP from combat',
            'active' => true,
        ]);

        $result = LFL::award('test-combat-xp')
            ->to($user)
            ->for('defeated enemy')
            ->amount(50)
            ->grant();

        expect($result->succeeded())->toBeTrue()
            ->and($result->message)->toContain('Awarded 50 XP')
            ->and($result->meta['total_xp'])->toBe(50)
            ->and($result->meta['current_level'])->toBe(1)
            ->and($result->meta['gamed_metric_slug'])->toBe('test-combat-xp');
    });

    it('accumulates XP correctly across multiple awards', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'gamed-metric-accumulate@example.com']);

        $metric = GamedMetric::create([
            'slug' => 'test-crafting-xp',
            'name' => 'Crafting XP',
            'active' => true,
        ]);

        // Award XP twice
        LFL::award('test-crafting-xp')->to($user)->amount(30)->grant();
        $result = LFL::award('test-crafting-xp')->to($user)->amount(20)->grant();

        expect($result->succeeded())->toBeTrue()
            ->and($result->meta['total_xp'])->toBe(50);
    });

    it('updates profile total XP', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'profile-xp@example.com']);

        $metric = GamedMetric::create([
            'slug' => 'test-profile-xp',
            'name' => 'Profile XP',
            'active' => true,
        ]);

        LFL::award('test-profile-xp')->to($user)->amount(100)->grant();

        $user->refresh();
        expect($user->getTotalXp())->toBe(100);
    });

    it('fails when GamedMetric is inactive', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'gamed-metric-inactive@example.com']);

        $metric = GamedMetric::create([
            'slug' => 'inactive-xp',
            'name' => 'Inactive XP',
            'active' => false,
        ]);

        $result = LFL::award('inactive-xp')
            ->to($user)
            ->amount(50)
            ->grant();

        expect($result->failed())->toBeTrue()
            ->and($result->message)->toContain('not active');
    });

    it('throws exception for non-existent GamedMetric slug', function () {
        LFL::award('non-existent-metric');
    })->throws(\InvalidArgumentException::class, "Award type 'non-existent-metric' is not registered");

});

describe('Achievement Awards', function () {

    it('can grant an achievement using fluent API', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);
        $achievement = Achievement::create([
            'slug' => 'first-login',
            'name' => 'First Login',
            'is_active' => true,
        ]);

        $result = LFL::award('achievement')
            ->to($user)
            ->achievement('first-login')
            ->for('completed first login')
            ->grant();

        expect($result->succeeded())->toBeTrue()
            ->and($result->award)->toBeInstanceOf(AchievementGrant::class)
            ->and($user->hasAchievement('first-login'))->toBeTrue();
    });

    it('can grant an achievement using the shorthand method', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);
        Achievement::create([
            'slug' => 'task-master',
            'name' => 'Task Master',
            'is_active' => true,
        ]);

        $result = LFL::grantAchievement($user, 'task-master', 'completed all tasks', 'task-system');

        expect($result->succeeded())->toBeTrue()
            ->and($user->hasAchievement('task-master'))->toBeTrue();
    });

    it('fails when achievement does not exist', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        $result = LFL::award('achievement')
            ->to($user)
            ->achievement('non-existent')
            ->grant();

        expect($result->failed())->toBeTrue()
            ->and($result->message)->toBe('Achievement not found');
    });

    it('fails when achievement is inactive', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);
        Achievement::create([
            'slug' => 'inactive-achievement',
            'name' => 'Inactive',
            'is_active' => false,
        ]);

        $result = LFL::award('achievement')
            ->to($user)
            ->achievement('inactive-achievement')
            ->grant();

        expect($result->failed())->toBeTrue()
            ->and($result->message)->toBe('Achievement is not active');
    });

    it('fails when achievement already granted', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);
        Achievement::create([
            'slug' => 'one-time',
            'name' => 'One Time Only',
            'is_active' => true,
        ]);

        // Grant first time
        LFL::grantAchievement($user, 'one-time');

        // Try to grant again
        $result = LFL::grantAchievement($user, 'one-time');

        expect($result->failed())->toBeTrue()
            ->and($result->message)->toBe('Achievement already granted');
    });

    it('can use reason as achievement slug when no explicit achievement set', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);
        Achievement::create([
            'slug' => 'explorer',
            'name' => 'Explorer',
            'is_active' => true,
        ]);

        $result = LFL::award('achievement')
            ->to($user)
            ->for('explorer') // Using reason as slug
            ->grant();

        expect($result->succeeded())->toBeTrue()
            ->and($user->hasAchievement('explorer'))->toBeTrue();
    });

});

describe('Prize Awards', function () {

    it('can award a prize using fluent API', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);
        $prize = Prize::create([
            'slug' => 'gold-medal',
            'name' => 'Gold Medal',
            'type' => 'virtual',
            'is_active' => true,
        ]);

        $result = LFL::award('prize')
            ->to($user)
            ->for('winning competition')
            ->withMeta(['prize_id' => $prize->id])
            ->grant();

        expect($result->succeeded())->toBeTrue()
            ->and($result->award)->toBeInstanceOf(PrizeGrant::class)
            ->and($result->meta['prize_name'])->toBe('Gold Medal');
    });

    it('can award a prize using prize_slug in meta', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);
        Prize::create([
            'slug' => 'silver-medal',
            'name' => 'Silver Medal',
            'type' => 'virtual',
            'is_active' => true,
        ]);

        $result = LFL::award('prize')
            ->to($user)
            ->withMeta(['prize_slug' => 'silver-medal'])
            ->grant();

        expect($result->succeeded())->toBeTrue()
            ->and($result->meta['prize_slug'])->toBe('silver-medal');
    });

    it('fails when prize not specified', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        $result = LFL::award('prize')
            ->to($user)
            ->grant();

        expect($result->failed())->toBeTrue()
            ->and($result->message)->toContain('Prize not specified');
    });

    it('fails when prize does not exist', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        $result = LFL::award('prize')
            ->to($user)
            ->withMeta(['prize_id' => 99999])
            ->grant();

        expect($result->failed())->toBeTrue()
            ->and($result->message)->toBe('Prize not found');
    });

    it('increments profile prize count', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);
        $prize = Prize::create([
            'slug' => 'bronze-medal',
            'name' => 'Bronze Medal',
            'type' => 'virtual',
            'is_active' => true,
        ]);

        LFL::award('prize')
            ->to($user)
            ->withMeta(['prize_id' => $prize->id])
            ->grant();

        $user->refresh();
        expect($user->getPrizeCount())->toBe(1);
    });

});

describe('Validation', function () {

    it('fails when no recipient is specified', function () {
        GamedMetric::create([
            'slug' => 'validation-test',
            'name' => 'Validation Test',
            'active' => true,
        ]);

        $result = LFL::award('validation-test')
            ->amount(50)
            ->grant();

        expect($result->failed())->toBeTrue()
            ->and($result->message)->toBe('No recipient specified for award');
    });

    it('fails when recipient does not use Awardable trait', function () {
        GamedMetric::create([
            'slug' => 'awardable-test',
            'name' => 'Awardable Test',
            'active' => true,
        ]);

        $nonAwardable = new NonAwardableModel;
        $nonAwardable->id = 1;

        $result = LFL::award('awardable-test')
            ->to($nonAwardable)
            ->amount(50)
            ->grant();

        expect($result->failed())->toBeTrue()
            ->and($result->message)->toBe('Recipient must use the Awardable trait');
    });

});

describe('Events', function () {

    it('dispatches AwardGranted event on successful award', function () {
        Event::fake([AwardGranted::class]);

        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);
        GamedMetric::create([
            'slug' => 'event-test',
            'name' => 'Event Test',
            'active' => true,
        ]);

        LFL::award('event-test')->to($user)->amount(50)->grant();

        Event::assertDispatched(AwardGranted::class);
    });

    it('dispatches AwardFailed event on failed award', function () {
        Event::fake([AwardFailed::class]);

        GamedMetric::create([
            'slug' => 'failed-event-test',
            'name' => 'Failed Event Test',
            'active' => true,
        ]);

        // No recipient
        LFL::award('failed-event-test')->amount(50)->grant();

        Event::assertDispatched(AwardFailed::class);
    });

    it('does not dispatch events when disabled in config', function () {
        Event::fake([AwardGranted::class, AwardFailed::class]);
        config(['lfl.events.dispatch' => false]);

        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);
        GamedMetric::create([
            'slug' => 'no-event-test',
            'name' => 'No Event Test',
            'active' => true,
        ]);

        LFL::award('no-event-test')->to($user)->amount(50)->grant();

        Event::assertNotDispatched(AwardGranted::class);

        // Reset config
        config(['lfl.events.dispatch' => true]);
    });

});

describe('AwardResult', function () {

    it('can convert result to array for API responses', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);
        GamedMetric::create([
            'slug' => 'array-test',
            'name' => 'Array Test',
            'active' => true,
        ]);

        $result = LFL::award('array-test')->to($user)->amount(50)->grant();

        $array = $result->toArray();

        expect($array)->toHaveKeys(['success', 'type', 'message', 'meta']);
    });

    it('provides helper methods for checking status', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);
        GamedMetric::create([
            'slug' => 'status-test',
            'name' => 'Status Test',
            'active' => true,
        ]);

        $successResult = LFL::award('status-test')->to($user)->amount(50)->grant();
        $failResult = LFL::award('status-test')->grant(); // No recipient

        expect($successResult->succeeded())->toBeTrue()
            ->and($successResult->failed())->toBeFalse()
            ->and($failResult->succeeded())->toBeFalse()
            ->and($failResult->failed())->toBeTrue();
    });

    it('can get first error message', function () {
        GamedMetric::create([
            'slug' => 'error-test',
            'name' => 'Error Test',
            'active' => true,
        ]);

        $result = LFL::award('error-test')->grant();

        expect($result->firstError())->toBe('A recipient is required to grant an award');
    });

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
