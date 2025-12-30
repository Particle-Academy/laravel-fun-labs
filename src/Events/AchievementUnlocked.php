<?php

declare(strict_types=1);

namespace LaravelFunLab\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use LaravelFunLab\Contracts\LflEvent;
use LaravelFunLab\Enums\AwardType;
use LaravelFunLab\Models\Achievement;
use LaravelFunLab\Models\AchievementGrant;

/**
 * AchievementUnlocked Event
 *
 * Dispatched whenever an achievement is unlocked/granted to an entity.
 * Contains full context including the achievement definition and grant record.
 */
class AchievementUnlocked implements LflEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  Model  $recipient  The entity that unlocked the achievement
     * @param  Achievement  $achievement  The achievement that was unlocked
     * @param  AchievementGrant  $grant  The grant record that was created
     * @param  string|null  $reason  Why the achievement was granted
     * @param  string|null  $source  Where the grant originated
     */
    public function __construct(
        public Model $recipient,
        public Achievement $achievement,
        public AchievementGrant $grant,
        public ?string $reason = null,
        public ?string $source = null,
    ) {}

    /**
     * Get the award type for this event.
     */
    public function getAwardType(): AwardType
    {
        return AwardType::Achievement;
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
     * Get the achievement slug.
     */
    public function getAchievementSlug(): string
    {
        return $this->achievement->slug;
    }

    /**
     * Get the achievement name.
     */
    public function getAchievementName(): string
    {
        return $this->achievement->name;
    }

    /**
     * Convert the event to an array for logging/analytics.
     *
     * @return array<string, mixed>
     */
    public function toLogArray(): array
    {
        return [
            'event_type' => 'achievement_unlocked',
            'award_type' => AwardType::Achievement->value,
            'recipient_type' => get_class($this->recipient),
            'recipient_id' => $this->recipient->getKey(),
            'achievement_id' => $this->achievement->id,
            'achievement_slug' => $this->achievement->slug,
            'achievement_name' => $this->achievement->name,
            'grant_id' => $this->grant->id,
            'reason' => $this->reason,
            'source' => $this->source,
            'occurred_at' => now()->toIso8601String(),
        ];
    }
}
