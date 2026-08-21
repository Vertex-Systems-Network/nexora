<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Commerce\BillingController;
use App\Http\Middleware\EnsureTenantRouteBinding;
use Illuminate\Support\Facades\Route;

Route::prefix('admin/commerce')
    ->name('admin.commerce.')
    ->middleware(['web', 'auth', 'verified', 'admin', EnsureTenantRouteBinding::class])
    ->group(function (): void {
        Route::post('/billing/invoices/{invoice}/payments', [BillingController::class, 'collect'])
            ->middleware(['permission:commerce.billing.manage', 'throttle:20,1'])
            ->name('billing.payments.store');
        Route::post('/billing/transactions/{payment}/refunds', [BillingController::class, 'refund'])
            ->middleware(['permission:commerce.billing.manage', 'throttle:20,1'])
            ->name('billing.refunds.store');
    });
