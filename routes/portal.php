<?php

declare(strict_types=1);

use App\Http\Controllers\Portal\CustomerPortalController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'verified'])
    ->group(function (): void {
        Route::get('/account', CustomerPortalController::class)
            ->name('portal.dashboard');
    });
