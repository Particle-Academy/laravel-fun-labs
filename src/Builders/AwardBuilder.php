<?php

declare(strict_types=1);

namespace LaravelFunLab\Builders;

use Illuminate\Database\Eloquent\Model;
use LaravelFunLab\Enums\AwardType;
use LaravelFunLab\Events\AchievementUnlocked;
use LaravelFunLab\Events\AwardFailed;
use LaravelFunLab\Events\AwardGranted;
use LaravelFunLab\Events\BadgeAwarded;
use LaravelFunLab\Events\PointsAwarded;
use LaravelFunLab\Events\PrizeAwarded;
use LaravelFunLab\Models\Achievement;
use LaravelFunLab\Models\AchievementGrant;
use LaravelFunLab\Models\GamedMetric;
use LaravelFunLab\Pipelines\AwardValidationPipeline;
use LaravelFunLab\Registries\AwardTypeRegistry;
use LaravelFunLab\ValueObjects\AwardResult;

/**
 * AwardBuilder - Fluent Builder for Award Operations
 *
 * Provides a clean, expressive API for constructing and executing award operations.
 * Supports method chaining for readable award creation:
 *
 * LFL::award('points')->for('task completion')->to($user)->amount(10)->grant();
 * LFL::award('coins')->to($user)->amount(100)->grant(); // Custom type
 */
class AwardBuilder
{
    protected AwardType|string $type;

    protected ?Model $recipient = null;

    protected ?string $reason = null;

    protected ?string $source = null;

    /** @var int|float|null Null means use config default */
    protected int|float|null $amount = null;

    /** @var array<string, mixed> */
    protected array $meta = [];

    /** @var string|Achievement|null Achievement slug or model for achievement awards */
    protected string|Achievement|null $achievement = null;

    /** @var GamedMetric|null Resolved GamedMetric for XP awards */
    protected ?GamedMetric $gamedMetric = null;

    /**
     * Create a new award builder for the specified type.
     *
     * Supports built-in enum types, custom string types registered via AwardTypeRegistry,
     * and GamedMetric slugs for XP awards.
     */
    public function __construct(AwardType|string $type)
    {
        if ($type instanceof AwardType) {
            $this->type = $type;
        } else {
            // Try to resolve as enum type first
            if (in_array($type, ['points', 'achievement', 'prize', 'badge'], true)) {
                $this->type = AwardType::from($type);
            } elseif (AwardTypeRegistry::isRegistered($type)) {
                // Custom type registered via registry or config
                $this->type = $type;
            } elseif ($gamedMetric = GamedMetric::findBySlug($type)) {
                // Type is a GamedMetric slug - store for XP award
                $this->gamedMetric = $gamedMetric;
                $this->type = $type;
            } else {
                throw new \InvalidArgumentException(
                    "Award type '{$type}' is not registered. Register it via AwardTypeRegistry::register() or config('lfl.award_types'), or create a GamedMetric with this slug."
                );
            }
        }
    }

    /**
     * Set the reason for the award (the "for" clause).
     */
    public function for(string $reason): self
    {
        $this->reason = $reason;

        return $this;
    }

    /**
     * Set the recipient of the award (the "to" clause).
     */
    public function to(Model $recipient): self
    {
        $this->recipient = $recipient;

        return $this;
    }

    /**
     * Set the source/origin of the award (the "from" clause).
     */
    public function from(string $source): self
    {
        $this->source = $source;

        return $this;
    }

    /**
     * Set the amount for cumulative awards like points.
     */
    public function amount(int|float $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Add metadata to the award.
     *
     * @param  array<string, mixed>  $meta
     */
    public function withMeta(array $meta): self
    {
        $this->meta = array_merge($this->meta, $meta);

        return $this;
    }

    /**
     * Specify which achievement to grant (for achievement type awards).
     */
    public function achievement(string|Achievement $achievement): self
    {
        $this->achievement = $achievement;

        return $this;
    }

    /**
     * Execute the award operation and persist to database.
     */
    public function grant(): AwardResult
    {
        // Resolve amount from config if not explicitly set
        $this->resolveDefaultAmount();

        // Validate recipient
        if ($this->recipient === null) {
            $typeValue = $this->getTypeValue();
            $result = AwardResult::failure(
                message: 'No recipient specified for award',
                errors: ['recipient' => ['A recipient is required to grant an award']],
                type: $typeValue,
            );
            $this->dispatchFailedEvent($result);

            return $result;
        }

        // Validate that recipient uses Awardable trait
        if (! $this->recipientIsAwardable()) {
            $typeValue = $this->getTypeValue();
            $result = AwardResult::failure(
                message: 'Recipient must use the Awardable trait',
                errors: ['recipient' => ['Model must implement the Awardable trait']],
                recipient: $this->recipient,
                type: $typeValue,
            );
            $this->dispatchFailedEvent($result);

            return $result;
        }

        // Check if recipient is opted out of gamification
        if ($this->recipientIsOptedOut()) {
            $typeValue = $this->getTypeValue();
            $result = AwardResult::failure(
                message: 'Recipient has opted out of gamification',
                errors: ['recipient' => ['This recipient has opted out and cannot receive awards']],
                recipient: $this->recipient,
                type: $typeValue,
            );
            $this->dispatchFailedEvent($result);

            return $result;
        }

        // Run validation pipeline
        $typeValue = $this->getTypeValue();
        $validation = AwardValidationPipeline::validate(
            awardable: $this->recipient,
            type: $this->type,
            amount: $this->amount ?? 0,
            reason: $this->reason,
            source: $this->source,
            meta: $this->meta,
        );

        if (! $validation['valid']) {
            $result = AwardResult::failure(
                message: $validation['message'] ?? 'Award validation failed',
                errors: $validation['errors'] ?? [],
                recipient: $this->recipient,
                type: $typeValue,
            );
            $this->dispatchFailedEvent($result);

            return $result;
        }

        // Delegate to type-specific grant method
        $typeValue = $this->getTypeValue();
        $result = match (true) {
            $this->gamedMetric !== null => $this->grantGamedMetricXp(),
            $this->isEnumType(AwardType::Points) || $this->isEnumType(AwardType::Badge) || $this->isCustomCumulativeType() => $this->grantPointsOrBadge(),
            $this->isEnumType(AwardType::Achievement) => $this->grantAchievement(),
            $this->isEnumType(AwardType::Prize) => $this->grantPrize(),
            default => $this->grantPointsOrBadge(), // Default custom types to points/badge behavior
        };

        // Dispatch appropriate events based on result
        if ($result->succeeded() && config('lfl.events.dispatch', true)) {
            // Dispatch generic event for backwards compatibility
            $typeValue = $this->getTypeValue();
            AwardGranted::dispatch($result, $typeValue, $this->recipient, $result->award);

            // Dispatch type-specific event for targeted listeners (only for enum types)
            if ($this->type instanceof AwardType) {
                $this->dispatchSpecificEvent($result);
            }
        } elseif ($result->failed()) {
            $this->dispatchFailedEvent($result);
        }

        return $result;
    }

    /**
     * Dispatch a type-specific event for targeted listening.
     */
    protected function dispatchSpecificEvent(AwardResult $result): void
    {
        $event = match ($this->type) {
            AwardType::Points => new PointsAwarded(
                $this->recipient,
                $result->award,
                (float) $this->amount,
                $this->reason,
                $this->source,
                $result->meta['previous_total'] ?? 0,
                $result->meta['new_total'] ?? 0,
            ),
            AwardType::Achievement => new AchievementUnlocked(
                $this->recipient,
                $this->resolveAchievement(),
                $result->award,
                $this->reason,
                $this->source,
            ),
            AwardType::Prize => new PrizeAwarded(
                $this->recipient,
                $result->award,
                $this->reason,
                $this->source,
                $this->meta,
            ),
            AwardType::Badge => new BadgeAwarded(
                $this->recipient,
                $result->award,
                $this->reason,
                $this->source,
                $this->meta,
            ),
        };

        event($event);
    }

    /**
     * Dispatch failure event if events are enabled.
     */
    protected function dispatchFailedEvent(AwardResult $result): void
    {
        if (config('lfl.events.dispatch', true)) {
            $typeValue = $this->getTypeValue();
            AwardFailed::dispatch($result, $typeValue, $this->recipient);
        }
    }

    /**
     * Grant points or badge award.
     *
     * @deprecated Use GamedMetrics for XP tracking instead of generic points/badges.
     *             Call LFL::award('your-metric-slug') with a GamedMetric slug.
     */
    protected function grantPointsOrBadge(): AwardResult
    {
        $typeValue = $this->getTypeValue();

        return AwardResult::failure(
            message: "The '{$typeValue}' award type is deprecated. Use GamedMetrics for XP tracking instead. Create a GamedMetric and use LFL::award('your-metric-slug').",
            errors: ['type' => ["Award type '{$typeValue}' is not supported. Use a GamedMetric slug instead."]],
            recipient: $this->recipient,
            type: $typeValue,
        );
    }

    /**
     * Grant an achievement to the recipient.
     */
    protected function grantAchievement(): AwardResult
    {
        $typeValue = $this->getTypeValue();

        // Resolve achievement from slug or model
        $achievement = $this->resolveAchievement();

        if ($achievement === null) {
            return AwardResult::failure(
                message: 'Achievement not found',
                errors: ['achievement' => ['The specified achievement does not exist']],
                recipient: $this->recipient,
                type: $typeValue,
            );
        }

        // Check if achievement is active
        if (! $achievement->is_active) {
            return AwardResult::failure(
                message: 'Achievement is not active',
                errors: ['achievement' => ['This achievement is currently inactive']],
                recipient: $this->recipient,
                type: $typeValue,
            );
        }

        // Check if already granted (achievements are typically one-time)
        if ($this->recipient->hasAchievement($achievement)) {
            return AwardResult::failure(
                message: 'Achievement already granted',
                errors: ['achievement' => ['Recipient already has this achievement']],
                recipient: $this->recipient,
                type: $typeValue,
            );
        }

        // Create the achievement grant
        $grant = AchievementGrant::create([
            'achievement_id' => $achievement->id,
            'awardable_type' => get_class($this->recipient),
            'awardable_id' => $this->recipient->getKey(),
            'meta' => array_merge($this->meta, [
                'reason' => $this->reason,
                'source' => $this->source,
            ]) ?: null,
            'granted_at' => now(),
        ]);

        // Update profile achievement count
        $profile = $this->recipient->profile ?? $this->recipient->getProfile();
        $profile->increment('achievement_count');

        return AwardResult::success(
            award: $grant,
            message: "Granted achievement '{$achievement->name}' to recipient",
            recipient: $this->recipient,
            type: $typeValue,
            meta: [
                'achievement_id' => $achievement->id,
                'achievement_slug' => $achievement->slug,
                'achievement_name' => $achievement->name,
            ],
        );
    }

    /**
     * Grant a prize to the recipient.
     *
     * Creates a PrizeGrant record linking the recipient to a prize.
     * Requires a prize to be specified via the meta['prize_id'] or meta['prize_slug'].
     */
    protected function grantPrize(): AwardResult
    {
        $typeValue = $this->getTypeValue();

        // Prize grants require a prize to be specified
        $prizeId = $this->meta['prize_id'] ?? null;
        $prizeSlug = $this->meta['prize_slug'] ?? null;

        if ($prizeId === null && $prizeSlug === null) {
            return AwardResult::failure(
                message: 'Prize not specified. Use withMeta([\'prize_id\' => id]) or withMeta([\'prize_slug\' => slug]).',
                errors: ['prize' => ['A prize_id or prize_slug must be provided in meta']],
                recipient: $this->recipient,
                type: $typeValue,
            );
        }

        // Resolve prize
        $prize = $prizeId
            ? \LaravelFunLab\Models\Prize::find($prizeId)
            : \LaravelFunLab\Models\Prize::where('slug', $prizeSlug)->first();

        if ($prize === null) {
            return AwardResult::failure(
                message: 'Prize not found',
                errors: ['prize' => ['The specified prize does not exist']],
                recipient: $this->recipient,
                type: $typeValue,
            );
        }

        // Create prize grant
        $prizeGrant = \LaravelFunLab\Models\PrizeGrant::create([
            'prize_id' => $prize->id,
            'awardable_type' => get_class($this->recipient),
            'awardable_id' => $this->recipient->getKey(),
            'status' => 'pending',
            'meta' => array_merge($this->meta, [
                'reason' => $this->reason,
                'source' => $this->source,
            ]),
            'granted_at' => now(),
        ]);

        // Update profile prize count
        $profile = $this->recipient->profile ?? $this->recipient->getProfile();
        $profile->increment('prize_count');

        return AwardResult::success(
            award: $prizeGrant,
            message: "Prize '{$prize->name}' awarded to recipient",
            recipient: $this->recipient,
            type: $typeValue,
            meta: [
                'prize_id' => $prize->id,
                'prize_slug' => $prize->slug,
                'prize_name' => $prize->name,
                'grant_id' => $prizeGrant->id,
            ],
        );
    }

    /**
     * Grant XP to a GamedMetric bucket.
     *
     * Uses the GamedMetricService to award XP and handle level progression.
     */
    protected function grantGamedMetricXp(): AwardResult
    {
        $typeValue = $this->getTypeValue();

        // Validate GamedMetric is active
        if (! $this->gamedMetric->active) {
            return AwardResult::failure(
                message: "GamedMetric '{$this->gamedMetric->slug}' is not active",
                errors: ['gamed_metric' => ['This GamedMetric is currently inactive']],
                recipient: $this->recipient,
                type: $typeValue,
            );
        }

        // Use the GamedMetricService to award XP
        $gamedMetricService = app(\LaravelFunLab\Services\GamedMetricService::class);
        $profileMetric = $gamedMetricService->awardXp(
            $this->recipient,
            $this->gamedMetric,
            (int) ($this->amount ?? 1)
        );

        return AwardResult::success(
            award: $profileMetric,
            message: "Awarded {$this->amount} XP to '{$this->gamedMetric->name}'",
            recipient: $this->recipient,
            type: $typeValue,
            meta: [
                'gamed_metric_id' => $this->gamedMetric->id,
                'gamed_metric_slug' => $this->gamedMetric->slug,
                'total_xp' => $profileMetric->total_xp,
                'current_level' => $profileMetric->current_level,
                'reason' => $this->reason,
                'source' => $this->source,
            ],
        );
    }

    /**
     * Resolve achievement from slug string or model instance.
     */
    protected function resolveAchievement(): ?Achievement
    {
        if ($this->achievement === null) {
            // Try using reason as achievement slug if no explicit achievement set
            if ($this->reason !== null) {
                return Achievement::findBySlug($this->reason);
            }

            return null;
        }

        if ($this->achievement instanceof Achievement) {
            return $this->achievement;
        }

        return Achievement::findBySlug($this->achievement);
    }

    /**
     * Check if the recipient model uses the Awardable trait.
     */
    protected function recipientIsAwardable(): bool
    {
        return method_exists($this->recipient, 'profile')
            && method_exists($this->recipient, 'achievementGrants');
    }

    /**
     * Check if the recipient is opted out of gamification features.
     *
     * Returns false if the recipient doesn't use HasProfile trait (defaults to opted in).
     */
    protected function recipientIsOptedOut(): bool
    {
        // Only check opt-out if recipient uses HasProfile trait
        if (! method_exists($this->recipient, 'isOptedOut')) {
            return false;
        }

        return $this->recipient->isOptedOut();
    }

    /**
     * Resolve the default amount from config if not explicitly set.
     *
     * Uses the award_types config or registry to get type-specific defaults,
     * falling back to the global defaults.points config.
     */
    protected function resolveDefaultAmount(): void
    {
        if ($this->amount !== null) {
            return;
        }

        $typeValue = $this->getTypeValue();

        // Try to get default from registry first
        $registryDefault = AwardTypeRegistry::getDefaultAmount($typeValue);
        if ($registryDefault !== 1 || AwardTypeRegistry::isRegistered($typeValue)) {
            $this->amount = $registryDefault;

            return;
        }

        // Try to get type-specific default from award_types config
        $typeDefault = config("lfl.award_types.{$typeValue}.default_amount");

        if ($typeDefault !== null) {
            $this->amount = $typeDefault;

            return;
        }

        // Fall back to global default points
        $this->amount = config('lfl.defaults.points', 1);
    }

    /**
     * Get the recipient's total points before this award.
     */
    protected function getPreviousTotal(): int|float
    {
        if (! $this->isCumulativeType()) {
            return 0;
        }

        return $this->recipient->getTotalPoints($this->getTypeValue());
    }

    /**
     * Get the recipient's total points after this award.
     */
    protected function getNewTotal(): int|float
    {
        if (! $this->isCumulativeType()) {
            return 0;
        }

        return $this->getPreviousTotal() + $this->amount;
    }

    /**
     * Get the type value (string) regardless of whether it's an enum or custom type.
     */
    protected function getTypeValue(): string
    {
        return $this->type instanceof AwardType ? $this->type->value : $this->type;
    }

    /**
     * Get the type label (human-readable name).
     */
    protected function getTypeLabel(): string
    {
        if ($this->type instanceof AwardType) {
            return $this->type->label();
        }

        return AwardTypeRegistry::getName($this->type);
    }

    /**
     * Check if the type is an enum type.
     */
    protected function isEnumType(AwardType $enumType): bool
    {
        return $this->type instanceof AwardType && $this->type === $enumType;
    }

    /**
     * Check if the type is cumulative (points-like).
     */
    protected function isCumulativeType(): bool
    {
        if ($this->type instanceof AwardType) {
            return $this->type->isCumulative();
        }

        return AwardTypeRegistry::isCumulative($this->type);
    }

    /**
     * Check if this is a custom cumulative type (for match expression).
     */
    protected function isCustomCumulativeType(): bool
    {
        return ! ($this->type instanceof AwardType) && $this->isCumulativeType();
    }
}
