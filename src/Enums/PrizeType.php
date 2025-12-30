<?php

declare(strict_types=1);

namespace LaravelFunLab\Enums;

/**
 * PrizeType Enum
 *
 * Defines the types of prizes supported by Laravel Fun Lab.
 * Each type represents a different category of reward that can be awarded.
 */
enum PrizeType: string
{
    case Virtual = 'virtual';
    case Physical = 'physical';
    case FeatureUnlock = 'feature_unlock';
    case Custom = 'custom';

    /**
     * Get a human-readable label for this prize type.
     */
    public function label(): string
    {
        return match ($this) {
            self::Virtual => 'Virtual',
            self::Physical => 'Physical',
            self::FeatureUnlock => 'Feature Unlock',
            self::Custom => 'Custom',
        };
    }

    /**
     * Get a description of what this prize type represents.
     */
    public function description(): string
    {
        return match ($this) {
            self::Virtual => 'Digital or in-app rewards (credits, items, etc.)',
            self::Physical => 'Tangible physical rewards (merchandise, gift cards, etc.)',
            self::FeatureUnlock => 'Unlocks access to features or content',
            self::Custom => 'Custom prize type defined by the application',
        };
    }
}
