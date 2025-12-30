<?php

declare(strict_types=1);

namespace LaravelFunLab\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use LaravelFunLab\Enums\AwardType;
use LaravelFunLab\ValueObjects\AwardResult;

/**
 * AwardGranted Event
 *
 * Dispatched whenever an award (points, achievement, prize, badge) is successfully granted.
 * Listeners can use this for notifications, analytics, triggers, or any custom business logic.
 */
class AwardGranted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  AwardResult  $result  The result of the award operation
     * @param  AwardType|string  $type  The type of award that was granted
     * @param  Model  $recipient  The entity that received the award
     * @param  Model  $award  The award/achievement model that was created
     */
    public function __construct(
        public AwardResult $result,
        public AwardType|string $type,
        public Model $recipient,
        public Model $award,
    ) {}

    /**
     * Get the reason for the award.
     */
    public function getReason(): ?string
    {
        return $this->result->meta['reason'] ?? null;
    }

    /**
     * Get the source of the award.
     */
    public function getSource(): ?string
    {
        return $this->result->meta['source'] ?? null;
    }
}
