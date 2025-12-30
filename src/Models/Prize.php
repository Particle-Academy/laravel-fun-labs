<?php

declare(strict_types=1);

namespace LaravelFunLab\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LaravelFunLab\Enums\PrizeType;

/**
 * Prize Model
 *
 * Defines prize types with name, description, type enum, cost in points,
 * inventory tracking, and metadata JSON for flexible prize management.
 *
 * @property int $id
 * @property string $slug
 * @property string $name
 * @property string|null $description
 * @property PrizeType $type
 * @property int|float $cost_in_points
 * @property int|null $inventory_quantity Null means unlimited
 * @property array<string, mixed>|null $meta
 * @property bool $is_active
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, PrizeGrant> $grants
 */
class Prize extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'slug',
        'name',
        'description',
        'type',
        'cost_in_points',
        'inventory_quantity',
        'meta',
        'is_active',
        'sort_order',
    ];

    /**
     * The model's default attribute values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
        'cost_in_points' => 0,
        'sort_order' => 0,
    ];

    /**
     * Get the table name with configurable prefix.
     */
    public function getTable(): string
    {
        return config('lfl.table_prefix', 'lfl_').'prizes';
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => PrizeType::class,
            'cost_in_points' => 'decimal:2',
            'inventory_quantity' => 'integer',
            'meta' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Get all grants for this prize.
     *
     * @return HasMany<PrizeGrant, $this>
     */
    public function grants(): HasMany
    {
        return $this->hasMany(PrizeGrant::class);
    }

    /**
     * Scope to only active prizes.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Prize>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Prize>
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by prize type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Prize>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Prize>
     */
    public function scopeOfType($query, PrizeType $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to filter prizes that are available (have inventory or unlimited).
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Prize>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Prize>
     */
    public function scopeAvailable($query)
    {
        $prizeGrantTable = config('lfl.table_prefix', 'lfl_').'prize_grants';
        $prizeTable = $this->getTable();

        return $query->where(function ($q) use ($prizeGrantTable, $prizeTable) {
            $q->whereNull('inventory_quantity')
                ->orWhereRaw('inventory_quantity > (
                    SELECT COUNT(*) FROM '.$prizeGrantTable.'
                    WHERE prize_id = '.$prizeTable.'.id
                    AND status != ?
                )', ['cancelled']);
        });
    }

    /**
     * Scope to order by sort order.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Prize>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Prize>
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    /**
     * Check if this prize is available (has inventory or is unlimited).
     */
    public function isAvailable(): bool
    {
        if ($this->inventory_quantity === null) {
            return true; // Unlimited
        }

        $grantedCount = $this->grants()
            ->where('status', '!=', 'cancelled')
            ->count();

        return $grantedCount < $this->inventory_quantity;
    }

    /**
     * Get the remaining inventory quantity.
     *
     * Returns null if unlimited, otherwise returns the remaining count.
     */
    public function getRemainingInventory(): ?int
    {
        if ($this->inventory_quantity === null) {
            return null; // Unlimited
        }

        $grantedCount = $this->grants()
            ->where('status', '!=', 'cancelled')
            ->count();

        return max(0, $this->inventory_quantity - $grantedCount);
    }

    /**
     * Find a prize by slug.
     */
    public static function findBySlug(string $slug): ?self
    {
        return static::where('slug', $slug)->first();
    }

    /**
     * Get all achievements this prize is attached to.
     *
     * @return BelongsToMany<Achievement, $this>
     */
    public function achievements(): BelongsToMany
    {
        return $this->belongsToMany(
            Achievement::class,
            config('lfl.table_prefix', 'lfl_').'achievement_prizes',
            'prize_id',
            'achievement_id'
        );
    }
}
