<?php

declare(strict_types=1);

namespace LaravelFunLab\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use LaravelFunLab\Models\Award;

/**
 * AwardsController
 *
 * API controller for retrieving award history for awardable entities.
 * Supports filtering by award type and pagination.
 */
class AwardsController extends Controller
{
    /**
     * Get award history for a specific awardable entity.
     *
     * GET /awards/{type}/{id}
     *
     * Query parameters:
     * - award_type: Filter by award type (e.g., 'points', 'badge') - optional
     * - per_page: Items per page - default: 15
     * - page: Page number - default: 1
     *
     * @param  string  $type  The awardable type (e.g., 'App\Models\User')
     * @param  int  $id  The awardable ID
     */
    public function index(string $type, int $id, Request $request): JsonResponse
    {
        $query = Award::query()
            ->where('awardable_type', $type)
            ->where('awardable_id', $id)
            ->orderByDesc('created_at');

        // Filter by award type if provided
        if ($request->has('award_type')) {
            $query->ofType($request->input('award_type'));
        }

        $perPage = (int) $request->input('per_page', 15);
        $awards = $query->paginate($perPage);

        return response()->json([
            'data' => $awards->map(function (Award $award) {
                return [
                    'id' => $award->id,
                    'awardable_type' => $award->awardable_type,
                    'awardable_id' => $award->awardable_id,
                    'type' => $award->type,
                    'amount' => (float) $award->amount,
                    'reason' => $award->reason,
                    'source' => $award->source,
                    'meta' => $award->meta,
                    'created_at' => $award->created_at->toIso8601String(),
                    'updated_at' => $award->updated_at->toIso8601String(),
                ];
            }),
            'meta' => [
                'current_page' => $awards->currentPage(),
                'from' => $awards->firstItem(),
                'last_page' => $awards->lastPage(),
                'per_page' => $awards->perPage(),
                'to' => $awards->lastItem(),
                'total' => $awards->total(),
            ],
            'links' => [
                'first' => $awards->url(1),
                'last' => $awards->url($awards->lastPage()),
                'prev' => $awards->previousPageUrl(),
                'next' => $awards->nextPageUrl(),
            ],
        ]);
    }
}
