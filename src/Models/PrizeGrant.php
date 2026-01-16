<?php

declare(strict_types=1);

namespace LaravelFunLab\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LaravelFunLab\Enums\RedemptionStatus;

/**
 * PrizeGrant Model
 *
 * Tracks awarded prizes per Profile with redemption status,
 * timestamps, and metadata. Links prizes to profiles (which link to any awardable model).
 *
 * @property int $id
 * @property int $profile_id
 * @property int $prize_id
 * @property string|null $reason
 * @property string|null $source
 * @property RedemptionStatus $status
 * @property array<string, mixed>|null $meta
 * @property \Illuminate\Support\Carbon|null $granted_at
 * @property \Illuminate\Support\Carbon|null $claimed_at
 * @property \Illuminate\Support\Carbon|null $fulfilled_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read Profile $profile
 * @property-read Prize $prize
 */
class PrizeGrant extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'profile_id',
        'prize_id',
        'reason',
        'source',
        'status',
        'meta',
        'granted_at',
        'claimed_at',
        'fulfilled_at',
    ];

    /**
     * The model's default attribute values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'pending',
    ];

    /**
     * Get the table name with configurable prefix.
     */
    public function getTable(): string
    {
        return config('lfl.table_prefix', 'lfl_').'prize_grants';
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => RedemptionStatus::class,
            'meta' => 'array',
            'granted_at' => 'datetime',
            'claimed_at' => 'datetime',
            'fulfilled_at' => 'datetime',
        ];
    }

    /**
     * Get the profile that received this prize.
     *
     * @return BelongsTo<Profile, $this>
     */
    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    /**
     * Get the prize that was granted.
     *
     * @return BelongsTo<Prize, $this>
     */
    public function prize(): BelongsTo
    {
        return $this->belongsTo(Prize::class);
    }

    /**
     * Scope to filter by profile.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<PrizeGrant>  $query
     * @return \Illuminate\Database\Eloquent\Builder<PrizeGrant>
     */
    public function scopeForProfile($query, int|Profile $profile)
    {
        $profileId = $profile instanceof Profile
            ? $profile->id
            : $profile;

        return $query->where('profile_id', $profileId);
    }

    /**
     * Scope to filter by redemption status.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<PrizeGrant>  $query
     * @return \Illuminate\Database\Eloquent\Builder<PrizeGrant>
     */
    public function scopeWithStatus($query, RedemptionStatus $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter pending redemptions.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<PrizeGrant>  $query
     * @return \Illuminate\Database\Eloquent\Builder<PrizeGrant>
     */
    public function scopePending($query)
    {
        return $query->where('status', RedemptionStatus::Pending);
    }

    /**
     * Scope to filter claimed redemptions.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<PrizeGrant>  $query
     * @return \Illuminate\Database\Eloquent\Builder<PrizeGrant>
     */
    public function scopeClaimed($query)
    {
        return $query->where('status', RedemptionStatus::Claimed);
    }

    /**
     * Scope to filter fulfilled redemptions.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<PrizeGrant>  $query
     * @return \Illuminate\Database\Eloquent\Builder<PrizeGrant>
     */
    public function scopeFulfilled($query)
    {
        return $query->where('status', RedemptionStatus::Fulfilled);
    }

    /**
     * Mark this grant as claimed.
     */
    public function claim(): bool
    {
        return $this->update([
            'status' => RedemptionStatus::Claimed,
            'claimed_at' => now(),
        ]);
    }

    /**
     * Mark this grant as fulfilled.
     */
    public function fulfill(): bool
    {
        return $this->update([
            'status' => RedemptionStatus::Fulfilled,
            'fulfilled_at' => now(),
        ]);
    }

    /**
     * Cancel this grant.
     */
    public function cancel(): bool
    {
        return $this->update([
            'status' => RedemptionStatus::Cancelled,
        ]);
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        // Auto-set granted_at if not provided
        static::creating(function (PrizeGrant $grant) {
            if ($grant->granted_at === null) {
                $grant->granted_at = now();
            }
        });
    }
}
