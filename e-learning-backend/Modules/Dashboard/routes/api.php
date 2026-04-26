<?php

use Illuminate\Support\Facades\Route;
use Modules\Dashboard\Http\Controllers\DashboardController;

Route::middleware(['auth:admin'])->prefix('admin/dashboard')->group(function () {
    Route::get('stats', [DashboardController::class, 'getStats']);
});
