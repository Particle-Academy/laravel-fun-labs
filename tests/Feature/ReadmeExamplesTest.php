<?php

declare(strict_types=1);

use Carbon\Carbon;
use LaravelFunLab\Facades\LFL;
use LaravelFunLab\Models\Achievement;
use LaravelFunLab\Models\Award;
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
    });

    it('example 1: can add Awardable trait to User model', function () {
        // This is verified by the User fixture already using the trait
        // Verify the trait methods work by calling them
        expect($this->user->getTotalPoints())->toBe(0.0) // getTotalPoints returns float
            ->and($this->user->hasAchievement('test'))->toBeFalse();
    });

    it('example 2: can award points using fluent API', function () {
        $result = LFL::award('points')
            ->to($this->user)
            ->for('completed task')
            ->from('task-system')
            ->amount(50)
            ->grant();

        expect($result)->toBeSuccessfulAward()
            ->and($result->award)->toBeInstanceOf(Award::class)
            ->and($result->award->amount)->toBe('50.00')
            ->and($result->award->reason)->toBe('completed task')
            ->and($result->award->source)->toBe('task-system');
    });

    it('example 2: can award points using shorthand method', function () {
        $result = LFL::awardPoints($this->user, 100, 'first login', 'auth');

        expect($result)->toBeSuccessfulAward()
            ->and($result->award->amount)->toBe('100.00');
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

    it('example 4: can query analytics for total points', function () {
        // Create some awards
        LFL::awardPoints($this->user, 50, 'task', 'system');
        LFL::awardPoints($this->user, 30, 'bonus', 'admin');

        $totalPoints = LFL::analytics()
            ->byType('points')
            ->period('monthly')
            ->total();

        expect($totalPoints)->toBe(80.0);
    });

    it('example 4: can query active users', function () {
        $user2 = User::create(['name' => 'User 2', 'email' => 'user2@example.com']);

        // Create awards in the last 7 days
        LFL::awardPoints($this->user, 50, 'task', 'system');
        LFL::awardPoints($user2, 30, 'task', 'system');

        $activeUsers = LFL::analytics()
            ->since(Carbon::now()->subDays(7))
            ->activeUsers();

        expect($activeUsers)->toBe(2);
    });

    it('example 4: can query achievement completion rate', function () {
        $user2 = User::create(['name' => 'User 2', 'email' => 'user2@example.com']);

        // Setup achievement
        LFL::setup(an: 'first-login', for: 'User');

        // Grant to one user
        LFL::grantAchievement($this->user, 'first-login', 'login', 'auth');

        // Create some activity for both users
        LFL::awardPoints($this->user, 10, 'activity', 'system');
        LFL::awardPoints($user2, 10, 'activity', 'system');

        $completionRate = LFL::analytics()
            ->forAchievement('first-login')
            ->achievementCompletionRate();

        // Should be 50% (1 out of 2 users with activity)
        expect($completionRate)->toBe(50.0);
    });

});
