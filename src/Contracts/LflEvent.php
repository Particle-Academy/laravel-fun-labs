<?php

declare(strict_types=1);

namespace LaravelFunLab\Contracts;

use Illuminate\Database\Eloquent\Model;
use LaravelFunLab\Enums\AwardType;

/**
 * LflEvent Contract
 *
 * Interface for all LFL events to ensure consistent structure across
 * the event pipeline. All award events implement this contract.
 */
interface LflEvent
{
    /**
     * Get the award type for this event.
     */
    public function getAwardType(): AwardType;

    /**
     * Get the recipient/awardable entity.
     */
    public function getRecipient(): Model;

    /**
     * Get the reason for the award.
     */
    public function getReason(): ?string;

    /**
     * Get the source of the award.
     */
    public function getSource(): ?string;

    /**
     * Convert the event to an array for logging/analytics.
     *
     * @return array<string, mixed>
     */
    public function toLogArray(): array;
}
