<?php

declare(strict_types=1);

namespace LaravelFunLab\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * MetricLevelGroupMetric Model
 *
 * Pivot table linking MetricLevelGroups to GamedMetrics.
 * Defines which metrics are included in a group and their weights.
 *
 * @property int $id
 * @property int $metric_level_group_id
 * @property int $gamed_metric_id
 * @property float $weight
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read MetricLevelGroup $metricLevelGroup
 * @property-read GamedMetric $gamedMetric
 */
class MetricLevelGroupMetric extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'metric_level_group_id',
        'gamed_metric_id',
        'weight',
    ];

    /**
     * Get the table name with configurable prefix.
     */
    public function getTable(): string
    {
        return config('lfl.table_prefix', 'lfl_').'metric_level_group_metrics';
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'weight' => 'decimal:2',
        ];
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
     * Get the GamedMetric this references.
     *
     * @return BelongsTo<GamedMetric, $this>
     */
    public function gamedMetric(): BelongsTo
    {
        return $this->belongsTo(GamedMetric::class);
    }
}

