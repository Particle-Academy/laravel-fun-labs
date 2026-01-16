<?php

declare(strict_types=1);

namespace LaravelFunLab\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AchievementGrant Model
 *
 * Tracks awarded achievements per Profile with grant timestamp and metadata.
 * Links achievements to profiles (which in turn link to any awardable entity).
 *
 * @property int $id
 * @property int $profile_id
 * @property int $achievement_id
 * @property string|null $reason
 * @property string|null $source
 * @property array<string, mixed>|null $meta
 * @property \Illuminate\Support\Carbon $granted_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read Profile $profile
 * @property-read Achievement $achievement
 */
class AchievementGrant extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'profile_id',
        'achievement_id',
        'reason',
        'source',
        'meta',
        'granted_at',
    ];

    /**
     * Get the table name with configurable prefix.
     */
    public function getTable(): string
    {
        return config('lfl.table_prefix', 'lfl_').'achievement_grants';
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'granted_at' => 'datetime',
        ];
    }

    /**
     * Get the profile that received this achievement.
     *
     * @return BelongsTo<Profile, $this>
     */
    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    /**
     * Get the achievement that was granted.
     *
     * @return BelongsTo<Achievement, $this>
     */
    public function achievement(): BelongsTo
    {
        return $this->belongsTo(Achievement::class);
    }

    /**
     * Scope to filter by achievement.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<AchievementGrant>  $query
     * @return \Illuminate\Database\Eloquent\Builder<AchievementGrant>
     */
    public function scopeForAchievement($query, int|Achievement $achievement)
    {
        $achievementId = $achievement instanceof Achievement
            ? $achievement->id
            : $achievement;

        return $query->where('achievement_id', $achievementId);
    }

    /**
     * Scope to filter by profile.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<AchievementGrant>  $query
     * @return \Illuminate\Database\Eloquent\Builder<AchievementGrant>
     */
    public function scopeForProfile($query, int|Profile $profile)
    {
        $profileId = $profile instanceof Profile
            ? $profile->id
            : $profile;

        return $query->where('profile_id', $profileId);
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        // Auto-set granted_at if not provided
        static::creating(function (AchievementGrant $grant) {
            if ($grant->granted_at === null) {
                $grant->granted_at = now();
            }
        });
    }
}
