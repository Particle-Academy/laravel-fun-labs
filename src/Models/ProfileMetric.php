<?php

declare(strict_types=1);

namespace LaravelFunLab\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ProfileMetric Model
 *
 * Tracks accumulated XP for each GamedMetric per Profile.
 * Updated when XP is awarded to a specific metric.
 *
 * @property int $id
 * @property int $profile_id
 * @property int $gamed_metric_id
 * @property int $total_xp
 * @property int $current_level
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read Profile $profile
 * @property-read GamedMetric $gamedMetric
 */
class ProfileMetric extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'profile_id',
        'gamed_metric_id',
        'total_xp',
        'current_level',
    ];

    /**
     * Get the table name with configurable prefix.
     */
    public function getTable(): string
    {
        return config('lfl.table_prefix', 'lfl_').'profile_metrics';
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_xp' => 'integer',
            'current_level' => 'integer',
        ];
    }

    /**
     * Get the Profile this metric belongs to.
     *
     * @return BelongsTo<Profile, $this>
     */
    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    /**
     * Get the GamedMetric this belongs to.
     *
     * @return BelongsTo<GamedMetric, $this>
     */
    public function gamedMetric(): BelongsTo
    {
        return $this->belongsTo(GamedMetric::class);
    }

    /**
     * Add XP to this metric.
     */
    public function addXp(int $amount): void
    {
        $this->increment('total_xp', $amount);
    }

    /**
     * Set the current level for this metric.
     */
    public function setLevel(int $level): void
    {
        $this->update(['current_level' => $level]);
    }
}
