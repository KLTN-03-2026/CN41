<?php

use Illuminate\Support\Facades\Route;
use Modules\Users\Http\Controllers\ActivityLogController;
use Modules\Users\Http\Controllers\RolesController;
use Modules\Users\Http\Controllers\UsersController;

Route::middleware(['auth:admin'])->prefix('admin')->group(function () {
    // Bulk routes
    Route::get('users/trashed', [UsersController::class, 'trashed'])->middleware('permission:users.view');
    Route::post('users/bulk-restore', [UsersController::class, 'bulkRestore'])->middleware('permission:users.edit');
    Route::delete('users/bulk-delete', [UsersController::class, 'bulkDelete'])->middleware('permission:users.delete');
    Route::delete('users/bulk-force-delete', [UsersController::class, 'bulkForceDelete'])->middleware('permission:users.delete');
    Route::post('users/bulk-action', [UsersController::class, 'bulkAction'])->middleware('permission:users.edit');
    Route::get('users/roles', [UsersController::class, 'getRoles'])->middleware('permission:users.view');
    Route::post('users/bulk-assign-role', [UsersController::class, 'bulkAssignRole'])->middleware('permission:users.edit');

    // apiResource: index, show (view), store (create), update (edit), destroy (delete)
    // Tạm thời bảo vệ chung bằng users.view, sau đó chặn riêng tạo/sửa/xóa
    Route::apiResource('users', UsersController::class)->names('admin.users')
        ->middleware('permission:users.view|users.create|users.edit|users.delete');

    Route::post('users/{id}/assign-role', [UsersController::class, 'assignRole'])->middleware('permission:users.edit');
    Route::post('users/{id}/revoke-role', [UsersController::class, 'revokeRole'])->middleware('permission:users.edit');
    Route::post('users/{id}/restore', [UsersController::class, 'restore'])->middleware('permission:users.edit');
    Route::delete('users/{id}/force-delete', [UsersController::class, 'forceDelete'])->middleware('permission:users.delete');

    // Roles & Permissions (Dùng chung quyền users.view hoặc riêng roles.view)
    Route::get('permissions', [RolesController::class, 'getPermissions'])->middleware('permission:users.view');
    Route::apiResource('roles', RolesController::class)->names('admin.roles')
        ->middleware('permission:users.view');

    // System Logs
    Route::prefix('system')->group(function () {
        Route::get('logs', [ActivityLogController::class, 'index'])
            ->middleware('permission:system.logs.view');
        Route::delete('logs/clear', [ActivityLogController::class, 'clear'])
            ->middleware('permission:system.logs.delete');
    });
});
