<?php

declare(strict_types=1);

namespace LaravelFunLab\Http\Controllers\Web;

use Illuminate\Http\Request;
use LaravelFunLab\Http\Controllers\Controller;
use Illuminate\Support\Str;
use LaravelFunLab\Models\Achievement;
use LaravelFunLab\Models\Award;
use LaravelFunLab\Models\GamedMetric;
use LaravelFunLab\Models\MetricLevel;
use LaravelFunLab\Models\MetricLevelGroup;
use LaravelFunLab\Models\MetricLevelGroupLevel;
use LaravelFunLab\Models\Prize;
use LaravelFunLab\Models\Profile;

/**
 * AdminController
 *
 * Web controller for the admin dashboard.
 */
class AdminController extends Controller
{
    /**
     * Display the admin dashboard index.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $stats = [
            'total_profiles' => Profile::count(),
            'total_achievements' => Achievement::count(),
            'total_prizes' => Prize::count(),
            'total_awards' => Award::count(),
            'total_points_awarded' => Award::where('type', 'points')->sum('amount'),
        ];

        return view('lfl::admin.index', [
            'stats' => $stats,
        ]);
    }

    /**
     * Display achievements management page.
     *
     * @return \Illuminate\View\View
     */
    public function achievements()
    {
        $achievements = Achievement::query()
            ->orderBy('sort_order')
            ->paginate(20);

        return view('lfl::admin.achievements', [
            'achievements' => $achievements,
        ]);
    }

    /**
     * Show the form for creating a new achievement.
     *
     * @return \Illuminate\View\View
     */
    public function createAchievement()
    {
        $prizes = Prize::active()->orderBy('name')->get();

        return view('lfl::admin.achievements.create', [
            'prizes' => $prizes,
        ]);
    }

    /**
     * Store a newly created achievement.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeAchievement(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:'.config('lfl.table_prefix', 'lfl_').'achievements,slug',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:255',
            'awardable_type' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
            'prize_ids' => 'nullable|array',
            'prize_ids.*' => 'exists:'.config('lfl.table_prefix', 'lfl_').'prizes,id',
        ]);

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['name']);
        $validated['is_active'] = $request->has('is_active');
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        $achievement = Achievement::create($validated);

        // Attach prizes
        if ($request->has('prize_ids')) {
            $achievement->prizes()->attach($request->prize_ids);
        }

        return redirect()->route('lfl.admin.achievements')
            ->with('success', 'Achievement created successfully.');
    }

    /**
     * Show the form for editing an achievement.
     *
     * @return \Illuminate\View\View
     */
    public function editAchievement(Achievement $achievement)
    {
        $prizes = Prize::active()->orderBy('name')->get();

        return view('lfl::admin.achievements.edit', [
            'achievement' => $achievement,
            'prizes' => $prizes,
        ]);
    }

    /**
     * Update an achievement.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateAchievement(Request $request, Achievement $achievement)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:'.config('lfl.table_prefix', 'lfl_').'achievements,slug,'.$achievement->id,
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:255',
            'awardable_type' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
            'prize_ids' => 'nullable|array',
            'prize_ids.*' => 'exists:'.config('lfl.table_prefix', 'lfl_').'prizes,id',
        ]);

        $validated['is_active'] = $request->has('is_active');
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        $achievement->update($validated);

        // Sync prizes
        $achievement->prizes()->sync($request->prize_ids ?? []);

        return redirect()->route('lfl.admin.achievements')
            ->with('success', 'Achievement updated successfully.');
    }

    /**
     * Delete an achievement.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function deleteAchievement(Achievement $achievement)
    {
        $achievement->delete();

        return redirect()->route('lfl.admin.achievements')
            ->with('success', 'Achievement deleted successfully.');
    }

    /**
     * Display prizes management page.
     *
     * @return \Illuminate\View\View
     */
    public function prizes()
    {
        $prizes = Prize::query()
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('lfl::admin.prizes', [
            'prizes' => $prizes,
        ]);
    }

    /**
     * Show the form for creating a new prize.
     *
     * @return \Illuminate\View\View
     */
    public function createPrize()
    {
        return view('lfl::admin.prizes.create');
    }

    /**
     * Store a newly created prize.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storePrize(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:'.config('lfl.table_prefix', 'lfl_').'prizes,slug',
            'description' => 'nullable|string',
            'type' => 'required|string|in:virtual,physical,feature_unlock,custom',
            'cost_in_points' => 'nullable|numeric|min:0',
            'inventory_quantity' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['name']);
        $validated['is_active'] = $request->has('is_active');
        $validated['cost_in_points'] = $validated['cost_in_points'] ?? 0;
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        Prize::create($validated);

        return redirect()->route('lfl.admin.prizes')
            ->with('success', 'Prize created successfully.');
    }

    /**
     * Show the form for editing a prize.
     *
     * @return \Illuminate\View\View
     */
    public function editPrize(Prize $prize)
    {
        return view('lfl::admin.prizes.edit', [
            'prize' => $prize,
        ]);
    }

    /**
     * Update a prize.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updatePrize(Request $request, Prize $prize)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:'.config('lfl.table_prefix', 'lfl_').'prizes,slug,'.$prize->id,
            'description' => 'nullable|string',
            'type' => 'required|string|in:virtual,physical,feature_unlock,custom',
            'cost_in_points' => 'nullable|numeric|min:0',
            'inventory_quantity' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $validated['is_active'] = $request->has('is_active');
        $validated['cost_in_points'] = $validated['cost_in_points'] ?? 0;
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        $prize->update($validated);

        return redirect()->route('lfl.admin.prizes')
            ->with('success', 'Prize updated successfully.');
    }

    /**
     * Delete a prize.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function deletePrize(Prize $prize)
    {
        $prize->delete();

        return redirect()->route('lfl.admin.prizes')
            ->with('success', 'Prize deleted successfully.');
    }

    /**
     * Display analytics dashboard.
     *
     * @return \Illuminate\View\View
     */
    public function analytics(Request $request)
    {
        $period = $request->input('period', '30'); // days

        // Awards over time
        $awardsOverTime = Award::query()
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count, SUM(amount) as total_points')
            ->where('created_at', '>=', now()->subDays((int) $period))
            ->where('type', 'points')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Top earners
        $topEarners = Profile::query()
            ->orderByDesc('total_points')
            ->limit(10)
            ->with('awardable')
            ->get();

        return view('lfl::admin.analytics', [
            'awardsOverTime' => $awardsOverTime,
            'topEarners' => $topEarners,
            'period' => $period,
        ]);
    }

    /**
     * Display GamedMetrics management page.
     *
     * @return \Illuminate\View\View
     */
    public function gamedMetrics()
    {
        $metrics = GamedMetric::query()
            ->orderBy('name')
            ->paginate(20);

        return view('lfl::admin.gamed-metrics', [
            'metrics' => $metrics,
        ]);
    }

    /**
     * Store a new GamedMetric.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeGamedMetric(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:'.config('lfl.table_prefix', 'lfl_').'gamed_metrics,slug',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:255',
            'active' => 'boolean',
        ]);

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['name']);
        $validated['active'] = $request->has('active');

        GamedMetric::create($validated);

        return redirect()->route('lfl.admin.gamed-metrics')
            ->with('success', 'GamedMetric created successfully.');
    }

    /**
     * Display MetricLevels management page.
     *
     * @return \Illuminate\View\View
     */
    public function metricLevels(Request $request)
    {
        $query = MetricLevel::query()->with('gamedMetric');

        if ($request->has('metric_id')) {
            $query->where('gamed_metric_id', $request->metric_id);
        }

        $levels = $query->orderBy('gamed_metric_id')->orderBy('level')->paginate(20);
        $metrics = GamedMetric::active()->orderBy('name')->get();

        return view('lfl::admin.metric-levels', [
            'levels' => $levels,
            'metrics' => $metrics,
        ]);
    }

    /**
     * Show the form for editing a MetricLevel.
     *
     * @return \Illuminate\View\View
     */
    public function editMetricLevel(MetricLevel $metricLevel)
    {
        $metrics = GamedMetric::active()->orderBy('name')->get();
        $achievements = Achievement::active()->orderBy('name')->get();

        return view('lfl::admin.metric-levels.edit', [
            'level' => $metricLevel,
            'metrics' => $metrics,
            'achievements' => $achievements,
        ]);
    }

    /**
     * Store a new MetricLevel.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeMetricLevel(Request $request)
    {
        $validated = $request->validate([
            'gamed_metric_id' => 'required|exists:'.config('lfl.table_prefix', 'lfl_').'gamed_metrics,id',
            'level' => 'required|integer|min:1',
            'xp_threshold' => 'required|integer|min:0',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'achievement_ids' => 'nullable|array',
            'achievement_ids.*' => 'exists:'.config('lfl.table_prefix', 'lfl_').'achievements,id',
        ]);

        $level = MetricLevel::create($validated);

        // Attach achievements
        if ($request->has('achievement_ids')) {
            $level->achievements()->attach($request->achievement_ids);
        }

        return redirect()->route('lfl.admin.metric-levels')
            ->with('success', 'MetricLevel created successfully.');
    }

    /**
     * Update a MetricLevel.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateMetricLevel(Request $request, MetricLevel $metricLevel)
    {
        $validated = $request->validate([
            'gamed_metric_id' => 'required|exists:'.config('lfl.table_prefix', 'lfl_').'gamed_metrics,id',
            'level' => 'required|integer|min:1',
            'xp_threshold' => 'required|integer|min:0',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'achievement_ids' => 'nullable|array',
            'achievement_ids.*' => 'exists:'.config('lfl.table_prefix', 'lfl_').'achievements,id',
        ]);

        $metricLevel->update($validated);

        // Sync achievements
        $metricLevel->achievements()->sync($request->achievement_ids ?? []);

        return redirect()->route('lfl.admin.metric-levels')
            ->with('success', 'MetricLevel updated successfully.');
    }

    /**
     * Display MetricLevelGroups management page.
     *
     * @return \Illuminate\View\View
     */
    public function metricLevelGroups()
    {
        $groups = MetricLevelGroup::query()
            ->with(['metrics.gamedMetric', 'levels'])
            ->orderBy('name')
            ->paginate(20);

        return view('lfl::admin.metric-level-groups', [
            'groups' => $groups,
        ]);
    }

    /**
     * Show the form for editing a MetricLevelGroupLevel.
     *
     * @return \Illuminate\View\View
     */
    public function editMetricLevelGroupLevel(MetricLevelGroupLevel $metricLevelGroupLevel)
    {
        $achievements = Achievement::active()->orderBy('name')->get();

        return view('lfl::admin.metric-level-group-levels.edit', [
            'level' => $metricLevelGroupLevel,
            'achievements' => $achievements,
        ]);
    }

    /**
     * Update a MetricLevelGroupLevel.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateMetricLevelGroupLevel(Request $request, MetricLevelGroupLevel $metricLevelGroupLevel)
    {
        $validated = $request->validate([
            'level' => 'required|integer|min:1',
            'xp_threshold' => 'required|integer|min:0',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'achievement_ids' => 'nullable|array',
            'achievement_ids.*' => 'exists:'.config('lfl.table_prefix', 'lfl_').'achievements,id',
        ]);

        $metricLevelGroupLevel->update($validated);

        // Sync achievements
        $metricLevelGroupLevel->achievements()->sync($request->achievement_ids ?? []);

        return redirect()->route('lfl.admin.metric-level-groups')
            ->with('success', 'MetricLevelGroupLevel updated successfully.');
    }

    /**
     * Store a new MetricLevelGroup.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeMetricLevelGroup(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:'.config('lfl.table_prefix', 'lfl_').'metric_level_groups,slug',
            'description' => 'nullable|string',
        ]);

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['name']);

        MetricLevelGroup::create($validated);

        return redirect()->route('lfl.admin.metric-level-groups')
            ->with('success', 'MetricLevelGroup created successfully.');
    }
}
