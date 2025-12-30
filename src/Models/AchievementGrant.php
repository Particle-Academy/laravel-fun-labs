<?php

declare(strict_types=1);

namespace LaravelFunLab\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * AchievementGrant Model
 *
 * Tracks awarded achievements per awardable entity with grant timestamp and metadata.
 * Acts as a pivot between achievements and awardable models.
 *
 * @property int $id
 * @property int $achievement_id
 * @property string $awardable_type
 * @property int $awardable_id
 * @property array<string, mixed>|null $meta
 * @property \Illuminate\Support\Carbon $granted_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read Achievement $achievement
 * @property-read Model $awardable
 */
class AchievementGrant extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'achievement_id',
        'awardable_type',
        'awardable_id',
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
     * Get the achievement that was granted.
     *
     * @return BelongsTo<Achievement, $this>
     */
    public function achievement(): BelongsTo
    {
        return $this->belongsTo(Achievement::class);
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
     * Scope to filter by awardable type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<AchievementGrant>  $query
     * @return \Illuminate\Database\Eloquent\Builder<AchievementGrant>
     */
    public function scopeForAwardableType($query, string $awardableType)
    {
        return $query->where('awardable_type', $awardableType);
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
