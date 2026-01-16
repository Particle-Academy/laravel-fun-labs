<?php

declare(strict_types=1);

namespace LaravelFunLab\Builders;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use LaravelFunLab\Contracts\LeaderboardServiceContract;
use LaravelFunLab\Models\Profile;

/**
 * LeaderboardBuilder - Fluent Builder for Leaderboard Queries
 *
 * Provides a clean, expressive API for constructing leaderboard queries
 * with filtering, sorting, pagination, and opt-out exclusion.
 *
 * Usage:
 * LFL::leaderboard()->for(User::class)->by('xp')->period('weekly')->get();
 */
class LeaderboardBuilder implements LeaderboardServiceContract
{
    protected ?string $awardableType = null;

    protected string $sortBy = 'xp';

    protected ?string $period = null;

    protected int $perPage = 15;

    protected ?int $page = null;

    protected bool $excludeOptedOut = true;

    /**
     * Filter leaderboard by awardable type (User, Team, Organization, etc.).
     *
     * @param  string  $type  The fully qualified class name of the awardable model
     * @return $this
     */
    public function for(string $type): self
    {
        $this->awardableType = $type;

        return $this;
    }

    /**
     * Sort leaderboard by metric.
     *
     * @param  string  $metric  The metric to sort by: 'xp', 'achievements', 'prizes', or 'points' (alias for xp)
     * @return $this
     */
    public function by(string $metric): self
    {
        // Map 'points' to 'xp' for backwards compatibility
        $this->sortBy = $metric === 'points' ? 'xp' : $metric;

        return $this;
    }

    /**
     * Filter leaderboard by time period.
     *
     * @param  string  $period  The time period: 'daily', 'weekly', 'monthly', 'all-time', or null for all-time
     * @return $this
     */
    public function period(?string $period = null): self
    {
        $this->period = $period;

        return $this;
    }

    /**
     * Set pagination per page.
     *
     * @param  int  $perPage  Number of items per page
     * @return $this
     */
    public function perPage(int $perPage): self
    {
        $this->perPage = $perPage;

        return $this;
    }

    /**
     * Set the current page number.
     *
     * @param  int  $page  Page number
     * @return $this
     */
    public function page(int $page): self
    {
        $this->page = $page;

        return $this;
    }

    /**
     * Include or exclude opted-out profiles.
     *
     * @param  bool  $exclude  Whether to exclude opted-out profiles (default: true)
     * @return $this
     */
    public function excludeOptedOut(bool $exclude = true): self
    {
        $this->excludeOptedOut = $exclude;

        return $this;
    }

    /**
     * Build and execute the query, returning a collection of profiles with ranks.
     *
     * @return Collection<int, Profile>
     */
    public function get(): Collection
    {
        $query = $this->buildQuery();

        return $query->get()->map(function (Profile $profile, int $index) {
            $profile->setAttribute('rank', $index + 1);

            return $profile;
        });
    }

    /**
     * Build and execute the query with pagination.
     */
    public function paginate(): LengthAwarePaginator
    {
        $query = $this->buildQuery();

        $paginator = $query->paginate($this->perPage, ['*'], 'page', $this->page);

        // Add rank to each item based on pagination offset
        $rankOffset = ($paginator->currentPage() - 1) * $paginator->perPage();

        $paginator->getCollection()->transform(function (Profile $profile, int $index) use ($rankOffset) {
            $profile->setAttribute('rank', $rankOffset + $index + 1);

            return $profile;
        });

        return $paginator;
    }

    /**
     * Get the first N results from the leaderboard.
     *
     * @param  int  $limit  Number of results to return
     * @return Collection<int, Profile>
     */
    public function take(int $limit): Collection
    {
        $query = $this->buildQuery();

        return $query->limit($limit)->get()->map(function (Profile $profile, int $index) {
            $profile->setAttribute('rank', $index + 1);

            return $profile;
        });
    }

    /**
     * Build the base query with all filters applied.
     *
     * @return Builder<Profile>
     */
    protected function buildQuery(): Builder
    {
        $profileTable = (new Profile)->getTable();
        $query = Profile::query();

        // Filter by awardable type (apply before join to avoid ambiguity)
        if ($this->awardableType !== null) {
            $query->where($profileTable.'.awardable_type', $this->awardableType);
        }

        // Exclude opted-out profiles by default
        if ($this->excludeOptedOut) {
            $query->optedIn();
        }

        // Apply time-based filtering if needed
        if ($this->period !== null && $this->period !== 'all-time') {
            $query = $this->applyTimeFilter($query);
        }

        // Apply sorting
        $query = $this->applySorting($query);

        return $query;
    }

    /**
     * Apply time-based filtering to the query.
     *
     * For time-based leaderboards, we need to join with profile_metrics and filter by updated_at.
     * This requires a more complex query that aggregates XP within the time period.
     *
     * @param  Builder<Profile>  $query
     * @return Builder<Profile>
     */
    protected function applyTimeFilter(Builder $query): Builder
    {
        $startDate = $this->getPeriodStartDate();

        if ($startDate === null) {
            return $query;
        }

        // For time-based filtering, we need to calculate scores from profile_metrics within the period
        // This requires joining with the profile_metrics table and aggregating
        $profileTable = (new Profile)->getTable();
        $metricsTable = config('lfl.table_prefix', 'lfl_').'profile_metrics';

        // Build a subquery to calculate period-specific scores
        return $query
            ->select($profileTable.'.*')
            ->selectRaw('COALESCE(SUM('.$metricsTable.'.total_xp), 0) as period_xp')
            ->leftJoin($metricsTable, function ($join) use ($profileTable, $metricsTable, $startDate) {
                $join->on($profileTable.'.id', '=', $metricsTable.'.profile_id')
                    ->where($metricsTable.'.updated_at', '>=', $startDate);
            })
            ->groupBy($profileTable.'.id')
            ->havingRaw('COALESCE(SUM('.$metricsTable.'.total_xp), 0) > 0');
    }

    /**
     * Get the start date for the current period.
     */
    protected function getPeriodStartDate(): ?Carbon
    {
        return match ($this->period) {
            'daily' => Carbon::today(),
            'weekly' => Carbon::now()->startOfWeek(),
            'monthly' => Carbon::now()->startOfMonth(),
            default => null,
        };
    }

    /**
     * Apply sorting to the query based on the selected metric.
     *
     * @param  Builder<Profile>  $query
     * @return Builder<Profile>
     */
    protected function applySorting(Builder $query): Builder
    {
        // For time-based filtering, sort by period_xp if it exists
        if ($this->period !== null && $this->period !== 'all-time') {
            return match ($this->sortBy) {
                'xp' => $query->orderByDesc('period_xp'),
                'achievements' => $query->orderByDesc('achievement_count'),
                'prizes' => $query->orderByDesc('prize_count'),
                default => $query->orderByDesc('period_xp'),
            };
        }

        // For all-time leaderboards, use profile aggregates
        return match ($this->sortBy) {
            'xp' => $query->orderByDesc('total_xp'),
            'achievements' => $query->orderByDesc('achievement_count'),
            'prizes' => $query->orderByDesc('prize_count'),
            default => $query->orderByDesc('total_xp'),
        };
    }
}
