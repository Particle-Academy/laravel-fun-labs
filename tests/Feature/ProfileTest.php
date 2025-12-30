<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use LaravelFunLab\Events\AwardFailed;
use LaravelFunLab\Facades\LFL;
use LaravelFunLab\Models\Achievement;
use LaravelFunLab\Models\Award;
use LaravelFunLab\Models\Profile;
use LaravelFunLab\Tests\Fixtures\User;

/*
|--------------------------------------------------------------------------
| Profile Tests
|--------------------------------------------------------------------------
|
| Tests for profile creation, opt-in/opt-out logic, and aggregations.
| Covers all profile-related functionality including award blocking.
|
*/

describe('Profile Model', function () {

    it('can create a profile for an awardable model', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        $profile = Profile::create([
            'awardable_type' => User::class,
            'awardable_id' => $user->id,
            'is_opted_in' => true,
        ]);

        expect($profile)->toBeInstanceOf(Profile::class)
            ->and($profile->awardable)->toBeInstanceOf(User::class)
            ->and($profile->awardable->id)->toBe($user->id);
    });

    it('has polymorphic relationship to awardable', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);
        $profile = Profile::create([
            'awardable_type' => User::class,
            'awardable_id' => $user->id,
        ]);

        expect($profile->awardable)->toBeInstanceOf(User::class)
            ->and($profile->awardable->is($user))->toBeTrue();
    });

    it('defaults to opted in when created', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);
        $profile = Profile::create([
            'awardable_type' => User::class,
            'awardable_id' => $user->id,
            'is_opted_in' => true, // Explicitly set default
        ]);

        expect($profile->is_opted_in)->toBeTrue()
            ->and($profile->isOptedIn())->toBeTrue()
            ->and($profile->isOptedOut())->toBeFalse();
    });

    it('can opt in and opt out', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);
        $profile = Profile::create([
            'awardable_type' => User::class,
            'awardable_id' => $user->id,
            'is_opted_in' => false,
        ]);

        expect($profile->isOptedOut())->toBeTrue();

        $profile->optIn();

        expect($profile->fresh()->isOptedIn())->toBeTrue();

        $profile->optOut();

        expect($profile->fresh()->isOptedOut())->toBeTrue();
    });

    it('can update last activity timestamp', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);
        $profile = Profile::create([
            'awardable_type' => User::class,
            'awardable_id' => $user->id,
        ]);

        expect($profile->last_activity_at)->toBeNull();

        $profile->touchActivity();

        expect($profile->fresh()->last_activity_at)->not->toBeNull();
    });

    it('can store display preferences', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);
        $profile = Profile::create([
            'awardable_type' => User::class,
            'awardable_id' => $user->id,
            'display_preferences' => [
                'theme' => 'dark',
                'show_points' => true,
            ],
        ]);

        expect($profile->display_preferences)->toBe([
            'theme' => 'dark',
            'show_points' => true,
        ]);
    });

    it('can store visibility settings', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);
        $profile = Profile::create([
            'awardable_type' => User::class,
            'awardable_id' => $user->id,
            'visibility_settings' => [
                'show_on_leaderboard' => true,
                'show_achievements' => false,
            ],
        ]);

        expect($profile->visibility_settings)->toBe([
            'show_on_leaderboard' => true,
            'show_achievements' => false,
        ]);
    });

    describe('Scopes', function () {

        it('can filter by opted in status', function () {
            $user1 = User::create(['name' => 'User 1', 'email' => 'user1@example.com']);
            $user2 = User::create(['name' => 'User 2', 'email' => 'user2@example.com']);

            Profile::create([
                'awardable_type' => User::class,
                'awardable_id' => $user1->id,
                'is_opted_in' => true,
            ]);

            Profile::create([
                'awardable_type' => User::class,
                'awardable_id' => $user2->id,
                'is_opted_in' => false,
            ]);

            $optedIn = Profile::optedIn()->get();
            $optedOut = Profile::optedOut()->get();

            expect($optedIn)->toHaveCount(1)
                ->and($optedIn->first()->is_opted_in)->toBeTrue()
                ->and($optedOut)->toHaveCount(1)
                ->and($optedOut->first()->is_opted_in)->toBeFalse();
        });

        it('can filter by awardable type', function () {
            $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

            Profile::create([
                'awardable_type' => User::class,
                'awardable_id' => $user->id,
            ]);

            $profiles = Profile::forAwardableType(User::class)->get();

            expect($profiles)->toHaveCount(1)
                ->and($profiles->first()->awardable_type)->toBe(User::class);
        });

        it('can order by total points', function () {
            $user1 = User::create(['name' => 'User 1', 'email' => 'user1@example.com']);
            $user2 = User::create(['name' => 'User 2', 'email' => 'user2@example.com']);

            Profile::create([
                'awardable_type' => User::class,
                'awardable_id' => $user1->id,
                'total_points' => 100,
            ]);

            Profile::create([
                'awardable_type' => User::class,
                'awardable_id' => $user2->id,
                'total_points' => 200,
            ]);

            $ordered = Profile::orderedByPoints()->get();

            expect($ordered->first()->total_points)->toBe('200.00')
                ->and($ordered->last()->total_points)->toBe('100.00');
        });

    });

});

describe('HasProfile Trait', function () {

    it('provides profile relationship', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        $profile = Profile::create([
            'awardable_type' => User::class,
            'awardable_id' => $user->id,
        ]);

        expect($user->profile)->toBeInstanceOf(Profile::class)
            ->and($user->profile->id)->toBe($profile->id);
    });

    it('can get or create profile', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        $profile1 = $user->getProfile();
        $profile2 = $user->getProfile();

        expect($profile1)->toBeInstanceOf(Profile::class)
            ->and($profile2->id)->toBe($profile1->id)
            ->and(Profile::count())->toBe(1);
    });

    it('creates profile with default values when using getProfile', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        $profile = $user->getProfile();

        expect($profile->is_opted_in)->toBeTrue()
            ->and($profile->total_points)->toBe('0.00')
            ->and($profile->achievement_count)->toBe(0)
            ->and($profile->prize_count)->toBe(0);
    });

    it('can check if user has profile', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        expect($user->hasProfile())->toBeFalse();

        $user->getProfile();

        expect($user->hasProfile())->toBeTrue();
    });

    it('defaults to opted in when no profile exists', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        expect($user->isOptedIn())->toBeTrue()
            ->and($user->isOptedOut())->toBeFalse();
    });

    it('can opt in through trait', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        Profile::create([
            'awardable_type' => User::class,
            'awardable_id' => $user->id,
            'is_opted_in' => false,
        ]);

        $user->optIn();

        expect($user->fresh()->profile->is_opted_in)->toBeTrue();
    });

    it('can opt out through trait', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        $user->optOut();

        expect($user->fresh()->profile->is_opted_in)->toBeFalse();
    });

    it('creates profile when opting out if none exists', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        $user->optOut();

        $user->refresh();

        expect($user->hasProfile())->toBeTrue()
            ->and($user->profile->is_opted_in)->toBeFalse();
    });

});

describe('Opt-In/Opt-Out Logic', function () {

    it('allows awards when opted in', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        $user->getProfile(); // Create profile (defaults to opted in)

        $result = LFL::awardPoints($user, 50);

        expect($result)->toBeSuccessfulAward()
            ->and($result->award->amount)->toBe('50.00');
    });

    it('blocks awards when opted out', function () {
        Event::fake([AwardFailed::class]);

        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        $user->optOut();

        $result = LFL::awardPoints($user, 50);

        expect($result)->toBeFailedAward()
            ->and($result->message)->toBe('Recipient has opted out of gamification')
            ->and($result->hasError('recipient'))->toBeTrue();

        Event::assertDispatched(AwardFailed::class);
    });

    it('blocks achievements when opted out', function () {
        Achievement::create([
            'slug' => 'test-achievement',
            'name' => 'Test Achievement',
            'is_active' => true,
        ]);

        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        $user->optOut();

        $result = LFL::grantAchievement($user, 'test-achievement');

        expect($result)->toBeFailedAward()
            ->and($result->message)->toBe('Recipient has opted out of gamification');
    });

    it('blocks prizes when opted out', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        $user->optOut();

        $result = LFL::awardPrize($user, 'test prize');

        expect($result)->toBeFailedAward()
            ->and($result->message)->toBe('Recipient has opted out of gamification');
    });

    it('allows awards again after opting back in', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        $user->optOut();

        $result1 = LFL::awardPoints($user, 50);
        expect($result1)->toBeFailedAward();

        $user->optIn();

        $result2 = LFL::awardPoints($user, 50);
        expect($result2)->toBeSuccessfulAward();
    });

});

describe('Profile Aggregations', function () {

    it('can calculate total points from awards', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);
        $profile = $user->getProfile();

        LFL::awardPoints($user, 50);
        LFL::awardPoints($user, 30);
        LFL::awardPoints($user, 20);

        $total = $profile->calculateTotalPoints();

        expect($total)->toBe(100.0);
    });

    it('can calculate achievement count', function () {
        Achievement::create([
            'slug' => 'achievement-1',
            'name' => 'Achievement 1',
            'is_active' => true,
        ]);

        Achievement::create([
            'slug' => 'achievement-2',
            'name' => 'Achievement 2',
            'is_active' => true,
        ]);

        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);
        $profile = $user->getProfile();

        LFL::grantAchievement($user, 'achievement-1');
        LFL::grantAchievement($user, 'achievement-2');

        $count = $profile->calculateAchievementCount();

        expect($count)->toBe(2);
    });

    it('can calculate prize count', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);
        $profile = $user->getProfile();

        LFL::awardPrize($user, 'prize 1');
        LFL::awardPrize($user, 'prize 2');

        $count = $profile->calculatePrizeCount();

        expect($count)->toBe(2);
    });

    it('can recalculate all aggregations', function () {
        Achievement::create([
            'slug' => 'test-achievement',
            'name' => 'Test Achievement',
            'is_active' => true,
        ]);

        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);
        $profile = $user->getProfile();

        // Set incorrect values
        $profile->update([
            'total_points' => 0,
            'achievement_count' => 0,
            'prize_count' => 0,
        ]);

        // Create actual awards
        LFL::awardPoints($user, 100);
        LFL::grantAchievement($user, 'test-achievement');
        LFL::awardPrize($user, 'test prize');

        // Recalculate
        $profile->recalculateAggregations();

        expect($profile->fresh()->total_points)->toBe('100.00')
            ->and($profile->fresh()->achievement_count)->toBe(1)
            ->and($profile->fresh()->prize_count)->toBe(1);
    });

    it('can increment points', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);
        $profile = $user->getProfile();

        $profile->incrementPoints(50.5);

        expect($profile->fresh()->total_points)->toBe('50.50');
    });

    it('can increment achievement count', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);
        $profile = $user->getProfile();

        $profile->incrementAchievementCount();

        expect($profile->fresh()->achievement_count)->toBe(1);
    });

    it('can increment prize count', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);
        $profile = $user->getProfile();

        $profile->incrementPrizeCount();

        expect($profile->fresh()->prize_count)->toBe(1);
    });

    it('returns zero for aggregations when awardable does not exist', function () {
        // Create a profile without saving (to avoid relationship loading)
        $profile = Profile::make([
            'awardable_type' => User::class,
            'awardable_id' => 999999, // Non-existent ID
        ]);

        // Mock the awardable relationship to return null
        $profile->setRelation('awardable', null);

        expect($profile->calculateTotalPoints())->toBe(0.0)
            ->and($profile->calculateAchievementCount())->toBe(0)
            ->and($profile->calculatePrizeCount())->toBe(0);
    });

});
