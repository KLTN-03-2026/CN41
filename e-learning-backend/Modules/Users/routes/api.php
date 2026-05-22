<?php

use Illuminate\Support\Facades\Route;
use Modules\Users\Http\Controllers\ActivityLogController;
use Modules\Users\Http\Controllers\RolesController;
use Modules\Users\Http\Controllers\UsersController;

Route::middleware(['auth:admin'])->prefix('admin')->group(function () {
    // Users — static/bulk routes BEFORE parameterized routes
    Route::get('users/trashed', [UsersController::class, 'trashed'])->middleware('permission:admin_users.view');
    Route::post('users/bulk-restore', [UsersController::class, 'bulkRestore'])->middleware('permission:admin_users.edit');
    Route::delete('users/bulk-delete', [UsersController::class, 'bulkDelete'])->middleware('permission:admin_users.delete');
    Route::delete('users/bulk-force-delete', [UsersController::class, 'bulkForceDelete'])->middleware('permission:admin_users.delete');
    Route::post('users/bulk-action', [UsersController::class, 'bulkAction'])->middleware('permission:admin_users.edit');
    Route::get('users/roles', [UsersController::class, 'getRoles'])->middleware('permission:admin_users.view');
    Route::post('users/bulk-assign-role', [UsersController::class, 'bulkAssignRole'])->middleware('permission:admin_users.edit');

    Route::get('users', [UsersController::class, 'index'])->middleware('permission:admin_users.view');
    Route::get('users/{id}', [UsersController::class, 'show'])->middleware('permission:admin_users.view');
    Route::post('users', [UsersController::class, 'store'])->middleware('permission:admin_users.create');
    Route::patch('users/{id}', [UsersController::class, 'update'])->middleware('permission:admin_users.edit');
    Route::delete('users/{id}', [UsersController::class, 'destroy'])->middleware('permission:admin_users.delete');

    Route::post('users/{id}/assign-role', [UsersController::class, 'assignRole'])->middleware('permission:admin_users.edit');
    Route::post('users/{id}/revoke-role', [UsersController::class, 'revokeRole'])->middleware('permission:admin_users.edit');
    Route::post('users/{id}/restore', [UsersController::class, 'restore'])->middleware('permission:admin_users.edit');
    Route::patch('users/{id}/verify-email', [UsersController::class, 'verifyEmail'])->middleware('permission:admin_users.edit');
    Route::delete('users/{id}/force-delete', [UsersController::class, 'forceDelete'])->middleware('permission:admin_users.delete');

    // Permissions list
    Route::get('permissions', [RolesController::class, 'getPermissions'])->middleware('permission:roles.view');

    // Roles — each verb uses its own roles.* permission
    Route::get('roles', [RolesController::class, 'index'])->middleware('permission:roles.view');
    Route::get('roles/{role}', [RolesController::class, 'show'])->middleware('permission:roles.view');
    Route::post('roles', [RolesController::class, 'store'])->middleware('permission:roles.create');
    Route::patch('roles/{role}', [RolesController::class, 'update'])->middleware('permission:roles.edit');
    Route::delete('roles/{role}', [RolesController::class, 'destroy'])->middleware('permission:roles.delete');

    // System Logs
    Route::prefix('system')->group(function () {
        Route::get('logs', [ActivityLogController::class, 'index'])
            ->middleware('permission:system.logs.view');
        Route::delete('logs/clear', [ActivityLogController::class, 'clear'])
            ->middleware('permission:system.logs.delete');
    });
});
