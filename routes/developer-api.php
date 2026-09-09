<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Developer\ApiTokenController;
use App\Http\Middleware\EnsureTenantRouteBinding;
use Illuminate\Support\Facades\Route;

Route::prefix('admin/developer')
    ->name('admin.developer.')
    ->middleware(['web', 'auth', 'verified', 'admin', EnsureTenantRouteBinding::class])
    ->group(function (): void {
        Route::get('/api-tokens', [ApiTokenController::class, 'index'])
            ->middleware('permission:enterprise.identity.manage')
            ->name('api-tokens.index');

        Route::post('/api-tokens', [ApiTokenController::class, 'store'])
            ->middleware(['permission:enterprise.identity.manage', 'throttle:20,1'])
            ->name('api-tokens.store');

        Route::delete('/api-tokens/{token}', [ApiTokenController::class, 'destroy'])
            ->middleware(['permission:enterprise.identity.manage', 'throttle:30,1'])
            ->name('api-tokens.destroy');
    });
