<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Extensions\ExtensionController;
use App\Http\Middleware\EnsureTenantRouteBinding;
use Illuminate\Support\Facades\Route;

Route::prefix('admin/extensions/marketplace')
    ->name('admin.extensions.marketplace.')
    ->middleware(['web', 'auth', 'verified', 'admin', EnsureTenantRouteBinding::class])
    ->group(function (): void {
        Route::patch('/sources/{source}/status', [ExtensionController::class, 'sourceStatus'])
            ->middleware('permission:marketplace.manage')
            ->name('sources.status');
        Route::delete('/sources/{source}', [ExtensionController::class, 'deleteSource'])
            ->middleware('permission:marketplace.manage')
            ->name('sources.destroy');
        Route::post('/catalog/{item}/stage', [ExtensionController::class, 'stage'])
            ->middleware('throttle:8,1')
            ->name('catalog.stage');
    });
