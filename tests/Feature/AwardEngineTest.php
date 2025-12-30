<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use LaravelFunLab\Enums\AwardType;
use LaravelFunLab\Events\AwardFailed;
use LaravelFunLab\Events\AwardGranted;
use LaravelFunLab\Facades\LFL;
use LaravelFunLab\Models\Achievement;
use LaravelFunLab\Models\Award;
use LaravelFunLab\Tests\Fixtures\NonAwardableModel;
use LaravelFunLab\Tests\Fixtures\User;

/*
|--------------------------------------------------------------------------
| Award Engine Tests
|--------------------------------------------------------------------------
|
| Tests for the unified award API: LFL::award()
| Covers points, achievements, prizes, and badges with all edge cases.
|
*/

describe('AwardEngine Facade', function () {

    it('can access the LFL facade', function () {
        expect(LFL::getFacadeRoot())->toBeInstanceOf(\LaravelFunLab\Services\AwardEngine::class);
    });

    it('returns an AwardBuilder when calling award()', function () {
        $builder = LFL::award('points');

        expect($builder)->toBeInstanceOf(\LaravelFunLab\Builders\AwardBuilder::class);
    });

});

describe('Points Awards', function () {

    it('can award points to a user using fluent API', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        $result = LFL::award('points')
            ->to($user)
            ->for('completing a task')
            ->from('task-system')
            ->amount(50)
            ->grant();

        expect($result)->toBeSuccessfulAward()
            ->and($result->type)->toBe(AwardType::Points)
            ->and($result->award)->toBeInstanceOf(Award::class)
            ->and($result->award->amount)->toBe('50.00')
            ->and($result->award->reason)->toBe('completing a task')
            ->and($result->award->source)->toBe('task-system');
    });

    it('can award points using the shorthand method', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        $result = LFL::awardPoints($user, 100, 'bonus points', 'admin');

        expect($result)->toBeSuccessfulAward()
            ->and($result->award->amount)->toBe('100.00')
            ->and($result->award->type)->toBe('points');
    });

    it('accumulates points correctly', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        LFL::awardPoints($user, 50);
        LFL::awardPoints($user, 30);
        LFL::awardPoints($user, 20);

        expect($user->getTotalPoints())->toBe(100.0);
    });

    it('can award points with metadata', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        $result = LFL::award('points')
            ->to($user)
            ->amount(25)
            ->withMeta(['task_id' => 123, 'multiplier' => 2])
            ->grant();

        expect($result)->toBeSuccessfulAward()
            ->and($result->award->meta)->toBe(['task_id' => 123, 'multiplier' => 2]);
    });

    it('returns previous and new totals in result meta', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        // First award
        LFL::awardPoints($user, 50);

        // Second award
        $result = LFL::awardPoints($user, 30);

        expect($result->meta['previous_total'])->toBe(50.0)
            ->and($result->meta['new_total'])->toBe(80.0);
    });

});

describe('Achievement Awards', function () {

    beforeEach(function () {
        // Create a test achievement
        Achievement::create([
            'slug' => 'first-login',
            'name' => 'First Login',
            'description' => 'Logged in for the first time',
            'is_active' => true,
        ]);

        Achievement::create([
            'slug' => 'inactive-achievement',
            'name' => 'Inactive Achievement',
            'is_active' => false,
        ]);
    });

    it('can grant an achievement using fluent API', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        $result = LFL::award('achievement')
            ->to($user)
            ->achievement('first-login')
            ->for('user logged in')
            ->grant();

        expect($result)->toBeSuccessfulAward()
            ->and($result->type)->toBe(AwardType::Achievement)
            ->and($result->meta['achievement_slug'])->toBe('first-login')
            ->and($user->hasAchievement('first-login'))->toBeTrue();
    });

    it('can grant an achievement using the shorthand method', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        $result = LFL::grantAchievement($user, 'first-login', 'first time login');

        expect($result)->toBeSuccessfulAward()
            ->and($user->hasAchievement('first-login'))->toBeTrue();
    });

    it('fails when achievement does not exist', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        $result = LFL::award('achievement')
            ->to($user)
            ->achievement('non-existent')
            ->grant();

        expect($result)->toBeFailedAward()
            ->and($result->message)->toBe('Achievement not found');
    });

    it('fails when achievement is inactive', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        $result = LFL::grantAchievement($user, 'inactive-achievement');

        expect($result)->toBeFailedAward()
            ->and($result->message)->toBe('Achievement is not active');
    });

    it('fails when achievement already granted', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        // Grant once
        LFL::grantAchievement($user, 'first-login');

        // Try to grant again
        $result = LFL::grantAchievement($user, 'first-login');

        expect($result)->toBeFailedAward()
            ->and($result->message)->toBe('Achievement already granted');
    });

    it('can use reason as achievement slug when no explicit achievement set', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        $result = LFL::award('achievement')
            ->to($user)
            ->for('first-login') // Using reason as slug
            ->grant();

        expect($result)->toBeSuccessfulAward()
            ->and($user->hasAchievement('first-login'))->toBeTrue();
    });

});

describe('Prize Awards', function () {

    it('can award a prize using fluent API', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        $result = LFL::award('prize')
            ->to($user)
            ->for('weekly contest winner')
            ->from('contest-system')
            ->grant();

        expect($result)->toBeSuccessfulAward()
            ->and($result->type)->toBe(AwardType::Prize)
            ->and($result->award->type)->toBe('prize');
    });

    it('can award a prize using the shorthand method', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        $result = LFL::awardPrize($user, 'lottery winner', 'lottery-system');

        expect($result)->toBeSuccessfulAward()
            ->and($result->award->reason)->toBe('lottery winner')
            ->and($result->award->source)->toBe('lottery-system');
    });

    it('includes prize pending integration note', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        $result = LFL::awardPrize($user, 'test prize');

        // Prize is stored as Award until Story #9 full integration
        expect($result->meta['note'])->toBe('Full prize system coming in Story #9');
    });

});

describe('Badge Awards', function () {

    it('can award a badge using fluent API', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        $result = LFL::award('badge')
            ->to($user)
            ->for('early-adopter')
            ->from('onboarding')
            ->grant();

        expect($result)->toBeSuccessfulAward()
            ->and($result->type)->toBe(AwardType::Badge)
            ->and($result->award->type)->toBe('badge');
    });

    it('can award a badge using the shorthand method', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        $result = LFL::awardBadge($user, 'verified-user', 'identity-system');

        expect($result)->toBeSuccessfulAward()
            ->and($result->award->reason)->toBe('verified-user');
    });

});

describe('Validation', function () {

    it('fails when no recipient is specified', function () {
        $result = LFL::award('points')
            ->for('orphan points')
            ->amount(100)
            ->grant();

        expect($result)->toBeFailedAward()
            ->and($result->message)->toBe('No recipient specified for award')
            ->and($result->hasError('recipient'))->toBeTrue();
    });

    it('fails when recipient does not use Awardable trait', function () {
        $model = NonAwardableModel::create(['name' => 'Test']);

        $result = LFL::award('points')
            ->to($model)
            ->amount(50)
            ->grant();

        expect($result)->toBeFailedAward()
            ->and($result->message)->toBe('Recipient must use the Awardable trait');
    });

});

describe('Events', function () {

    it('dispatches AwardGranted event on successful award', function () {
        Event::fake([AwardGranted::class]);

        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        LFL::awardPoints($user, 50);

        Event::assertDispatched(AwardGranted::class, function ($event) use ($user) {
            return $event->type === AwardType::Points
                && $event->recipient->is($user)
                && $event->award->amount === '50.00';
        });
    });

    it('dispatches AwardFailed event on failed award', function () {
        Event::fake([AwardFailed::class]);

        $model = NonAwardableModel::create(['name' => 'Test']);

        LFL::award('points')->to($model)->amount(50)->grant();

        Event::assertDispatched(AwardFailed::class, function ($event) {
            return $event->type === AwardType::Points
                && $event->result->message === 'Recipient must use the Awardable trait';
        });
    });

    it('does not dispatch events when disabled in config', function () {
        Event::fake([AwardGranted::class]);

        config(['lfl.events.dispatch' => false]);

        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        LFL::awardPoints($user, 50);

        Event::assertNotDispatched(AwardGranted::class);
    });

});

describe('AwardResult', function () {

    it('can convert result to array for API responses', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        $result = LFL::awardPoints($user, 50, 'test award');

        $array = $result->toArray();

        expect($array)
            ->toHaveKey('success', true)
            ->toHaveKey('type', 'points')
            ->toHaveKey('award_id')
            ->toHaveKey('recipient_type')
            ->toHaveKey('recipient_id', $user->id);
    });

    it('provides helper methods for checking status', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        $successResult = LFL::awardPoints($user, 50);
        $failResult = LFL::award('points')->grant(); // No recipient

        expect($successResult->succeeded())->toBeTrue()
            ->and($successResult->failed())->toBeFalse()
            ->and($failResult->succeeded())->toBeFalse()
            ->and($failResult->failed())->toBeTrue();
    });

    it('can get first error message', function () {
        $result = LFL::award('points')->grant();

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
