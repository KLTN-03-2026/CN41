<?php

namespace Modules\Users\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Teachers\Models\Teachers;
use Modules\Users\Http\Requests\AssignRoleRequest;
use Modules\Users\Http\Requests\BulkActionUsersRequest;
use Modules\Users\Http\Requests\BulkAssignRoleRequest;
use Modules\Users\Http\Requests\BulkDeleteUsersRequest;
use Modules\Users\Http\Requests\BulkForceDeleteUsersRequest;
use Modules\Users\Http\Requests\BulkRestoreUsersRequest;
use Modules\Users\Http\Requests\RevokeRoleRequest;
use Modules\Users\Http\Requests\StoreUsersRequest;
use Modules\Users\Http\Requests\UpdateUsersRequest;
use Modules\Users\Http\Resources\UserResource;
use Modules\Users\Models\User;
use Modules\Users\Repositories\UsersRepositoryInterface;
use Spatie\Permission\Models\Role;

class UsersController extends Controller
{
    use ApiResponse;

    public function __construct(private UsersRepositoryInterface $repository) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);
        $filters = $request->only(['search', 'role', 'status']);
        $allowedRoles = null;

        if (! auth('admin')->user()->hasRole('super-admin')) {
            $allowedRoles = ['student', 'teacher'];

            if (! empty($filters['role']) && ! in_array($filters['role'], $allowedRoles)) {
                return $this->error('Bạn không có quyền xem tài khoản với role này.', 403);
            }
        }

        $data = $this->repository->paginateFiltered($filters, $perPage, false, $allowedRoles);
        $data->setCollection(UserResource::collection($data->getCollection())->collection);

        return $this->paginated($data);
    }

    public function store(StoreUsersRequest $request): JsonResponse
    {
        if ($request->filled('role') && $request->role === 'super-admin') {
            if (! auth('admin')->user()->hasRole('super-admin')) {
                return $this->error('Bạn không có quyền gán role super-admin.', 403);
            }
        }

        $user = DB::transaction(function () use ($request) {
            $user = $this->repository->create($request->validated());
            $user->forceFill(['email_verified_at' => now()])->save();

            if ($request->filled('role')) {
                $user->assignRole($request->role);
                if ($request->role === 'teacher') {
                    $this->createTeacherProfileIfNeeded($user);
                }
            }

            return $user;
        });

        $user->load('roles');

        return $this->success(new UserResource($user), 'User đã được tạo thành công.', 201);
    }

    public function show(int $id): JsonResponse
    {
        $user = $this->repository->findOrFail($id);
        $user->load('roles', 'permissions');

        if (! auth('admin')->user()->hasRole('super-admin')) {
            $allowedRoles = ['student', 'teacher'];
            $userRoleNames = $user->roles->pluck('name')->toArray();
            $hasSystemRole = count(array_diff($userRoleNames, $allowedRoles)) > 0;

            if ($hasSystemRole || empty($userRoleNames)) {
                return $this->error('Bạn không có quyền xem tài khoản này.', 403);
            }
        }

        return $this->success(new UserResource($user));
    }

    public function update(UpdateUsersRequest $request, int $id): JsonResponse
    {
        $targetUser = $this->repository->findOrFail($id);
        $currentAdmin = auth('admin')->user();

        if ($targetUser->hasRole('super-admin') && ! $currentAdmin->hasRole('super-admin')) {
            return $this->error('Bạn không có quyền chỉnh sửa tài khoản Super Admin.', 403);
        }

        if ($request->filled('role') && $request->role === 'super-admin') {
            if (! $currentAdmin->hasRole('super-admin')) {
                return $this->error('Bạn không có quyền gán role super-admin.', 403);
            }
        }

        $user = DB::transaction(function () use ($request, $id) {
            $user = $this->repository->update($id, $request->validated());
            if ($request->filled('role')) {
                $user->syncRoles([$request->role]);
                if ($request->role === 'teacher') {
                    $this->createTeacherProfileIfNeeded($user);
                }
            }

            return $user;
        });

        $user->load('roles');

        return $this->success(new UserResource($user), 'User đã được cập nhật thành công.');
    }

    public function destroy(int $id): JsonResponse
    {
        $targetUser = $this->repository->findOrFail($id);

        if ($targetUser->hasRole('super-admin')) {
            return $this->error('Không thể xóa tài khoản Super Admin.', 403);
        }

        if ($targetUser->id === auth('admin')->id()) {
            return $this->error('Bạn không thể xóa chính tài khoản của mình.', 403);
        }

        $this->repository->delete($id);

        return $this->success(null, 'User đã được xoá thành công.');
    }

    public function assignRole(AssignRoleRequest $request, int $id): JsonResponse
    {
        if ($request->role === 'super-admin' && ! auth('admin')->user()->hasRole('super-admin')) {
            return $this->error('Bạn không có quyền gán role super-admin.', 403);
        }

        $targetUser = $this->repository->findOrFail($id);

        if ($targetUser->hasRole('super-admin') && ! auth('admin')->user()->hasRole('super-admin')) {
            return $this->error('Bạn không có quyền thao tác trên tài khoản Super Admin.', 403);
        }

        $targetUser->assignRole($request->role);
        $targetUser->load('roles');

        return $this->success(new UserResource($targetUser), 'Gán role thành công.');
    }

    public function revokeRole(RevokeRoleRequest $request, int $id): JsonResponse
    {
        $targetUser = $this->repository->findOrFail($id);

        if ($targetUser->hasRole('super-admin') && ! auth('admin')->user()->hasRole('super-admin')) {
            return $this->error('Bạn không có quyền thao tác trên tài khoản Super Admin.', 403);
        }

        $targetUser->removeRole($request->role);
        $targetUser->load('roles');

        return $this->success(new UserResource($targetUser), 'Thu hồi role thành công.');
    }

    public function bulkDelete(BulkDeleteUsersRequest $request): JsonResponse
    {
        $ids = $request->ids;

        if (in_array(auth('admin')->id(), $ids)) {
            return $this->error('Không thể xóa chính tài khoản của mình.', 403);
        }

        if ($this->repository->hasSuperAdmin($ids)) {
            return $this->error('Không thể xóa tài khoản Super Admin.', 403);
        }

        $deleted = $this->repository->deleteMany($ids);

        return $this->success(
            ['deleted_count' => $deleted, 'deleted_ids' => $ids],
            "Đã xoá {$deleted} user thành công."
        );
    }

    public function bulkAction(BulkActionUsersRequest $request): JsonResponse
    {
        $affected = $this->repository->actionMany($request->ids, $request->action);

        return $this->success(
            ['affected_count' => $affected, 'affected_ids' => $request->ids],
            "Đã thực hiện '{$request->action}' cho {$affected} user thành công."
        );
    }

    public function trashed(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);
        $filters = $request->only(['search', 'role', 'status']);
        $allowedRoles = null;

        if (! auth('admin')->user()->hasRole('super-admin')) {
            $allowedRoles = ['student', 'teacher'];
        }

        $data = $this->repository->paginateFiltered($filters, $perPage, true, $allowedRoles);
        $data->setCollection(UserResource::collection($data->getCollection())->collection);

        return $this->paginated($data);
    }

    public function restore(int $id): JsonResponse
    {
        $this->repository->restore($id);

        return $this->success(null, 'User đã được khôi phục thành công.');
    }

    public function bulkRestore(BulkRestoreUsersRequest $request): JsonResponse
    {
        $restored = $this->repository->restoreMany($request->ids);

        return $this->success(
            ['restored_count' => $restored, 'restored_ids' => $request->ids],
            "Đã khôi phục {$restored} user thành công."
        );
    }

    public function forceDelete(int $id): JsonResponse
    {
        $this->repository->forceDeleteById($id);

        return $this->success(null, 'User đã bị xoá vĩnh viễn.');
    }

    public function bulkForceDelete(BulkForceDeleteUsersRequest $request): JsonResponse
    {
        $deleted = $this->repository->forceDeleteMany($request->ids);

        return $this->success(
            ['deleted_count' => $deleted, 'deleted_ids' => $request->ids],
            "Đã xoá vĩnh viễn {$deleted} user."
        );
    }

    public function bulkAssignRole(BulkAssignRoleRequest $request): JsonResponse
    {
        if ($request->role === 'super-admin' && ! auth('admin')->user()->hasRole('super-admin')) {
            return $this->error('Bạn không có quyền gán role super-admin.', 403);
        }

        $affected = $this->repository->assignRoleMany($request->ids, $request->role);

        return $this->success(
            ['affected_count' => $affected],
            "Đã gán role '{$request->role}' cho {$affected} user thành công."
        );
    }

    public function verifyEmail(int $id): JsonResponse
    {
        $user = $this->repository->findOrFail($id);

        if ($user->email_verified_at) {
            return $this->error('Tài khoản này đã được xác thực.', 422);
        }

        $user->forceFill(['email_verified_at' => now()])->save();

        $user->load('roles');

        return $this->success(new UserResource($user), 'Xác thực tài khoản thành công.');
    }

    public function getRoles(): JsonResponse
    {
        $roles = Role::where('guard_name', 'admin')->get(['id', 'name']);

        return $this->success($roles);
    }

    private function createTeacherProfileIfNeeded(User $user): void
    {
        if ($user->teacher()->exists()) {
            return;
        }

        $baseSlug = Str::slug($user->name);
        if (empty($baseSlug)) {
            $baseSlug = 'giang-vien-'.$user->id;
        }

        $slug = $baseSlug;
        $i = 1;
        while (Teachers::where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$i++;
        }

        Teachers::create([
            'user_id' => $user->id,
            'name' => $user->name,
            'slug' => $slug,
            'status' => 1,
        ]);
    }
}
