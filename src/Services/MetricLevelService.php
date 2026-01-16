<?php

declare(strict_types=1);

namespace LaravelFunLab\Services;

use Illuminate\Database\Eloquent\Model;
use LaravelFunLab\Facades\LFL;
use LaravelFunLab\Models\Achievement;
use LaravelFunLab\Models\GamedMetric;
use LaravelFunLab\Models\MetricLevel;
use LaravelFunLab\Models\Profile;
use LaravelFunLab\Models\ProfileMetric;

/**
 * MetricLevelService
 *
 * Handles level checking and progression for GamedMetrics.
 */
class MetricLevelService
{
    /**
     * Check and update level progression for a ProfileMetric.
     *
     * @param  ProfileMetric  $profileMetric  The ProfileMetric to check
     * @return array<string, mixed> Array with 'level_reached' (bool), 'new_level' (int|null), 'levels_unlocked' (array)
     */
    public function checkProgression(ProfileMetric $profileMetric): array
    {
        $metric = $profileMetric->gamedMetric;
        $totalXp = $profileMetric->total_xp;
        $currentLevel = $profileMetric->current_level;

        // Get all levels for this metric, ordered by level
        $levels = MetricLevel::forMetric($metric->id)
            ->ordered()
            ->get();

        $newLevel = null;
        $levelsUnlocked = [];

        // Find the highest level reached
        foreach ($levels as $level) {
            if ($level->isReached($totalXp) && $level->level > $currentLevel) {
                $newLevel = $level->level;
                $levelsUnlocked[] = $level;
            }
        }

        // Update current level if a new level was reached
        if ($newLevel !== null) {
            $profileMetric->setLevel($newLevel);

            // Auto-award achievements attached to unlocked levels
            foreach ($levelsUnlocked as $level) {
                $achievements = $level->achievements()->active()->get();
                $awardable = $profileMetric->profile->awardable;

                foreach ($achievements as $achievement) {
                    // Only award if the achievement is for this awardable type or universal
                    if ($achievement->awardable_type === null || $achievement->awardable_type === get_class($awardable)) {
                        // Check if already granted (skip if already has it)
                        if (! $awardable->hasAchievement($achievement)) {
                            LFL::grant($achievement->slug)
                                ->to($awardable)
                                ->because("Reached level {$level->level}: {$level->name}")
                                ->from('metric-level-progression')
                                ->withMeta([
                                    'metric_level_id' => $level->id,
                                    'gamed_metric_id' => $metric->id,
                                    'level' => $level->level,
                                ])
                                ->save();
                        }
                    }
                }
            }
        }

        return [
            'level_reached' => $newLevel !== null,
            'new_level' => $newLevel,
            'levels_unlocked' => $levelsUnlocked,
        ];
    }

    /**
     * Check if a profile has reached a specific level in a GamedMetric.
     *
     * @param  Profile  $profile  The profile to check
     * @param  string  $metricSlug  The GamedMetric slug
     * @param  int  $level  The level to check
     * @return bool Whether the profile has reached the level
     */
    public function hasReachedLevel(Profile $profile, string $metricSlug, int $level): bool
    {
        $metric = GamedMetric::findBySlug($metricSlug);
        if ($metric === null) {
            return false;
        }

        $profileMetric = ProfileMetric::where('profile_id', $profile->id)
            ->where('gamed_metric_id', $metric->id)
            ->first();

        if ($profileMetric === null) {
            return false;
        }

        // Check if current level is >= requested level
        if ($profileMetric->current_level >= $level) {
            return true;
        }

        // Also check if XP meets the level threshold (in case level wasn't updated)
        $metricLevel = MetricLevel::forMetric($metric->id)
            ->where('level', $level)
            ->first();

        if ($metricLevel === null) {
            // Level not defined, just check current level
            return $profileMetric->current_level >= $level;
        }

        return $profileMetric->total_xp >= $metricLevel->xp_threshold;
    }

    /**
     * Get the current level for a GamedMetric based on XP.
     *
     * @param  Model  $awardable  The awardable entity
     * @param  string|GamedMetric  $gamedMetric  GamedMetric slug or model instance
     * @return int The current level (1 if no levels defined or XP insufficient)
     */
    public function getCurrentLevel(
        Model $awardable,
        string|GamedMetric $gamedMetric
    ): int {
        $metric = $gamedMetric instanceof GamedMetric
            ? $gamedMetric
            : GamedMetric::findBySlug($gamedMetric);

        if ($metric === null) {
            return 1;
        }

        $profile = Profile::where('awardable_type', get_class($awardable))
            ->where('awardable_id', $awardable->getKey())
            ->first();

        if ($profile === null) {
            return 1;
        }

        $profileMetric = ProfileMetric::where('profile_id', $profile->id)
            ->where('gamed_metric_id', $metric->id)
            ->first();

        if ($profileMetric === null) {
            return 1;
        }

        return $profileMetric->current_level;
    }

    /**
     * Get the next level threshold for a GamedMetric.
     *
     * @param  Model  $awardable  The awardable entity
     * @param  string|GamedMetric  $gamedMetric  GamedMetric slug or model instance
     * @return int|null The next level's XP threshold, or null if max level reached
     */
    public function getNextLevelThreshold(
        Model $awardable,
        string|GamedMetric $gamedMetric
    ): ?int {
        $metric = $gamedMetric instanceof GamedMetric
            ? $gamedMetric
            : GamedMetric::findBySlug($gamedMetric);

        if ($metric === null) {
            return null;
        }

        $profile = Profile::where('awardable_type', get_class($awardable))
            ->where('awardable_id', $awardable->getKey())
            ->first();

        $profileMetric = $profile ? ProfileMetric::where('profile_id', $profile->id)
            ->where('gamed_metric_id', $metric->id)
            ->first() : null;

        $currentLevel = $profileMetric?->current_level ?? 1;

        $nextLevel = MetricLevel::forMetric($metric->id)
            ->where('level', '>', $currentLevel)
            ->ordered()
            ->first();

        return $nextLevel?->xp_threshold;
    }

    /**
     * Get progress percentage towards next level.
     *
     * @param  Model  $awardable  The awardable entity
     * @param  string|GamedMetric  $gamedMetric  GamedMetric slug or model instance
     * @return float Progress percentage (0-100)
     */
    public function getProgressPercentage(
        Model $awardable,
        string|GamedMetric $gamedMetric
    ): float {
        $metric = $gamedMetric instanceof GamedMetric
            ? $gamedMetric
            : GamedMetric::findBySlug($gamedMetric);

        if ($metric === null) {
            return 0.0;
        }

        $profile = Profile::where('awardable_type', get_class($awardable))
            ->where('awardable_id', $awardable->getKey())
            ->first();

        if ($profile === null) {
            return 0.0;
        }

        $profileMetric = ProfileMetric::where('profile_id', $profile->id)
            ->where('gamed_metric_id', $metric->id)
            ->first();

        if ($profileMetric === null) {
            return 0.0;
        }

        $currentXp = $profileMetric->total_xp;
        $currentLevel = $profileMetric->current_level;

        // Get current level threshold
        $currentLevelThreshold = MetricLevel::forMetric($metric->id)
            ->where('level', $currentLevel)
            ->first()?->xp_threshold ?? 0;

        // Get next level threshold
        $nextLevelThreshold = $this->getNextLevelThreshold($awardable, $gamedMetric);

        if ($nextLevelThreshold === null) {
            // Max level reached
            return 100.0;
        }

        $progressXp = $currentXp - $currentLevelThreshold;
        $requiredXp = $nextLevelThreshold - $currentLevelThreshold;

        if ($requiredXp <= 0) {
            return 100.0;
        }

        return min(100.0, ($progressXp / $requiredXp) * 100);
    }
}
