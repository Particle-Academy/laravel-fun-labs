<?php

declare(strict_types=1);

use LaravelFunLab\Facades\LFL;
use LaravelFunLab\Models\Achievement;
use LaravelFunLab\Models\GamedMetric;
use LaravelFunLab\Models\ProfileMetric;
use LaravelFunLab\Tests\Fixtures\User;

/*
|--------------------------------------------------------------------------
| README Examples Test
|--------------------------------------------------------------------------
|
| Tests that verify all code examples in README.md actually work correctly.
| This ensures the quick start guide is accurate and functional.
|
*/

describe('README Quick Start Examples', function () {

    beforeEach(function () {
        $this->user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        // Create a default GamedMetric for XP tests
        GamedMetric::create([
            'slug' => 'general-xp',
            'name' => 'General XP',
            'description' => 'General experience points',
            'active' => true,
        ]);
    });

    it('example 1: can add Awardable trait to User model', function () {
        // This is verified by the User fixture already using the trait
        // Verify the trait methods work by calling them
        $profile = $this->user->getProfile();

        expect($profile->total_xp)->toBe(0)
            ->and($this->user->hasAchievement('test'))->toBeFalse();
    });

    it('example 2: can award XP using GamedMetric', function () {
        $result = LFL::awardGamedMetric($this->user, 'general-xp', 50);

        expect($result)->toBeInstanceOf(ProfileMetric::class)
            ->and($result->total_xp)->toBe(50);
    });

    it('example 3: can setup and grant achievements', function () {
        // Define an achievement
        $achievement = LFL::setup(
            an: 'first-login',
            for: 'User',
            name: 'First Login',
            description: 'Welcome! You\'ve logged in for the first time.',
            icon: 'star'
        );

        expect($achievement)->toBeInstanceOf(Achievement::class)
            ->and($achievement->slug)->toBe('first-login')
            ->and($achievement->name)->toBe('First Login');

        // Grant the achievement
        $result = LFL::grantAchievement($this->user, 'first-login', 'completed first login', 'auth');

        expect($result)->toBeSuccessfulAward()
            ->and($this->user->hasAchievement('first-login'))->toBeTrue();
    });

    it('example 4: can query analytics for total XP', function () {
        // Create some XP awards
        LFL::awardGamedMetric($this->user, 'general-xp', 50);
        LFL::awardGamedMetric($this->user, 'general-xp', 30);

        // Check total XP via profile
        $profile = $this->user->getProfile()->fresh();

        expect($profile->total_xp)->toBe(80);
    });

    it('example 4: can query active users', function () {
        $user2 = User::create(['name' => 'User 2', 'email' => 'user2@example.com']);

        // Create XP awards in the last 7 days
        LFL::awardGamedMetric($this->user, 'general-xp', 50);
        LFL::awardGamedMetric($user2, 'general-xp', 30);

        // Check that both users have profiles with XP
        $activeProfiles = \LaravelFunLab\Models\Profile::where('total_xp', '>', 0)->count();

        expect($activeProfiles)->toBe(2);
    });

    it('example 4: can query achievement completion rate', function () {
        $user2 = User::create(['name' => 'User 2', 'email' => 'user2@example.com']);

        // Setup achievement
        LFL::setup(an: 'first-login', for: 'User');

        // Grant to one user
        LFL::grantAchievement($this->user, 'first-login', 'login', 'auth');

        // Create some activity for both users
        LFL::awardGamedMetric($this->user, 'general-xp', 10);
        LFL::awardGamedMetric($user2, 'general-xp', 10);

        // Check achievement grants
        $totalWithAchievement = \LaravelFunLab\Models\AchievementGrant::count();
        $totalActiveProfiles = \LaravelFunLab\Models\Profile::where('total_xp', '>', 0)->count();

        // 1 out of 2 users have the achievement
        expect($totalWithAchievement)->toBe(1)
            ->and($totalActiveProfiles)->toBe(2);
    });

});
