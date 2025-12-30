<?php

declare(strict_types=1);

namespace LaravelFunLab\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Profile Model
 *
 * Stores engagement profiles for awardable models with opt-in/opt-out logic,
 * display preferences, visibility settings, and aggregated engagement metrics.
 *
 * @property int $id
 * @property string $awardable_type
 * @property int $awardable_id
 * @property bool $is_opted_in
 * @property array<string, mixed>|null $display_preferences
 * @property array<string, mixed>|null $visibility_settings
 * @property float $total_points
 * @property int $achievement_count
 * @property int $prize_count
 * @property \Illuminate\Support\Carbon|null $last_activity_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read Model $awardable
 */
class Profile extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'awardable_type',
        'awardable_id',
        'is_opted_in',
        'display_preferences',
        'visibility_settings',
        'total_points',
        'achievement_count',
        'prize_count',
        'last_activity_at',
    ];

    /**
     * Get the table name with configurable prefix.
     */
    public function getTable(): string
    {
        return config('lfl.table_prefix', 'lfl_').'profiles';
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_opted_in' => 'boolean',
            'display_preferences' => 'array',
            'visibility_settings' => 'array',
            'total_points' => 'decimal:2',
            'achievement_count' => 'integer',
            'prize_count' => 'integer',
            'last_activity_at' => 'datetime',
        ];
    }

    /**
     * Get the awardable entity (User, Team, etc.).
     *
     * @return MorphTo<Model, $this>
     */
    public function awardable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope to filter by opt-in status.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Profile>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Profile>
     */
    public function scopeOptedIn($query)
    {
        return $query->where('is_opted_in', true);
    }

    /**
     * Scope to filter by opt-out status.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Profile>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Profile>
     */
    public function scopeOptedOut($query)
    {
        return $query->where('is_opted_in', false);
    }

    /**
     * Scope to filter by awardable type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Profile>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Profile>
     */
    public function scopeForAwardableType($query, string $awardableType)
    {
        return $query->where('awardable_type', $awardableType);
    }

    /**
     * Scope to order by total points (descending).
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Profile>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Profile>
     */
    public function scopeOrderedByPoints($query)
    {
        return $query->orderByDesc('total_points');
    }

    /**
     * Check if the profile is opted in.
     */
    public function isOptedIn(): bool
    {
        return $this->is_opted_in;
    }

    /**
     * Check if the profile is opted out.
     */
    public function isOptedOut(): bool
    {
        return ! $this->is_opted_in;
    }

    /**
     * Opt in to gamification features.
     */
    public function optIn(): bool
    {
        return $this->update(['is_opted_in' => true]);
    }

    /**
     * Opt out of gamification features.
     */
    public function optOut(): bool
    {
        return $this->update(['is_opted_in' => false]);
    }

    /**
     * Update the last activity timestamp.
     */
    public function touchActivity(): bool
    {
        return $this->update(['last_activity_at' => now()]);
    }

    /**
     * Calculate total points from all awards.
     *
     * @param  string|null  $type  Optional award type to filter by (e.g., 'points')
     */
    public function calculateTotalPoints(?string $type = 'points'): float
    {
        $awardable = $this->awardable;

        if ($awardable === null || ! method_exists($awardable, 'awards')) {
            return 0.0;
        }

        $query = $awardable->awards();

        if ($type !== null) {
            $query->where('type', $type);
        }

        return (float) $query->sum('amount');
    }

    /**
     * Calculate achievement count from achievement grants.
     */
    public function calculateAchievementCount(): int
    {
        $awardable = $this->awardable;

        if ($awardable === null || ! method_exists($awardable, 'achievementGrants')) {
            return 0;
        }

        return $awardable->achievementGrants()->count();
    }

    /**
     * Calculate prize count from awards.
     */
    public function calculatePrizeCount(): int
    {
        $awardable = $this->awardable;

        if ($awardable === null || ! method_exists($awardable, 'awards')) {
            return 0;
        }

        return $awardable->awards()
            ->where('type', 'prize')
            ->count();
    }

    /**
     * Recalculate all aggregated values from related awards and achievements.
     */
    public function recalculateAggregations(): bool
    {
        return $this->update([
            'total_points' => $this->calculateTotalPoints(),
            'achievement_count' => $this->calculateAchievementCount(),
            'prize_count' => $this->calculatePrizeCount(),
        ]);
    }

    /**
     * Increment total points by the given amount.
     */
    public function incrementPoints(float $amount): bool
    {
        $this->increment('total_points', $amount);

        return true;
    }

    /**
     * Increment achievement count by 1.
     */
    public function incrementAchievementCount(): bool
    {
        $this->increment('achievement_count');

        return true;
    }

    /**
     * Increment prize count by 1.
     */
    public function incrementPrizeCount(): bool
    {
        $this->increment('prize_count');

        return true;
    }
}
