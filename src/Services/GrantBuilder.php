<?php

declare(strict_types=1);

namespace LaravelFunLab\Services;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use LaravelFunLab\Enums\AwardType;
use LaravelFunLab\Models\Achievement;
use LaravelFunLab\Models\Prize;
use LaravelFunLab\Pipelines\AwardValidationPipeline;
use LaravelFunLab\ValueObjects\AwardResult;

/**
 * GrantBuilder
 *
 * Fluent builder for granting Achievements or Prizes.
 * Created via LFL::grant('slug').
 *
 * Automatically detects whether the slug refers to an Achievement or Prize.
 *
 * @example LFL::grant('first-login')->to($user)->because('completed onboarding')->save()
 * @example LFL::grant('premium-access')->to($user)->save()
 */
class GrantBuilder
{
    protected ?Model $recipient = null;

    protected ?string $reason = null;

    protected ?string $source = null;

    protected array $meta = [];

    protected ?string $entityType = null;

    public function __construct(
        protected string $slug,
        protected AwardEngine $awardEngine
    ) {
        // Auto-detect entity type
        $this->detectEntityType();
    }

    /**
     * Detect whether the slug refers to an Achievement or Prize.
     */
    protected function detectEntityType(): void
    {
        // Check if it's an achievement
        if (Achievement::where('slug', $this->slug)->exists()) {
            $this->entityType = 'achievement';

            return;
        }

        // Check if it's a prize
        if (Prize::where('slug', $this->slug)->exists()) {
            $this->entityType = 'prize';

            return;
        }

        // Entity type will be determined at save time if not found
        $this->entityType = null;
    }

    /**
     * Set the recipient of the grant.
     *
     * @param  Model  $recipient  The awardable entity (must use Awardable trait)
     * @return $this
     */
    public function to(Model $recipient): self
    {
        $this->recipient = $recipient;

        return $this;
    }

    /**
     * Set the reason for the grant.
     *
     * @param  string  $reason  Why the grant is being made
     * @return $this
     */
    public function because(string $reason): self
    {
        $this->reason = $reason;

        return $this;
    }

    /**
     * Alias for because().
     *
     * @param  string  $reason  Why the grant is being made
     * @return $this
     */
    public function for(string $reason): self
    {
        return $this->because($reason);
    }

    /**
     * Set the source of the grant.
     *
     * @param  string  $source  Where the grant came from
     * @return $this
     */
    public function from(string $source): self
    {
        $this->source = $source;

        return $this;
    }

    /**
     * Set additional metadata for the grant.
     *
     * @param  array<string, mixed>  $meta  Additional data
     * @return $this
     */
    public function withMeta(array $meta): self
    {
        $this->meta = $meta;

        return $this;
    }

    /**
     * Execute the grant and return the result.
     *
     * @return AwardResult The result of the grant operation
     *
     * @throws InvalidArgumentException If recipient is not set or entity not found
     */
    public function save(): AwardResult
    {
        if ($this->recipient === null) {
            throw new InvalidArgumentException('Recipient is required. Use ->to($user) to set the recipient.');
        }

        // Re-detect entity type in case it was created after builder instantiation
        if ($this->entityType === null) {
            $this->detectEntityType();
        }

        if ($this->entityType === null) {
            return AwardResult::failure("No Achievement or Prize found with slug '{$this->slug}'");
        }

        // Determine the award type for validation
        $awardType = $this->entityType === 'achievement' ? AwardType::Achievement : AwardType::Prize;

        // Run validation pipeline
        $validation = AwardValidationPipeline::validate(
            awardable: $this->recipient,
            type: $awardType,
            amount: 0,
            reason: $this->reason,
            source: $this->source,
            meta: $this->meta
        );

        if (! $validation['valid']) {
            return AwardResult::failure($validation['message'] ?? 'Validation failed');
        }

        if ($this->entityType === 'achievement') {
            return $this->awardEngine->grantAchievementInternal(
                $this->recipient,
                $this->slug,
                $this->reason,
                $this->source,
                $this->meta
            );
        }

        return $this->awardEngine->grantPrizeInternal(
            $this->recipient,
            $this->slug,
            $this->reason,
            $this->source,
            $this->meta
        );
    }

    /**
     * Alias for save().
     */
    public function grant(): AwardResult
    {
        return $this->save();
    }
}
