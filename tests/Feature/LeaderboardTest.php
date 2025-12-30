<?php

declare(strict_types=1);

use Illuminate\Pagination\LengthAwarePaginator;
use LaravelFunLab\Facades\LFL;
use LaravelFunLab\Tests\Fixtures\User;

/*
|--------------------------------------------------------------------------
| Leaderboard Tests
|--------------------------------------------------------------------------
|
| Tests for leaderboard building, filtering, sorting, pagination, and
| opt-out exclusion. Covers all leaderboard functionality.
|
*/

describe('LeaderboardBuilder', function () {

    it('returns a leaderboard builder instance', function () {
        $builder = LFL::leaderboard();

        expect($builder)->toBeInstanceOf(\LaravelFunLab\Builders\LeaderboardBuilder::class);
    });

    it('can filter by awardable type', function () {
        $user1 = User::create(['name' => 'User 1', 'email' => 'user1@example.com']);
        $user2 = User::create(['name' => 'User 2', 'email' => 'user2@example.com']);

        $profile1 = $user1->getProfile();
        $profile2 = $user2->getProfile();

        $profile1->update(['total_points' => 100]);
        $profile2->update(['total_points' => 50]);

        $leaderboard = LFL::leaderboard()
            ->for(User::class)
            ->get();

        expect($leaderboard)->toHaveCount(2)
            ->and($leaderboard->first()->awardable_type)->toBe(User::class);
    });

    it('sorts by points by default', function () {
        $user1 = User::create(['name' => 'User 1', 'email' => 'user1@example.com']);
        $user2 = User::create(['name' => 'User 2', 'email' => 'user2@example.com']);
        $user3 = User::create(['name' => 'User 3', 'email' => 'user3@example.com']);

        $profile1 = $user1->getProfile();
        $profile2 = $user2->getProfile();
        $profile3 = $user3->getProfile();

        $profile1->update(['total_points' => 50]);
        $profile2->update(['total_points' => 100]);
        $profile3->update(['total_points' => 75]);

        $leaderboard = LFL::leaderboard()
            ->for(User::class)
            ->get();

        expect($leaderboard->first()->total_points)->toBe('100.00')
            ->and($leaderboard->last()->total_points)->toBe('50.00');
    });

    it('can sort by achievements', function () {
        $user1 = User::create(['name' => 'User 1', 'email' => 'user1@example.com']);
        $user2 = User::create(['name' => 'User 2', 'email' => 'user2@example.com']);

        $profile1 = $user1->getProfile();
        $profile2 = $user2->getProfile();

        $profile1->update(['achievement_count' => 5]);
        $profile2->update(['achievement_count' => 10]);

        $leaderboard = LFL::leaderboard()
            ->for(User::class)
            ->by('achievements')
            ->get();

        expect($leaderboard->first()->achievement_count)->toBe(10)
            ->and($leaderboard->last()->achievement_count)->toBe(5);
    });

    it('can sort by prizes', function () {
        $user1 = User::create(['name' => 'User 1', 'email' => 'user1@example.com']);
        $user2 = User::create(['name' => 'User 2', 'email' => 'user2@example.com']);

        $profile1 = $user1->getProfile();
        $profile2 = $user2->getProfile();

        $profile1->update(['prize_count' => 2]);
        $profile2->update(['prize_count' => 5]);

        $leaderboard = LFL::leaderboard()
            ->for(User::class)
            ->by('prizes')
            ->get();

        expect($leaderboard->first()->prize_count)->toBe(5)
            ->and($leaderboard->last()->prize_count)->toBe(2);
    });

    it('excludes opted-out profiles by default', function () {
        $user1 = User::create(['name' => 'User 1', 'email' => 'user1@example.com']);
        $user2 = User::create(['name' => 'User 2', 'email' => 'user2@example.com']);

        $profile1 = $user1->getProfile();
        $profile2 = $user2->getProfile();

        $profile1->update(['total_points' => 100]);
        $profile2->update(['total_points' => 50, 'is_opted_in' => false]);

        $leaderboard = LFL::leaderboard()
            ->for(User::class)
            ->get();

        expect($leaderboard)->toHaveCount(1)
            ->and($leaderboard->first()->total_points)->toBe('100.00');
    });

    it('can include opted-out profiles when explicitly requested', function () {
        $user1 = User::create(['name' => 'User 1', 'email' => 'user1@example.com']);
        $user2 = User::create(['name' => 'User 2', 'email' => 'user2@example.com']);

        $profile1 = $user1->getProfile();
        $profile2 = $user2->getProfile();

        $profile1->update(['total_points' => 100]);
        $profile2->update(['total_points' => 50, 'is_opted_in' => false]);

        $leaderboard = LFL::leaderboard()
            ->for(User::class)
            ->excludeOptedOut(false)
            ->get();

        expect($leaderboard)->toHaveCount(2);
    });

    it('adds rank to leaderboard results', function () {
        $user1 = User::create(['name' => 'User 1', 'email' => 'user1@example.com']);
        $user2 = User::create(['name' => 'User 2', 'email' => 'user2@example.com']);
        $user3 = User::create(['name' => 'User 3', 'email' => 'user3@example.com']);

        $profile1 = $user1->getProfile();
        $profile2 = $user2->getProfile();
        $profile3 = $user3->getProfile();

        $profile1->update(['total_points' => 50]);
        $profile2->update(['total_points' => 100]);
        $profile3->update(['total_points' => 75]);

        $leaderboard = LFL::leaderboard()
            ->for(User::class)
            ->get();

        expect($leaderboard->first()->rank)->toBe(1)
            ->and($leaderboard->get(1)->rank)->toBe(2)
            ->and($leaderboard->last()->rank)->toBe(3);
    });

    it('can paginate leaderboard results', function () {
        // Create 5 users with profiles
        $users = [];
        for ($i = 1; $i <= 5; $i++) {
            $users[] = User::create(['name' => "User {$i}", 'email' => "user{$i}@example.com"]);
            $users[$i - 1]->getProfile()->update(['total_points' => $i * 10]);
        }

        $paginator = LFL::leaderboard()
            ->for(User::class)
            ->perPage(2)
            ->page(1)
            ->paginate();

        expect($paginator)->toBeInstanceOf(LengthAwarePaginator::class)
            ->and($paginator->count())->toBe(2)
            ->and($paginator->total())->toBe(5)
            ->and($paginator->currentPage())->toBe(1);
    });

    it('calculates ranks correctly with pagination', function () {
        // Create 5 users with profiles
        $users = [];
        for ($i = 1; $i <= 5; $i++) {
            $users[] = User::create(['name' => "User {$i}", 'email' => "user{$i}@example.com"]);
            $users[$i - 1]->getProfile()->update(['total_points' => $i * 10]);
        }

        $paginator = LFL::leaderboard()
            ->for(User::class)
            ->perPage(2)
            ->page(2)
            ->paginate();

        // Page 2 should have ranks 3 and 4
        expect($paginator->first()->rank)->toBe(3)
            ->and($paginator->last()->rank)->toBe(4);
    });

    it('can take a limited number of results', function () {
        $users = [];
        for ($i = 1; $i <= 5; $i++) {
            $users[] = User::create(['name' => "User {$i}", 'email' => "user{$i}@example.com"]);
            $users[$i - 1]->getProfile()->update(['total_points' => $i * 10]);
        }

        $leaderboard = LFL::leaderboard()
            ->for(User::class)
            ->take(3);

        expect($leaderboard)->toHaveCount(3)
            ->and($leaderboard->first()->rank)->toBe(1)
            ->and($leaderboard->last()->rank)->toBe(3);
    });

    describe('Time-based filtering', function () {

        it('filters by daily period', function () {
            $user1 = User::create(['name' => 'User 1', 'email' => 'user1@example.com']);
            $user2 = User::create(['name' => 'User 2', 'email' => 'user2@example.com']);

            $profile1 = $user1->getProfile();
            $profile2 = $user2->getProfile();

            // Award points today
            LFL::awardPoints($user1, 50, 'today activity');
            LFL::awardPoints($user2, 30, 'today activity');

            // Award points yesterday (should be excluded)
            $yesterday = now()->subDay();
            \LaravelFunLab\Models\Award::create([
                'awardable_type' => User::class,
                'awardable_id' => $user1->id,
                'type' => 'points',
                'amount' => 100,
                'created_at' => $yesterday,
            ]);

            $leaderboard = LFL::leaderboard()
                ->for(User::class)
                ->period('daily')
                ->get();

            // Should only include today's points
            expect($leaderboard->first()->awardable_id)->toBe($user1->id);
        });

        it('filters by weekly period', function () {
            $user1 = User::create(['name' => 'User 1', 'email' => 'user1@example.com']);
            $user2 = User::create(['name' => 'User 2', 'email' => 'user2@example.com']);

            $profile1 = $user1->getProfile();
            $profile2 = $user2->getProfile();

            // Award points this week
            LFL::awardPoints($user1, 50, 'this week');
            LFL::awardPoints($user2, 30, 'this week');

            $leaderboard = LFL::leaderboard()
                ->for(User::class)
                ->period('weekly')
                ->get();

            expect($leaderboard)->toHaveCount(2);
        });

        it('filters by monthly period', function () {
            $user1 = User::create(['name' => 'User 1', 'email' => 'user1@example.com']);
            $user2 = User::create(['name' => 'User 2', 'email' => 'user2@example.com']);

            $profile1 = $user1->getProfile();
            $profile2 = $user2->getProfile();

            // Award points this month
            LFL::awardPoints($user1, 50, 'this month');
            LFL::awardPoints($user2, 30, 'this month');

            $leaderboard = LFL::leaderboard()
                ->for(User::class)
                ->period('monthly')
                ->get();

            expect($leaderboard)->toHaveCount(2);
        });

        it('uses all-time when period is null or all-time', function () {
            $user1 = User::create(['name' => 'User 1', 'email' => 'user1@example.com']);
            $user2 = User::create(['name' => 'User 2', 'email' => 'user2@example.com']);

            $profile1 = $user1->getProfile();
            $profile2 = $user2->getProfile();

            $profile1->update(['total_points' => 100]);
            $profile2->update(['total_points' => 50]);

            $leaderboard1 = LFL::leaderboard()
                ->for(User::class)
                ->period(null)
                ->get();

            $leaderboard2 = LFL::leaderboard()
                ->for(User::class)
                ->period('all-time')
                ->get();

            expect($leaderboard1->first()->total_points)->toBe('100.00')
                ->and($leaderboard2->first()->total_points)->toBe('100.00');
        });

    });

});
