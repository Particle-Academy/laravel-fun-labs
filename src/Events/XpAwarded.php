<?php

declare(strict_types=1);

namespace LaravelFunLab\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use LaravelFunLab\Contracts\LflEvent;
use LaravelFunLab\Enums\AwardType;
use LaravelFunLab\Models\GamedMetric;
use LaravelFunLab\Models\ProfileMetric;

/**
 * XpAwarded Event
 *
 * Dispatched whenever XP is awarded to a GamedMetric for an entity.
 * Contains full context including the metric, amount, and profile record.
 */
class XpAwarded implements LflEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  ProfileMetric  $profileMetric  The profile metric record that was updated
     * @param  GamedMetric  $gamedMetric  The GamedMetric XP was awarded to
     * @param  Model  $recipient  The entity that received the XP
     * @param  int  $amount  The amount of XP awarded
     * @param  string|null  $reason  Why the XP was awarded
     * @param  string|null  $source  Where the XP came from
     * @param  array<string, mixed>  $meta  Additional metadata
     */
    public function __construct(
        public ProfileMetric $profileMetric,
        public GamedMetric $gamedMetric,
        public Model $recipient,
        public int $amount,
        public ?string $reason = null,
        public ?string $source = null,
        public array $meta = [],
    ) {}

    /**
     * Get the award type for this event.
     */
    public function getAwardType(): AwardType
    {
        return AwardType::Points; // XP is tracked as points internally
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
     * Get the GamedMetric slug.
     */
    public function getMetricSlug(): string
    {
        return $this->gamedMetric->slug;
    }

    /**
     * Get the amount of XP awarded.
     */
    public function getAmount(): int
    {
        return $this->amount;
    }

    /**
     * Get the new total XP for this metric.
     */
    public function getTotalXp(): int
    {
        return $this->profileMetric->total_xp;
    }

    /**
     * Get the current level for this metric.
     */
    public function getCurrentLevel(): int
    {
        return $this->profileMetric->current_level;
    }

    /**
     * Convert the event to an array for logging/analytics.
     *
     * @return array<string, mixed>
     */
    public function toLogArray(): array
    {
        return [
            'event_type' => 'xp_awarded',
            'award_type' => AwardType::Points->value,
            'recipient_type' => get_class($this->recipient),
            'recipient_id' => $this->recipient->getKey(),
            'gamed_metric_id' => $this->gamedMetric->id,
            'gamed_metric_slug' => $this->gamedMetric->slug,
            'amount' => $this->amount,
            'total_xp' => $this->profileMetric->total_xp,
            'current_level' => $this->profileMetric->current_level,
            'reason' => $this->reason,
            'source' => $this->source,
            'meta' => $this->meta,
            'occurred_at' => now()->toIso8601String(),
        ];
    }
}
