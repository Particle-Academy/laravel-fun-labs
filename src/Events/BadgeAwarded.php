<?php

declare(strict_types=1);

namespace LaravelFunLab\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use LaravelFunLab\Contracts\LflEvent;
use LaravelFunLab\Enums\AwardType;

/**
 * BadgeAwarded Event
 *
 * @deprecated Badges should be implemented as Achievements. Use AchievementUnlocked event instead.
 *
 * Dispatched whenever a badge is awarded to an entity.
 * Contains full context for badge display and analytics.
 */
class BadgeAwarded implements LflEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  Model  $recipient  The entity that received the badge
     * @param  Model  $award  The award record that was created
     * @param  string|null  $reason  Badge identifier or reason
     * @param  string|null  $source  Where the badge came from
     * @param  array<string, mixed>  $meta  Additional badge metadata (icon, color, etc.)
     */
    public function __construct(
        public Model $recipient,
        public Model $award,
        public ?string $reason = null,
        public ?string $source = null,
        public array $meta = [],
    ) {}

    /**
     * Get the award type for this event.
     */
    public function getAwardType(): AwardType
    {
        return AwardType::Badge;
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
     * Get the badge identifier (often stored in reason).
     */
    public function getBadgeIdentifier(): ?string
    {
        return $this->reason;
    }

    /**
     * Convert the event to an array for logging/analytics.
     *
     * @return array<string, mixed>
     */
    public function toLogArray(): array
    {
        return [
            'event_type' => 'badge_awarded',
            'award_type' => AwardType::Badge->value,
            'recipient_type' => get_class($this->recipient),
            'recipient_id' => $this->recipient->getKey(),
            'award_id' => $this->award->id,
            'badge_identifier' => $this->reason,
            'source' => $this->source,
            'meta' => $this->meta,
            'occurred_at' => now()->toIso8601String(),
        ];
    }
}
