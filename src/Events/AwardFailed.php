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
 * AwardFailed Event
 *
 * Dispatched when an award operation fails (validation error, duplicate achievement, etc.).
 * Useful for logging, monitoring, and debugging award issues.
 */
class AwardFailed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  AwardResult  $result  The result of the failed award operation
     * @param  AwardType|string  $type  The type of award that was attempted
     * @param  Model|null  $recipient  The intended recipient (if available)
     */
    public function __construct(
        public AwardResult $result,
        public AwardType|string $type,
        public ?Model $recipient = null,
    ) {}

    /**
     * Get the error message.
     */
    public function getErrorMessage(): ?string
    {
        return $this->result->message;
    }

    /**
     * Get all errors.
     *
     * @return array<string, mixed>
     */
    public function getErrors(): array
    {
        return $this->result->errors;
    }
}
