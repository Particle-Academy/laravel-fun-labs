<?php

declare(strict_types=1);

namespace LaravelFunLab\Http\Controllers\Web;

use LaravelFunLab\Http\Controllers\Controller;
use LaravelFunLab\Models\Profile;

/**
 * ProfileController (Web)
 *
 * Web controller for displaying profile views.
 */
class ProfileController extends Controller
{
    /**
     * Display the profile for a specific awardable entity.
     *
     * @param  string  $type  The awardable type (e.g., 'App\Models\User')
     * @param  int  $id  The awardable ID
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function show(string $type, int $id)
    {
        $profile = Profile::query()
            ->where('awardable_type', $type)
            ->where('awardable_id', $id)
            ->with(['awardable', 'metrics.gamedMetric'])
            ->first();

        if ($profile === null) {
            abort(404, 'Profile not found');
        }

        // Load profile metrics (XP per GamedMetric)
        $profileMetrics = $profile->metrics()
            ->with('gamedMetric')
            ->orderByDesc('total_xp')
            ->get();

        // Load achievements
        $achievements = collect();
        if ($profile->awardable && method_exists($profile->awardable, 'achievementGrants')) {
            $achievements = $profile->awardable->achievementGrants()
                ->with('achievement')
                ->orderByDesc('created_at')
                ->get();
        }

        // Load recent XP grants (from profile metrics) for backwards compatibility
        $recentAwards = $profileMetrics;

        return view('lfl::profile', [
            'profile' => $profile,
            'profileMetrics' => $profileMetrics,
            'achievements' => $achievements,
            'recentAwards' => $recentAwards,
        ]);
    }
}
