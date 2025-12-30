<?php

declare(strict_types=1);

namespace LaravelFunLab\Registries;

/**
 * AwardTypeRegistry
 *
 * Manages registration and metadata for custom award types.
 * Allows developers to register custom award types via config or service provider.
 *
 * Custom types extend the built-in enum types (points, achievement, prize, badge)
 * and can be used with the same API: LFL::award('custom-type')->to($user)->grant()
 */
class AwardTypeRegistry
{
    /**
     * Registered custom award types.
     *
     * @var array<string, array{name: string, icon: string, cumulative: bool, default_amount: int|float}>
     */
    protected static array $types = [];

    /**
     * Register a custom award type.
     *
     * @param  string  $type  The award type identifier (e.g., 'coins', 'stars', 'xp')
     * @param  string  $name  Human-readable name
     * @param  string  $icon  Icon identifier for UI display
     * @param  bool  $cumulative  Whether this type accumulates (like points) or is singular (like achievements)
     * @param  int|float  $defaultAmount  Default amount for this award type
     *
     * @example AwardTypeRegistry::register('coins', 'Coins', 'coin', true, 100)
     */
    public static function register(
        string $type,
        string $name,
        string $icon = 'star',
        bool $cumulative = true,
        int|float $defaultAmount = 1,
    ): void {
        static::$types[$type] = [
            'name' => $name,
            'icon' => $icon,
            'cumulative' => $cumulative,
            'default_amount' => $defaultAmount,
        ];
    }

    /**
     * Register multiple award types at once.
     *
     * @param  array<string, array{name: string, icon?: string, cumulative?: bool, default_amount?: int|float}>  $types
     *
     * @example AwardTypeRegistry::registerMany([
     *     'coins' => ['name' => 'Coins', 'icon' => 'coin', 'cumulative' => true],
     *     'stars' => ['name' => 'Stars', 'icon' => 'star', 'cumulative' => true],
     * ])
     */
    public static function registerMany(array $types): void
    {
        foreach ($types as $type => $config) {
            static::register(
                $type,
                $config['name'],
                $config['icon'] ?? 'star',
                $config['cumulative'] ?? true,
                $config['default_amount'] ?? 1,
            );
        }
    }

    /**
     * Check if an award type is registered (custom or built-in).
     *
     * @param  string  $type  The award type identifier
     */
    public static function isRegistered(string $type): bool
    {
        // Check built-in enum types
        if (in_array($type, ['points', 'achievement', 'prize', 'badge'], true)) {
            return true;
        }

        // Check custom registered types
        return isset(static::$types[$type]) || isset(config('lfl.award_types', [])[$type]);
    }

    /**
     * Get metadata for an award type.
     *
     * @param  string  $type  The award type identifier
     * @return array{name: string, icon: string, cumulative: bool, default_amount: int|float}|null
     */
    public static function getMetadata(string $type): ?array
    {
        // Check custom registered types first
        if (isset(static::$types[$type])) {
            return static::$types[$type];
        }

        // Check config-based types
        $configTypes = config('lfl.award_types', []);
        if (isset($configTypes[$type])) {
            return [
                'name' => $configTypes[$type]['name'] ?? ucfirst($type),
                'icon' => $configTypes[$type]['icon'] ?? 'star',
                'cumulative' => $configTypes[$type]['cumulative'] ?? true,
                'default_amount' => $configTypes[$type]['default_amount'] ?? 1,
            ];
        }

        // Check built-in enum types
        if (in_array($type, ['points', 'achievement', 'prize', 'badge'], true)) {
            $enumType = \LaravelFunLab\Enums\AwardType::from($type);

            return [
                'name' => $enumType->label(),
                'icon' => $enumType->icon(),
                'cumulative' => $enumType->isCumulative(),
                'default_amount' => config("lfl.award_types.{$type}.default_amount", 1),
            ];
        }

        return null;
    }

    /**
     * Get the name for an award type.
     *
     * @param  string  $type  The award type identifier
     */
    public static function getName(string $type): string
    {
        $metadata = static::getMetadata($type);

        return $metadata['name'] ?? ucfirst($type);
    }

    /**
     * Get the icon for an award type.
     *
     * @param  string  $type  The award type identifier
     */
    public static function getIcon(string $type): string
    {
        $metadata = static::getMetadata($type);

        return $metadata['icon'] ?? 'star';
    }

    /**
     * Check if an award type is cumulative.
     *
     * @param  string  $type  The award type identifier
     */
    public static function isCumulative(string $type): bool
    {
        $metadata = static::getMetadata($type);

        return $metadata['cumulative'] ?? true;
    }

    /**
     * Get the default amount for an award type.
     *
     * @param  string  $type  The award type identifier
     */
    public static function getDefaultAmount(string $type): int|float
    {
        $metadata = static::getMetadata($type);

        return $metadata['default_amount'] ?? 1;
    }

    /**
     * Get all registered custom types.
     *
     * @return array<string, array{name: string, icon: string, cumulative: bool, default_amount: int|float}>
     */
    public static function all(): array
    {
        return static::$types;
    }

    /**
     * Clear all registered custom types.
     */
    public static function flush(): void
    {
        static::$types = [];
    }
}
