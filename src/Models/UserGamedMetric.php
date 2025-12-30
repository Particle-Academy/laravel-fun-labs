<?php

declare(strict_types=1);

namespace LaravelFunLab\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * UserGamedMetric Model
 *
 * Tracks accumulated XP for each GamedMetric per awardable entity.
 * Updated when XP is awarded to a specific metric.
 *
 * @property int $id
 * @property string $awardable_type
 * @property int $awardable_id
 * @property int $gamed_metric_id
 * @property int $total_xp
 * @property int $current_level
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read Model $awardable
 * @property-read GamedMetric $gamedMetric
 */
class UserGamedMetric extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'awardable_type',
        'awardable_id',
        'gamed_metric_id',
        'total_xp',
        'current_level',
    ];

    /**
     * Get the table name with configurable prefix.
     */
    public function getTable(): string
    {
        return config('lfl.table_prefix', 'lfl_').'user_gamed_metrics';
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
     * Get the awardable entity (User, Team, etc.).
     *
     * @return MorphTo<Model, $this>
     */
    public function awardable(): MorphTo
    {
        return $this->morphTo();
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

