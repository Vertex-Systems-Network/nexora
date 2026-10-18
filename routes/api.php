<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AuthTokenController;
use App\Http\Controllers\Api\V1\DocumentController;
use App\Http\Controllers\Api\V1\MeController;
use Illuminate\Support\Facades\Route;

// MOBILE-AUTH-API-100: unauthenticated credential exchange. Brute-force limits are enforced in the controller.
Route::post('v1/auth/token', [AuthTokenController::class, 'store'])->name('api.v1.auth.token');

Route::prefix('v1')
    ->name('api.v1.')
    ->middleware(['api.token'])
    ->group(function (): void {
        Route::get('/me', [MeController::class, 'show'])
            ->middleware('api.ability:profile.read')
            ->name('me');

        Route::post('/auth/logout', [AuthTokenController::class, 'destroy'])->name('auth.logout');
    });

Route::prefix('v1')
    ->name('api.v1.')
    ->middleware(['api.token'])
    ->group(function (): void {
        Route::get('/documents', [DocumentController::class, 'index'])
            ->middleware('api.ability:documents.read')
            ->name('documents.index');

        Route::get('/documents/{document}', [DocumentController::class, 'show'])
            ->whereNumber('document')
            ->middleware('api.ability:documents.read')
            ->name('documents.show');
    });
