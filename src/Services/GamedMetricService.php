<?php

declare(strict_types=1);

namespace LaravelFunLab\Services;

use Illuminate\Database\Eloquent\Model;
use LaravelFunLab\Models\GamedMetric;
use LaravelFunLab\Models\UserGamedMetric;
use LaravelFunLab\Services\MetricLevelService;

/**
 * GamedMetricService
 *
 * Handles awarding XP to GamedMetrics and tracking user progress.
 */
class GamedMetricService
{
    public function __construct(
        protected MetricLevelService $levelService
    ) {}
    /**
     * Award XP to a specific GamedMetric for an awardable entity.
     *
     * @param  Model  $awardable  The entity receiving XP
     * @param  string|GamedMetric  $gamedMetric  GamedMetric slug or model instance
     * @param  int  $amount  Amount of XP to award
     * @return UserGamedMetric The updated UserGamedMetric record
     */
    public function awardXp(
        Model $awardable,
        string|GamedMetric $gamedMetric,
        int $amount
    ): UserGamedMetric {
        // Resolve GamedMetric
        $metric = $gamedMetric instanceof GamedMetric
            ? $gamedMetric
            : GamedMetric::findBySlug($gamedMetric);

        if ($metric === null) {
            throw new \InvalidArgumentException("GamedMetric '{$gamedMetric}' not found.");
        }

        if (! $metric->active) {
            throw new \InvalidArgumentException("GamedMetric '{$metric->slug}' is not active.");
        }

        // Get or create UserGamedMetric record
        $userMetric = UserGamedMetric::firstOrCreate(
            [
                'awardable_type' => get_class($awardable),
                'awardable_id' => $awardable->getKey(),
                'gamed_metric_id' => $metric->id,
            ],
            [
                'total_xp' => 0,
                'current_level' => 1,
            ]
        );

        // Add XP
        $userMetric->addXp($amount);
        $userMetric->refresh();

        // Check for level progression
        $this->levelService->checkProgression($userMetric);

        return $userMetric;
    }

    /**
     * Get the UserGamedMetric record for an awardable entity and GamedMetric.
     *
     * @param  Model  $awardable  The entity
     * @param  string|GamedMetric  $gamedMetric  GamedMetric slug or model instance
     * @return UserGamedMetric|null The UserGamedMetric record or null if not found
     */
    public function getUserMetric(
        Model $awardable,
        string|GamedMetric $gamedMetric
    ): ?UserGamedMetric {
        $metric = $gamedMetric instanceof GamedMetric
            ? $gamedMetric
            : GamedMetric::findBySlug($gamedMetric);

        if ($metric === null) {
            return null;
        }

        return UserGamedMetric::where('awardable_type', get_class($awardable))
            ->where('awardable_id', $awardable->getKey())
            ->where('gamed_metric_id', $metric->id)
            ->first();
    }

    /**
     * Get total XP for a GamedMetric for an awardable entity.
     *
     * @param  Model  $awardable  The entity
     * @param  string|GamedMetric  $gamedMetric  GamedMetric slug or model instance
     * @return int Total XP (0 if no record exists)
     */
    public function getTotalXp(
        Model $awardable,
        string|GamedMetric $gamedMetric
    ): int {
        $userMetric = $this->getUserMetric($awardable, $gamedMetric);

        return $userMetric?->total_xp ?? 0;
    }

    /**
     * Get current level for a GamedMetric for an awardable entity.
     *
     * @param  Model  $awardable  The entity
     * @param  string|GamedMetric  $gamedMetric  GamedMetric slug or model instance
     * @return int Current level (1 if no record exists)
     */
    public function getCurrentLevel(
        Model $awardable,
        string|GamedMetric $gamedMetric
    ): int {
        $userMetric = $this->getUserMetric($awardable, $gamedMetric);

        return $userMetric?->current_level ?? 1;
    }
}

