<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Content\ContentCollectionController;
use App\Http\Middleware\EnsureTenantRouteBinding;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->middleware(['web', 'auth', 'verified', 'admin', EnsureTenantRouteBinding::class])->group(function (): void {
    Route::get('/collections', [ContentCollectionController::class, 'index'])->middleware('permission:collections.view')->name('collections.index');
    Route::post('/collections', [ContentCollectionController::class, 'store'])->middleware('permission:collections.manage')->name('collections.store');
    Route::get('/collections/{collection}', [ContentCollectionController::class, 'show'])->middleware('permission:collections.view')->name('collections.show');
    Route::put('/collections/{collection}', [ContentCollectionController::class, 'update'])->middleware('permission:collections.manage')->name('collections.update');
    Route::delete('/collections/{collection}', [ContentCollectionController::class, 'destroy'])->middleware('permission:collections.manage')->name('collections.destroy');
    Route::post('/collections/{collection}/documents', [ContentCollectionController::class, 'attach'])->middleware('permission:collections.manage')->name('collections.documents.attach');
    Route::put('/collections/{collection}/documents/{document}', [ContentCollectionController::class, 'updateEntry'])->middleware('permission:collections.manage')->name('collections.documents.update');
    Route::delete('/collections/{collection}/documents/{document}', [ContentCollectionController::class, 'detach'])->middleware('permission:collections.manage')->name('collections.documents.detach');
});
