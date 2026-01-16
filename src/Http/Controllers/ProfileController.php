<?php

declare(strict_types=1);

namespace LaravelFunLab\Http\Controllers;

use Illuminate\Http\JsonResponse;
use LaravelFunLab\Models\Profile;

/**
 * ProfileController
 *
 * API controller for retrieving profile data by awardable type and ID.
 * Provides access to engagement profiles with opt-in status, metrics, and preferences.
 */
class ProfileController extends Controller
{
    /**
     * Get profile data for a specific awardable entity.
     *
     * GET /profiles/{type}/{id}
     *
     * @param  string  $type  The awardable type (e.g., 'App\Models\User')
     * @param  int  $id  The awardable ID
     */
    public function show(string $type, int $id): JsonResponse
    {
        $profile = Profile::query()
            ->where('awardable_type', $type)
            ->where('awardable_id', $id)
            ->first();

        if ($profile === null) {
            return response()->json([
                'message' => 'Profile not found',
            ], 404);
        }

        return response()->json([
            'data' => [
                'id' => $profile->id,
                'awardable_type' => $profile->awardable_type,
                'awardable_id' => $profile->awardable_id,
                'is_opted_in' => $profile->is_opted_in,
                'display_preferences' => $profile->display_preferences,
                'visibility_settings' => $profile->visibility_settings,
                'total_xp' => (int) $profile->total_xp,
                'achievement_count' => $profile->achievement_count,
                'prize_count' => $profile->prize_count,
                'last_activity_at' => $profile->last_activity_at?->toIso8601String(),
                'created_at' => $profile->created_at->toIso8601String(),
                'updated_at' => $profile->updated_at->toIso8601String(),
            ],
        ]);
    }
}
