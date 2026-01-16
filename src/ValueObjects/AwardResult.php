<?php

declare(strict_types=1);

namespace LaravelFunLab\ValueObjects;

use Illuminate\Database\Eloquent\Model;
use LaravelFunLab\Enums\AwardType;

/**
 * AwardResult Value Object
 *
 * Encapsulates the result of an award operation, providing success/failure status,
 * the awarded entity, error messages, and additional metadata for debugging and display.
 */
readonly class AwardResult
{
    /**
     * @param  bool  $success  Whether the award operation succeeded
     * @param  string|null  $message  Human-readable result message
     * @param  Model|null  $award  The created award/achievement/prize model (null on failure)
     * @param  Model|null  $recipient  The entity that received the award
     * @param  AwardType|string|null  $type  The type of award that was attempted
     * @param  array<string, mixed>  $errors  Validation or processing errors
     * @param  array<string, mixed>  $meta  Additional metadata about the award operation
     */
    public function __construct(
        public bool $success,
        public ?string $message = null,
        public ?Model $award = null,
        public ?Model $recipient = null,
        public AwardType|string|null $type = null,
        public array $errors = [],
        public array $meta = [],
    ) {}

    /**
     * Create a successful award result.
     *
     * @param  Model  $award  The awarded model
     * @param  string|null  $message  Optional success message
     * @param  Model|null  $recipient  The recipient
     * @param  AwardType|string|null  $type  The award type
     * @param  array<string, mixed>  $meta  Additional metadata
     */
    public static function success(
        Model $award,
        ?string $message = null,
        ?Model $recipient = null,
        AwardType|string|null $type = null,
        array $meta = [],
    ): self {
        return new self(
            success: true,
            message: $message ?? 'Award granted successfully',
            award: $award,
            recipient: $recipient,
            type: $type,
            meta: $meta,
        );
    }

    /**
     * Create a failed award result.
     *
     * @param  string  $message  The error message
     * @param  array<string, mixed>  $errors  Additional error details
     * @param  Model|null  $recipient  The intended recipient
     * @param  AwardType|string|null  $type  The award type that was attempted
     * @param  array<string, mixed>  $meta  Additional metadata
     */
    public static function failure(
        string $message,
        array $errors = [],
        ?Model $recipient = null,
        AwardType|string|null $type = null,
        array $meta = [],
    ): self {
        return new self(
            success: false,
            message: $message,
            award: null,
            recipient: $recipient,
            type: $type,
            errors: $errors,
            meta: $meta,
        );
    }

    /**
     * Check if the award operation was successful.
     */
    public function succeeded(): bool
    {
        return $this->success;
    }

    /**
     * Check if the award operation failed.
     */
    public function failed(): bool
    {
        return ! $this->success;
    }

    /**
     * Get the first error message if any.
     */
    public function firstError(): ?string
    {
        if (empty($this->errors)) {
            return $this->message;
        }

        $values = array_values($this->errors);
        $first = $values[0] ?? null;

        return is_array($first) ? ($first[0] ?? null) : $first;
    }

    /**
     * Check if a specific error exists.
     */
    public function hasError(string $key): bool
    {
        return isset($this->errors[$key]);
    }

    /**
     * Get the awarded model (alias for fluent API).
     */
    public function getAward(): ?Model
    {
        return $this->award;
    }

    /**
     * Convert the result to an array for API responses.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $typeValue = $this->type instanceof AwardType ? $this->type->value : $this->type;

        return [
            'success' => $this->success,
            'type' => $typeValue,
            'message' => $this->message,
            'award_id' => $this->award?->getKey(),
            'recipient_type' => $this->recipient ? get_class($this->recipient) : null,
            'recipient_id' => $this->recipient?->getKey(),
            'errors' => $this->errors,
            'meta' => $this->meta,
        ];
    }
}
