<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Forms\FormController as AdminFormController;
use App\Http\Controllers\Public\FormController as PublicFormController;
use App\Http\Middleware\EnsureTenantRouteBinding;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function (): void {
    Route::get('/forms/{form}', [PublicFormController::class, 'show'])
        ->name('forms.public.show');
    Route::post('/forms/{form}', [PublicFormController::class, 'submit'])
        ->middleware('throttle:10,1')
        ->name('forms.public.submit');
});

Route::prefix('admin')
    ->middleware(['web', 'auth', 'verified', 'admin', EnsureTenantRouteBinding::class])
    ->group(function (): void {
        Route::get('/forms', [AdminFormController::class, 'index'])
            ->middleware('permission:forms.view')
            ->name('forms.admin.index');
        Route::get('/forms/create', [AdminFormController::class, 'create'])
            ->middleware('permission:forms.manage')
            ->name('forms.admin.create');
        Route::post('/forms', [AdminFormController::class, 'store'])
            ->middleware('permission:forms.manage')
            ->name('forms.admin.store');
        Route::get('/forms/{form}/edit', [AdminFormController::class, 'edit'])
            ->middleware('permission:forms.manage')
            ->name('forms.admin.edit');
        Route::put('/forms/{form}', [AdminFormController::class, 'update'])
            ->middleware('permission:forms.manage')
            ->name('forms.admin.update');
        Route::patch('/forms/{form}/status', [AdminFormController::class, 'status'])
            ->middleware('permission:forms.manage')
            ->name('forms.admin.status');
        Route::get('/forms/{form}/submissions', [AdminFormController::class, 'submissions'])
            ->middleware('permission:forms.submissions.view')
            ->name('forms.admin.submissions');
    });
