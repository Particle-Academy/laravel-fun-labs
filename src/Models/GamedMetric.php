<?php

declare(strict_types=1);

namespace LaravelFunLab\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * GamedMetric Model
 *
 * Represents an XP category that can be awarded independently.
 * Each GamedMetric tracks accumulated XP separately (e.g., "Combat XP", "Crafting XP", "Social XP").
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string|null $icon
 * @property bool $active
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProfileMetric> $profileMetrics
 * @property-read \Illuminate\Database\Eloquent\Collection<int, MetricLevel> $levels
 */
class GamedMetric extends Model
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
        'icon',
        'active',
    ];

    /**
     * Get the table name with configurable prefix.
     */
    public function getTable(): string
    {
        return config('lfl.table_prefix', 'lfl_').'gamed_metrics';
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    /**
     * Get all profile metrics for this GamedMetric.
     *
     * @return HasMany<ProfileMetric, $this>
     */
    public function profileMetrics(): HasMany
    {
        return $this->hasMany(ProfileMetric::class);
    }

    /**
     * Get all levels for this GamedMetric.
     *
     * @return HasMany<MetricLevel, $this>
     */
    public function levels(): HasMany
    {
        return $this->hasMany(MetricLevel::class)->orderBy('level');
    }

    /**
     * Scope to only active metrics.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<GamedMetric>  $query
     * @return \Illuminate\Database\Eloquent\Builder<GamedMetric>
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Find a GamedMetric by slug.
     */
    public static function findBySlug(string $slug): ?self
    {
        return static::where('slug', $slug)->first();
    }
}
