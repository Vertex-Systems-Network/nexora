<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\DocumentController;
use Illuminate\Support\Facades\Route;

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
