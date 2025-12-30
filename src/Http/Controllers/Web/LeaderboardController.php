<?php

declare(strict_types=1);

namespace LaravelFunLab\Http\Controllers\Web;

use Illuminate\Http\Request;
use LaravelFunLab\Facades\LFL;
use LaravelFunLab\Http\Controllers\Controller;

/**
 * LeaderboardController (Web)
 *
 * Web controller for displaying leaderboard views.
 */
class LeaderboardController extends Controller
{
    /**
     * Display the leaderboard for a specific awardable type.
     *
     * @param  string  $type  The awardable type (e.g., 'App\Models\User')
     * @return \Illuminate\View\View
     */
    public function index(string $type, Request $request)
    {
        $builder = LFL::leaderboard()
            ->for($type)
            ->by($request->input('by', 'points'))
            ->period($request->input('period', 'all-time'))
            ->perPage((int) $request->input('per_page', 15))
            ->page((int) $request->input('page', 1));

        $leaderboard = $builder->paginate();

        return view('lfl::leaderboard', [
            'leaderboard' => $leaderboard,
            'type' => $type,
            'sortBy' => $request->input('by', 'points'),
            'period' => $request->input('period', 'all-time'),
        ]);
    }
}
