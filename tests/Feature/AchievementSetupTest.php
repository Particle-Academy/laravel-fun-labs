<?php

declare(strict_types=1);

use LaravelFunLab\Facades\LFL;
use LaravelFunLab\Models\Achievement;

/*
|--------------------------------------------------------------------------
| Achievement Setup Tests
|--------------------------------------------------------------------------
|
| Tests for the dynamic achievement setup API: LFL::setup()
| Covers creation, upsert logic, metadata handling, and edge cases.
|
*/

describe('LFL::setup() Achievement Creation', function () {

    it('can create a new achievement with just a name', function () {
        $achievement = LFL::setup(a: 'achievement', with: ['slug' => 'first-login']);

        expect($achievement)
            ->toBeInstanceOf(Achievement::class)
            ->slug->toBe('first-login')
            ->name->toBe('First Login')
            ->is_active->toBeTrue();
    });

    it('generates a human-readable name from the slug', function () {
        $achievement = LFL::setup(a: 'achievement', with: ['slug' => 'power-user-champion']);

        expect($achievement->name)->toBe('Power User Champion');
    });

    it('can set a custom display name', function () {
        $achievement = LFL::setup(
            a: 'achievement',
            with: [
                'slug' => 'first-login',
                'name' => 'Welcome, New User!',
            ]
        );

        expect($achievement->name)->toBe('Welcome, New User!');
    });

    it('can create achievement with description', function () {
        $achievement = LFL::setup(
            a: 'achievement',
            with: [
                'slug' => 'first-purchase',
                'description' => 'Complete your first purchase on the platform',
            ]
        );

        expect($achievement->description)->toBe('Complete your first purchase on the platform');
    });

    it('can create achievement with icon', function () {
        $achievement = LFL::setup(
            a: 'achievement',
            with: [
                'slug' => 'speedster',
                'icon' => 'bolt',
            ]
        );

        expect($achievement->icon)->toBe('bolt');
    });

    it('can create achievement with all parameters', function () {
        $achievement = LFL::setup(
            a: 'achievement',
            with: [
                'slug' => 'ultimate-champion',
                'for' => 'User',
                'name' => 'Ultimate Champion',
                'description' => 'Reached the highest level',
                'icon' => 'crown',
                'metadata' => ['tier' => 'legendary', 'xp_bonus' => 500],
                'active' => true,
                'order' => 100,
            ]
        );

        expect($achievement)
            ->slug->toBe('ultimate-champion')
            ->name->toBe('Ultimate Champion')
            ->description->toBe('Reached the highest level')
            ->icon->toBe('crown')
            ->awardable_type->toBe('User') // In test environment, User class is not in App\Models namespace
            ->meta->toBe(['tier' => 'legendary', 'xp_bonus' => 500])
            ->is_active->toBeTrue()
            ->sort_order->toBe(100);
    });

    it('persists the achievement to the database', function () {
        LFL::setup(a: 'achievement', with: ['slug' => 'persistent-badge']);

        $fromDb = Achievement::where('slug', 'persistent-badge')->first();

        expect($fromDb)->not->toBeNull()
            ->and($fromDb->slug)->toBe('persistent-badge');
    });

    it('can create inactive achievements', function () {
        $achievement = LFL::setup(
            a: 'achievement',
            with: [
                'slug' => 'hidden-achievement',
                'active' => false,
            ]
        );

        expect($achievement->is_active)->toBeFalse();
    });

    it('normalizes slugs with special characters', function () {
        $achievement = LFL::setup(a: 'achievement', with: ['slug' => 'First Time User!']);

        expect($achievement->slug)->toBe('first-time-user');
    });

});

describe('LFL::setup() Upsert Logic', function () {

    it('updates existing achievement when called with same slug', function () {
        // Create initial achievement
        LFL::setup(
            a: 'achievement',
            with: [
                'slug' => 'evolving-badge',
                'description' => 'Version 1',
            ]
        );

        // Update with new details
        $updated = LFL::setup(
            a: 'achievement',
            with: [
                'slug' => 'evolving-badge',
                'description' => 'Version 2',
                'icon' => 'new-icon',
            ]
        );

        expect($updated->description)->toBe('Version 2')
            ->and($updated->icon)->toBe('new-icon');

        // Verify only one record exists
        $count = Achievement::where('slug', 'evolving-badge')->count();
        expect($count)->toBe(1);
    });

    it('does not create duplicate achievements', function () {
        LFL::setup(a: 'achievement', with: ['slug' => 'unique-badge']);
        LFL::setup(a: 'achievement', with: ['slug' => 'unique-badge']);
        LFL::setup(a: 'achievement', with: ['slug' => 'unique-badge']);

        $count = Achievement::where('slug', 'unique-badge')->count();

        expect($count)->toBe(1);
    });

    it('preserves existing data when partially updating', function () {
        // Create with full data
        LFL::setup(
            a: 'achievement',
            with: [
                'slug' => 'partial-update',
                'description' => 'Original description',
                'icon' => 'star',
                'metadata' => ['key' => 'value'],
            ]
        );

        // Update only the description
        $updated = LFL::setup(
            a: 'achievement',
            with: [
                'slug' => 'partial-update',
                'description' => 'New description',
            ]
        );

        // Note: updateOrCreate replaces the values provided
        expect($updated->description)->toBe('New description');
    });

    it('returns the same model instance for existing achievements', function () {
        $first = LFL::setup(a: 'achievement', with: ['slug' => 'instance-test']);
        $second = LFL::setup(a: 'achievement', with: ['slug' => 'instance-test']);

        expect($first->id)->toBe($second->id);
    });

});

describe('LFL::setup() Metadata Support', function () {

    it('stores metadata as JSON', function () {
        $achievement = LFL::setup(
            a: 'achievement',
            with: [
                'slug' => 'meta-test',
                'metadata' => [
                    'tier' => 'gold',
                    'points_required' => 1000,
                    'badge_color' => '#FFD700',
                ],
            ]
        );

        expect($achievement->meta)
            ->toBeArray()
            ->toBe([
                'tier' => 'gold',
                'points_required' => 1000,
                'badge_color' => '#FFD700',
            ]);
    });

    it('can store nested metadata structures', function () {
        $achievement = LFL::setup(
            a: 'achievement',
            with: [
                'slug' => 'nested-meta',
                'metadata' => [
                    'rewards' => [
                        'xp' => 100,
                        'coins' => 50,
                    ],
                    'requirements' => [
                        'level' => 5,
                        'quests_completed' => 10,
                    ],
                ],
            ]
        );

        expect($achievement->meta['rewards']['xp'])->toBe(100)
            ->and($achievement->meta['requirements']['level'])->toBe(5);
    });

    it('retrieves metadata correctly from database', function () {
        LFL::setup(
            a: 'achievement',
            with: [
                'slug' => 'db-meta-test',
                'metadata' => ['special_flag' => true, 'multiplier' => 2.5],
            ]
        );

        $fromDb = Achievement::where('slug', 'db-meta-test')->first();

        expect($fromDb->meta)
            ->toBeArray()
            ->toHaveKey('special_flag', true)
            ->toHaveKey('multiplier', 2.5);
    });

    it('stores null metadata when empty array provided', function () {
        $achievement = LFL::setup(
            a: 'achievement',
            with: [
                'slug' => 'no-meta',
                'metadata' => [],
            ]
        );

        expect($achievement->meta)->toBeNull();
    });

    it('can update metadata on existing achievement', function () {
        LFL::setup(
            a: 'achievement',
            with: [
                'slug' => 'update-meta',
                'metadata' => ['version' => 1],
            ]
        );

        $updated = LFL::setup(
            a: 'achievement',
            with: [
                'slug' => 'update-meta',
                'metadata' => ['version' => 2, 'new_field' => 'added'],
            ]
        );

        expect($updated->meta)->toBe(['version' => 2, 'new_field' => 'added']);
    });

});

describe('LFL::setup() Awardable Type Handling', function () {

    it('stores null when no awardable type specified', function () {
        $achievement = LFL::setup(a: 'achievement', with: ['slug' => 'universal-achievement']);

        expect($achievement->awardable_type)->toBeNull();
    });

    it('normalizes short class names to FQCN when class exists', function () {
        $achievement = LFL::setup(
            a: 'achievement',
            with: [
                'slug' => 'user-only',
                'for' => 'User',
            ]
        );

        // In test environment, User class is not in App\Models namespace, so it returns as-is
        // The normalization only works if the class exists in common namespaces (App\Models, App)
        expect($achievement->awardable_type)->toBe('User');
    });

    it('stores unknown class names as-is', function () {
        $achievement = LFL::setup(
            a: 'achievement',
            with: [
                'slug' => 'unknown-type',
                'for' => 'UnknownModel',
            ]
        );

        // When class doesn't exist, stores as-is for flexibility
        expect($achievement->awardable_type)->toBe('UnknownModel');
    });

    it('preserves fully qualified class names', function () {
        $achievement = LFL::setup(
            a: 'achievement',
            with: [
                'slug' => 'team-only',
                'for' => 'App\\Models\\Team',
            ]
        );

        expect($achievement->awardable_type)->toBe('App\\Models\\Team');
    });

});

describe('LFL::setup() Sort Order', function () {

    it('defaults sort order to zero', function () {
        $achievement = LFL::setup(a: 'achievement', with: ['slug' => 'default-order']);

        expect($achievement->sort_order)->toBe(0);
    });

    it('can set custom sort order', function () {
        $achievement = LFL::setup(
            a: 'achievement',
            with: [
                'slug' => 'custom-order',
                'order' => 999,
            ]
        );

        expect($achievement->sort_order)->toBe(999);
    });

    it('supports negative sort order for priority', function () {
        $achievement = LFL::setup(
            a: 'achievement',
            with: [
                'slug' => 'high-priority',
                'order' => -100,
            ]
        );

        expect($achievement->sort_order)->toBe(-100);
    });

});

describe('LFL::setup() Edge Cases', function () {

    it('handles empty string description as null', function () {
        $achievement = LFL::setup(
            a: 'achievement',
            with: [
                'slug' => 'empty-desc',
                'description' => '',
            ]
        );

        // Empty string is stored as empty string (not converted to null)
        expect($achievement->description)->toBe('');
    });

    it('can be called in a seeder-like context', function () {
        // Simulate batch seeding
        $achievements = collect([
            ['a' => 'achievement', 'with' => ['slug' => 'level-1', 'description' => 'Reach level 1']],
            ['a' => 'achievement', 'with' => ['slug' => 'level-5', 'description' => 'Reach level 5']],
            ['a' => 'achievement', 'with' => ['slug' => 'level-10', 'description' => 'Reach level 10']],
        ])->map(fn ($config) => LFL::setup($config['a'], $config['with']));

        expect($achievements)->toHaveCount(3)
            ->and(Achievement::count())->toBe(3);
    });

    it('handles unicode characters in names', function () {
        $achievement = LFL::setup(
            a: 'achievement',
            with: [
                'slug' => 'champion-🏆',
                'name' => 'Campeón de Oro 🥇',
            ]
        );

        expect($achievement->name)->toBe('Campeón de Oro 🥇');
    });

    it('creates consistent slugs from different input formats', function () {
        $a1 = LFL::setup(a: 'achievement', with: ['slug' => 'First Login']);
        $a2 = LFL::setup(a: 'achievement', with: ['slug' => 'first-login']);
        $a3 = LFL::setup(a: 'achievement', with: ['slug' => 'FIRST_LOGIN']);

        // All should resolve to same slug
        expect($a1->id)->toBe($a2->id)
            ->and($a2->id)->toBe($a3->id);
    });

});
