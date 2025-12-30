<?php

declare(strict_types=1);

namespace LaravelFunLab\Pipelines;

use Closure;
use Illuminate\Database\Eloquent\Model;
use LaravelFunLab\Enums\AwardType;

/**
 * AwardValidationPipeline
 *
 * Provides a pipeline pattern for award validation that allows developers
 * to add custom validation steps. Each step can pass the award through,
 * modify it, or halt the pipeline by returning a failure result.
 *
 * Usage:
 * AwardValidationPipeline::addStep(function ($awardable, $type, $amount, $reason) {
 *     if ($someCondition) {
 *         return ['valid' => false, 'message' => 'Validation failed'];
 *     }
 *     return ['valid' => true];
 * });
 */
class AwardValidationPipeline
{
    /**
     * Registered validation steps.
     *
     * @var array<int, Closure>
     */
    protected static array $steps = [];

    /**
     * Add a validation step to the pipeline.
     *
     * The closure receives:
     * - Model $awardable: The recipient of the award
     * - AwardType|string $type: The award type
     * - int|float $amount: The award amount
     * - ?string $reason: The reason for the award
     * - ?string $source: The source of the award
     * - array $meta: Additional metadata
     *
     * The closure should return:
     * - ['valid' => true] to pass validation
     * - ['valid' => false, 'message' => '...', 'errors' => [...]] to fail validation
     *
     * @param  Closure  $step  The validation step closure
     *
     * @example
     * AwardValidationPipeline::addStep(function ($awardable, $type, $amount) {
     *     if ($amount > 1000) {
     *         return ['valid' => false, 'message' => 'Amount too high'];
     *     }
     *     return ['valid' => true];
     * });
     */
    public static function addStep(Closure $step): void
    {
        static::$steps[] = $step;
    }

    /**
     * Run all validation steps through the pipeline.
     *
     * @param  Model  $awardable  The recipient of the award
     * @param  AwardType|string  $type  The award type
     * @param  int|float  $amount  The award amount
     * @param  string|null  $reason  The reason for the award
     * @param  string|null  $source  The source of the award
     * @param  array<string, mixed>  $meta  Additional metadata
     * @return array{valid: bool, message?: string, errors?: array<string, array<string>>}
     */
    public static function validate(
        Model $awardable,
        AwardType|string $type,
        int|float $amount,
        ?string $reason = null,
        ?string $source = null,
        array $meta = [],
    ): array {
        foreach (static::$steps as $step) {
            $result = $step($awardable, $type, $amount, $reason, $source, $meta);

            // If result is not an array, assume it's valid
            if (! is_array($result)) {
                continue;
            }

            // If validation failed, return the failure result
            if (isset($result['valid']) && $result['valid'] === false) {
                return [
                    'valid' => false,
                    'message' => $result['message'] ?? 'Award validation failed',
                    'errors' => $result['errors'] ?? [],
                ];
            }
        }

        // All steps passed
        return ['valid' => true];
    }

    /**
     * Clear all registered validation steps.
     */
    public static function flush(): void
    {
        static::$steps = [];
    }

    /**
     * Get all registered validation steps.
     *
     * @return array<int, Closure>
     */
    public static function getSteps(): array
    {
        return static::$steps;
    }

    /**
     * Get the count of registered validation steps.
     */
    public static function count(): int
    {
        return count(static::$steps);
    }
}
