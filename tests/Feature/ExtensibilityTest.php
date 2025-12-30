<?php

declare(strict_types=1);

use LaravelFunLab\Contracts\AnalyticsServiceContract;
use LaravelFunLab\Contracts\AwardEngineContract;
use LaravelFunLab\Contracts\LeaderboardServiceContract;
use LaravelFunLab\Enums\AwardType;
use LaravelFunLab\Facades\LFL;
use LaravelFunLab\Pipelines\AwardValidationPipeline;
use LaravelFunLab\Registries\AwardTypeRegistry;
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

describe('Custom Award Type Registration', function () {
    beforeEach(function () {
        AwardTypeRegistry::flush();
    });

    it('can register a custom award type', function () {
        AwardTypeRegistry::register('coins', 'Coins', 'coin', true, 100);

        expect(AwardTypeRegistry::isRegistered('coins'))->toBeTrue();
    });

    it('can register multiple custom award types', function () {
        AwardTypeRegistry::registerMany([
            'coins' => ['name' => 'Coins', 'icon' => 'coin', 'cumulative' => true],
            'stars' => ['name' => 'Stars', 'icon' => 'star', 'cumulative' => true],
        ]);

        expect(AwardTypeRegistry::isRegistered('coins'))->toBeTrue()
            ->and(AwardTypeRegistry::isRegistered('stars'))->toBeTrue();
    });

    it('can get metadata for registered custom type', function () {
        AwardTypeRegistry::register('coins', 'Coins', 'coin', true, 100);

        $metadata = AwardTypeRegistry::getMetadata('coins');

        expect($metadata)->toBeArray()
            ->and($metadata['name'])->toBe('Coins')
            ->and($metadata['icon'])->toBe('coin')
            ->and($metadata['cumulative'])->toBeTrue()
            ->and($metadata['default_amount'])->toBe(100);
    });

    it('can award custom award type', function () {
        AwardTypeRegistry::register('coins', 'Coins', 'coin', true, 100);

        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        $result = LFL::award('coins')
            ->to($user)
            ->amount(50)
            ->for('test')
            ->grant();

        expect($result->succeeded())->toBeTrue()
            ->and($result->award->type)->toBe('coins')
            ->and((float) $result->award->amount)->toBe(50.0);
    });

    it('throws exception for unregistered custom type', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        expect(fn () => LFL::award('unregistered-type')->to($user)->grant())
            ->toThrow(\InvalidArgumentException::class);
    });

    it('uses default amount from registry for custom type', function () {
        AwardTypeRegistry::register('coins', 'Coins', 'coin', true, 200);

        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        $result = LFL::award('coins')
            ->to($user)
            ->for('test')
            ->grant();

        expect($result->succeeded())->toBeTrue()
            ->and((float) $result->award->amount)->toBe(200.0);
    });

    it('recognizes built-in types as registered', function () {
        expect(AwardTypeRegistry::isRegistered('points'))->toBeTrue()
            ->and(AwardTypeRegistry::isRegistered('achievement'))->toBeTrue()
            ->and(AwardTypeRegistry::isRegistered('prize'))->toBeTrue()
            ->and(AwardTypeRegistry::isRegistered('badge'))->toBeTrue();
    });
});

describe('Award Validation Pipeline', function () {
    beforeEach(function () {
        AwardValidationPipeline::flush();
    });

    it('passes validation when no steps are registered', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        $result = AwardValidationPipeline::validate(
            awardable: $user,
            type: AwardType::Points,
            amount: 100,
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
            type: AwardType::Points,
            amount: 100,
        );

        expect($result['valid'])->toBeTrue();
    });

    it('fails validation when a step returns invalid', function () {
        AwardValidationPipeline::addStep(function ($awardable, $type, $amount) {
            if ($amount > 50) {
                return [
                    'valid' => false,
                    'message' => 'Amount too high',
                    'errors' => ['amount' => ['Amount cannot exceed 50']],
                ];
            }

            return ['valid' => true];
        });

        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        $result = AwardValidationPipeline::validate(
            awardable: $user,
            type: AwardType::Points,
            amount: 100,
        );

        expect($result['valid'])->toBeFalse()
            ->and($result['message'])->toBe('Amount too high')
            ->and($result['errors'])->toHaveKey('amount');
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
            type: AwardType::Points,
            amount: 100,
        );

        expect($callCount)->toBe(1); // Only first step should be called
    });

    it('prevents award when validation fails', function () {
        AwardValidationPipeline::addStep(function ($awardable, $type, $amount) {
            if ($amount > 1000) {
                return [
                    'valid' => false,
                    'message' => 'Amount exceeds maximum',
                    'errors' => ['amount' => ['Maximum amount is 1000']],
                ];
            }

            return ['valid' => true];
        });

        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        $result = LFL::award('points')
            ->to($user)
            ->amount(2000)
            ->for('test')
            ->grant();

        expect($result->failed())->toBeTrue()
            ->and($result->message)->toContain('Amount exceeds maximum');
    });

    it('allows award when validation passes', function () {
        AwardValidationPipeline::addStep(function ($awardable, $type, $amount) {
            return ['valid' => true];
        });

        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);

        $result = LFL::award('points')
            ->to($user)
            ->amount(100)
            ->for('test')
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
            type: AwardType::Points,
            amount: 100,
            reason: 'test reason',
            source: 'test source',
            meta: ['key' => 'value'],
        );

        expect($capturedParams)->not->toBeNull()
            ->and($capturedParams['awardable'])->toBe($user)
            ->and($capturedParams['type'])->toBeInstanceOf(AwardType::class)
            ->and($capturedParams['amount'])->toBe(100)
            ->and($capturedParams['reason'])->toBe('test reason')
            ->and($capturedParams['source'])->toBe('test source')
            ->and($capturedParams['meta'])->toBe(['key' => 'value']);
    });
});
