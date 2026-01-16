<?php

declare(strict_types=1);

use LaravelFunLab\Contracts\AnalyticsServiceContract;
use LaravelFunLab\Contracts\AwardEngineContract;
use LaravelFunLab\Contracts\LeaderboardServiceContract;
use LaravelFunLab\Enums\AwardType;
use LaravelFunLab\Facades\LFL;
use LaravelFunLab\Models\Achievement;
use LaravelFunLab\Models\GamedMetric;
use LaravelFunLab\Pipelines\AwardValidationPipeline;
use LaravelFunLab\Services\AwardEngine;
use LaravelFunLab\Tests\Fixtures\User;

/*
|--------------------------------------------------------------------------
| Extensibility Tests
|--------------------------------------------------------------------------
|
| Tests for all extension points including contracts, swappable bindings,
| macros, custom award types, and validation pipelines.
|
*/

describe('Service Contracts', function () {
    it('AwardEngine implements AwardEngineContract', function () {
        $engine = app(AwardEngineContract::class);

        expect($engine)->toBeInstanceOf(AwardEngineContract::class)
            ->and($engine)->toBeInstanceOf(AwardEngine::class);
    });

    it('LeaderboardBuilder implements LeaderboardServiceContract', function () {
        $builder = LFL::leaderboard();

        expect($builder)->toBeInstanceOf(LeaderboardServiceContract::class);
    });

    it('AnalyticsBuilder implements AnalyticsServiceContract', function () {
        $builder = LFL::analytics();

        expect($builder)->toBeInstanceOf(AnalyticsServiceContract::class);
    });
});

describe('Swappable Service Bindings', function () {
    it('can resolve AwardEngineContract from container', function () {
        $engine = app(AwardEngineContract::class);

        expect($engine)->toBeInstanceOf(AwardEngineContract::class);
    });

    it('can resolve LeaderboardServiceContract from container', function () {
        $service = app(LeaderboardServiceContract::class);

        expect($service)->toBeInstanceOf(LeaderboardServiceContract::class);
    });

    it('can resolve AnalyticsServiceContract from container', function () {
        $service = app(AnalyticsServiceContract::class);

        expect($service)->toBeInstanceOf(AnalyticsServiceContract::class);
    });

    it('facade resolves AwardEngineContract implementation', function () {
        $engine = LFL::getFacadeRoot();

        expect($engine)->toBeInstanceOf(AwardEngineContract::class);
    });
});

describe('Macro Support', function () {
    beforeEach(function () {
        // Clear any existing macros
        AwardEngine::flushMacros();
    });

    it('can register a macro on AwardEngine', function () {
        AwardEngine::macro('customMethod', function () {
            return 'custom-result';
        });

        expect(AwardEngine::hasMacro('customMethod'))->toBeTrue();
    });

    it('can call macro via facade', function () {
        AwardEngine::macro('greet', function (string $name) {
            return "Hello, {$name}!";
        });

        $result = LFL::greet('World');

        expect($result)->toBe('Hello, World!');
    });

    it('can call macro with instance context', function () {
        AwardEngine::macro('getApp', function () {
            return $this->app;
        });

        $app = LFL::getApp();

        expect($app)->toBeInstanceOf(\Illuminate\Contracts\Foundation\Application::class);
    });

    it('throws exception for non-existent macro', function () {
        expect(fn () => LFL::nonExistentMethod())
            ->toThrow(\BadMethodCallException::class);
    });
});

describe('Award Validation Pipeline', function () {
    beforeEach(function () {
        AwardValidationPipeline::flush();

        // Create an achievement for testing
        Achievement::create([
            'slug' => 'test-achievement',
            'name' => 'Test Achievement',
            'is_active' => true,
        ]);
    });

    it('passes validation when no steps are registered', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        $result = AwardValidationPipeline::validate(
            awardable: $user,
            type: AwardType::Achievement,
            amount: 0,
            reason: 'test',
        );

        expect($result['valid'])->toBeTrue();
    });

    it('passes validation when all steps return valid', function () {
        AwardValidationPipeline::addStep(function ($awardable, $type, $amount) {
            return ['valid' => true];
        });

        AwardValidationPipeline::addStep(function ($awardable, $type, $amount) {
            return ['valid' => true];
        });

        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        $result = AwardValidationPipeline::validate(
            awardable: $user,
            type: AwardType::Achievement,
            amount: 0,
        );

        expect($result['valid'])->toBeTrue();
    });

    it('fails validation when a step returns invalid', function () {
        AwardValidationPipeline::addStep(function ($awardable, $type, $amount, $reason) {
            if ($reason === 'blocked') {
                return [
                    'valid' => false,
                    'message' => 'Award blocked by custom validation',
                    'errors' => ['reason' => ['This reason is not allowed']],
                ];
            }

            return ['valid' => true];
        });

        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        $result = AwardValidationPipeline::validate(
            awardable: $user,
            type: AwardType::Achievement,
            amount: 0,
            reason: 'blocked',
        );

        expect($result['valid'])->toBeFalse()
            ->and($result['message'])->toBe('Award blocked by custom validation')
            ->and($result['errors'])->toHaveKey('reason');
    });

    it('halts pipeline execution on first failure', function () {
        $callCount = 0;

        AwardValidationPipeline::addStep(function ($awardable, $type, $amount) use (&$callCount) {
            $callCount++;

            return ['valid' => false, 'message' => 'First step failed'];
        });

        AwardValidationPipeline::addStep(function ($awardable, $type, $amount) use (&$callCount) {
            $callCount++; // This should not be called

            return ['valid' => true];
        });

        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        AwardValidationPipeline::validate(
            awardable: $user,
            type: AwardType::Achievement,
            amount: 0,
        );

        expect($callCount)->toBe(1); // Only first step should be called
    });

    it('prevents award when validation fails', function () {
        AwardValidationPipeline::addStep(function ($awardable, $type, $amount, $reason) {
            if ($reason === 'blocked') {
                return [
                    'valid' => false,
                    'message' => 'Award blocked',
                    'errors' => ['reason' => ['This reason is not allowed']],
                ];
            }

            return ['valid' => true];
        });

        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        $result = LFL::award('achievement')
            ->to($user)
            ->achievement('test-achievement')
            ->for('blocked')
            ->grant();

        expect($result->failed())->toBeTrue()
            ->and($result->message)->toContain('Award blocked');
    });

    it('allows award when validation passes', function () {
        AwardValidationPipeline::addStep(function ($awardable, $type, $amount) {
            return ['valid' => true];
        });

        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        $result = LFL::award('achievement')
            ->to($user)
            ->achievement('test-achievement')
            ->for('allowed')
            ->grant();

        expect($result->succeeded())->toBeTrue();
    });

    it('can access all pipeline parameters in validation step', function () {
        $capturedParams = null;

        AwardValidationPipeline::addStep(function ($awardable, $type, $amount, $reason, $source, $meta) use (&$capturedParams) {
            $capturedParams = [
                'awardable' => $awardable,
                'type' => $type,
                'amount' => $amount,
                'reason' => $reason,
                'source' => $source,
                'meta' => $meta,
            ];

            return ['valid' => true];
        });

        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        AwardValidationPipeline::validate(
            awardable: $user,
            type: AwardType::Achievement,
            amount: 0,
            reason: 'test reason',
            source: 'test source',
            meta: ['key' => 'value'],
        );

        expect($capturedParams)->not->toBeNull()
            ->and($capturedParams['awardable'])->toBe($user)
            ->and($capturedParams['type'])->toBeInstanceOf(AwardType::class)
            ->and($capturedParams['amount'])->toBe(0)
            ->and($capturedParams['reason'])->toBe('test reason')
            ->and($capturedParams['source'])->toBe('test source')
            ->and($capturedParams['meta'])->toBe(['key' => 'value']);
    });
});

describe('GamedMetric XP System', function () {
    beforeEach(function () {
        GamedMetric::create([
            'slug' => 'combat-xp',
            'name' => 'Combat XP',
            'description' => 'Experience from combat',
            'active' => true,
        ]);

        GamedMetric::create([
            'slug' => 'crafting-xp',
            'name' => 'Crafting XP',
            'description' => 'Experience from crafting',
            'active' => true,
        ]);
    });

    it('can award XP to different GamedMetrics', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        $combatResult = LFL::awardGamedMetric($user, 'combat-xp', 100);
        $craftingResult = LFL::awardGamedMetric($user, 'crafting-xp', 50);

        expect($combatResult->total_xp)->toBe(100)
            ->and($craftingResult->total_xp)->toBe(50);
    });

    it('accumulates XP within the same GamedMetric', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        LFL::awardGamedMetric($user, 'combat-xp', 100);
        LFL::awardGamedMetric($user, 'combat-xp', 50);

        $profile = $user->getProfile()->fresh();
        $combatXp = $profile->getXpFor('combat-xp');

        expect($combatXp)->toBe(150);
    });

    it('tracks total XP across all metrics', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        LFL::awardGamedMetric($user, 'combat-xp', 100);
        LFL::awardGamedMetric($user, 'crafting-xp', 50);

        $profile = $user->getProfile()->fresh();

        expect($profile->total_xp)->toBe(150);
    });

    it('throws exception for inactive GamedMetric', function () {
        GamedMetric::create([
            'slug' => 'inactive-xp',
            'name' => 'Inactive XP',
            'description' => 'This metric is inactive',
            'active' => false,
        ]);

        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        expect(fn () => LFL::awardGamedMetric($user, 'inactive-xp', 100))
            ->toThrow(\InvalidArgumentException::class);
    });

    it('throws exception for non-existent GamedMetric', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        expect(fn () => LFL::awardGamedMetric($user, 'non-existent', 100))
            ->toThrow(\InvalidArgumentException::class);
    });
});
