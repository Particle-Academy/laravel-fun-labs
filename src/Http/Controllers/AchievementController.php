<?php

declare(strict_types=1);

namespace LaravelFunLab\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use LaravelFunLab\Models\Achievement;

/**
 * AchievementController
 *
 * API controller for retrieving available achievements.
 * Supports filtering by awardable type and active status.
 */
class AchievementController extends Controller
{
    /**
     * Get all available achievements.
     *
     * GET /achievements
     *
     * Query parameters:
     * - awardable_type: Filter by awardable type (optional)
     * - active: Filter by active status (true/false) - default: true
     */
    public function index(Request $request): JsonResponse
    {
        $query = Achievement::query();

        // Filter by awardable type if provided
        if ($request->has('awardable_type')) {
            $query->forAwardableType($request->input('awardable_type'));
        }

        // Filter by active status (default to active only)
        if ($request->has('active')) {
            if ($request->boolean('active')) {
                $query->active();
            } else {
                $query->where('is_active', false);
            }
        } else {
            $query->active();
        }

        // Order by sort_order
        $query->ordered();

        $achievements = $query->get();

        return response()->json([
            'data' => $achievements->map(function (Achievement $achievement) {
                return [
                    'id' => $achievement->id,
                    'slug' => $achievement->slug,
                    'name' => $achievement->name,
                    'description' => $achievement->description,
                    'icon' => $achievement->icon,
                    'awardable_type' => $achievement->awardable_type,
                    'meta' => $achievement->meta,
                    'is_active' => $achievement->is_active,
                    'sort_order' => $achievement->sort_order,
                    'created_at' => $achievement->created_at->toIso8601String(),
                    'updated_at' => $achievement->updated_at->toIso8601String(),
                ];
            }),
        ]);
    }
}
