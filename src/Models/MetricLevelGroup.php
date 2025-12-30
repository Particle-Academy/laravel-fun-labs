<?php

declare(strict_types=1);

namespace LaravelFunLab\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * MetricLevelGroup Model
 *
 * Combines multiple GamedMetrics, sums their XP, and triggers level criteria based on combined totals.
 * Allows for composite leveling systems.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, MetricLevelGroupMetric> $metrics
 * @property-read \Illuminate\Database\Eloquent\Collection<int, MetricLevelGroupLevel> $levels
 */
class MetricLevelGroup extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    /**
     * Get the table name with configurable prefix.
     */
    public function getTable(): string
    {
        return config('lfl.table_prefix', 'lfl_').'metric_level_groups';
    }

    /**
     * Get the GamedMetrics in this group.
     *
     * @return HasMany<MetricLevelGroupMetric, $this>
     */
    public function metrics(): HasMany
    {
        return $this->hasMany(MetricLevelGroupMetric::class);
    }

    /**
     * Get the levels defined for this group.
     *
     * @return HasMany<MetricLevelGroupLevel, $this>
     */
    public function levels(): HasMany
    {
        return $this->hasMany(MetricLevelGroupLevel::class);
    }

    /**
     * Find a MetricLevelGroup by slug.
     */
    public static function findBySlug(string $slug): ?self
    {
        return static::where('slug', $slug)->first();
    }
}

