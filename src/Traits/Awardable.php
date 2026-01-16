<?php

declare(strict_types=1);

namespace LaravelFunLab\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use LaravelFunLab\Models\Achievement;
use LaravelFunLab\Models\AchievementGrant;
use LaravelFunLab\Models\GamedMetric;
use LaravelFunLab\Models\PrizeGrant;
use LaravelFunLab\Models\Profile;

/**
 * Awardable Trait
 *
 * Apply this trait to any Eloquent model to enable gamification features.
 * Provides relationships and helper methods for receiving XP, achievements, and prizes.
 *
 * This trait automatically includes HasProfile functionality for profile management.
 *
 * @property-read Profile|null $profile
 * @property-read \Illuminate\Database\Eloquent\Collection<int, AchievementGrant> $achievementGrants
 * @property-read \Illuminate\Database\Eloquent\Collection<int, PrizeGrant> $prizeGrants
 */
trait Awardable
{
    use HasProfile;

    /**
     * Get all achievement grants for this model.
     *
     * @return MorphMany<AchievementGrant, $this>
     */
    public function achievementGrants(): MorphMany
    {
        return $this->morphMany(AchievementGrant::class, 'awardable');
    }

    /**
     * Get all prize grants for this model.
     *
     * @return MorphMany<PrizeGrant, $this>
     */
    public function prizeGrants(): MorphMany
    {
        return $this->morphMany(PrizeGrant::class, 'awardable');
    }

    /**
     * Get total XP accumulated by this model across all GamedMetrics.
     */
    public function getTotalXp(): int
    {
        return $this->profile?->total_xp ?? 0;
    }

    /**
     * Get XP for a specific GamedMetric.
     *
     * @param  string|GamedMetric  $gamedMetric  GamedMetric slug or model instance
     */
    public function getXpFor(string|GamedMetric $gamedMetric): int
    {
        $profile = $this->profile;

        if (! $profile) {
            return 0;
        }

        return $profile->getXpFor($gamedMetric);
    }

    /**
     * Get current level for a specific GamedMetric.
     *
     * @param  string|GamedMetric  $gamedMetric  GamedMetric slug or model instance
     */
    public function getLevelFor(string|GamedMetric $gamedMetric): int
    {
        $profile = $this->profile;

        if (! $profile) {
            return 1;
        }

        return $profile->getLevelFor($gamedMetric);
    }

    /**
     * Check if this model has a specific achievement.
     *
     * @param  string|Achievement  $achievement  Achievement slug or model instance
     */
    public function hasAchievement(string|Achievement $achievement): bool
    {
        $achievementId = $achievement instanceof Achievement
            ? $achievement->id
            : Achievement::where('slug', $achievement)->value('id');

        if ($achievementId === null) {
            return false;
        }

        return $this->achievementGrants()
            ->where('achievement_id', $achievementId)
            ->exists();
    }

    /**
     * Get all achievements unlocked by this model.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Achievement>
     */
    public function getAchievements(): \Illuminate\Database\Eloquent\Collection
    {
        $grantedIds = $this->achievementGrants()->pluck('achievement_id');

        return Achievement::whereIn('id', $grantedIds)->get();
    }

    /**
     * Get achievement count for this model.
     */
    public function getAchievementCount(): int
    {
        return $this->achievementGrants()->count();
    }

    /**
     * Get prize count for this model.
     */
    public function getPrizeCount(): int
    {
        return $this->prizeGrants()->count();
    }

    /**
     * Get the most recent achievement grants.
     *
     * @param  int  $limit  Number of grants to return
     * @return \Illuminate\Database\Eloquent\Collection<int, AchievementGrant>
     */
    public function getRecentAchievements(int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return $this->achievementGrants()
            ->with('achievement')
            ->latest()
            ->limit($limit)
            ->get();
    }
}
