<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Migrations\ContentMigrationController;
use App\Http\Middleware\EnsureTenantRouteBinding;
use Illuminate\Support\Facades\Route;

Route::prefix('admin/migrations')
    ->name('admin.migrations.')
    ->middleware(['web', 'auth', 'verified', 'admin', EnsureTenantRouteBinding::class])
    ->group(function (): void {
        Route::get('/', [ContentMigrationController::class, 'index'])
            ->middleware('permission:documents.view')
            ->name('index');
        Route::post('/wordpress', [ContentMigrationController::class, 'store'])
            ->middleware(['permission:documents.create', 'throttle:5,1'])
            ->name('wordpress.store');
        Route::post('/{run}/resume', [ContentMigrationController::class, 'resume'])
            ->whereUuid('run')
            ->middleware(['permission:documents.create', 'throttle:10,1'])
            ->name('resume');
        Route::get('/export/documents', [ContentMigrationController::class, 'exportDocuments'])
            ->middleware(['permission:documents.view', 'throttle:5,1'])
            ->name('export.documents');
    });
