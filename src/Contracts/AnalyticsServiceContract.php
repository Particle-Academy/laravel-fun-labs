<?php

declare(strict_types=1);

namespace LaravelFunLab\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use LaravelFunLab\Enums\AwardType;

/**
 * AnalyticsService Contract
 *
 * Defines the public API surface for analytics building and querying.
 * This contract allows developers to swap analytics implementations
 * while maintaining compatibility with the LFL facade.
 */
interface AnalyticsServiceContract
{
    /**
     * Filter analytics by award type.
     *
     * @param  AwardType|string  $type  The award type to filter by
     * @return $this
     */
    public function byType(AwardType|string $type): self;

    /**
     * Filter analytics by awardable type (User, Team, Organization, etc.).
     *
     * @param  string  $type  The fully qualified class name of the awardable model
     * @return $this
     */
    public function forAwardableType(string $type): self;

    /**
     * Filter analytics by specific awardable entity.
     *
     * @param  Model  $awardable  The awardable model instance
     * @return $this
     */
    public function forAwardable(Model $awardable): self;

    /**
     * Filter analytics by source.
     *
     * @param  string  $source  The source identifier
     * @return $this
     */
    public function fromSource(string $source): self;

    /**
     * Filter analytics by achievement slug.
     *
     * @param  string  $slug  The achievement slug
     * @return $this
     */
    public function forAchievement(string $slug): self;

    /**
     * Filter analytics by time period.
     *
     * @param  string  $period  The time period: 'daily', 'weekly', 'monthly', 'yearly', or null for all-time
     * @return $this
     */
    public function period(?string $period = null): self;

    /**
     * Filter analytics between two dates.
     *
     * @param  \Carbon\Carbon|\Illuminate\Support\Carbon|string  $start  Start date
     * @param  \Carbon\Carbon|\Illuminate\Support\Carbon|string|null  $end  End date (defaults to now)
     * @return $this
     */
    public function between(\Carbon\Carbon|\Illuminate\Support\Carbon|string $start, \Carbon\Carbon|\Illuminate\Support\Carbon|string|null $end = null): self;

    /**
     * Filter analytics since a specific date.
     *
     * @param  \Carbon\Carbon|\Illuminate\Support\Carbon|string  $date  The start date
     * @return $this
     */
    public function since(\Carbon\Carbon|\Illuminate\Support\Carbon|string $date): self;

    /**
     * Filter analytics until a specific date.
     *
     * @param  \Carbon\Carbon|\Illuminate\Support\Carbon|string  $date  The end date
     * @return $this
     */
    public function until(\Carbon\Carbon|\Illuminate\Support\Carbon|string $date): self;

    /**
     * Get the total count of events matching the current filters.
     */
    public function count(): int;

    /**
     * Get the total amount (sum) for cumulative award types.
     */
    public function total(): float;

    /**
     * Get the average amount for cumulative award types.
     */
    public function average(): float;

    /**
     * Get the minimum amount for cumulative award types.
     */
    public function min(): ?float;

    /**
     * Get the maximum amount for cumulative award types.
     */
    public function max(): ?float;

    /**
     * Get distinct active users (awardables) within the filtered period.
     */
    public function activeUsers(): int;

    /**
     * Get achievement completion rate for a specific achievement.
     *
     * @param  string|null  $achievementSlug  Optional achievement slug (if not already filtered)
     * @return float Completion rate as a percentage (0-100)
     */
    public function achievementCompletionRate(?string $achievementSlug = null): float;

    /**
     * Get time-series data grouped by a time interval.
     *
     * @param  string  $interval  The interval: 'hour', 'day', 'week', 'month', 'year'
     * @return array<int, array<string, mixed>> Array of intervals with counts and totals
     */
    public function timeSeries(string $interval = 'day'): array;

    /**
     * Get aggregated data grouped by award type.
     *
     * @return array<string, array<string, mixed>> Array keyed by award type with counts and totals
     */
    public function byAwardType(): array;

    /**
     * Get aggregated data grouped by awardable type.
     *
     * @return array<string, array<string, mixed>> Array keyed by awardable type with counts and totals
     */
    public function byAwardableType(): array;

    /**
     * Get aggregated data grouped by source.
     *
     * @return array<string, array<string, mixed>> Array keyed by source with counts and totals
     */
    public function bySource(): array;

    /**
     * Get export-ready data as an array.
     *
     * @param  int|null  $limit  Optional limit on number of records
     * @return array<int, array<string, mixed>> Array of event log records
     */
    public function export(?int $limit = null): array;

    /**
     * Get the underlying query builder for custom queries.
     *
     * @return Builder<\LaravelFunLab\Models\EventLog>
     */
    public function query(): Builder;
}
