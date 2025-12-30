<?php

declare(strict_types=1);

namespace LaravelFunLab\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use LaravelFunLab\Contracts\LflEvent;
use LaravelFunLab\Enums\AwardType;
use LaravelFunLab\Models\Award;

/**
 * PointsAwarded Event
 *
 * Dispatched whenever points are awarded to an entity.
 * Contains full context about the award for listeners to use.
 */
class PointsAwarded implements LflEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  Model  $recipient  The entity that received points
     * @param  Award  $award  The award record that was created
     * @param  float  $amount  The number of points awarded
     * @param  string|null  $reason  Why points were awarded
     * @param  string|null  $source  Where points came from
     * @param  float  $previousTotal  Total points before this award
     * @param  float  $newTotal  Total points after this award
     */
    public function __construct(
        public Model $recipient,
        public Award $award,
        public float $amount,
        public ?string $reason = null,
        public ?string $source = null,
        public float $previousTotal = 0,
        public float $newTotal = 0,
    ) {}

    /**
     * Get the award type for this event.
     */
    public function getAwardType(): AwardType
    {
        return AwardType::Points;
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
     * Get the points delta (how many points were added/removed).
     */
    public function getDelta(): float
    {
        return $this->amount;
    }

    /**
     * Convert the event to an array for logging/analytics.
     *
     * @return array<string, mixed>
     */
    public function toLogArray(): array
    {
        return [
            'event_type' => 'points_awarded',
            'award_type' => AwardType::Points->value,
            'recipient_type' => get_class($this->recipient),
            'recipient_id' => $this->recipient->getKey(),
            'award_id' => $this->award->id,
            'amount' => $this->amount,
            'reason' => $this->reason,
            'source' => $this->source,
            'previous_total' => $this->previousTotal,
            'new_total' => $this->newTotal,
            'occurred_at' => now()->toIso8601String(),
        ];
    }
}
