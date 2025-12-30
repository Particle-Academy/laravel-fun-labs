<?php

declare(strict_types=1);

namespace LaravelFunLab\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * MetricLevel Model
 *
 * Represents a defined level threshold based on accumulated GamedMetric XP.
 * When a user reaches a MetricLevel threshold, associated prizes and achievements can be automatically granted.
 *
 * @property int $id
 * @property int $gamed_metric_id
 * @property int $level
 * @property int $xp_threshold
 * @property string $name
 * @property string|null $description
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read GamedMetric $gamedMetric
 */
class MetricLevel extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'gamed_metric_id',
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
        return config('lfl.table_prefix', 'lfl_').'metric_levels';
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
     * Get the GamedMetric this level belongs to.
     *
     * @return BelongsTo<GamedMetric, $this>
     */
    public function gamedMetric(): BelongsTo
    {
        return $this->belongsTo(GamedMetric::class);
    }

    /**
     * Scope to filter by GamedMetric.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<MetricLevel>  $query
     * @return \Illuminate\Database\Eloquent\Builder<MetricLevel>
     */
    public function scopeForMetric($query, int $gamedMetricId)
    {
        return $query->where('gamed_metric_id', $gamedMetricId);
    }

    /**
     * Scope to order by level ascending.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<MetricLevel>  $query
     * @return \Illuminate\Database\Eloquent\Builder<MetricLevel>
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
     * Get all achievements attached to this MetricLevel.
     *
     * @return BelongsToMany<Achievement, $this>
     */
    public function achievements(): BelongsToMany
    {
        return $this->belongsToMany(
            Achievement::class,
            config('lfl.table_prefix', 'lfl_').'achievement_metric_levels',
            'metric_level_id',
            'achievement_id'
        );
    }
}

