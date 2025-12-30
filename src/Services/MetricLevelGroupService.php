<?php

declare(strict_types=1);

namespace LaravelFunLab\Services;

use Illuminate\Database\Eloquent\Model;
use LaravelFunLab\Facades\LFL;
use LaravelFunLab\Models\GamedMetric;
use LaravelFunLab\Models\MetricLevelGroup;
use LaravelFunLab\Models\MetricLevelGroupLevel;
use LaravelFunLab\Models\UserGamedMetric;

/**
 * MetricLevelGroupService
 *
 * Handles aggregation of multiple GamedMetrics and group level calculations.
 */
class MetricLevelGroupService
{
    /**
     * Calculate total combined XP for a MetricLevelGroup for an awardable entity.
     *
     * @param  Model  $awardable  The awardable entity
     * @param  string|MetricLevelGroup  $group  Group slug or model instance
     * @return int Total combined XP (sum of weighted XP from all metrics in group)
     */
    public function getTotalXp(
        Model $awardable,
        string|MetricLevelGroup $group
    ): int {
        $groupModel = $group instanceof MetricLevelGroup
            ? $group
            : MetricLevelGroup::findBySlug($group);

        if ($groupModel === null) {
            return 0;
        }

        $totalXp = 0;
        $awardableType = get_class($awardable);
        $awardableId = $awardable->getKey();

        // Sum XP from all metrics in the group with their weights
        foreach ($groupModel->metrics as $groupMetric) {
            $userMetric = UserGamedMetric::where('awardable_type', $awardableType)
                ->where('awardable_id', $awardableId)
                ->where('gamed_metric_id', $groupMetric->gamed_metric_id)
                ->first();

            if ($userMetric !== null) {
                // Apply weight to XP
                $weightedXp = (int) ($userMetric->total_xp * $groupMetric->weight);
                $totalXp += $weightedXp;
            }
        }

        return $totalXp;
    }

    /**
     * Get current level for a MetricLevelGroup based on combined XP.
     *
     * @param  Model  $awardable  The awardable entity
     * @param  string|MetricLevelGroup  $group  Group slug or model instance
     * @return int Current level (1 if no levels defined or XP insufficient)
     */
    public function getCurrentLevel(
        Model $awardable,
        string|MetricLevelGroup $group
    ): int {
        $groupModel = $group instanceof MetricLevelGroup
            ? $group
            : MetricLevelGroup::findBySlug($group);

        if ($groupModel === null) {
            return 1;
        }

        $totalXp = $this->getTotalXp($awardable, $groupModel);

        // Find the highest level reached
        $levels = $groupModel->levels()->ordered()->get();
        $currentLevel = 1;

        foreach ($levels as $level) {
            if ($level->isReached($totalXp)) {
                $currentLevel = $level->level;
            } else {
                break;
            }
        }

        return $currentLevel;
    }

    /**
     * Get the next level threshold for a MetricLevelGroup.
     *
     * @param  Model  $awardable  The awardable entity
     * @param  string|MetricLevelGroup  $group  Group slug or model instance
     * @return int|null The next level's XP threshold, or null if max level reached
     */
    public function getNextLevelThreshold(
        Model $awardable,
        string|MetricLevelGroup $group
    ): ?int {
        $groupModel = $group instanceof MetricLevelGroup
            ? $group
            : MetricLevelGroup::findBySlug($group);

        if ($groupModel === null) {
            return null;
        }

        $currentLevel = $this->getCurrentLevel($awardable, $groupModel);

        $nextLevel = MetricLevelGroupLevel::where('metric_level_group_id', $groupModel->id)
            ->where('level', '>', $currentLevel)
            ->ordered()
            ->first();

        return $nextLevel?->xp_threshold;
    }

    /**
     * Get progress percentage towards next level for a group.
     *
     * @param  Model  $awardable  The awardable entity
     * @param  string|MetricLevelGroup  $group  Group slug or model instance
     * @return float Progress percentage (0-100)
     */
    public function getProgressPercentage(
        Model $awardable,
        string|MetricLevelGroup $group
    ): float {
        $groupModel = $group instanceof MetricLevelGroup
            ? $group
            : MetricLevelGroup::findBySlug($group);

        if ($groupModel === null) {
            return 0.0;
        }

        $totalXp = $this->getTotalXp($awardable, $groupModel);
        $currentLevel = $this->getCurrentLevel($awardable, $groupModel);

        // Get current level threshold
        $currentLevelThreshold = MetricLevelGroupLevel::where('metric_level_group_id', $groupModel->id)
            ->where('level', $currentLevel)
            ->first()?->xp_threshold ?? 0;

        // Get next level threshold
        $nextLevelThreshold = $this->getNextLevelThreshold($awardable, $groupModel);

        if ($nextLevelThreshold === null) {
            // Max level reached
            return 100.0;
        }

        $progressXp = $totalXp - $currentLevelThreshold;
        $requiredXp = $nextLevelThreshold - $currentLevelThreshold;

        if ($requiredXp <= 0) {
            return 100.0;
        }

        return min(100.0, ($progressXp / $requiredXp) * 100);
    }

    /**
     * Check and return level progression for a group.
     *
     * @param  Model  $awardable  The awardable entity
     * @param  string|MetricLevelGroup  $group  Group slug or model instance
     * @return array<string, mixed> Array with level information
     */
    public function getLevelInfo(
        Model $awardable,
        string|MetricLevelGroup $group
    ): array {
        $groupModel = $group instanceof MetricLevelGroup
            ? $group
            : MetricLevelGroup::findBySlug($group);

        if ($groupModel === null) {
            return [
                'current_level' => 1,
                'total_xp' => 0,
                'next_level_threshold' => null,
                'progress_percentage' => 0.0,
            ];
        }

        $totalXp = $this->getTotalXp($awardable, $groupModel);
        $currentLevel = $this->getCurrentLevel($awardable, $groupModel);
        $nextThreshold = $this->getNextLevelThreshold($awardable, $groupModel);
        $progress = $this->getProgressPercentage($awardable, $groupModel);

        return [
            'current_level' => $currentLevel,
            'total_xp' => $totalXp,
            'next_level_threshold' => $nextThreshold,
            'progress_percentage' => $progress,
        ];
    }

    /**
     * Check and award achievements for group level progression.
     * Call this method after XP is awarded to any metric in a group.
     *
     * @param  Model  $awardable  The awardable entity
     * @param  string|MetricLevelGroup  $group  Group slug or model instance
     * @return array<string, mixed> Array with 'levels_unlocked' and 'achievements_awarded'
     */
    public function checkProgression(
        Model $awardable,
        string|MetricLevelGroup $group
    ): array {
        $groupModel = $group instanceof MetricLevelGroup
            ? $group
            : MetricLevelGroup::findBySlug($group);

        if ($groupModel === null) {
            return [
                'levels_unlocked' => [],
                'achievements_awarded' => [],
            ];
        }

        $totalXp = $this->getTotalXp($awardable, $groupModel);
        $levels = $groupModel->levels()->ordered()->get();
        $levelsUnlocked = [];
        $achievementsAwarded = [];

        // Find all levels reached
        foreach ($levels as $level) {
            if ($level->isReached($totalXp)) {
                $levelsUnlocked[] = $level;

                // Auto-award achievements attached to this level
                $achievements = $level->achievements()->active()->get();

                foreach ($achievements as $achievement) {
                    // Only award if the achievement is for this awardable type or universal
                    if ($achievement->awardable_type === null || $achievement->awardable_type === get_class($awardable)) {
                        // Check if already granted (skip if already has it)
                        if (! $awardable->hasAchievement($achievement)) {
                            $result = LFL::grantAchievement(
                                $awardable,
                                $achievement->slug,
                                "Reached level {$level->level}: {$level->name}",
                                'metric-level-group-progression',
                                [
                                    'metric_level_group_level_id' => $level->id,
                                    'metric_level_group_id' => $groupModel->id,
                                    'level' => $level->level,
                                ]
                            );

                            if ($result->succeeded()) {
                                $achievementsAwarded[] = $achievement;
                            }
                        }
                    }
                }
            }
        }

        return [
            'levels_unlocked' => $levelsUnlocked,
            'achievements_awarded' => $achievementsAwarded,
        ];
    }
}

