<?php

declare(strict_types=1);

namespace LaravelFunLab\Enums;

/**
 * RedemptionStatus Enum
 *
 * Defines the redemption statuses for prize grants.
 * Tracks the lifecycle of prize redemption from pending to fulfilled.
 */
enum RedemptionStatus: string
{
    case Pending = 'pending';
    case Granted = 'granted';
    case Claimed = 'claimed';
    case Fulfilled = 'fulfilled';
    case Cancelled = 'cancelled';

    /**
     * Get a human-readable label for this redemption status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Granted => 'Granted',
            self::Claimed => 'Claimed',
            self::Fulfilled => 'Fulfilled',
            self::Cancelled => 'Cancelled',
        };
    }

    /**
     * Check if this status represents an active redemption (not cancelled).
     */
    public function isActive(): bool
    {
        return $this !== self::Cancelled;
    }
}
