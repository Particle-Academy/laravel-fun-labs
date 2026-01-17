<?php

declare(strict_types=1);

use LaravelFunLab\Facades\LFL;
use LaravelFunLab\Models\GamedMetric;
use LaravelFunLab\Models\MetricLevelGroup;
use LaravelFunLab\Models\Profile;
use LaravelFunLab\Models\ProfileMetricGroup;
use LaravelFunLab\Services\MetricLevelGroupService;
use LaravelFunLab\Tests\Fixtures\User;

/*
|--------------------------------------------------------------------------
| MetricLevelGroup Tests
|--------------------------------------------------------------------------
|
| Tests for MetricLevelGroup functionality including ProfileMetricGroup
| model, stored level progression, and group level calculations.
|
*/

describe('ProfileMetricGroup Model', function () {

    it('can create a ProfileMetricGroup for a profile and group', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'profile-metric-group@example.com']);
        $profile = $user->getProfile();

        $group = MetricLevelGroup::create([
            'slug' => 'test-group',
            'name' => 'Test Group',
        ]);

        $profileMetricGroup = ProfileMetricGroup::create([
            'profile_id' => $profile->id,
            'metric_level_group_id' => $group->id,
            'current_level' => 1,
        ]);

        expect($profileMetricGroup)->toBeInstanceOf(ProfileMetricGroup::class)
            ->and($profileMetricGroup->profile_id)->toBe($profile->id)
            ->and($profileMetricGroup->metric_level_group_id)->toBe($group->id)
            ->and($profileMetricGroup->current_level)->toBe(1);
    });

    it('has relationship to Profile', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'profile-relation@example.com']);
        $profile = $user->getProfile();

        $group = MetricLevelGroup::create([
            'slug' => 'test-group-2',
            'name' => 'Test Group 2',
        ]);

        $profileMetricGroup = ProfileMetricGroup::create([
            'profile_id' => $profile->id,
            'metric_level_group_id' => $group->id,
            'current_level' => 1,
        ]);

        expect($profileMetricGroup->profile)->toBeInstanceOf(Profile::class)
            ->and($profileMetricGroup->profile->id)->toBe($profile->id);
    });

    it('has relationship to MetricLevelGroup', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'group-relation@example.com']);
        $profile = $user->getProfile();

        $group = MetricLevelGroup::create([
            'slug' => 'test-group-3',
            'name' => 'Test Group 3',
        ]);

        $profileMetricGroup = ProfileMetricGroup::create([
            'profile_id' => $profile->id,
            'metric_level_group_id' => $group->id,
            'current_level' => 1,
        ]);

        expect($profileMetricGroup->metricLevelGroup)->toBeInstanceOf(MetricLevelGroup::class)
            ->and($profileMetricGroup->metricLevelGroup->id)->toBe($group->id);
    });

    it('can update current level using setLevel method', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'set-level@example.com']);
        $profile = $user->getProfile();

        $group = MetricLevelGroup::create([
            'slug' => 'test-group-4',
            'name' => 'Test Group 4',
        ]);

        $profileMetricGroup = ProfileMetricGroup::create([
            'profile_id' => $profile->id,
            'metric_level_group_id' => $group->id,
            'current_level' => 1,
        ]);

        $profileMetricGroup->setLevel(3);
        $profileMetricGroup->refresh();

        expect($profileMetricGroup->current_level)->toBe(3);
    });

});

describe('MetricLevelGroupService with ProfileMetricGroup', function () {

    it('creates ProfileMetricGroup when checking progression', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'service-progression@example.com']);
        $profile = $user->getProfile();

        // Create metrics
        $metric1 = GamedMetric::create([
            'slug' => 'test-metric-1',
            'name' => 'Test Metric 1',
            'active' => true,
        ]);

        $metric2 = GamedMetric::create([
            'slug' => 'test-metric-2',
            'name' => 'Test Metric 2',
            'active' => true,
        ]);

        // Create group
        $group = MetricLevelGroup::create([
            'slug' => 'test-service-group',
            'name' => 'Test Service Group',
        ]);

        // Add metrics to group
        LFL::setup(a: 'metric-level-group-metric', with: ['group' => 'test-service-group', 'metric' => 'test-metric-1', 'weight' => 1.0]);
        LFL::setup(a: 'metric-level-group-metric', with: ['group' => 'test-service-group', 'metric' => 'test-metric-2', 'weight' => 1.0]);

        // Create levels
        LFL::setup(a: 'metric-level-group-level', with: ['group' => 'test-service-group', 'level' => 1, 'xp' => 0, 'name' => 'Level 1']);
        LFL::setup(a: 'metric-level-group-level', with: ['group' => 'test-service-group', 'level' => 2, 'xp' => 100, 'name' => 'Level 2']);
        LFL::setup(a: 'metric-level-group-level', with: ['group' => 'test-service-group', 'level' => 3, 'xp' => 300, 'name' => 'Level 3']);

        $service = app(MetricLevelGroupService::class);
        $profileMetricGroup = $service->getOrCreateProfileMetricGroup($profile, $group);

        expect($profileMetricGroup)->toBeInstanceOf(ProfileMetricGroup::class)
            ->and($profileMetricGroup->current_level)->toBe(1);
    });

    it('updates stored current_level when progression occurs', function () {
        // This test verifies that awarding XP automatically triggers group progression
        // The GamedMetricService::awardXp calls checkGroupProgression after each award
        $user = User::create(['name' => 'Test User', 'email' => 'stored-progression@example.com']);
        $profile = $user->getProfile();

        // Create metrics
        $metric1 = GamedMetric::create([
            'slug' => 'test-metric-prog-1',
            'name' => 'Test Metric Prog 1',
            'active' => true,
        ]);

        $metric2 = GamedMetric::create([
            'slug' => 'test-metric-prog-2',
            'name' => 'Test Metric Prog 2',
            'active' => true,
        ]);

        // Create group
        $group = MetricLevelGroup::create([
            'slug' => 'test-progression-group',
            'name' => 'Test Progression Group',
        ]);

        // Add metrics to group
        LFL::setup(a: 'metric-level-group-metric', with: ['group' => 'test-progression-group', 'metric' => 'test-metric-prog-1', 'weight' => 1.0]);
        LFL::setup(a: 'metric-level-group-metric', with: ['group' => 'test-progression-group', 'metric' => 'test-metric-prog-2', 'weight' => 1.0]);

        // Create levels
        LFL::setup(a: 'metric-level-group-level', with: ['group' => 'test-progression-group', 'level' => 1, 'xp' => 0, 'name' => 'Level 1']);
        LFL::setup(a: 'metric-level-group-level', with: ['group' => 'test-progression-group', 'level' => 2, 'xp' => 100, 'name' => 'Level 2']);
        LFL::setup(a: 'metric-level-group-level', with: ['group' => 'test-progression-group', 'level' => 3, 'xp' => 300, 'name' => 'Level 3']);

        // Award XP to reach level 2 - this automatically triggers group progression
        LFL::award('test-metric-prog-1')->to($user)->amount(60)->save();
        LFL::award('test-metric-prog-2')->to($user)->amount(60)->save();

        $service = app(MetricLevelGroupService::class);

        // Verify the ProfileMetricGroup was created and level was updated automatically
        $profileMetricGroup = ProfileMetricGroup::where('profile_id', $profile->id)
            ->where('metric_level_group_id', $group->id)
            ->first();

        // Verify XP totals
        $totalXp = $service->getTotalXp($user, $group);

        expect($profileMetricGroup)->not->toBeNull('ProfileMetricGroup should be created automatically')
            ->and($profileMetricGroup->current_level)->toBe(2, 'Level should be automatically updated to 2')
            ->and($totalXp)->toBe(120, 'Total XP should be 120');
    });

    it('uses stored current_level in getCurrentLevel', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'stored-current@example.com']);
        $profile = $user->getProfile();

        // Create metrics
        $metric1 = GamedMetric::create([
            'slug' => 'test-metric-current-1',
            'name' => 'Test Metric Current 1',
            'active' => true,
        ]);

        $metric2 = GamedMetric::create([
            'slug' => 'test-metric-current-2',
            'name' => 'Test Metric Current 2',
            'active' => true,
        ]);

        // Create group
        $group = MetricLevelGroup::create([
            'slug' => 'test-current-group',
            'name' => 'Test Current Group',
        ]);

        // Add metrics to group
        LFL::setup(a: 'metric-level-group-metric', with: ['group' => 'test-current-group', 'metric' => 'test-metric-current-1', 'weight' => 1.0]);
        LFL::setup(a: 'metric-level-group-metric', with: ['group' => 'test-current-group', 'metric' => 'test-metric-current-2', 'weight' => 1.0]);

        // Create levels
        LFL::setup(a: 'metric-level-group-level', with: ['group' => 'test-current-group', 'level' => 1, 'xp' => 0, 'name' => 'Level 1']);
        LFL::setup(a: 'metric-level-group-level', with: ['group' => 'test-current-group', 'level' => 2, 'xp' => 100, 'name' => 'Level 2']);

        // Create ProfileMetricGroup with stored level
        $profileMetricGroup = ProfileMetricGroup::create([
            'profile_id' => $profile->id,
            'metric_level_group_id' => $group->id,
            'current_level' => 2,
        ]);

        $service = app(MetricLevelGroupService::class);
        $currentLevel = $service->getCurrentLevel($user, $group);

        expect($currentLevel)->toBe(2);
    });

    it('checks group progression when XP is awarded to metrics in group', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'auto-check@example.com']);
        $profile = $user->getProfile();

        // Create metrics
        $metric1 = GamedMetric::create([
            'slug' => 'test-auto-metric-1',
            'name' => 'Test Auto Metric 1',
            'active' => true,
        ]);

        $metric2 = GamedMetric::create([
            'slug' => 'test-auto-metric-2',
            'name' => 'Test Auto Metric 2',
            'active' => true,
        ]);

        // Create group
        $group = MetricLevelGroup::create([
            'slug' => 'test-auto-group',
            'name' => 'Test Auto Group',
        ]);

        // Add metrics to group
        LFL::setup(a: 'metric-level-group-metric', with: ['group' => 'test-auto-group', 'metric' => 'test-auto-metric-1', 'weight' => 1.0]);
        LFL::setup(a: 'metric-level-group-metric', with: ['group' => 'test-auto-group', 'metric' => 'test-auto-metric-2', 'weight' => 1.0]);

        // Create levels
        LFL::setup(a: 'metric-level-group-level', with: ['group' => 'test-auto-group', 'level' => 1, 'xp' => 0, 'name' => 'Level 1']);
        LFL::setup(a: 'metric-level-group-level', with: ['group' => 'test-auto-group', 'level' => 2, 'xp' => 150, 'name' => 'Level 2']);

        // Award XP - this should trigger group progression check
        LFL::award('test-auto-metric-1')->to($user)->amount(80)->save();
        LFL::award('test-auto-metric-2')->to($user)->amount(80)->save();

        // Check that ProfileMetricGroup was created and updated
        $profileMetricGroup = ProfileMetricGroup::where('profile_id', $profile->id)
            ->where('metric_level_group_id', $group->id)
            ->first();

        expect($profileMetricGroup)->not->toBeNull()
            ->and($profileMetricGroup->current_level)->toBe(2);
    });

});
