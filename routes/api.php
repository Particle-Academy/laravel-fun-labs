<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/**
 * Laravel Fun Lab API Routes
 *
 * These routes provide the REST API surface for LFL operations.
 * They are only loaded if config('lfl.api.enabled') is true.
 */
$middleware = config('lfl.api.middleware', ['api']);

// Add authentication middleware if configured (only if not null)
$authMiddleware = config('lfl.api.auth.middleware');
if ($authMiddleware !== null && $authMiddleware !== '') {
    $middleware[] = $authMiddleware;
}

Route::prefix(config('lfl.api.prefix', 'api/lfl'))
    ->middleware($middleware)
    ->group(function () {
        // Profile routes
        Route::get('/profiles/{type}/{id}', [\LaravelFunLab\Http\Controllers\ProfileController::class, 'show']);

        // Leaderboard routes
        Route::get('/leaderboards/{type}', [\LaravelFunLab\Http\Controllers\LeaderboardController::class, 'index']);

        // Achievement routes
        Route::get('/achievements', [\LaravelFunLab\Http\Controllers\AchievementController::class, 'index']);

        // Award routes
        Route::get('/awards/{type}/{id}', [\LaravelFunLab\Http\Controllers\AwardsController::class, 'index']);
    });
