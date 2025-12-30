<?php

declare(strict_types=1);

namespace LaravelFunLab\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use LaravelFunLab\Models\Achievement;
use LaravelFunLab\Models\AchievementGrant;
use LaravelFunLab\Models\Award;

/**
 * Awardable Trait
 *
 * Apply this trait to any Eloquent model to enable gamification features.
 * Provides relationships and helper methods for receiving points, achievements, and prizes.
 *
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Award> $awards
 * @property-read \Illuminate\Database\Eloquent\Collection<int, AchievementGrant> $achievementGrants
 */
trait Awardable
{
    /**
     * Get all awards (points/badges) granted to this model.
     *
     * @return MorphMany<Award, $this>
     */
    public function awards(): MorphMany
    {
        return $this->morphMany(Award::class, 'awardable');
    }

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
     * Get total points accumulated by this model.
     *
     * @param  string|null  $type  Optional award type to filter by
     */
    public function getTotalPoints(?string $type = 'points'): int|float
    {
        $query = $this->awards();

        if ($type !== null) {
            $query->where('type', $type);
        }

        // PostgreSQL returns string from sum(), so cast to float
        return (float) $query->sum('amount');
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
     * Get award count by type.
     *
     * @param  string  $type  The award type to count
     */
    public function getAwardCount(string $type): int
    {
        return $this->awards()
            ->where('type', $type)
            ->count();
    }

    /**
     * Get the most recent awards.
     *
     * @param  int  $limit  Number of awards to return
     * @return \Illuminate\Database\Eloquent\Collection<int, Award>
     */
    public function getRecentAwards(int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return $this->awards()
            ->latest()
            ->limit($limit)
            ->get();
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
