<?php

namespace Modules\Users\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Users\Http\Requests\StoreRoleRequest;
use Modules\Users\Http\Requests\UpdateRoleRequest;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of the roles.
     */
    public function index(Request $request): JsonResponse
    {
        $roles = Role::with('permissions')
            ->where('guard_name', 'admin')
            ->withCount('users')
            ->get();

        return $this->success($roles);
    }

    /**
     * Get all available permissions in the system.
     */
    public function getPermissions(): JsonResponse
    {
        $permissions = Permission::where('guard_name', 'admin')->get();

        return $this->success($permissions);
    }

    /**
     * Store a newly created role in storage.
     */
    public function store(StoreRoleRequest $request): JsonResponse
    {
        $role = Role::create([
            'name' => $request->name,
            'guard_name' => 'admin',
        ]);

        if ($request->has('permissions') && is_array($request->permissions)) {
            $role->syncPermissions($request->permissions);
        }

        $role->load('permissions');

        return $this->success($role, 'Tạo vai trò thành công', 201);
    }

    /**
     * Display the specified role.
     */
    public function show($id): JsonResponse
    {
        $role = Role::with('permissions')->findOrFail($id);

        return $this->success($role);
    }

    /**
     * Update the specified role in storage.
     */
    public function update(UpdateRoleRequest $request, $id): JsonResponse
    {
        $role = Role::findOrFail($id);

        if ($role->name === 'super-admin') {
            return $this->error('Không thể chỉnh sửa vai trò Super Admin mặc định.', 403);
        }

        if ($request->has('name')) {
            $role->update(['name' => $request->name]);
        }

        if ($request->has('permissions') && is_array($request->permissions)) {
            $permissions = $request->permissions;
            // Prevent privilege escalation: only super-admin can grant users.delete
            if (! auth('admin')->user()?->hasPermissionTo('users.delete', 'admin')) {
                $permissions = array_values(array_filter($permissions, fn ($p) => $p !== 'users.delete'));
            }
            $role->syncPermissions($permissions);
        }

        $role->load('permissions');

        return $this->success($role, 'Cập nhật vai trò thành công');
    }

    /**
     * Remove the specified role from storage.
     */
    public function destroy($id): JsonResponse
    {
        $role = Role::findOrFail($id);

        if ($role->name === 'super-admin') {
            return $this->error('Không thể xóa vai trò Super Admin mặc định.', 403);
        }

        if ($role->users()->count() > 0) {
            return $this->error('Không thể xóa vai trò đang được gán cho người dùng.', 400);
        }

        $role->delete();

        return $this->success(null, 'Xóa vai trò thành công');
    }
}
