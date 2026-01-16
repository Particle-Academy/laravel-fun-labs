<?php

declare(strict_types=1);

namespace LaravelFunLab\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use LaravelFunLab\Models\Profile;
use LaravelFunLab\Models\ProfileMetric;

/**
 * AwardsController
 *
 * API controller for retrieving XP and metrics for awardable entities.
 * Returns ProfileMetrics (XP per GamedMetric) for a given profile.
 */
class AwardsController extends Controller
{
    /**
     * Get XP metrics for a specific awardable entity.
     *
     * GET /awards/{type}/{id}
     *
     * Query parameters:
     * - metric_slug: Filter by GamedMetric slug - optional
     * - per_page: Items per page - default: 15
     * - page: Page number - default: 1
     *
     * @param  string  $type  The awardable type (e.g., 'App\Models\User')
     * @param  int  $id  The awardable ID
     */
    public function index(string $type, int $id, Request $request): JsonResponse
    {
        // Find the profile for this awardable
        $profile = Profile::where('awardable_type', $type)
            ->where('awardable_id', $id)
            ->first();

        if (! $profile) {
            return response()->json([
                'data' => [],
                'profile' => null,
                'meta' => [
                    'total' => 0,
                ],
            ]);
        }

        $query = ProfileMetric::query()
            ->where('profile_id', $profile->id)
            ->with('gamedMetric')
            ->orderByDesc('total_xp');

        // Filter by metric slug if provided
        if ($request->has('metric_slug')) {
            $query->whereHas('gamedMetric', function ($q) use ($request) {
                $q->where('slug', $request->input('metric_slug'));
            });
        }

        $perPage = (int) $request->input('per_page', 15);
        $metrics = $query->paginate($perPage);

        return response()->json([
            'data' => $metrics->map(function (ProfileMetric $metric) {
                return [
                    'id' => $metric->id,
                    'profile_id' => $metric->profile_id,
                    'gamed_metric_id' => $metric->gamed_metric_id,
                    'gamed_metric_slug' => $metric->gamedMetric?->slug,
                    'gamed_metric_name' => $metric->gamedMetric?->name,
                    'total_xp' => $metric->total_xp,
                    'current_level' => $metric->current_level,
                    'created_at' => $metric->created_at->toIso8601String(),
                    'updated_at' => $metric->updated_at->toIso8601String(),
                ];
            }),
            'profile' => [
                'id' => $profile->id,
                'total_xp' => $profile->total_xp,
                'achievement_count' => $profile->achievement_count,
                'prize_count' => $profile->prize_count,
                'is_opted_in' => $profile->is_opted_in,
            ],
            'meta' => [
                'current_page' => $metrics->currentPage(),
                'from' => $metrics->firstItem(),
                'last_page' => $metrics->lastPage(),
                'per_page' => $metrics->perPage(),
                'to' => $metrics->lastItem(),
                'total' => $metrics->total(),
            ],
            'links' => [
                'first' => $metrics->url(1),
                'last' => $metrics->url($metrics->lastPage()),
                'prev' => $metrics->previousPageUrl(),
                'next' => $metrics->nextPageUrl(),
            ],
        ]);
    }
}
