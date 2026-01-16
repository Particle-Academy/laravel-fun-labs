<?php

declare(strict_types=1);

namespace LaravelFunLab\Traits;

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
 * All grants (achievements, prizes) are linked through the Profile, not directly to the model.
 *
 * @property-read Profile|null $profile
 */
trait Awardable
{
    use HasProfile;

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
        // Use fresh profile query to avoid cached relationship issues
        $profile = Profile::where('awardable_type', static::class)
            ->where('awardable_id', $this->getKey())
            ->first();

        if (! $profile) {
            return false;
        }

        $achievementId = $achievement instanceof Achievement
            ? $achievement->id
            : Achievement::where('slug', $achievement)->value('id');

        if ($achievementId === null) {
            return false;
        }

        return AchievementGrant::where('profile_id', $profile->id)
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
        $profile = $this->profile;

        if (! $profile) {
            return Achievement::query()->whereRaw('1 = 0')->get();
        }

        $grantedIds = AchievementGrant::where('profile_id', $profile->id)
            ->pluck('achievement_id');

        return Achievement::whereIn('id', $grantedIds)->get();
    }

    /**
     * Get achievement count for this model.
     */
    public function getAchievementCount(): int
    {
        return $this->profile?->achievement_count ?? 0;
    }

    /**
     * Get prize count for this model.
     */
    public function getPrizeCount(): int
    {
        return $this->profile?->prize_count ?? 0;
    }

    /**
     * Get the most recent achievement grants.
     *
     * @param  int  $limit  Number of grants to return
     * @return \Illuminate\Database\Eloquent\Collection<int, AchievementGrant>
     */
    public function getRecentAchievements(int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        $profile = $this->profile;

        if (! $profile) {
            return AchievementGrant::query()->whereRaw('1 = 0')->get();
        }

        return AchievementGrant::where('profile_id', $profile->id)
            ->with('achievement')
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Get the most recent prize grants.
     *
     * @param  int  $limit  Number of grants to return
     * @return \Illuminate\Database\Eloquent\Collection<int, PrizeGrant>
     */
    public function getRecentPrizes(int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        $profile = $this->profile;

        if (! $profile) {
            return PrizeGrant::query()->whereRaw('1 = 0')->get();
        }

        return PrizeGrant::where('profile_id', $profile->id)
            ->with('prize')
            ->latest()
            ->limit($limit)
            ->get();
    }
}
