<?php

declare(strict_types=1);

namespace LaravelFunLab\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Award Model
 *
 * Stores point grants and other awards with polymorphic relationship to any awardable entity.
 * Tracks amount, type, reason, and source for analytics and display purposes.
 *
 * @property int $id
 * @property string $awardable_type
 * @property int $awardable_id
 * @property string $type
 * @property int|float $amount
 * @property string|null $reason
 * @property string|null $source
 * @property array<string, mixed>|null $meta
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read Model $awardable
 */
class Award extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'awardable_type',
        'awardable_id',
        'type',
        'amount',
        'reason',
        'source',
        'meta',
    ];

    /**
     * Get the table name with configurable prefix.
     */
    public function getTable(): string
    {
        return config('lfl.table_prefix', 'lfl_').'awards';
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'meta' => 'array',
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
     * Scope to filter by award type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Award>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Award>
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to filter by awardable type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Award>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Award>
     */
    public function scopeForAwardableType($query, string $awardableType)
    {
        return $query->where('awardable_type', $awardableType);
    }

    /**
     * Scope to get awards from a specific source.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Award>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Award>
     */
    public function scopeFromSource($query, string $source)
    {
        return $query->where('source', $source);
    }
}
