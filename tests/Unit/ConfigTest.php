<?php

declare(strict_types=1);

use LaravelFunLab\Facades\LFL;

/**
 * Config System Tests
 *
 * Verifies that the LFL configuration system works correctly,
 * including table prefixes, feature flags, defaults, and API config.
 */
it('has default table prefix of lfl_', function () {
    expect(config('lfl.table_prefix'))->toBe('lfl_');
    expect(LFL::getTablePrefix())->toBe('lfl_');
});

it('allows custom table prefix via config', function () {
    config()->set('lfl.table_prefix', 'custom_');

    expect(config('lfl.table_prefix'))->toBe('custom_');
    expect(LFL::getTablePrefix())->toBe('custom_');
});

it('has default feature flags enabled', function () {
    expect(config('lfl.features.achievements'))->toBeTrue();
    expect(config('lfl.features.leaderboards'))->toBeTrue();
    expect(config('lfl.features.prizes'))->toBeTrue();
    expect(config('lfl.features.profiles'))->toBeTrue();
    expect(config('lfl.features.analytics'))->toBeTrue();
});

it('can check if feature is enabled via facade', function () {
    expect(LFL::isFeatureEnabled('achievements'))->toBeTrue();
    expect(LFL::isFeatureEnabled('leaderboards'))->toBeTrue();
    expect(LFL::isFeatureEnabled('prizes'))->toBeTrue();
    expect(LFL::isFeatureEnabled('profiles'))->toBeTrue();
    expect(LFL::isFeatureEnabled('analytics'))->toBeTrue();
});

it('returns false for disabled features', function () {
    config()->set('lfl.features.profiles', false);

    expect(LFL::isFeatureEnabled('profiles'))->toBeFalse();
});

it('returns false for undefined features', function () {
    expect(LFL::isFeatureEnabled('nonexistent_feature'))->toBeFalse();
});

it('can get all enabled features', function () {
    config()->set('lfl.features.profiles', false);
    config()->set('lfl.features.analytics', false);

    $enabled = LFL::getEnabledFeatures();

    expect($enabled)->toHaveKey('achievements');
    expect($enabled)->toHaveKey('leaderboards');
    expect($enabled)->toHaveKey('prizes');
    expect($enabled)->not->toHaveKey('profiles');
    expect($enabled)->not->toHaveKey('analytics');
});

it('has default points configuration', function () {
    expect(config('lfl.defaults.points'))->toBe(10);
    expect(LFL::getDefaultPoints())->toBe(10);
});

it('allows custom default points', function () {
    config()->set('lfl.defaults.points', 25);

    expect(LFL::getDefaultPoints())->toBe(25);
});

it('has default multipliers configured', function () {
    expect(config('lfl.defaults.multipliers.streak_bonus'))->toBe(1.5);
    expect(config('lfl.defaults.multipliers.first_time_bonus'))->toBe(2.0);
});

it('can get multipliers via facade', function () {
    expect(LFL::getMultiplier('streak_bonus'))->toBe(1.5);
    expect(LFL::getMultiplier('first_time_bonus'))->toBe(2.0);
    expect(LFL::getMultiplier('undefined'))->toBe(1.0);
});

it('has default award types configured', function () {
    $awardTypes = config('lfl.award_types');

    expect($awardTypes)->toHaveKey('points');
    expect($awardTypes)->toHaveKey('badge');
    expect($awardTypes)->toHaveKey('achievement');

    expect($awardTypes['points']['name'])->toBe('Points');
    expect($awardTypes['points']['cumulative'])->toBeTrue();
    expect($awardTypes['points']['default_amount'])->toBe(10);

    expect($awardTypes['badge']['cumulative'])->toBeFalse();
    expect($awardTypes['achievement']['cumulative'])->toBeFalse();
});

it('has event configuration options', function () {
    expect(config('lfl.events.dispatch'))->toBeTrue();
    expect(config('lfl.events.log_to_database'))->toBeTrue();
    expect(config('lfl.events.queue'))->toBeNull();
});

it('can check event logging status via facade', function () {
    expect(LFL::isEventLoggingEnabled())->toBeTrue();

    config()->set('lfl.events.log_to_database', false);
    expect(LFL::isEventLoggingEnabled())->toBeFalse();
});

it('can check event dispatch status via facade', function () {
    expect(LFL::isEventDispatchEnabled())->toBeTrue();

    config()->set('lfl.events.dispatch', false);
    expect(LFL::isEventDispatchEnabled())->toBeFalse();
});

it('has API configuration options', function () {
    expect(config('lfl.api.enabled'))->toBeTrue();
    expect(config('lfl.api.prefix'))->toBe('api/lfl');
    expect(config('lfl.api.middleware'))->toBe(['api']);
});

it('can check API status via facade', function () {
    expect(LFL::isApiEnabled())->toBeTrue();
    expect(LFL::getApiPrefix())->toBe('api/lfl');

    config()->set('lfl.api.enabled', false);
    expect(LFL::isApiEnabled())->toBeFalse();
});

it('has UI configuration options', function () {
    expect(config('lfl.ui.enabled'))->toBeFalse();
    expect(config('lfl.ui.prefix'))->toBe('lfl');
    expect(config('lfl.ui.middleware'))->toBe(['web']);
});

it('can check UI status via facade', function () {
    expect(LFL::isUiEnabled())->toBeFalse();

    config()->set('lfl.ui.enabled', true);
    expect(LFL::isUiEnabled())->toBeTrue();
});

it('has migrations config toggle', function () {
    expect(config('lfl.migrations'))->toBeTrue();
});
