<?php

declare(strict_types=1);

use LaravelFunLab\Enums\PrizeType;
use LaravelFunLab\Enums\RedemptionStatus;
use LaravelFunLab\Models\Prize;
use LaravelFunLab\Models\PrizeGrant;
use LaravelFunLab\Tests\Fixtures\User;

/*
|--------------------------------------------------------------------------
| Prize System Tests
|--------------------------------------------------------------------------
|
| Tests for prize models, inventory tracking, redemption status,
| and prize grant functionality.
|
*/

describe('PrizeType Enum', function () {

    it('has all required prize types', function () {
        expect(PrizeType::Virtual)->toBeInstanceOf(PrizeType::class)
            ->and(PrizeType::Physical)->toBeInstanceOf(PrizeType::class)
            ->and(PrizeType::FeatureUnlock)->toBeInstanceOf(PrizeType::class)
            ->and(PrizeType::Custom)->toBeInstanceOf(PrizeType::class);
    });

    it('provides labels for all types', function () {
        expect(PrizeType::Virtual->label())->toBe('Virtual')
            ->and(PrizeType::Physical->label())->toBe('Physical')
            ->and(PrizeType::FeatureUnlock->label())->toBe('Feature Unlock')
            ->and(PrizeType::Custom->label())->toBe('Custom');
    });

    it('provides descriptions for all types', function () {
        expect(PrizeType::Virtual->description())->not->toBeEmpty()
            ->and(PrizeType::Physical->description())->not->toBeEmpty()
            ->and(PrizeType::FeatureUnlock->description())->not->toBeEmpty()
            ->and(PrizeType::Custom->description())->not->toBeEmpty();
    });

});

describe('Prize Model', function () {

    it('can create a prize', function () {
        $prize = Prize::create([
            'slug' => 'test-prize',
            'name' => 'Test Prize',
            'description' => 'A test prize',
            'type' => PrizeType::Virtual,
            'cost_in_points' => 100,
        ]);

        expect($prize)->toBeInstanceOf(Prize::class)
            ->and($prize->slug)->toBe('test-prize')
            ->and($prize->name)->toBe('Test Prize')
            ->and($prize->type)->toBe(PrizeType::Virtual)
            ->and($prize->cost_in_points)->toBe('100.00');
    });

    it('casts type to PrizeType enum', function () {
        $prize = Prize::create([
            'slug' => 'test-prize',
            'name' => 'Test Prize',
            'type' => PrizeType::Physical,
        ]);

        expect($prize->type)->toBeInstanceOf(PrizeType::class)
            ->and($prize->type)->toBe(PrizeType::Physical);
    });

    it('can store metadata', function () {
        $prize = Prize::create([
            'slug' => 'test-prize',
            'name' => 'Test Prize',
            'type' => PrizeType::Virtual,
            'meta' => ['key' => 'value', 'number' => 42],
        ]);

        expect($prize->meta)->toBe(['key' => 'value', 'number' => 42]);
    });

    it('defaults to active when created', function () {
        $prize = Prize::create([
            'slug' => 'test-prize',
            'name' => 'Test Prize',
            'type' => PrizeType::Virtual,
        ]);

        expect($prize->is_active)->toBeTrue();
    });

    it('can filter by active status', function () {
        Prize::create([
            'slug' => 'active-prize',
            'name' => 'Active Prize',
            'type' => PrizeType::Virtual,
            'is_active' => true,
        ]);

        Prize::create([
            'slug' => 'inactive-prize',
            'name' => 'Inactive Prize',
            'type' => PrizeType::Virtual,
            'is_active' => false,
        ]);

        $active = Prize::active()->get();

        expect($active)->toHaveCount(1)
            ->and($active->first()->slug)->toBe('active-prize');
    });

    it('can filter by prize type', function () {
        Prize::create([
            'slug' => 'virtual-prize',
            'name' => 'Virtual Prize',
            'type' => PrizeType::Virtual,
        ]);

        Prize::create([
            'slug' => 'physical-prize',
            'name' => 'Physical Prize',
            'type' => PrizeType::Physical,
        ]);

        $virtual = Prize::ofType(PrizeType::Virtual)->get();

        expect($virtual)->toHaveCount(1)
            ->and($virtual->first()->type)->toBe(PrizeType::Virtual);
    });

    it('can find prize by slug', function () {
        Prize::create([
            'slug' => 'findable-prize',
            'name' => 'Findable Prize',
            'type' => PrizeType::Virtual,
        ]);

        $prize = Prize::findBySlug('findable-prize');

        expect($prize)->not->toBeNull()
            ->and($prize->slug)->toBe('findable-prize');
    });

    describe('Inventory Tracking', function () {

        it('considers prize available when inventory is unlimited', function () {
            $prize = Prize::create([
                'slug' => 'unlimited-prize',
                'name' => 'Unlimited Prize',
                'type' => PrizeType::Virtual,
                'inventory_quantity' => null, // Unlimited
            ]);

            expect($prize->isAvailable())->toBeTrue()
                ->and($prize->getRemainingInventory())->toBeNull();
        });

        it('considers prize available when inventory remains', function () {
            $prize = Prize::create([
                'slug' => 'limited-prize',
                'name' => 'Limited Prize',
                'type' => PrizeType::Virtual,
                'inventory_quantity' => 5,
            ]);

            expect($prize->isAvailable())->toBeTrue()
                ->and($prize->getRemainingInventory())->toBe(5);
        });

        it('considers prize unavailable when inventory is exhausted', function () {
            $prize = Prize::create([
                'slug' => 'exhausted-prize',
                'name' => 'Exhausted Prize',
                'type' => PrizeType::Virtual,
                'inventory_quantity' => 2,
            ]);

            $user1 = User::create(['name' => 'User 1', 'email' => 'user1@example.com']);
            $user2 = User::create(['name' => 'User 2', 'email' => 'user2@example.com']);

            // Grant prizes to exhaust inventory
            PrizeGrant::create([
                'prize_id' => $prize->id,
                'awardable_type' => User::class,
                'awardable_id' => $user1->id,
                'status' => RedemptionStatus::Pending,
            ]);

            PrizeGrant::create([
                'prize_id' => $prize->id,
                'awardable_type' => User::class,
                'awardable_id' => $user2->id,
                'status' => RedemptionStatus::Pending,
            ]);

            expect($prize->fresh()->isAvailable())->toBeFalse()
                ->and($prize->fresh()->getRemainingInventory())->toBe(0);
        });

        it('excludes cancelled grants from inventory count', function () {
            $prize = Prize::create([
                'slug' => 'cancelled-test-prize',
                'name' => 'Cancelled Test Prize',
                'type' => PrizeType::Virtual,
                'inventory_quantity' => 1,
            ]);

            $user = User::create(['name' => 'User', 'email' => 'user@example.com']);

            // Create a cancelled grant (should not count against inventory)
            PrizeGrant::create([
                'prize_id' => $prize->id,
                'awardable_type' => User::class,
                'awardable_id' => $user->id,
                'status' => RedemptionStatus::Cancelled,
            ]);

            expect($prize->fresh()->isAvailable())->toBeTrue()
                ->and($prize->fresh()->getRemainingInventory())->toBe(1);
        });

    });

});

describe('PrizeGrant Model', function () {

    it('can create a prize grant', function () {
        $prize = Prize::create([
            'slug' => 'test-prize',
            'name' => 'Test Prize',
            'type' => PrizeType::Virtual,
        ]);

        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        $grant = PrizeGrant::create([
            'prize_id' => $prize->id,
            'awardable_type' => User::class,
            'awardable_id' => $user->id,
            'status' => RedemptionStatus::Pending,
        ]);

        expect($grant)->toBeInstanceOf(PrizeGrant::class)
            ->and($grant->prize_id)->toBe($prize->id)
            ->and($grant->status)->toBe(RedemptionStatus::Pending);
    });

    it('has relationship to prize', function () {
        $prize = Prize::create([
            'slug' => 'test-prize',
            'name' => 'Test Prize',
            'type' => PrizeType::Virtual,
        ]);

        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        $grant = PrizeGrant::create([
            'prize_id' => $prize->id,
            'awardable_type' => User::class,
            'awardable_id' => $user->id,
            'status' => RedemptionStatus::Pending,
        ]);

        expect($grant->prize)->toBeInstanceOf(Prize::class)
            ->and($grant->prize->id)->toBe($prize->id);
    });

    it('has polymorphic relationship to awardable', function () {
        $prize = Prize::create([
            'slug' => 'test-prize',
            'name' => 'Test Prize',
            'type' => PrizeType::Virtual,
        ]);

        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        $grant = PrizeGrant::create([
            'prize_id' => $prize->id,
            'awardable_type' => User::class,
            'awardable_id' => $user->id,
            'status' => RedemptionStatus::Pending,
        ]);

        expect($grant->awardable)->toBeInstanceOf(User::class)
            ->and($grant->awardable->id)->toBe($user->id);
    });

    it('defaults to pending status', function () {
        $prize = Prize::create([
            'slug' => 'test-prize',
            'name' => 'Test Prize',
            'type' => PrizeType::Virtual,
        ]);

        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        $grant = PrizeGrant::create([
            'prize_id' => $prize->id,
            'awardable_type' => User::class,
            'awardable_id' => $user->id,
        ]);

        expect($grant->status)->toBe(RedemptionStatus::Pending);
    });

    it('can claim a prize grant', function () {
        $prize = Prize::create([
            'slug' => 'test-prize',
            'name' => 'Test Prize',
            'type' => PrizeType::Virtual,
        ]);

        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        $grant = PrizeGrant::create([
            'prize_id' => $prize->id,
            'awardable_type' => User::class,
            'awardable_id' => $user->id,
            'status' => RedemptionStatus::Pending,
        ]);

        $grant->claim();

        expect($grant->fresh()->status)->toBe(RedemptionStatus::Claimed)
            ->and($grant->fresh()->claimed_at)->not->toBeNull();
    });

    it('can fulfill a prize grant', function () {
        $prize = Prize::create([
            'slug' => 'test-prize',
            'name' => 'Test Prize',
            'type' => PrizeType::Virtual,
        ]);

        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        $grant = PrizeGrant::create([
            'prize_id' => $prize->id,
            'awardable_type' => User::class,
            'awardable_id' => $user->id,
            'status' => RedemptionStatus::Claimed,
        ]);

        $grant->fulfill();

        expect($grant->fresh()->status)->toBe(RedemptionStatus::Fulfilled)
            ->and($grant->fresh()->fulfilled_at)->not->toBeNull();
    });

    it('can cancel a prize grant', function () {
        $prize = Prize::create([
            'slug' => 'test-prize',
            'name' => 'Test Prize',
            'type' => PrizeType::Virtual,
        ]);

        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        $grant = PrizeGrant::create([
            'prize_id' => $prize->id,
            'awardable_type' => User::class,
            'awardable_id' => $user->id,
            'status' => RedemptionStatus::Pending,
        ]);

        $grant->cancel();

        expect($grant->fresh()->status)->toBe(RedemptionStatus::Cancelled);
    });

    it('can filter by redemption status', function () {
        $prize = Prize::create([
            'slug' => 'test-prize',
            'name' => 'Test Prize',
            'type' => PrizeType::Virtual,
        ]);

        $user1 = User::create(['name' => 'User 1', 'email' => 'user1@example.com']);
        $user2 = User::create(['name' => 'User 2', 'email' => 'user2@example.com']);
        $user3 = User::create(['name' => 'User 3', 'email' => 'user3@example.com']);

        PrizeGrant::create([
            'prize_id' => $prize->id,
            'awardable_type' => User::class,
            'awardable_id' => $user1->id,
            'status' => RedemptionStatus::Pending,
        ]);

        PrizeGrant::create([
            'prize_id' => $prize->id,
            'awardable_type' => User::class,
            'awardable_id' => $user2->id,
            'status' => RedemptionStatus::Claimed,
        ]);

        PrizeGrant::create([
            'prize_id' => $prize->id,
            'awardable_type' => User::class,
            'awardable_id' => $user3->id,
            'status' => RedemptionStatus::Fulfilled,
        ]);

        expect(PrizeGrant::pending()->count())->toBe(1)
            ->and(PrizeGrant::claimed()->count())->toBe(1)
            ->and(PrizeGrant::fulfilled()->count())->toBe(1);
    });

});

describe('RedemptionStatus Enum', function () {

    it('has all required statuses', function () {
        expect(RedemptionStatus::Pending)->toBeInstanceOf(RedemptionStatus::class)
            ->and(RedemptionStatus::Claimed)->toBeInstanceOf(RedemptionStatus::class)
            ->and(RedemptionStatus::Fulfilled)->toBeInstanceOf(RedemptionStatus::class)
            ->and(RedemptionStatus::Cancelled)->toBeInstanceOf(RedemptionStatus::class);
    });

    it('provides labels for all statuses', function () {
        expect(RedemptionStatus::Pending->label())->toBe('Pending')
            ->and(RedemptionStatus::Claimed->label())->toBe('Claimed')
            ->and(RedemptionStatus::Fulfilled->label())->toBe('Fulfilled')
            ->and(RedemptionStatus::Cancelled->label())->toBe('Cancelled');
    });

    it('correctly identifies active statuses', function () {
        expect(RedemptionStatus::Pending->isActive())->toBeTrue()
            ->and(RedemptionStatus::Claimed->isActive())->toBeTrue()
            ->and(RedemptionStatus::Fulfilled->isActive())->toBeTrue()
            ->and(RedemptionStatus::Cancelled->isActive())->toBeFalse();
    });

});
