<?php

namespace Modules\Users\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Users\Http\Requests\BulkActionUsersRequest;
use Modules\Users\Http\Requests\BulkAssignRoleRequest;
use Modules\Users\Http\Requests\BulkDeleteUsersRequest;
use Modules\Users\Http\Requests\BulkForceDeleteUsersRequest;
use Modules\Users\Http\Requests\BulkRestoreUsersRequest;
use Modules\Users\Http\Requests\StoreUsersRequest;
use Modules\Users\Http\Requests\UpdateUsersRequest;
use Modules\Users\Repositories\UsersRepositoryInterface;
use Spatie\Permission\Models\Role;

class UsersController extends Controller
{
    use ApiResponse;

    protected UsersRepositoryInterface $repository;

    public function __construct(UsersRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Danh sách Users (có phân trang).
     * Nếu không phải super-admin thì chỉ được xem tài khoản student và teacher.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);
        $filters = $request->only(['search', 'role', 'status']);

        // Giới hạn role được phép xem nếu không phải super-admin
        $allowedRoles = null;
        if (! auth('admin')->user()->hasRole('super-admin')) {
            $allowedRoles = ['student', 'teacher'];

            // Nếu user cố lọc sang role khác (admin, super-admin...) thì chặn
            if (! empty($filters['role']) && ! in_array($filters['role'], $allowedRoles)) {
                return $this->error('Bạn không có quyền xem tài khoản với role này.', 403);
            }
        }

        $data = $this->repository->paginateFiltered($filters, $perPage, false, $allowedRoles);

        return $this->paginated($data);
    }

    /**
     * Tạo mới User + gán role.
     */
    public function store(StoreUsersRequest $request): JsonResponse
    {
        // Chặn không cho tạo user với role super-admin nếu người tạo không phải super-admin
        if ($request->filled('role') && $request->role === 'super-admin') {
            if (! auth('admin')->user()->hasRole('super-admin')) {
                return $this->error('Bạn không có quyền gán role super-admin.', 403);
            }
        }

        $user = $this->repository->create($request->validated());

        if ($request->filled('role')) {
            $user->assignRole($request->role);
        }

        $user->load('roles');

        return $this->success($user, 'User đã được tạo thành công.', 201);
    }

    /**
     * Chi tiết User.
     * Nếu không phải super-admin thì không được xem tài khoản có role hệ thống.
     */
    public function show(int $id): JsonResponse
    {
        $user = $this->repository->findOrFail($id);
        $user->load('roles', 'permissions');

        // Kiểm tra nếu người xem không phải super-admin
        if (! auth('admin')->user()->hasRole('super-admin')) {
            $allowedRoles = ['student', 'teacher'];
            $userRoleNames = $user->roles->pluck('name')->toArray();
            $hasSystemRole = count(array_diff($userRoleNames, $allowedRoles)) > 0;

            if ($hasSystemRole || empty($userRoleNames)) {
                return $this->error('Bạn không có quyền xem tài khoản này.', 403);
            }
        }

        return $this->success($user);
    }

    /**
     * Cập nhật User + đổi role.
     */
    public function update(UpdateUsersRequest $request, int $id): JsonResponse
    {
        $targetUser = $this->repository->findOrFail($id);
        $currentAdmin = auth('admin')->user();

        // Chặn: không phải super-admin thì không được sửa thông tin của super-admin
        if ($targetUser->hasRole('super-admin') && ! $currentAdmin->hasRole('super-admin')) {
            return $this->error('Bạn không có quyền chỉnh sửa tài khoản Super Admin.', 403);
        }

        // Chặn: không cho gán role super-admin nếu người thao tác không phải super-admin
        if ($request->filled('role') && $request->role === 'super-admin') {
            if (! $currentAdmin->hasRole('super-admin')) {
                return $this->error('Bạn không có quyền gán role super-admin.', 403);
            }
        }

        $user = $this->repository->update($id, $request->validated());

        if ($request->filled('role')) {
            $user->syncRoles([$request->role]);
        }

        $user->load('roles');

        return $this->success($user, 'User đã được cập nhật thành công.');
    }

    /**
     * Xoá User (soft delete).
     */
    public function destroy(int $id): JsonResponse
    {
        $targetUser = $this->repository->findOrFail($id);

        // Chặn: không cho xóa tài khoản super-admin
        if ($targetUser->hasRole('super-admin')) {
            return $this->error('Không thể xóa tài khoản Super Admin.', 403);
        }

        // Chặn: không cho tự xóa chính mình
        if ($targetUser->id === auth('admin')->id()) {
            return $this->error('Bạn không thể xóa chính tài khoản của mình.', 403);
        }

        $this->repository->delete($id);

        return $this->success(null, 'User đã được xoá thành công.');
    }

    /**
     * Gán role cho User.
     */
    public function assignRole(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'role' => 'required|string|exists:roles,name',
        ]);

        // Chặn: không cho gán role super-admin nếu người thao tác không phải super-admin
        if ($request->role === 'super-admin' && ! auth('admin')->user()->hasRole('super-admin')) {
            return $this->error('Bạn không có quyền gán role super-admin.', 403);
        }

        $targetUser = $this->repository->findOrFail($id);

        // Chặn: không cho thao tác trên tài khoản super-admin
        if ($targetUser->hasRole('super-admin') && ! auth('admin')->user()->hasRole('super-admin')) {
            return $this->error('Bạn không có quyền thao tác trên tài khoản Super Admin.', 403);
        }

        $targetUser->assignRole($request->role);
        $targetUser->load('roles');

        return $this->success($targetUser, 'Gán role thành công.');
    }

    /**
     * Thu hồi role của User.
     */
    public function revokeRole(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'role' => 'required|string|exists:roles,name',
        ]);

        $targetUser = $this->repository->findOrFail($id);

        // Chặn: không cho thao tác trên tài khoản super-admin
        if ($targetUser->hasRole('super-admin') && ! auth('admin')->user()->hasRole('super-admin')) {
            return $this->error('Bạn không có quyền thao tác trên tài khoản Super Admin.', 403);
        }

        $targetUser->removeRole($request->role);
        $targetUser->load('roles');

        return $this->success($targetUser, 'Thu hồi role thành công.');
    }

    /**
     * Xoá nhiều Users cùng lúc.
     */
    public function bulkDelete(BulkDeleteUsersRequest $request): JsonResponse
    {
        $deleted = $this->repository->deleteMany($request->ids);

        return $this->success(
            ['deleted_count' => $deleted, 'deleted_ids' => $request->ids],
            "Đã xoá {$deleted} user thành công."
        );
    }

    /**
     * Thực hiện action hàng loạt (activate / deactivate).
     */
    public function bulkAction(BulkActionUsersRequest $request): JsonResponse
    {
        $affected = $this->repository->actionMany($request->ids, $request->action);

        return $this->success(
            ['affected_count' => $affected, 'affected_ids' => $request->ids],
            "Đã thực hiện '{$request->action}' cho {$affected} user thành công."
        );
    }

    /**
     * Danh sách Users đã bị soft-delete (thùng rác).
     * Nếu không phải super-admin thì chỉ thấy student và teacher.
     */
    public function trashed(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);
        $filters = $request->only(['search', 'role', 'status']);

        // Giới hạn role được phép xem nếu không phải super-admin
        $allowedRoles = null;
        if (! auth('admin')->user()->hasRole('super-admin')) {
            $allowedRoles = ['student', 'teacher'];
        }

        $data = $this->repository->paginateFiltered($filters, $perPage, true, $allowedRoles);

        return $this->paginated($data);
    }

    /**
     * Khôi phục một User đã soft-delete.
     */
    public function restore(int $id): JsonResponse
    {
        $this->repository->restore($id);

        return $this->success(null, 'User đã được khôi phục thành công.');
    }

    /**
     * Khôi phục nhiều Users đã soft-delete.
     */
    public function bulkRestore(BulkRestoreUsersRequest $request): JsonResponse
    {
        $restored = $this->repository->restoreMany($request->ids);

        return $this->success(
            ['restored_count' => $restored, 'restored_ids' => $request->ids],
            "Đã khôi phục {$restored} user thành công."
        );
    }

    /**
     * Xoá vĩnh viễn một User (bao gồm cả đã soft-delete).
     */
    public function forceDelete(int $id): JsonResponse
    {
        $this->repository->forceDeleteById($id);

        return $this->success(null, 'User đã bị xoá vĩnh viễn.');
    }

    /**
     * Xoá vĩnh viễn nhiều Users cùng lúc (bao gồm cả đã soft-delete).
     */
    public function bulkForceDelete(BulkForceDeleteUsersRequest $request): JsonResponse
    {
        $deleted = $this->repository->forceDeleteMany($request->ids);

        return $this->success(
            ['deleted_count' => $deleted, 'deleted_ids' => $request->ids],
            "Đã xoá vĩnh viễn {$deleted} user."
        );
    }

    /**
     * Gán role cho nhiều Users cùng lúc.
     */
    public function bulkAssignRole(BulkAssignRoleRequest $request): JsonResponse
    {
        // Chặn: không cho gán role super-admin nếu người thao tác không phải super-admin
        if ($request->role === 'super-admin' && ! auth('admin')->user()->hasRole('super-admin')) {
            return $this->error('Bạn không có quyền gán role super-admin.', 403);
        }

        $affected = $this->repository->assignRoleMany($request->ids, $request->role);

        return $this->success(
            ['affected_count' => $affected],
            "Đã gán role '{$request->role}' cho {$affected} user thành công."
        );
    }

    /**
     * Lấy danh sách tất cả các role hiện có.
     */
    public function getRoles(): JsonResponse
    {
        // Sử dụng Spatie Role model
        $roles = Role::all();

        return $this->success($roles);
    }
}
