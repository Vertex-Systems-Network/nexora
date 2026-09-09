<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Ai\AiPlatformController;
use App\Http\Middleware\EnsureTenantRouteBinding;
use Illuminate\Support\Facades\Route;

Route::prefix('admin/ai')
    ->name('admin.ai.')
    ->middleware(['web', 'auth', 'verified', 'admin', EnsureTenantRouteBinding::class])
    ->group(function (): void {
        Route::get('/', [AiPlatformController::class, 'index'])
            ->middleware('permission:ai.view')
            ->name('index');
        Route::post('/connections', [AiPlatformController::class, 'store'])
            ->middleware('permission:ai.connections.manage')
            ->name('connections.store');
        Route::put('/connections/{connection}', [AiPlatformController::class, 'update'])
            ->middleware('permission:ai.connections.manage')
            ->name('connections.update');
        Route::post('/connections/{connection}/test', [AiPlatformController::class, 'test'])
            ->middleware(['permission:ai.connections.manage', 'throttle:12,1'])
            ->name('connections.test');
        Route::patch('/connections/{connection}/enabled', [AiPlatformController::class, 'toggle'])
            ->middleware('permission:ai.connections.manage')
            ->name('connections.toggle');
        Route::delete('/connections/{connection}', [AiPlatformController::class, 'destroy'])
            ->middleware('permission:ai.connections.manage')
            ->name('connections.destroy');
        Route::post('/connections/{connection}/generate', [AiPlatformController::class, 'generate'])
            ->middleware(['permission:ai.generate', 'throttle:30,1'])
            ->name('connections.generate');
    });
