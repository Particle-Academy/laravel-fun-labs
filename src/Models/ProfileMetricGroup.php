<?php

declare(strict_types=1);

namespace LaravelFunLab\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ProfileMetricGroup Model
 *
 * Tracks accumulated level progression for each MetricLevelGroup per Profile.
 * Updated when combined XP from group metrics reaches level thresholds.
 *
 * @property int $id
 * @property int $profile_id
 * @property int $metric_level_group_id
 * @property int $current_level
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read Profile $profile
 * @property-read MetricLevelGroup $metricLevelGroup
 */
class ProfileMetricGroup extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'profile_id',
        'metric_level_group_id',
        'current_level',
    ];

    /**
     * Get the table name with configurable prefix.
     */
    public function getTable(): string
    {
        return config('lfl.table_prefix', 'lfl_').'profile_metric_groups';
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'current_level' => 'integer',
        ];
    }

    /**
     * Get the Profile this metric group belongs to.
     *
     * @return BelongsTo<Profile, $this>
     */
    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    /**
     * Get the MetricLevelGroup this belongs to.
     *
     * @return BelongsTo<MetricLevelGroup, $this>
     */
    public function metricLevelGroup(): BelongsTo
    {
        return $this->belongsTo(MetricLevelGroup::class);
    }

    /**
     * Set the current level for this metric group.
     */
    public function setLevel(int $level): void
    {
        $this->update(['current_level' => $level]);
    }
}
