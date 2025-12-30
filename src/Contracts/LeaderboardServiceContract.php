<?php

declare(strict_types=1);

namespace LaravelFunLab\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * LeaderboardService Contract
 *
 * Defines the public API surface for leaderboard building and querying.
 * This contract allows developers to swap leaderboard implementations
 * while maintaining compatibility with the LFL facade.
 */
interface LeaderboardServiceContract
{
    /**
     * Filter leaderboard by awardable type (User, Team, Organization, etc.).
     *
     * @param  string  $type  The fully qualified class name of the awardable model
     * @return $this
     */
    public function for(string $type): self;

    /**
     * Sort leaderboard by metric.
     *
     * @param  string  $metric  The metric to sort by: 'points', 'achievements', 'prizes'
     * @return $this
     */
    public function by(string $metric): self;

    /**
     * Filter leaderboard by time period.
     *
     * @param  string  $period  The time period: 'daily', 'weekly', 'monthly', 'all-time', or null for all-time
     * @return $this
     */
    public function period(?string $period = null): self;

    /**
     * Set pagination per page.
     *
     * @param  int  $perPage  Number of items per page
     * @return $this
     */
    public function perPage(int $perPage): self;

    /**
     * Set the current page number.
     *
     * @param  int  $page  Page number
     * @return $this
     */
    public function page(int $page): self;

    /**
     * Include or exclude opted-out profiles.
     *
     * @param  bool  $exclude  Whether to exclude opted-out profiles (default: true)
     * @return $this
     */
    public function excludeOptedOut(bool $exclude = true): self;

    /**
     * Build and execute the query, returning a collection of profiles with ranks.
     *
     * @return Collection<int, \LaravelFunLab\Models\Profile>
     */
    public function get(): Collection;

    /**
     * Build and execute the query with pagination.
     */
    public function paginate(): LengthAwarePaginator;

    /**
     * Get the first N results from the leaderboard.
     *
     * @param  int  $limit  Number of results to return
     * @return Collection<int, \LaravelFunLab\Models\Profile>
     */
    public function take(int $limit): Collection;
}
