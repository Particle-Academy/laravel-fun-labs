<?php

declare(strict_types=1);

namespace LaravelFunLab\Enums;

/**
 * AwardType Enum
 *
 * Defines the core award types supported by Laravel Fun Lab.
 * Each type represents a different gamification primitive that can be awarded to entities.
 */
enum AwardType: string
{
    case Points = 'points';
    case Achievement = 'achievement';
    case Prize = 'prize';
    case Badge = 'badge';

    /**
     * Get a human-readable label for this award type.
     */
    public function label(): string
    {
        return match ($this) {
            self::Points => 'Points',
            self::Achievement => 'Achievement',
            self::Prize => 'Prize',
            self::Badge => 'Badge',
        };
    }

    /**
     * Check if this award type accumulates (like points) or is singular (like achievements).
     */
    public function isCumulative(): bool
    {
        return match ($this) {
            self::Points => true,
            self::Achievement, self::Prize, self::Badge => false,
        };
    }

    /**
     * Get the default icon for this award type.
     */
    public function icon(): string
    {
        return match ($this) {
            self::Points => 'star',
            self::Achievement => 'trophy',
            self::Prize => 'gift',
            self::Badge => 'badge',
        };
    }
}
