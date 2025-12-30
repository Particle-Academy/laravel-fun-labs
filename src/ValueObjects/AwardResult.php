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
     * @param  AwardType|string  $type  The type of award that was attempted
     * @param  Model|null  $award  The created award/achievement/prize model (null on failure)
     * @param  Model|null  $recipient  The entity that received the award
     * @param  string|null  $message  Human-readable result message
     * @param  array<string, mixed>  $errors  Validation or processing errors
     * @param  array<string, mixed>  $meta  Additional metadata about the award operation
     */
    public function __construct(
        public bool $success,
        public AwardType|string $type,
        public ?Model $award = null,
        public ?Model $recipient = null,
        public ?string $message = null,
        public array $errors = [],
        public array $meta = [],
    ) {}

    /**
     * Create a successful award result.
     *
     * @param  array<string, mixed>  $meta
     */
    public static function success(
        AwardType|string $type,
        Model $award,
        Model $recipient,
        ?string $message = null,
        array $meta = [],
    ): self {
        $typeLabel = $type instanceof AwardType ? $type->label() : \LaravelFunLab\Registries\AwardTypeRegistry::getName($type);

        return new self(
            success: true,
            type: $type,
            award: $award,
            recipient: $recipient,
            message: $message ?? "Successfully awarded {$typeLabel}",
            meta: $meta,
        );
    }

    /**
     * Create a failed award result.
     *
     * @param  array<string, mixed>  $errors
     * @param  array<string, mixed>  $meta
     */
    public static function failure(
        AwardType|string $type,
        string $message,
        array $errors = [],
        ?Model $recipient = null,
        array $meta = [],
    ): self {
        return new self(
            success: false,
            type: $type,
            award: null,
            recipient: $recipient,
            message: $message,
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
            return null;
        }

        // Use array_values to avoid modifying the readonly property with reset()
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
