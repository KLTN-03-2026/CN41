# Users Module Fix Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Fix 11 issues found in code review: missing UserResource, wrong route permissions, unsafe bulkDelete, missing DB::transaction, inline validation in controller, wrong auth guard, missing return types, missing failedValidation overrides, missing exists validation, putJson in tests, dead code.

**Architecture:** Follow existing module conventions — `StudentResource`/`StudentController` pattern is the reference for all changes. Resource wraps all User model output; `$data->setCollection(...)` pattern for paginated responses.

**Tech Stack:** Laravel 12, Nwidart Modules, Spatie Permission, PHPUnit

---

## File Map

| Action | File |
|--------|------|
| **Create** | `Modules/Users/app/Http/Resources/UserResource.php` |
| **Create** | `Modules/Users/app/Http/Requests/AssignRoleRequest.php` |
| **Create** | `Modules/Users/app/Http/Requests/RevokeRoleRequest.php` |
| **Create** | `tests/Feature/Admin/UserTest.php` |
| **Modify** | `Modules/Users/app/Http/Controllers/UsersController.php` |
| **Modify** | `Modules/Users/app/Http/Controllers/ActivityLogController.php` |
| **Modify** | `Modules/Users/app/Repositories/UsersRepositoryInterface.php` |
| **Modify** | `Modules/Users/app/Repositories/UsersRepository.php` |
| **Modify** | `Modules/Users/routes/api.php` |
| **Modify** | `Modules/Users/app/Http/Requests/StoreRoleRequest.php` |
| **Modify** | `Modules/Users/app/Http/Requests/UpdateRoleRequest.php` |
| **Modify** | `Modules/Users/app/Http/Requests/BulkRestoreUsersRequest.php` |
| **Modify** | `Modules/Users/app/Http/Requests/BulkForceDeleteUsersRequest.php` |
| **Modify** | `Modules/Users/app/Providers/UsersServiceProvider.php` |
| **Modify** | `Modules/Users/tests/Feature/RolesTest.php` |
| **Delete** | `Modules/Users/app/Helpers/UsersHelper.php` |

---

## Task 1: Create UserResource

**Files:**
- Create: `Modules/Users/app/Http/Resources/UserResource.php`

- [ ] **Step 1: Write the failing test** (in `tests/Feature/Admin/UserTest.php`)

```php
<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Users\Database\Seeders\RolePermissionSeeder;
use Modules\Users\Models\User;
use Tests\TestCase;
use Tests\Traits\HasAdminUser;

class UserTest extends TestCase
{
    use HasAdminUser, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->setupAdmin();
    }

    public function test_index_returns_paginated_users_through_resource(): void
    {
        User::forceCreate([
            'name'     => 'Alice',
            'email'    => 'alice@test.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->getJson('/api/v1/admin/users');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'email', 'status', 'roles'],
                ],
                'pagination',
            ]);

        // Raw model must NOT leak password
        $this->assertArrayNotHasKey('password', $response->json('data.0'));
        $this->assertArrayNotHasKey('remember_token', $response->json('data.0'));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
wsl.exe -d Ubuntu -- bash -c "php artisan test tests/Feature/Admin/UserTest.php --filter=test_index_returns_paginated_users_through_resource 2>&1" | cat
```

Expected: FAIL (test file doesn't exist yet, or structure assertion fails because raw model returns `password`).

- [ ] **Step 3: Create `UserResource`**

```php
<?php

namespace Modules\Users\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'email'      => $this->email,
            'avatar'     => $this->avatar,
            'status'     => $this->status,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'deleted_at' => $this->deleted_at?->toISOString(),
            'roles'       => $this->whenLoaded('roles', fn () => $this->roles->pluck('name')),
            'permissions' => $this->whenLoaded('permissions', fn () => $this->permissions->pluck('name')),
        ];
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

```bash
wsl.exe -d Ubuntu -- bash -c "php artisan test tests/Feature/Admin/UserTest.php --filter=test_index_returns_paginated_users_through_resource 2>&1" | cat
```

Expected: PASS (fails because controller still returns raw model — that is fixed in Task 2; if test passes now, the structure check works).

---

## Task 2: Apply UserResource across all UsersController endpoints

**Files:**
- Modify: `Modules/Users/app/Http/Controllers/UsersController.php`

- [ ] **Step 1: Rewrite `UsersController` in full**

Replace the entire file content with:

```php
<?php

namespace Modules\Users\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
use Modules\Users\Repositories\UsersRepositoryInterface;
use Spatie\Permission\Models\Role;

class UsersController extends Controller
{
    use ApiResponse;

    public function __construct(private UsersRepositoryInterface $repository) {}

    public function index(Request $request): JsonResponse
    {
        $perPage  = (int) $request->query('per_page', 15);
        $filters  = $request->only(['search', 'role', 'status']);
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
            if ($request->filled('role')) {
                $user->assignRole($request->role);
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
            $allowedRoles    = ['student', 'teacher'];
            $userRoleNames   = $user->roles->pluck('name')->toArray();
            $hasSystemRole   = count(array_diff($userRoleNames, $allowedRoles)) > 0;

            if ($hasSystemRole || empty($userRoleNames)) {
                return $this->error('Bạn không có quyền xem tài khoản này.', 403);
            }
        }

        return $this->success(new UserResource($user));
    }

    public function update(UpdateUsersRequest $request, int $id): JsonResponse
    {
        $targetUser   = $this->repository->findOrFail($id);
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
        $currentAdminId = auth('admin')->id();
        $ids = $request->ids;

        if (in_array($currentAdminId, $ids)) {
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
        $perPage      = (int) $request->query('per_page', 15);
        $filters      = $request->only(['search', 'role', 'status']);
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

    public function getRoles(): JsonResponse
    {
        $roles = Role::where('guard_name', 'admin')->get(['id', 'name']);

        return $this->success($roles);
    }
}
```

- [ ] **Step 2: Run the test suite to ensure no regressions**

```bash
wsl.exe -d Ubuntu -- bash -c "php artisan test tests/Feature/Admin/UserTest.php 2>&1" | cat
```

Expected: test fails because `hasSuperAdmin` doesn't exist yet — that's fixed in Task 3.

---

## Task 3: Add `hasSuperAdmin()` to repository

**Files:**
- Modify: `Modules/Users/app/Repositories/UsersRepositoryInterface.php`
- Modify: `Modules/Users/app/Repositories/UsersRepository.php`

- [ ] **Step 1: Add method to interface**

In `UsersRepositoryInterface.php`, add after `assignRoleMany`:

```php
/**
 * Returns true if any user in $ids has the super-admin role.
 *
 * @param  array<int>  $ids
 */
public function hasSuperAdmin(array $ids): bool;
```

Full file after change:

```php
<?php

namespace Modules\Users\Repositories;

use App\Repositories\RepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface UsersRepositoryInterface extends RepositoryInterface
{
    /**
     * @param  array<int>  $ids
     * @return int Số lượng user được cập nhật
     */
    public function assignRoleMany(array $ids, string $role): int;

    /**
     * @param  array|null  $allowedRoles
     */
    public function paginateFiltered(array $filters, int $perPage = 15, bool $trashed = false, ?array $allowedRoles = null): LengthAwarePaginator;

    /**
     * Returns true if any user in $ids has the super-admin role.
     *
     * @param  array<int>  $ids
     */
    public function hasSuperAdmin(array $ids): bool;
}
```

- [ ] **Step 2: Implement in `UsersRepository`**

Add the following method at the end of the class (before the closing `}`):

```php
public function hasSuperAdmin(array $ids): bool
{
    return $this->model->whereIn('id', $ids)
        ->whereHas('roles', fn ($q) => $q->where('name', 'super-admin'))
        ->exists();
}
```

- [ ] **Step 3: Write tests for `bulkDelete` guards**

Add to `tests/Feature/Admin/UserTest.php`:

```php
public function test_bulk_delete_blocks_self_deletion(): void
{
    $self = auth('admin')->user();

    $response = $this->deleteJson('/api/v1/admin/users/bulk-delete', [
        'ids' => [$self->id],
    ]);

    $response->assertStatus(403);
    $this->assertDatabaseHas('users', ['id' => $self->id, 'deleted_at' => null]);
}

public function test_bulk_delete_blocks_super_admin_deletion(): void
{
    $superAdmin = User::forceCreate([
        'name'     => 'Super2',
        'email'    => 'super2@test.com',
        'password' => bcrypt('password'),
    ]);
    $superAdmin->assignRole('super-admin');

    $response = $this->deleteJson('/api/v1/admin/users/bulk-delete', [
        'ids' => [$superAdmin->id],
    ]);

    $response->assertStatus(403);
    $this->assertDatabaseHas('users', ['id' => $superAdmin->id, 'deleted_at' => null]);
}
```

- [ ] **Step 4: Run all User tests**

```bash
wsl.exe -d Ubuntu -- bash -c "php artisan test tests/Feature/Admin/UserTest.php 2>&1" | cat
```

Expected: PASS for all tests added so far.

- [ ] **Step 5: Commit**

```bash
git add \
  Modules/Users/app/Http/Resources/UserResource.php \
  Modules/Users/app/Http/Controllers/UsersController.php \
  Modules/Users/app/Repositories/UsersRepositoryInterface.php \
  Modules/Users/app/Repositories/UsersRepository.php \
  tests/Feature/Admin/UserTest.php
git commit -m "refactor(users): add UserResource, add DB::transaction, guard bulkDelete"
```

---

## Task 4: Create `AssignRoleRequest` and `RevokeRoleRequest`

**Files:**
- Create: `Modules/Users/app/Http/Requests/AssignRoleRequest.php`
- Create: `Modules/Users/app/Http/Requests/RevokeRoleRequest.php`

- [ ] **Step 1: Create `AssignRoleRequest`**

```php
<?php

namespace Modules\Users\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class AssignRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'role' => 'required|string|exists:roles,name',
        ];
    }

    public function messages(): array
    {
        return [
            'role.required' => 'Role không được để trống.',
            'role.exists'   => 'Role không hợp lệ.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Dữ liệu không hợp lệ.',
            'errors'  => $validator->errors(),
        ], 422));
    }
}
```

- [ ] **Step 2: Create `RevokeRoleRequest`**

```php
<?php

namespace Modules\Users\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class RevokeRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'role' => 'required|string|exists:roles,name',
        ];
    }

    public function messages(): array
    {
        return [
            'role.required' => 'Role không được để trống.',
            'role.exists'   => 'Role không hợp lệ.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Dữ liệu không hợp lệ.',
            'errors'  => $validator->errors(),
        ], 422));
    }
}
```

- [ ] **Step 3: Run lint to verify files are clean**

```bash
wsl.exe -d Ubuntu -- bash -c "./vendor/bin/pint Modules/Users/app/Http/Requests/AssignRoleRequest.php Modules/Users/app/Http/Requests/RevokeRoleRequest.php --test 2>&1" | cat
```

Expected: no issues.

- [ ] **Step 4: Commit**

```bash
git add \
  Modules/Users/app/Http/Requests/AssignRoleRequest.php \
  Modules/Users/app/Http/Requests/RevokeRoleRequest.php
git commit -m "refactor(users): extract AssignRoleRequest and RevokeRoleRequest"
```

---

## Task 5: Fix role route permissions (split by verb)

**Files:**
- Modify: `Modules/Users/routes/api.php`

- [ ] **Step 1: Replace the entire `routes/api.php`**

```php
<?php

use Illuminate\Support\Facades\Route;
use Modules\Users\Http\Controllers\ActivityLogController;
use Modules\Users\Http\Controllers\RolesController;
use Modules\Users\Http\Controllers\UsersController;

Route::middleware(['auth:admin'])->prefix('admin')->group(function () {
    // Users — static/bulk routes BEFORE the parameterized apiResource
    Route::get('users/trashed', [UsersController::class, 'trashed'])->middleware('permission:users.view');
    Route::post('users/bulk-restore', [UsersController::class, 'bulkRestore'])->middleware('permission:users.edit');
    Route::delete('users/bulk-delete', [UsersController::class, 'bulkDelete'])->middleware('permission:users.delete');
    Route::delete('users/bulk-force-delete', [UsersController::class, 'bulkForceDelete'])->middleware('permission:users.delete');
    Route::post('users/bulk-action', [UsersController::class, 'bulkAction'])->middleware('permission:users.edit');
    Route::get('users/roles', [UsersController::class, 'getRoles'])->middleware('permission:users.view');
    Route::post('users/bulk-assign-role', [UsersController::class, 'bulkAssignRole'])->middleware('permission:users.edit');

    Route::apiResource('users', UsersController::class)->names('admin.users')
        ->middleware('permission:users.view|users.create|users.edit|users.delete');

    Route::post('users/{id}/assign-role', [UsersController::class, 'assignRole'])->middleware('permission:users.edit');
    Route::post('users/{id}/revoke-role', [UsersController::class, 'revokeRole'])->middleware('permission:users.edit');
    Route::post('users/{id}/restore', [UsersController::class, 'restore'])->middleware('permission:users.edit');
    Route::delete('users/{id}/force-delete', [UsersController::class, 'forceDelete'])->middleware('permission:users.delete');

    // Permissions list
    Route::get('permissions', [RolesController::class, 'getPermissions'])->middleware('permission:users.view');

    // Roles — each verb gets its own permission
    Route::get('roles', [RolesController::class, 'index'])->middleware('permission:users.view');
    Route::get('roles/{role}', [RolesController::class, 'show'])->middleware('permission:users.view');
    Route::post('roles', [RolesController::class, 'store'])->middleware('permission:users.create');
    Route::patch('roles/{role}', [RolesController::class, 'update'])->middleware('permission:users.edit');
    Route::delete('roles/{role}', [RolesController::class, 'destroy'])->middleware('permission:users.delete');

    // System Logs
    Route::prefix('system')->group(function () {
        Route::get('logs', [ActivityLogController::class, 'index'])
            ->middleware('permission:system.logs.view');
        Route::delete('logs/clear', [ActivityLogController::class, 'clear'])
            ->middleware('permission:system.logs.delete');
    });
});
```

- [ ] **Step 2: Run existing Roles tests to verify routes still work**

```bash
wsl.exe -d Ubuntu -- bash -c "php artisan test Modules/Users/tests/Feature/RolesTest.php 2>&1" | cat
```

Expected: all pass (routes changed from apiResource to explicit but same URL patterns).

- [ ] **Step 3: Commit**

```bash
git add Modules/Users/routes/api.php
git commit -m "refactor(users): split role route permissions by verb (view/create/edit/delete)"
```

---

## Task 6: Fix FormRequest issues

**Files:**
- Modify: `Modules/Users/app/Http/Requests/StoreRoleRequest.php`
- Modify: `Modules/Users/app/Http/Requests/UpdateRoleRequest.php`
- Modify: `Modules/Users/app/Http/Requests/BulkRestoreUsersRequest.php`
- Modify: `Modules/Users/app/Http/Requests/BulkForceDeleteUsersRequest.php`

- [ ] **Step 1: Add `failedValidation()` to `StoreRoleRequest`**

```php
<?php

namespace Modules\Users\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'           => 'required|string|max:255|unique:roles,name',
            'permissions'    => 'nullable|array',
            'permissions.*'  => 'string|exists:permissions,name',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Tên vai trò không được để trống.',
            'name.unique'   => 'Tên vai trò đã tồn tại.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Dữ liệu không hợp lệ.',
            'errors'  => $validator->errors(),
        ], 422));
    }
}
```

- [ ] **Step 2: Add `failedValidation()` to `UpdateRoleRequest`**

```php
<?php

namespace Modules\Users\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $roleId = $this->route('role');

        return [
            'name'           => 'sometimes|string|max:255|unique:roles,name,'.$roleId,
            'permissions'    => 'nullable|array',
            'permissions.*'  => 'string|exists:permissions,name',
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'Tên vai trò đã tồn tại.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Dữ liệu không hợp lệ.',
            'errors'  => $validator->errors(),
        ], 422));
    }
}
```

- [ ] **Step 3: Add `exists` validation to `BulkRestoreUsersRequest`**

The `ids.*` rule must include `exists` against soft-deleted records. Since standard `exists` doesn't check trashed rows, use a custom closure:

```php
<?php

namespace Modules\Users\Http\Requests;

use Illuminate\Validation\Rule;

class BulkRestoreUsersRequest extends BaseBulkRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ids'   => 'required|array|min:1|max:100',
            'ids.*' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->whereNotNull('deleted_at'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'ids.required'    => 'Danh sách ID không được để trống.',
            'ids.array'       => 'ids phải là mảng.',
            'ids.min'         => 'Phải chọn ít nhất 1 user.',
            'ids.max'         => 'Không thể xử lý quá 100 user cùng lúc.',
            'ids.*.integer'   => 'ID phải là số nguyên.',
            'ids.*.exists'    => 'Một hoặc nhiều user không tồn tại hoặc chưa bị xóa.',
        ];
    }
}
```

- [ ] **Step 4: Add `exists` validation to `BulkForceDeleteUsersRequest`**

```php
<?php

namespace Modules\Users\Http\Requests;

use Illuminate\Validation\Rule;

class BulkForceDeleteUsersRequest extends BaseBulkRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ids'   => 'required|array|min:1|max:100',
            'ids.*' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->whereNotNull('deleted_at'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'ids.required'    => 'Danh sách ID không được để trống.',
            'ids.array'       => 'ids phải là mảng.',
            'ids.min'         => 'Phải chọn ít nhất 1 user.',
            'ids.max'         => 'Không thể xử lý quá 100 user cùng lúc.',
            'ids.*.integer'   => 'ID phải là số nguyên.',
            'ids.*.exists'    => 'Một hoặc nhiều user không tồn tại hoặc chưa bị xóa.',
        ];
    }
}
```

- [ ] **Step 5: Run test suite to verify no regressions**

```bash
wsl.exe -d Ubuntu -- bash -c "php artisan test Modules/Users/tests/Feature/RolesTest.php tests/Feature/Admin/UserTest.php 2>&1" | cat
```

Expected: all pass.

- [ ] **Step 6: Commit**

```bash
git add \
  Modules/Users/app/Http/Requests/StoreRoleRequest.php \
  Modules/Users/app/Http/Requests/UpdateRoleRequest.php \
  Modules/Users/app/Http/Requests/BulkRestoreUsersRequest.php \
  Modules/Users/app/Http/Requests/BulkForceDeleteUsersRequest.php
git commit -m "refactor(users): add failedValidation JSON override, add exists validation to bulk restore/force-delete"
```

---

## Task 7: Fix `ActivityLogController`

**Files:**
- Modify: `Modules/Users/app/Http/Controllers/ActivityLogController.php`

- [ ] **Step 1: Replace the entire file**

```php
<?php

namespace Modules\Users\Http\Controllers;

use App\Events\ActivityLogsCleared;
use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);

        $logs = Activity::with('causer')
            ->latest()
            ->paginate($perPage);

        $logs->getCollection()->transform(function ($log) {
            return [
                'id'           => $log->id,
                'log_name'     => $log->log_name,
                'description'  => $log->description,
                'subject_type' => class_basename($log->subject_type),
                'subject_id'   => $log->subject_id,
                'causer_name'  => $log->causer ? $log->causer->name : 'Hệ thống',
                'properties'   => $log->properties,
                'created_at'   => $log->created_at->toDateTimeString(),
                'human_time'   => $log->created_at->diffForHumans(),
            ];
        });

        return $this->paginated($logs, 'Tải lịch sử hoạt động thành công.');
    }

    public function clear(): JsonResponse
    {
        $admin = auth('admin')->user();
        Activity::truncate();

        event(new ActivityLogsCleared($admin));

        return $this->success(null, 'Đã dọn dẹp lịch sử hoạt động.');
    }
}
```

- [ ] **Step 2: Run lint**

```bash
wsl.exe -d Ubuntu -- bash -c "./vendor/bin/pint Modules/Users/app/Http/Controllers/ActivityLogController.php --test 2>&1" | cat
```

Expected: clean.

- [ ] **Step 3: Commit**

```bash
git add Modules/Users/app/Http/Controllers/ActivityLogController.php
git commit -m "fix(users): use auth('admin') guard and add return types in ActivityLogController"
```

---

## Task 8: Fix `putJson` → `patchJson` in RolesTest

**Files:**
- Modify: `Modules/Users/tests/Feature/RolesTest.php`

- [ ] **Step 1: Replace `putJson` with `patchJson`**

In `Modules/Users/tests/Feature/RolesTest.php`, line 106:

Old:
```php
$response = $this->actingAs($this->admin, 'admin')
    ->putJson("/api/v1/admin/roles/{$role->id}", [
        'name' => 'staff-updated',
        'permissions' => ['users.view', 'users.edit'],
    ]);
```

New:
```php
$response = $this->actingAs($this->admin, 'admin')
    ->patchJson("/api/v1/admin/roles/{$role->id}", [
        'name' => 'staff-updated',
        'permissions' => ['users.view', 'users.edit'],
    ]);
```

Also replace the two `putJson` calls in `test_cannot_update_super_admin_role` (line 126):

Old:
```php
$response = $this->actingAs($this->admin, 'admin')
    ->putJson("/api/v1/admin/roles/{$superAdminRole->id}", [
        'name' => 'hacker-admin',
    ]);
```

New:
```php
$response = $this->actingAs($this->admin, 'admin')
    ->patchJson("/api/v1/admin/roles/{$superAdminRole->id}", [
        'name' => 'hacker-admin',
    ]);
```

- [ ] **Step 2: Run RolesTest to verify**

```bash
wsl.exe -d Ubuntu -- bash -c "php artisan test Modules/Users/tests/Feature/RolesTest.php 2>&1" | cat
```

Expected: all tests pass.

- [ ] **Step 3: Commit**

```bash
git add Modules/Users/tests/Feature/RolesTest.php
git commit -m "test(users): replace putJson with patchJson to match PATCH convention"
```

---

## Task 9: Remove dead code (`UsersHelper`)

**Files:**
- Delete: `Modules/Users/app/Helpers/UsersHelper.php`
- Modify: `Modules/Users/app/Providers/UsersServiceProvider.php`

- [ ] **Step 1: Delete the helper file**

```bash
rm Modules/Users/app/Helpers/UsersHelper.php
```

- [ ] **Step 2: Remove helper references from `UsersServiceProvider`**

In `UsersServiceProvider.php`, remove:
- The `use Modules\Users\Helpers\UsersHelper;` import
- The `$this->app->singleton('UsersHelper', ...)` block in `register()`

The `register()` method should become:

```php
public function register(): void
{
    $this->app->bind(
        UsersRepositoryInterface::class,
        UsersRepository::class
    );

    $this->app->register(EventServiceProvider::class);
    $this->app->register(RouteServiceProvider::class);
}
```

And remove the `use Modules\Users\Helpers\UsersHelper;` import at the top.

- [ ] **Step 3: Run the full module test suite**

```bash
wsl.exe -d Ubuntu -- bash -c "php artisan test Modules/Users/tests/Feature/RolesTest.php tests/Feature/Admin/UserTest.php 2>&1" | cat
```

Expected: all pass.

- [ ] **Step 4: Commit**

```bash
git add \
  Modules/Users/app/Providers/UsersServiceProvider.php
git commit -m "chore(users): remove dead UsersHelper stub"
```

---

## Task 10: Run full test suite and lint

- [ ] **Step 1: Run all tests**

```bash
wsl.exe -d Ubuntu -- bash -c "php artisan test 2>&1" | cat
```

Expected: all existing tests pass, no regressions.

- [ ] **Step 2: Run Pint on all changed files**

```bash
wsl.exe -d Ubuntu -- bash -c "./vendor/bin/pint 2>&1" | cat
```

Expected: no issues (or auto-fixed, then re-run with `--test` to confirm clean).

- [ ] **Step 3: Commit any auto-fix from Pint**

If Pint made changes:

```bash
git add -u
git commit -m "chore(users): pint formatting fixes"
```
