<?php

declare(strict_types=1);

namespace LaravelFunLab\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * MetricLevelGroupLevel Model
 *
 * Defines level thresholds for MetricLevelGroups based on combined/summed XP from multiple GamedMetrics.
 *
 * @property int $id
 * @property int $metric_level_group_id
 * @property int $level
 * @property int $xp_threshold
 * @property string $name
 * @property string|null $description
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read MetricLevelGroup $metricLevelGroup
 */
class MetricLevelGroupLevel extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'metric_level_group_id',
        'level',
        'xp_threshold',
        'name',
        'description',
    ];

    /**
     * Get the table name with configurable prefix.
     */
    public function getTable(): string
    {
        return config('lfl.table_prefix', 'lfl_').'metric_level_group_levels';
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'xp_threshold' => 'integer',
        ];
    }

    /**
     * Get the MetricLevelGroup this level belongs to.
     *
     * @return BelongsTo<MetricLevelGroup, $this>
     */
    public function metricLevelGroup(): BelongsTo
    {
        return $this->belongsTo(MetricLevelGroup::class);
    }

    /**
     * Scope to order by level ascending.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<MetricLevelGroupLevel>  $query
     * @return \Illuminate\Database\Eloquent\Builder<MetricLevelGroupLevel>
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('level');
    }

    /**
     * Check if the given XP amount reaches this level threshold.
     */
    public function isReached(int $xp): bool
    {
        return $xp >= $this->xp_threshold;
    }

    /**
     * Get all achievements attached to this MetricLevelGroupLevel.
     *
     * @return BelongsToMany<Achievement, $this>
     */
    public function achievements(): BelongsToMany
    {
        return $this->belongsToMany(
            Achievement::class,
            config('lfl.table_prefix', 'lfl_').'achievement_metric_level_group_levels',
            'metric_level_group_level_id',
            'achievement_id'
        );
    }
}

