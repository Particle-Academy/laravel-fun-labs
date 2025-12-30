<?php

declare(strict_types=1);

namespace LaravelFunLab\Services;

use Illuminate\Database\Eloquent\Model;
use LaravelFunLab\Facades\LFL;
use LaravelFunLab\Models\Achievement;
use LaravelFunLab\Models\GamedMetric;
use LaravelFunLab\Models\MetricLevel;
use LaravelFunLab\Models\UserGamedMetric;

/**
 * MetricLevelService
 *
 * Handles level checking and progression for GamedMetrics.
 */
class MetricLevelService
{
    /**
     * Check and update level progression for a UserGamedMetric.
     *
     * @param  UserGamedMetric  $userMetric  The UserGamedMetric to check
     * @return array<string, mixed> Array with 'level_reached' (bool), 'new_level' (int|null), 'levels_unlocked' (array)
     */
    public function checkProgression(UserGamedMetric $userMetric): array
    {
        $metric = $userMetric->gamedMetric;
        $totalXp = $userMetric->total_xp;
        $currentLevel = $userMetric->current_level;

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
            $userMetric->setLevel($newLevel);

            // Auto-award achievements attached to unlocked levels
            foreach ($levelsUnlocked as $level) {
                $achievements = $level->achievements()->active()->get();
                $awardable = $userMetric->awardable;

                foreach ($achievements as $achievement) {
                    // Only award if the achievement is for this awardable type or universal
                    if ($achievement->awardable_type === null || $achievement->awardable_type === get_class($awardable)) {
                        // Check if already granted (skip if already has it)
                        if (! $awardable->hasAchievement($achievement)) {
                            LFL::grantAchievement(
                                $awardable,
                                $achievement->slug,
                                "Reached level {$level->level}: {$level->name}",
                                'metric-level-progression',
                                [
                                    'metric_level_id' => $level->id,
                                    'gamed_metric_id' => $metric->id,
                                    'level' => $level->level,
                                ]
                            );
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

        $userMetric = UserGamedMetric::where('awardable_type', get_class($awardable))
            ->where('awardable_id', $awardable->getKey())
            ->where('gamed_metric_id', $metric->id)
            ->first();

        if ($userMetric === null) {
            return 1;
        }

        return $userMetric->current_level;
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

        $userMetric = UserGamedMetric::where('awardable_type', get_class($awardable))
            ->where('awardable_id', $awardable->getKey())
            ->where('gamed_metric_id', $metric->id)
            ->first();

        $currentLevel = $userMetric?->current_level ?? 1;

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

        $userMetric = UserGamedMetric::where('awardable_type', get_class($awardable))
            ->where('awardable_id', $awardable->getKey())
            ->where('gamed_metric_id', $metric->id)
            ->first();

        if ($userMetric === null) {
            return 0.0;
        }

        $currentXp = $userMetric->total_xp;
        $currentLevel = $userMetric->current_level;

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

