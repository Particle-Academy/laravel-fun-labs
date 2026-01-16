<?php

declare(strict_types=1);

namespace LaravelFunLab\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use LaravelFunLab\Contracts\LflEvent;
use LaravelFunLab\Enums\AwardType;
use LaravelFunLab\Models\PrizeGrant;

/**
 * PrizeAwarded Event
 *
 * Dispatched whenever a prize is awarded to an entity.
 * Contains full context for prize fulfillment and analytics.
 */
class PrizeAwarded implements LflEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  Model  $recipient  The entity that received the prize
     * @param  PrizeGrant|Model  $award  The PrizeGrant record that was created
     * @param  string|null  $reason  Why the prize was awarded
     * @param  string|null  $source  Where the prize came from
     * @param  array<string, mixed>  $meta  Additional prize metadata
     */
    public function __construct(
        public Model $recipient,
        public PrizeGrant|Model $award,
        public ?string $reason = null,
        public ?string $source = null,
        public array $meta = [],
    ) {}

    /**
     * Get the award type for this event.
     */
    public function getAwardType(): AwardType
    {
        return AwardType::Prize;
    }

    /**
     * Get the recipient/awardable entity.
     */
    public function getRecipient(): Model
    {
        return $this->recipient;
    }

    /**
     * Get the reason for the award.
     */
    public function getReason(): ?string
    {
        return $this->reason;
    }

    /**
     * Get the source of the award.
     */
    public function getSource(): ?string
    {
        return $this->source;
    }

    /**
     * Convert the event to an array for logging/analytics.
     *
     * @return array<string, mixed>
     */
    public function toLogArray(): array
    {
        return [
            'event_type' => 'prize_awarded',
            'award_type' => AwardType::Prize->value,
            'recipient_type' => get_class($this->recipient),
            'recipient_id' => $this->recipient->getKey(),
            'award_id' => $this->award->id,
            'reason' => $this->reason,
            'source' => $this->source,
            'meta' => $this->meta,
            'occurred_at' => now()->toIso8601String(),
        ];
    }
}
