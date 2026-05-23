# Permission-Based Sidebar Refactoring — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace all `hideForRoles`/`showOnlyForRoles` logic in the admin sidebar with pure permission-based control; split `users.*` into `admin_users.*` + `teachers.*` + `roles.*`; split `categories.*` into `course_categories.*` + `post_categories.*`; add missing commission module permissions.

**Architecture:** Backend changes touch 1 seeder + 5 route files + 1 controller — they define new permission names and add missing middleware. Frontend changes are confined to `AppSidebar.vue` — drop all role-checking logic, update permission strings. After all code changes, re-run the seeder to apply new permissions to the DB.

**Tech Stack:** Laravel 12 + Spatie Permission (backend), Vue 3 + TypeScript + Pinia `adminAuth.store` (frontend). All backend commands via `wsl.exe -d Ubuntu -- bash -c "..."`.

---

## Permission Rename Map

| Sidebar item | Old permission | New permission | Notes |
|---|---|---|---|
| Quản trị viên (`/admin/users`) | `users.*` | `admin_users.*` | Split from shared `users.*` |
| Giảng viên (`/admin/teachers`) | `users.*` + `hideForRoles` | `teachers.*` | Split from shared `users.*` |
| Phân quyền (`/admin/roles`) | `users.*` (bug: wrong permission) | `roles.*` | Already in seeder, never wired to routes |
| Danh mục khóa học (`/admin/categories`) | `categories.*` | `course_categories.*` | Split |
| Danh mục bài viết (`/admin/post-categories`) | `categories.view` | `post_categories.view` | New permission |
| Yêu cầu rút tiền (`/admin/payouts`) | *(none — security bug)* | `payouts.view` / `payouts.approve` | Fix |
| Hoa hồng giảng viên (`/admin/teacher-earnings`) | *(none — security bug)* | `teacher_earnings.view` | Fix |
| Cài đặt tỷ lệ (`/admin/commission-settings`) | *(none — security bug)* | `commission_settings.view` / `commission_settings.update` | Fix |

## Files to Modify (8 total)

| # | File | What changes |
|---|---|---|
| 1 | `Modules/Users/database/seeders/RolePermissionSeeder.php` | Delete old permissions, add new, re-assign roles |
| 2 | `Modules/Users/routes/api.php` | `users.*` → `admin_users.*` (users CRUD); `users.*` → `roles.*` (roles CRUD) |
| 3 | `Modules/Teachers/routes/api.php` | `users.*` → `teachers.*` |
| 4 | `Modules/Categories/routes/api.php` | `categories.*` → `course_categories.*` |
| 5 | `Modules/Commission/routes/api.php` | Add permission middleware to 5 admin routes |
| 6 | `Modules/Upload/routes/api.php` | `users.edit` → `admin_users.edit` (one line) |
| 7 | `Modules/Users/app/Http/Controllers/RolesController.php` | `users.delete` → `admin_users.delete` (lines 88–89) |
| 8 | `src/components/layout/AppSidebar.vue` | Drop `hideForRoles`/`showOnlyForRoles`; update permission strings; simplify logic |

---

## Task 1: Update RolePermissionSeeder

**Files:**
- Modify: `e-learning-backend/Modules/Users/database/seeders/RolePermissionSeeder.php`

- [ ] **Step 1: Replace the entire file with the updated seeder**

```php
<?php

namespace Modules\Users\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $guard = 'admin';

        // Delete renamed permissions so they don't linger in the DB
        Permission::where('guard_name', $guard)
            ->whereIn('name', [
                'users.view', 'users.create', 'users.edit', 'users.delete',
                'categories.view', 'categories.create', 'categories.edit', 'categories.delete',
            ])
            ->delete();

        $permissions = [
            // Admin-user management (staff accounts — /admin/users)
            'admin_users.view', 'admin_users.create', 'admin_users.edit', 'admin_users.delete',
            // Roles & permissions management (/admin/roles)
            'roles.view', 'roles.create', 'roles.edit', 'roles.delete',
            // Teacher account management (/admin/teachers)
            'teachers.view', 'teachers.create', 'teachers.edit', 'teachers.delete',
            // Courses
            'courses.view', 'courses.create', 'courses.edit', 'courses.delete',
            // Course categories (/admin/categories)
            'course_categories.view', 'course_categories.create', 'course_categories.edit', 'course_categories.delete',
            // Post categories (/admin/post-categories)
            'post_categories.view', 'post_categories.create', 'post_categories.edit', 'post_categories.delete',
            // Lessons
            'lessons.view', 'lessons.create', 'lessons.edit', 'lessons.delete',
            // Quizzes
            'quizzes.view', 'quizzes.create', 'quizzes.edit', 'quizzes.delete',
            // Orders & Coupons
            'orders.view', 'orders.edit',
            'coupons.view', 'coupons.create', 'coupons.edit', 'coupons.delete',
            // Students
            'students.view', 'students.edit',
            // Posts / News
            'posts.view', 'posts.create', 'posts.edit', 'posts.delete',
            'tags.view', 'tags.create', 'tags.edit', 'tags.delete',
            'comments.view', 'comments.delete',
            // Commission module
            'payouts.view', 'payouts.approve',
            'teacher_earnings.view',
            'commission_settings.view', 'commission_settings.update',
            // Dashboard
            'dashboard.view',
            // System logs
            'system.logs.view', 'system.logs.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => $guard]);
        }

        $superAdmin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => $guard]);
        $admin      = Role::firstOrCreate(['name' => 'admin',       'guard_name' => $guard]);
        $teacher    = Role::firstOrCreate(['name' => 'teacher',     'guard_name' => $guard]);

        // super-admin gets all permissions
        $superAdmin->syncPermissions(Permission::where('guard_name', $guard)->get());

        // admin gets all except admin_users.delete (cannot delete other admins)
        $admin->syncPermissions(
            Permission::where('guard_name', $guard)
                ->where('name', '!=', 'admin_users.delete')
                ->get()
        );

        // teacher manages only their own courses/lessons (portal at /teacher/*)
        $teacher->syncPermissions([
            'courses.view', 'courses.create', 'courses.edit', 'courses.delete',
            'lessons.view', 'lessons.create', 'lessons.edit', 'lessons.delete',
            'quizzes.view', 'quizzes.create', 'quizzes.edit',
            'dashboard.view',
            'course_categories.view',
        ]);
    }
}
```

- [ ] **Step 2: Run the seeder**

```bash
wsl.exe -d Ubuntu -- bash -c "php artisan db:seed --class='Modules\\Users\\Database\\Seeders\\RolePermissionSeeder' 2>&1" | cat
```

Expected: No errors. Seeder deletes old `users.*` and `categories.*`, creates new permissions, re-syncs all three roles.

- [ ] **Step 3: Verify no old permission names remain**

```bash
wsl.exe -d Ubuntu -- bash -c "php artisan tinker --execute=\"\\\\Spatie\\\\Permission\\\\Models\\\\Permission::where('guard_name','admin')->whereIn('name',['users.view','categories.view'])->count();\" 2>&1" | cat
```

Expected output: `= 0`

---

## Task 2: Update Users Module Routes

**Files:**
- Modify: `e-learning-backend/Modules/Users/routes/api.php`

Changes:
- `/admin/users*` routes: `users.*` → `admin_users.*`
- `/admin/permissions` + `/admin/roles*` routes: `users.*` → `roles.*`

- [ ] **Step 1: Replace the entire file**

```php
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
```

---

## Task 3: Update Teachers Module Routes

**Files:**
- Modify: `e-learning-backend/Modules/Teachers/routes/api.php`

Changes: all `users.*` → `teachers.*`. The `users.view|courses.view` pattern becomes `teachers.view|courses.view` (course managers still need read access to the teacher list for course assignment).

- [ ] **Step 1: Replace the entire file**

```php
<?php

use Illuminate\Support\Facades\Route;
use Modules\Teachers\Http\Controllers\TeachersController;

Route::middleware(['auth:admin'])->prefix('admin')->group(function () {
    // Static/bulk routes BEFORE parameterized routes
    Route::get('teachers/trashed', [TeachersController::class, 'trashed'])->middleware('permission:teachers.view');

    Route::patch('teachers/bulk-restore', [TeachersController::class, 'bulkRestore'])->middleware('permission:teachers.edit');
    Route::delete('teachers/bulk-delete', [TeachersController::class, 'bulkDelete'])->middleware('permission:teachers.delete');
    Route::delete('teachers/bulk-force-delete', [TeachersController::class, 'bulkForceDelete'])->middleware('permission:teachers.delete');

    // courses.view also grants read access so course managers can pick a teacher when creating a course
    Route::get('teachers', [TeachersController::class, 'index'])->middleware('permission:teachers.view|courses.view');
    Route::post('teachers', [TeachersController::class, 'store'])->middleware('permission:teachers.create');
    Route::get('teachers/{teacher}', [TeachersController::class, 'show'])->middleware('permission:teachers.view|courses.view');
    Route::patch('teachers/{teacher}', [TeachersController::class, 'update'])->middleware('permission:teachers.edit');
    Route::delete('teachers/{teacher}', [TeachersController::class, 'destroy'])->middleware('permission:teachers.delete');

    Route::patch('teachers/{id}/toggle-status', [TeachersController::class, 'toggleStatus'])->middleware('permission:teachers.edit');
    Route::patch('teachers/{id}/restore', [TeachersController::class, 'restore'])->middleware('permission:teachers.edit');
    Route::delete('teachers/{id}/force-delete', [TeachersController::class, 'forceDelete'])->middleware('permission:teachers.delete');
});

Route::group([], function () {
    Route::get('teachers', [TeachersController::class, 'publicIndex']);
    Route::get('teachers/{slug}', [TeachersController::class, 'publicShow']);
});
```

---

## Task 4: Update Categories Module Routes

**Files:**
- Modify: `e-learning-backend/Modules/Categories/routes/api.php`

Changes: all `categories.*` → `course_categories.*`. This endpoint is course categories only; post-categories frontend page also calls this API, so it will require `course_categories.view` at the API level regardless of which sidebar permission gates the menu item.

- [ ] **Step 1: Replace the entire file**

```php
<?php

use Illuminate\Support\Facades\Route;
use Modules\Categories\Http\Controllers\CategoriesController;

Route::middleware(['auth:admin'])->prefix('admin')->group(function () {
    // Nested set routes — BEFORE parameterized routes to avoid {category} matching
    Route::get('categories/tree', [CategoriesController::class, 'tree'])->middleware('permission:course_categories.view|courses.view');
    Route::get('categories/flat-tree', [CategoriesController::class, 'flatTree'])->middleware('permission:course_categories.view|courses.view');
    Route::get('categories/trashed', [CategoriesController::class, 'trashed'])->middleware('permission:course_categories.view');

    // Bulk routes
    Route::post('categories/bulk-restore', [CategoriesController::class, 'bulkRestore'])->middleware('permission:course_categories.delete');
    Route::delete('categories/bulk-delete', [CategoriesController::class, 'bulkDelete'])->middleware('permission:course_categories.delete');
    Route::delete('categories/bulk-force-delete', [CategoriesController::class, 'bulkForceDelete'])->middleware('permission:course_categories.delete');

    // Standard CRUD
    Route::get('categories', [CategoriesController::class, 'index'])->middleware('permission:course_categories.view');
    Route::post('categories', [CategoriesController::class, 'store'])->middleware('permission:course_categories.create');
    Route::get('categories/{category}', [CategoriesController::class, 'show'])->middleware('permission:course_categories.view');
    Route::patch('categories/{category}', [CategoriesController::class, 'update'])->middleware('permission:course_categories.edit');
    Route::delete('categories/{category}', [CategoriesController::class, 'destroy'])->middleware('permission:course_categories.delete');

    // Per-item actions
    Route::post('categories/{id}/move', [CategoriesController::class, 'move'])->middleware('permission:course_categories.edit');
    Route::get('categories/{id}/ancestors', [CategoriesController::class, 'ancestors'])->middleware('permission:course_categories.view');
    Route::get('categories/{id}/descendants', [CategoriesController::class, 'descendants'])->middleware('permission:course_categories.view');
    Route::patch('categories/{id}/toggle-status', [CategoriesController::class, 'toggleStatus'])->middleware('permission:course_categories.edit');
    Route::post('categories/{id}/restore', [CategoriesController::class, 'restore'])->middleware('permission:course_categories.delete');
    Route::delete('categories/{id}/force-delete', [CategoriesController::class, 'forceDelete'])->middleware('permission:course_categories.delete');
});

Route::group([], function () {
    Route::get('categories', [CategoriesController::class, 'publicIndex']);
    Route::get('categories/tree', [CategoriesController::class, 'publicTree']);
    Route::get('categories/{slug}', [CategoriesController::class, 'publicShow']);
});
```

- [ ] **Step 2: Commit Tasks 2, 3, 4 together**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning && git add e-learning-backend/Modules/Users/routes/api.php e-learning-backend/Modules/Teachers/routes/api.php e-learning-backend/Modules/Categories/routes/api.php && git commit -m 'refactor(auth): rename admin_users.*, teachers.*, course_categories.* in routes'" | cat
```

---

## Task 5: Add Permissions to Commission Routes

**Files:**
- Modify: `e-learning-backend/Modules/Commission/routes/api.php`

The 5 admin routes currently have **no `permission` middleware at all** — any authenticated admin can call them. This task fixes that.

- [ ] **Step 1: Replace the admin group in Commission routes**

Replace only the `Route::middleware(['auth:admin'])` group (lines 13–24 of the current file). The `role:teacher` group below it is **unchanged**.

Full replacement for `e-learning-backend/Modules/Commission/routes/api.php`:

```php
<?php

use Illuminate\Support\Facades\Route;
use Modules\Commission\Http\Controllers\Admin\CommissionSettingsController;
use Modules\Commission\Http\Controllers\Admin\PayoutController;
use Modules\Commission\Http\Controllers\Admin\TeacherEarningsController;
use Modules\Commission\Http\Controllers\Teacher\EarningsController;
use Modules\Commission\Http\Controllers\Teacher\TeacherCourseController;
use Modules\Commission\Http\Controllers\Teacher\TeacherLessonController;
use Modules\Commission\Http\Controllers\Teacher\TeacherPortalController;
use Modules\Commission\Http\Controllers\Teacher\TeacherSectionController;

Route::middleware(['auth:admin'])->prefix('admin')->group(function () {
    Route::get('commission-settings', [CommissionSettingsController::class, 'show'])
        ->middleware('permission:commission_settings.view');
    Route::patch('commission-settings', [CommissionSettingsController::class, 'update'])
        ->middleware('permission:commission_settings.update');

    Route::get('payouts', [PayoutController::class, 'index'])
        ->middleware('permission:payouts.view');
    Route::patch('payouts/{id}/approve', [PayoutController::class, 'approve'])
        ->middleware('permission:payouts.approve');
    Route::patch('payouts/{id}/reject', [PayoutController::class, 'reject'])
        ->middleware('permission:payouts.approve');
    Route::patch('payouts/{id}/mark-paid', [PayoutController::class, 'markPaid'])
        ->middleware('permission:payouts.approve');

    Route::get('teacher-earnings', [TeacherEarningsController::class, 'index'])
        ->middleware('permission:teacher_earnings.view');
});

Route::middleware(['auth:admin', 'role:teacher'])->prefix('teacher')->group(function () {
    Route::get('earnings', [EarningsController::class, 'index']);
    Route::get('payouts', [EarningsController::class, 'myPayouts']);
    Route::post('payouts', [EarningsController::class, 'requestPayout']);

    Route::get('dashboard', [TeacherPortalController::class, 'dashboard']);
    Route::get('courses', [TeacherPortalController::class, 'courses']);
    Route::get('profile', [TeacherPortalController::class, 'profile']);
    Route::patch('profile', [TeacherPortalController::class, 'updateProfile']);

    Route::post('change-password/send-otp', [TeacherPortalController::class, 'sendPasswordOtp']);
    Route::post('change-password/confirm', [TeacherPortalController::class, 'confirmPasswordChange']);
    Route::post('change-email/send-otp', [TeacherPortalController::class, 'sendEmailChangeOtp']);
    Route::post('change-email/confirm', [TeacherPortalController::class, 'confirmEmailChange']);

    Route::post('courses', [TeacherCourseController::class, 'store']);
    Route::get('courses/{id}', [TeacherCourseController::class, 'show']);
    Route::patch('courses/{id}', [TeacherCourseController::class, 'update']);
    Route::delete('courses/{id}', [TeacherCourseController::class, 'destroy']);
    Route::patch('courses/{id}/toggle-status', [TeacherCourseController::class, 'toggleStatus']);

    Route::post('sections/reorder', [TeacherSectionController::class, 'reorder']);
    Route::get('courses/{course_id}/sections', [TeacherSectionController::class, 'index']);
    Route::post('courses/{course_id}/sections', [TeacherSectionController::class, 'store']);
    Route::patch('sections/{id}', [TeacherSectionController::class, 'update']);
    Route::delete('sections/{id}', [TeacherSectionController::class, 'destroy']);
    Route::patch('sections/{id}/toggle-status', [TeacherSectionController::class, 'toggleStatus']);

    Route::get('lessons/trashed', [TeacherLessonController::class, 'trashed']);
    Route::post('lessons/reorder', [TeacherLessonController::class, 'reorder']);
    Route::delete('lessons/bulk-delete', [TeacherLessonController::class, 'bulkDelete']);
    Route::post('lessons/bulk-action', [TeacherLessonController::class, 'bulkAction']);
    Route::patch('lessons/bulk-restore', [TeacherLessonController::class, 'bulkRestore']);
    Route::delete('lessons/bulk-force-delete', [TeacherLessonController::class, 'bulkForceDelete']);
    Route::get('courses/{course_id}/lessons', [TeacherLessonController::class, 'index']);
    Route::post('courses/{course_id}/lessons', [TeacherLessonController::class, 'store']);
    Route::get('lessons/{id}', [TeacherLessonController::class, 'show']);
    Route::patch('lessons/{id}', [TeacherLessonController::class, 'update']);
    Route::delete('lessons/{id}', [TeacherLessonController::class, 'destroy']);
    Route::patch('lessons/{id}/toggle-status', [TeacherLessonController::class, 'toggleStatus']);
    Route::patch('lessons/{id}/restore', [TeacherLessonController::class, 'restore']);
    Route::delete('lessons/{id}/force-delete', [TeacherLessonController::class, 'forceDelete']);
});
```

---

## Task 6: Fix Upload Route + RolesController

**Files:**
- Modify: `e-learning-backend/Modules/Upload/routes/api.php` (line 20 — one change)
- Modify: `e-learning-backend/Modules/Users/app/Http/Controllers/RolesController.php` (lines 88–89 — two changes)

- [ ] **Step 1: Fix Upload route — `users.edit` → `admin_users.edit`**

In `e-learning-backend/Modules/Upload/routes/api.php`, line 20, change:

```php
// Before
->middleware('permission:courses.create|courses.edit|users.edit|posts.create|posts.edit');

// After
->middleware('permission:courses.create|courses.edit|admin_users.edit|posts.create|posts.edit');
```

- [ ] **Step 2: Fix RolesController privilege-escalation guard**

In `e-learning-backend/Modules/Users/app/Http/Controllers/RolesController.php`, lines 88–89, change:

```php
// Before (lines 88–89)
if (! auth('admin')->user()?->hasPermissionTo('users.delete', 'admin')) {
    $permissions = array_values(array_filter($permissions, fn ($p) => $p !== 'users.delete'));
}

// After
if (! auth('admin')->user()?->hasPermissionTo('admin_users.delete', 'admin')) {
    $permissions = array_values(array_filter($permissions, fn ($p) => $p !== 'admin_users.delete'));
}
```

- [ ] **Step 3: Run Pint dry-run to check style**

```bash
wsl.exe -d Ubuntu -- bash -c "./vendor/bin/pint --test 2>&1" | cat
```

Expected: "No files need to be updated." If violations found, run `./vendor/bin/pint` to auto-fix, then re-check.

- [ ] **Step 4: Run all backend tests**

```bash
wsl.exe -d Ubuntu -- bash -c "php artisan test 2>&1" | cat
```

Expected: All tests pass. If any test fails with "There is no permission named `users.view`", find the test that explicitly assigns the old permission by name and update it to `admin_users.view` / `course_categories.view` as appropriate.

- [ ] **Step 5: Commit Tasks 5 & 6**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning && git add e-learning-backend/Modules/Commission/routes/api.php e-learning-backend/Modules/Upload/routes/api.php e-learning-backend/Modules/Users/app/Http/Controllers/RolesController.php && git commit -m 'refactor(auth): add commission permissions, fix admin_users.* in upload and roles controller'" | cat
```

---

## Task 7: Refactor AppSidebar.vue

**Files:**
- Modify: `e-learning-frontend/src/components/layout/AppSidebar.vue`

The `<template>` block is **unchanged**. All modifications are in `<script setup>`:
- Remove `hideForRoles` / `showOnlyForRoles` from `MenuItem` and `MenuGroup` types
- Update all permission strings to new names
- Add `permission` to the 3 commission items (previously missing)
- Simplify `hasPermission()` — no more role checks
- Simplify `menuGroups` computed — no more group-level role filter

- [ ] **Step 1: Replace the `<script setup>` block**

Find the line `<script setup lang="ts">` (line 148 currently) and replace everything from that line to the closing `</script>` with:

```typescript
<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'

import {
  GridIcon,
  UserGroupIcon,
  PieChartIcon,
  ChevronDownIcon,
  HorizontalDots,
  PageIcon,
  BoxIcon,
  BoxCubeIcon,
  ListIcon,
  TaskIcon,
  BarChartIcon,
  SettingsIcon,
} from '@/components/icons'
import { useSidebar } from '@/composables/useSidebar'
import { useAdminAuthStore } from '@/stores/adminAuth.store'

const route = useRoute()
const { isExpanded, isMobileOpen, isHovered, openSubmenu } = useSidebar()
const adminStore = useAdminAuthStore()

type MenuItem = {
  icon?: unknown
  name: string
  path?: string
  permission?: string
  subItems?: MenuItem[]
}

type MenuGroup = {
  title: string
  items: MenuItem[]
}

const rawMenuGroups: MenuGroup[] = [
  {
    title: 'Quản trị',
    items: [
      {
        icon: GridIcon,
        name: 'Dashboard',
        path: '/admin/dashboard',
        permission: 'dashboard.view',
      },
      {
        icon: BoxCubeIcon,
        name: 'Khóa học',
        subItems: [
          { name: 'Danh sách', path: '/admin/courses', permission: 'courses.view' },
          { name: 'Thêm mới', path: '/admin/courses/create', permission: 'courses.create' },
        ],
      },
      {
        icon: ListIcon,
        name: 'Danh mục',
        path: '/admin/categories',
        permission: 'course_categories.view',
      },
      {
        icon: UserGroupIcon,
        name: 'Người dùng',
        subItems: [
          { name: 'Quản trị viên', path: '/admin/users',    permission: 'admin_users.view' },
          { name: 'Giảng viên',    path: '/admin/teachers', permission: 'teachers.view' },
          { name: 'Học viên',      path: '/admin/students', permission: 'students.view' },
        ],
      },
    ],
  },
  {
    title: 'Kinh doanh',
    items: [
      {
        icon: BoxIcon,
        name: 'Đơn hàng',
        path: '/admin/orders',
        permission: 'orders.view',
      },
      {
        icon: TaskIcon,
        name: 'Mã giảm giá',
        path: '/admin/coupons',
        permission: 'coupons.view',
      },
    ],
  },
  {
    title: 'Hoa hồng',
    items: [
      {
        icon: BarChartIcon,
        name: 'Yêu cầu rút tiền',
        path: '/admin/payouts',
        permission: 'payouts.view',
      },
      {
        icon: PieChartIcon,
        name: 'Hoa hồng giảng viên',
        path: '/admin/teacher-earnings',
        permission: 'teacher_earnings.view',
      },
      {
        icon: SettingsIcon,
        name: 'Cài đặt tỷ lệ',
        path: '/admin/commission-settings',
        permission: 'commission_settings.view',
      },
    ],
  },
  {
    title: 'Hệ thống',
    items: [
      {
        icon: PieChartIcon,
        name: 'Phân quyền',
        path: '/admin/roles',
        permission: 'roles.view',
      },
      {
        icon: ListIcon,
        name: 'Lịch sử hoạt động',
        path: '/admin/system-logs',
        permission: 'system.logs.view',
      },
    ],
  },
  {
    title: 'Nội dung',
    items: [
      {
        icon: PageIcon,
        name: 'Tin tức',
        subItems: [
          { name: 'Bài viết',    path: '/admin/posts',          permission: 'posts.view' },
          { name: 'Danh mục',    path: '/admin/post-categories', permission: 'post_categories.view' },
          { name: 'Thẻ (Tags)',  path: '/admin/tags',            permission: 'tags.view' },
          { name: 'Bình luận',   path: '/admin/post-comments',   permission: 'comments.view' },
        ],
      },
    ],
  },
]

const hasPermission = (item: MenuItem): boolean => {
  if (!item.permission) return true
  const userRoles = adminStore.user?.roles || []
  if (userRoles.includes('super-admin')) return true
  return adminStore.user?.permissions?.includes(item.permission) ?? false
}

const menuGroups = computed(() => {
  return rawMenuGroups
    .map((group) => {
      const filteredItems = group.items
        .filter((item) => hasPermission(item))
        .map((item) => {
          if (item.subItems) {
            const filteredSub = item.subItems.filter((sub) => hasPermission(sub))
            return { ...item, subItems: filteredSub.length ? filteredSub : undefined }
          }
          return item
        })
        .filter((item) => !item.subItems || item.subItems.length > 0)
      return { ...group, items: filteredItems }
    })
    .filter((group) => group.items.length > 0)
})

const isActive = (path: string) => route.path === path || route.path.startsWith(path + '/')

const toggleSubmenu = (groupIndex: number, itemIndex: number) => {
  const key = `${groupIndex}-${itemIndex}`
  openSubmenu.value = openSubmenu.value === key ? null : key
}

const isAnySubmenuRouteActive = computed(() => {
  return menuGroups.value.some((group) =>
    group.items.some(
      (item) => item.subItems && item.subItems.some((subItem) => isActive(subItem.path!)),
    ),
  )
})

const isSubmenuOpen = (groupIndex: number, itemIndex: number) => {
  const key = `${groupIndex}-${itemIndex}`
  return (
    openSubmenu.value === key ||
    (isAnySubmenuRouteActive.value &&
      menuGroups.value[groupIndex]?.items[itemIndex]?.subItems?.some((subItem) =>
        isActive(subItem.path!),
      ))
  )
}

const startTransition = (el: Element) => {
  const htmlEl = el as HTMLElement
  htmlEl.style.height = 'auto'
  const height = htmlEl.scrollHeight
  htmlEl.style.height = '0px'
  void htmlEl.offsetHeight
  htmlEl.style.height = height + 'px'
}

const endTransition = (el: Element) => {
  const htmlEl = el as HTMLElement
  htmlEl.style.height = ''
}
</script>
```

- [ ] **Step 2: Run frontend lint**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning/e-learning-frontend && npm run lint 2>&1" | cat
```

Expected: 0 errors, 0 warnings on AppSidebar.vue.

- [ ] **Step 3: Commit**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning && git add e-learning-frontend/src/components/layout/AppSidebar.vue && git commit -m 'refactor(frontend): replace role-based sidebar with pure permission-based filtering'" | cat
```

---

## Task 8: Smoke Test After All Changes

No code changes in this task — just verification.

- [ ] **Step 1: Run full backend test suite one final time**

```bash
wsl.exe -d Ubuntu -- bash -c "php artisan test 2>&1" | cat
```

Expected: All tests pass (green).

- [ ] **Step 2: Verify DB state — count permissions**

```bash
wsl.exe -d Ubuntu -- bash -c "php artisan tinker --execute=\"echo \\\\Spatie\\\\Permission\\\\Models\\\\Permission::where('guard_name','admin')->count();\" 2>&1" | cat
```

Expected: `58` (count of new permissions defined in the seeder — 4 groups × 4 permissions for admin_users/roles/teachers/courses/course_categories/post_categories/lessons/quizzes + smaller groups).

- [ ] **Step 3: Manual browser check — login as admin**

1. Login with `admin@elearning.com` / `password`
2. Sidebar should show all groups including **Hoa hồng** (now permission-gated)
3. Navigate to `/admin/roles` — page should load (admin has `roles.view`)
4. Navigate to `/admin/payouts` — page should load (admin has `payouts.view`)

- [ ] **Step 4: Verify teacher role has no stale `users.*` permission**

```bash
wsl.exe -d Ubuntu -- bash -c "php artisan tinker --execute=\"\\$r = \\\\Spatie\\\\Permission\\\\Models\\\\Role::findByName('teacher','admin'); echo implode(PHP_EOL, \\$r->permissions->pluck('name')->sort()->values()->toArray());\" 2>&1" | cat
```

Expected output — exactly these permissions (no `users.*`, no `categories.*`):
```
course_categories.view
courses.create
courses.delete
courses.edit
courses.view
dashboard.view
lessons.create
lessons.delete
lessons.edit
lessons.view
quizzes.create
quizzes.edit
quizzes.view
```

---

## What Each Role Sees in the Sidebar (After Refactor)

| Group | Menu Item | super-admin | admin | teacher* |
|---|---|---|---|---|
| Quản trị | Dashboard | ✅ | ✅ | ✅ |
| Quản trị | Khóa học | ✅ | ✅ | ✅ |
| Quản trị | Danh mục | ✅ | ✅ | ✅ (`course_categories.view`) |
| Quản trị | Quản trị viên | ✅ | ✅ | ❌ |
| Quản trị | Giảng viên | ✅ | ✅ | ❌ |
| Quản trị | Học viên | ✅ | ✅ | ❌ |
| Kinh doanh | Đơn hàng | ✅ | ✅ | ❌ |
| Kinh doanh | Mã giảm giá | ✅ | ✅ | ❌ |
| Hoa hồng | Yêu cầu rút tiền | ✅ | ✅ | ❌ |
| Hoa hồng | Hoa hồng giảng viên | ✅ | ✅ | ❌ |
| Hoa hồng | Cài đặt tỷ lệ | ✅ | ✅ | ❌ |
| Hệ thống | Phân quyền | ✅ | ✅ | ❌ |
| Hệ thống | Lịch sử hoạt động | ✅ | ✅ | ❌ |
| Nội dung | Bài viết | ✅ | ✅ | ❌ |
| Nội dung | Danh mục (posts) | ✅ | ✅ | ❌ |
| Nội dung | Thẻ (Tags) | ✅ | ✅ | ❌ |
| Nội dung | Bình luận | ✅ | ✅ | ❌ |

*teacher role users are redirected to `/teacher/*` on login and never reach the admin panel in normal flow. This table shows what they'd see if they somehow accessed it — which is now controlled by permissions, not by `hideForRoles` hardcoding.
