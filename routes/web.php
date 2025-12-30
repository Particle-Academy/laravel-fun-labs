<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/**
 * Laravel Fun Lab Web Routes
 *
 * These routes provide the UI layer for LFL operations.
 * They are only loaded if config('lfl.ui.enabled') is true.
 */
Route::prefix(config('lfl.ui.prefix', 'lfl'))
    ->middleware(config('lfl.ui.middleware', ['web']))
    ->name('lfl.')
    ->group(function () {
        // Leaderboard routes
        Route::get('/leaderboards/{type}', [\LaravelFunLab\Http\Controllers\Web\LeaderboardController::class, 'index'])
            ->name('leaderboards.index');

        // Profile routes
        Route::get('/profiles/{type}/{id}', [\LaravelFunLab\Http\Controllers\Web\ProfileController::class, 'show'])
            ->name('profiles.show');

        // Admin dashboard routes
        Route::prefix('admin')->name('admin.')->group(function () {
            Route::get('/', [\LaravelFunLab\Http\Controllers\Web\AdminController::class, 'index'])
                ->name('index');
            Route::get('/achievements', [\LaravelFunLab\Http\Controllers\Web\AdminController::class, 'achievements'])
                ->name('achievements');
            Route::get('/achievements/create', [\LaravelFunLab\Http\Controllers\Web\AdminController::class, 'createAchievement'])
                ->name('achievements.create');
            Route::post('/achievements', [\LaravelFunLab\Http\Controllers\Web\AdminController::class, 'storeAchievement'])
                ->name('achievements.store');
            Route::get('/achievements/{achievement}/edit', [\LaravelFunLab\Http\Controllers\Web\AdminController::class, 'editAchievement'])
                ->name('achievements.edit');
            Route::put('/achievements/{achievement}', [\LaravelFunLab\Http\Controllers\Web\AdminController::class, 'updateAchievement'])
                ->name('achievements.update');
            Route::get('/prizes', [\LaravelFunLab\Http\Controllers\Web\AdminController::class, 'prizes'])
                ->name('prizes');
            Route::get('/prizes/create', [\LaravelFunLab\Http\Controllers\Web\AdminController::class, 'createPrize'])
                ->name('prizes.create');
            Route::post('/prizes', [\LaravelFunLab\Http\Controllers\Web\AdminController::class, 'storePrize'])
                ->name('prizes.store');
            Route::get('/prizes/{prize}/edit', [\LaravelFunLab\Http\Controllers\Web\AdminController::class, 'editPrize'])
                ->name('prizes.edit');
            Route::put('/prizes/{prize}', [\LaravelFunLab\Http\Controllers\Web\AdminController::class, 'updatePrize'])
                ->name('prizes.update');
            Route::get('/analytics', [\LaravelFunLab\Http\Controllers\Web\AdminController::class, 'analytics'])
                ->name('analytics');

            // GamedMetrics management
            Route::get('/gamed-metrics', [\LaravelFunLab\Http\Controllers\Web\AdminController::class, 'gamedMetrics'])
                ->name('gamed-metrics');
            Route::post('/gamed-metrics', [\LaravelFunLab\Http\Controllers\Web\AdminController::class, 'storeGamedMetric'])
                ->name('gamed-metrics.store');

            // MetricLevels management
            Route::get('/metric-levels', [\LaravelFunLab\Http\Controllers\Web\AdminController::class, 'metricLevels'])
                ->name('metric-levels');
            Route::post('/metric-levels', [\LaravelFunLab\Http\Controllers\Web\AdminController::class, 'storeMetricLevel'])
                ->name('metric-levels.store');
            Route::get('/metric-levels/{metricLevel}/edit', [\LaravelFunLab\Http\Controllers\Web\AdminController::class, 'editMetricLevel'])
                ->name('metric-levels.edit');
            Route::put('/metric-levels/{metricLevel}', [\LaravelFunLab\Http\Controllers\Web\AdminController::class, 'updateMetricLevel'])
                ->name('metric-levels.update');

            // MetricLevelGroups management
            Route::get('/metric-level-groups', [\LaravelFunLab\Http\Controllers\Web\AdminController::class, 'metricLevelGroups'])
                ->name('metric-level-groups');
            Route::post('/metric-level-groups', [\LaravelFunLab\Http\Controllers\Web\AdminController::class, 'storeMetricLevelGroup'])
                ->name('metric-level-groups.store');
            Route::get('/metric-level-group-levels/{metricLevelGroupLevel}/edit', [\LaravelFunLab\Http\Controllers\Web\AdminController::class, 'editMetricLevelGroupLevel'])
                ->name('metric-level-group-levels.edit');
            Route::put('/metric-level-group-levels/{metricLevelGroupLevel}', [\LaravelFunLab\Http\Controllers\Web\AdminController::class, 'updateMetricLevelGroupLevel'])
                ->name('metric-level-group-levels.update');
        });
    });
