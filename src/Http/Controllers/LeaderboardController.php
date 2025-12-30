<?php

declare(strict_types=1);

namespace LaravelFunLab\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use LaravelFunLab\Facades\LFL;

/**
 * LeaderboardController
 *
 * API controller for retrieving leaderboard data by awardable type.
 * Supports filtering by metric (points, achievements, prizes) and time period.
 */
class LeaderboardController extends Controller
{
    /**
     * Get leaderboard data for a specific awardable type.
     *
     * GET /leaderboards/{type}
     *
     * Query parameters:
     * - by: Sort metric ('points', 'achievements', 'prizes') - default: 'points'
     * - period: Time period ('daily', 'weekly', 'monthly', 'all-time') - default: 'all-time'
     * - per_page: Items per page - default: 15
     * - page: Page number - default: 1
     *
     * @param  string  $type  The awardable type (e.g., 'App\Models\User')
     */
    public function index(string $type, Request $request): JsonResponse
    {
        $builder = LFL::leaderboard()
            ->for($type)
            ->by($request->input('by', 'points'))
            ->period($request->input('period', 'all-time'))
            ->perPage((int) $request->input('per_page', 15))
            ->page((int) $request->input('page', 1));

        $leaderboard = $builder->paginate();

        return response()->json([
            'data' => $leaderboard->map(function ($profile) {
                return [
                    'rank' => $profile->rank ?? null,
                    'id' => $profile->id,
                    'awardable_type' => $profile->awardable_type,
                    'awardable_id' => $profile->awardable_id,
                    'total_points' => (float) $profile->total_points,
                    'achievement_count' => $profile->achievement_count,
                    'prize_count' => $profile->prize_count,
                    'last_activity_at' => $profile->last_activity_at?->toIso8601String(),
                ];
            }),
            'meta' => [
                'current_page' => $leaderboard->currentPage(),
                'from' => $leaderboard->firstItem(),
                'last_page' => $leaderboard->lastPage(),
                'per_page' => $leaderboard->perPage(),
                'to' => $leaderboard->lastItem(),
                'total' => $leaderboard->total(),
            ],
            'links' => [
                'first' => $leaderboard->url(1),
                'last' => $leaderboard->url($leaderboard->lastPage()),
                'prev' => $leaderboard->previousPageUrl(),
                'next' => $leaderboard->nextPageUrl(),
            ],
        ]);
    }
}
