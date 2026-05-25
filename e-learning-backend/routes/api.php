<?php

use App\Http\Controllers\Admin\FeatureFlagController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1/admin')
    ->middleware(['auth:admin'])
    ->group(function () {
        Route::get('feature-flags', [FeatureFlagController::class, 'index'])
            ->middleware('permission:feature_flags.view');
        Route::patch('feature-flags/{flag}', [FeatureFlagController::class, 'update'])
            ->middleware('permission:feature_flags.update');
    });
