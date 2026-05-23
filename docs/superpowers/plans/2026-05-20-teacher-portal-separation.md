# Teacher Portal Separation — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Create a separate teacher portal at `/teacher/*` with its own layout, login redirect, and 4 pages (Dashboard, Courses, Earnings, Profile) — entirely distinct from the admin panel at `/admin/*`.

**Architecture:** Single login page `/admin/login` redirects to `/teacher/dashboard` for users with `teacher` role, `/admin/dashboard` for admins. A new `TeacherLayout.vue` provides a simplified fixed sidebar for teachers. Four new backend endpoints under `/api/v1/teacher/*` (auth:admin + role:teacher) expose dashboard stats, courses list, and profile management. The old `/admin/teacher/earnings` child route is removed; earnings move to `/teacher/earnings`.

**Tech Stack:** Laravel 12, Nwidart Modules (Commission module), Vue 3 + TypeScript, Pinia, Tailwind CSS, Axios

---

## File Map

**Backend — new/modified:**
- Create: `Modules/Commission/app/Http/Controllers/Teacher/TeacherPortalController.php`
- Create: `Modules/Commission/app/Http/Requests/UpdateTeacherProfileRequest.php`
- Modify: `Modules/Commission/routes/api.php` — add 4 new routes
- Modify: `tests/Feature/Commission/TeacherPortalTest.php` — add 4 new tests

**Frontend — new:**
- Create: `e-learning-frontend/src/layouts/TeacherLayout.vue`
- Create: `e-learning-frontend/src/composables/useTeacherDashboard.ts`
- Create: `e-learning-frontend/src/composables/useTeacherCourses.ts`
- Create: `e-learning-frontend/src/composables/useTeacherProfile.ts`
- Create: `e-learning-frontend/src/views/teacher/TeacherDashboardPage.vue`
- Create: `e-learning-frontend/src/views/teacher/TeacherCoursesPage.vue`
- Create: `e-learning-frontend/src/views/teacher/TeacherProfilePage.vue`

**Frontend — modified:**
- `e-learning-frontend/src/services/commission.service.ts` — add 4 new API methods
- `e-learning-frontend/src/router/index.js` — add `/teacher` route group, remove old `/admin/teacher/*`, add role guard
- `e-learning-frontend/src/views/auth/AdminLoginPage.vue` — redirect teacher to `/teacher/dashboard`
- `e-learning-frontend/src/components/layout/AppSidebar.vue` — remove `showOnlyForRoles: ['teacher']` group (teacher no longer uses admin layout)

---

## Context for implementers

- **Backend CWD:** `/home/vanthanh/DATN/e-learning/e-learning-backend` (all `php artisan` commands run from here via WSL)
- **Frontend CWD:** `/home/vanthanh/DATN/e-learning/e-learning-frontend`
- **All commands:** wrap with `wsl.exe -d Ubuntu -- bash -c "..." | cat`
- **Teachers model:** namespace `Modules\Teachers\Models\Teachers`, fields: `user_id`, `name`, `slug`, `description`, `exp`, `image`, `status`, `bank_name`, `bank_account_number`, `bank_account_name` — no `bio`, no `avatar`, no direct `email`
- **Course model:** namespace `Modules\Course\Models\Course`, fields include `name`, `slug`, `price`, `sale_price`, `status`, `total_students`, `teacher_id`
- **Commission module routes:** already have `auth:admin + role:teacher` middleware group — just add to it
- **Test setup:** use `HasAdminUser` trait, create a teacher-role user and a `Teachers` record linked via `user_id`
- **EarningsPage.vue** already exists at `src/views/teacher/EarningsPage.vue` — do NOT recreate it, just change its route

---

## Task 1: Backend — Teacher Portal API endpoints

**Files:**
- Create: `e-learning-backend/Modules/Commission/app/Http/Controllers/Teacher/TeacherPortalController.php`
- Create: `e-learning-backend/Modules/Commission/app/Http/Requests/UpdateTeacherProfileRequest.php`
- Modify: `e-learning-backend/Modules/Commission/routes/api.php`
- Modify: `e-learning-backend/tests/Feature/Commission/TeacherPortalTest.php`

- [ ] **Step 1: Write failing tests**

Add to `tests/Feature/Commission/TeacherPortalTest.php` — append these 4 test methods inside the existing class:

```php
public function test_teacher_can_view_dashboard(): void
{
    $user = User::forceCreate([
        'name' => 'Teacher Dashboard Test',
        'email' => 'teacher_dash@test.com',
        'password' => bcrypt('password'),
    ]);
    $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'teacher', 'guard_name' => 'admin']);
    $user->assignRole($role);
    $teacher = \Modules\Teachers\Models\Teachers::create([
        'user_id' => $user->id, 'name' => 'Teacher Dash', 'slug' => 'teacher-dash-' . uniqid(),
        'description' => 'Bio', 'exp' => 0, 'status' => 1,
    ]);
    $this->actingAs($user, 'admin');

    $response = $this->getJson('/api/v1/teacher/dashboard');
    $response->assertStatus(200)->assertJsonPath('success', true)
        ->assertJsonStructure(['data' => ['total_courses', 'total_students', 'total_earned', 'available_balance']]);
}

public function test_teacher_can_view_own_courses(): void
{
    $user = User::forceCreate([
        'name' => 'Teacher Courses', 'email' => 'teacher_courses@test.com', 'password' => bcrypt('password'),
    ]);
    $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'teacher', 'guard_name' => 'admin']);
    $user->assignRole($role);
    $teacher = \Modules\Teachers\Models\Teachers::create([
        'user_id' => $user->id, 'name' => 'Teacher C', 'slug' => 'teacher-c-' . uniqid(),
        'description' => 'Bio', 'exp' => 0, 'status' => 1,
    ]);
    $this->actingAs($user, 'admin');

    $response = $this->getJson('/api/v1/teacher/courses');
    $response->assertStatus(200)->assertJsonPath('success', true);
}

public function test_teacher_can_view_own_profile(): void
{
    $user = User::forceCreate([
        'name' => 'Teacher Profile', 'email' => 'teacher_profile@test.com', 'password' => bcrypt('password'),
    ]);
    $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'teacher', 'guard_name' => 'admin']);
    $user->assignRole($role);
    \Modules\Teachers\Models\Teachers::create([
        'user_id' => $user->id, 'name' => 'Teacher P', 'slug' => 'teacher-p-' . uniqid(),
        'description' => 'Bio text', 'exp' => 0, 'status' => 1,
        'bank_name' => 'VCB', 'bank_account_number' => '123456', 'bank_account_name' => 'NGUYEN VAN A',
    ]);
    $this->actingAs($user, 'admin');

    $response = $this->getJson('/api/v1/teacher/profile');
    $response->assertStatus(200)->assertJsonPath('success', true)
        ->assertJsonPath('data.bank_name', 'VCB');
}

public function test_teacher_can_update_own_profile(): void
{
    $user = User::forceCreate([
        'name' => 'Teacher Update', 'email' => 'teacher_update@test.com', 'password' => bcrypt('password'),
    ]);
    $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'teacher', 'guard_name' => 'admin']);
    $user->assignRole($role);
    \Modules\Teachers\Models\Teachers::create([
        'user_id' => $user->id, 'name' => 'Teacher U', 'slug' => 'teacher-u-' . uniqid(),
        'description' => 'Old bio', 'exp' => 0, 'status' => 1,
    ]);
    $this->actingAs($user, 'admin');

    $response = $this->patchJson('/api/v1/teacher/profile', [
        'description' => 'New bio text',
        'bank_name' => 'Techcombank',
        'bank_account_number' => '9876543210',
        'bank_account_name' => 'NGUYEN VAN B',
    ]);
    $response->assertStatus(200)->assertJsonPath('success', true)
        ->assertJsonPath('data.bank_name', 'Techcombank');

    $this->assertDatabaseHas('teachers', ['user_id' => $user->id, 'bank_name' => 'Techcombank']);
}
```

- [ ] **Step 2: Run tests — confirm they fail**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning/e-learning-backend && php artisan test tests/Feature/Commission/TeacherPortalTest.php --filter='test_teacher_can_view_dashboard|test_teacher_can_view_own_courses|test_teacher_can_view_own_profile|test_teacher_can_update_own_profile' 2>&1" | cat
```

Expected: FAIL — `Route [api/v1/teacher/dashboard] not defined` or 404.

- [ ] **Step 3: Create `UpdateTeacherProfileRequest.php`**

```php
<?php

namespace Modules\Commission\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateTeacherProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'description'          => 'nullable|string|max:1000',
            'bank_name'            => 'nullable|string|max:255',
            'bank_account_number'  => 'nullable|string|max:50',
            'bank_account_name'    => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'description.max'         => 'Giới thiệu không được vượt quá 1000 ký tự.',
            'bank_name.max'           => 'Tên ngân hàng không được vượt quá 255 ký tự.',
            'bank_account_number.max' => 'Số tài khoản không được vượt quá 50 ký tự.',
            'bank_account_name.max'   => 'Tên chủ tài khoản không được vượt quá 255 ký tự.',
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

- [ ] **Step 4: Create `TeacherPortalController.php`**

```php
<?php

namespace Modules\Commission\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Commission\Http\Requests\UpdateTeacherProfileRequest;
use Modules\Commission\Repositories\CommissionRepositoryInterface;
use Modules\Course\Models\Course;
use Modules\Teachers\Models\Teachers;

class TeacherPortalController extends Controller
{
    use ApiResponse;

    public function __construct(private CommissionRepositoryInterface $repository) {}

    private function getTeacher(): Teachers
    {
        return Teachers::where('user_id', auth('admin')->id())->firstOrFail();
    }

    public function dashboard(): JsonResponse
    {
        $teacher = $this->getTeacher();

        return $this->success([
            'total_courses'     => Course::where('teacher_id', $teacher->id)->count(),
            'total_students'    => (int) Course::where('teacher_id', $teacher->id)->sum('total_students'),
            'total_earned'      => $this->repository->getTotalEarned($teacher->id),
            'available_balance' => $this->repository->getAvailableBalance($teacher->id),
        ]);
    }

    public function courses(Request $request): JsonResponse
    {
        $teacher = $this->getTeacher();
        $perPage = max(1, min((int) $request->query('per_page', 15), 100));

        $courses = Course::where('teacher_id', $teacher->id)
            ->with('categories')
            ->latest()
            ->paginate($perPage);

        return $this->paginated($courses);
    }

    public function profile(): JsonResponse
    {
        $teacher = $this->getTeacher();

        return $this->success([
            'id'                   => $teacher->id,
            'name'                 => $teacher->name,
            'description'          => $teacher->description,
            'image'                => $teacher->image ? asset('storage/'.$teacher->image) : null,
            'bank_name'            => $teacher->bank_name,
            'bank_account_number'  => $teacher->bank_account_number,
            'bank_account_name'    => $teacher->bank_account_name,
        ]);
    }

    public function updateProfile(UpdateTeacherProfileRequest $request): JsonResponse
    {
        $teacher = $this->getTeacher();
        $teacher->update($request->validated());

        return $this->success([
            'id'                   => $teacher->id,
            'name'                 => $teacher->name,
            'description'          => $teacher->description,
            'image'                => $teacher->image ? asset('storage/'.$teacher->image) : null,
            'bank_name'            => $teacher->bank_name,
            'bank_account_number'  => $teacher->bank_account_number,
            'bank_account_name'    => $teacher->bank_account_name,
        ], 'Cập nhật hồ sơ thành công.');
    }
}
```

- [ ] **Step 5: Add 4 new routes to `Modules/Commission/routes/api.php`**

Add inside the existing `role:teacher` middleware group (after `Route::post('payouts', ...)`):

```php
Route::get('dashboard', [TeacherPortalController::class, 'dashboard']);
Route::get('courses', [TeacherPortalController::class, 'courses']);
Route::get('profile', [TeacherPortalController::class, 'profile']);
Route::patch('profile', [TeacherPortalController::class, 'updateProfile']);
```

Also add the import at the top:

```php
use Modules\Commission\Http\Controllers\Teacher\TeacherPortalController;
```

Full updated file:

```php
<?php

use Illuminate\Support\Facades\Route;
use Modules\Commission\Http\Controllers\Admin\CommissionSettingsController;
use Modules\Commission\Http\Controllers\Admin\PayoutController;
use Modules\Commission\Http\Controllers\Admin\TeacherEarningsController;
use Modules\Commission\Http\Controllers\Teacher\EarningsController;
use Modules\Commission\Http\Controllers\Teacher\TeacherPortalController;

Route::middleware(['auth:admin'])->prefix('admin')->group(function () {
    Route::get('commission-settings', [CommissionSettingsController::class, 'show']);
    Route::patch('commission-settings', [CommissionSettingsController::class, 'update']);

    Route::get('payouts', [PayoutController::class, 'index']);
    Route::patch('payouts/{id}/approve', [PayoutController::class, 'approve']);
    Route::patch('payouts/{id}/reject', [PayoutController::class, 'reject']);
    Route::patch('payouts/{id}/mark-paid', [PayoutController::class, 'markPaid']);

    Route::get('teacher-earnings', [TeacherEarningsController::class, 'index']);
});

Route::middleware(['auth:admin', 'role:teacher'])->prefix('teacher')->group(function () {
    Route::get('earnings', [EarningsController::class, 'index']);
    Route::get('payouts', [EarningsController::class, 'myPayouts']);
    Route::post('payouts', [EarningsController::class, 'requestPayout']);

    Route::get('dashboard', [TeacherPortalController::class, 'dashboard']);
    Route::get('courses', [TeacherPortalController::class, 'courses']);
    Route::get('profile', [TeacherPortalController::class, 'profile']);
    Route::patch('profile', [TeacherPortalController::class, 'updateProfile']);
});
```

- [ ] **Step 6: Run tests — confirm they pass**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning/e-learning-backend && php artisan test tests/Feature/Commission/TeacherPortalTest.php 2>&1" | cat
```

Expected: All tests PASS (now 7 total).

- [ ] **Step 7: Run pint**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning/e-learning-backend && ./vendor/bin/pint Modules/Commission/app/Http/Controllers/Teacher/TeacherPortalController.php Modules/Commission/app/Http/Requests/UpdateTeacherProfileRequest.php Modules/Commission/routes/api.php 2>&1" | cat
```

- [ ] **Step 8: Commit**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning && git add e-learning-backend/Modules/Commission/app/Http/Controllers/Teacher/TeacherPortalController.php e-learning-backend/Modules/Commission/app/Http/Requests/UpdateTeacherProfileRequest.php e-learning-backend/Modules/Commission/routes/api.php e-learning-backend/tests/Feature/Commission/TeacherPortalTest.php && git commit -m 'feat(backend): add teacher portal API endpoints (dashboard, courses, profile)'" | cat
```

---

## Task 2: Frontend — commission.service.ts additions

**Files:**
- Modify: `e-learning-frontend/src/services/commission.service.ts`

- [ ] **Step 1: Add 4 new methods to `commission.service.ts`**

Append inside the `commissionService` object (after `requestPayout`):

```typescript
  // Teacher portal extended
  getTeacherDashboard: () =>
    http.get('/teacher/dashboard'),
  getTeacherCourses: (params: Record<string, unknown>) =>
    http.get('/teacher/courses', { params }),
  getTeacherProfile: () =>
    http.get('/teacher/profile'),
  updateTeacherProfile: (data: { description?: string; bank_name?: string; bank_account_number?: string; bank_account_name?: string }) =>
    http.patch('/teacher/profile', data),
```

- [ ] **Step 2: Commit**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning && git add e-learning-frontend/src/services/commission.service.ts && git commit -m 'feat(frontend): add teacher dashboard, courses and profile API calls to commission service'" | cat
```

---

## Task 3: Frontend — TeacherLayout + Router restructure + Login redirect

**Files:**
- Create: `e-learning-frontend/src/layouts/TeacherLayout.vue`
- Modify: `e-learning-frontend/src/router/index.js`
- Modify: `e-learning-frontend/src/views/auth/AdminLoginPage.vue`
- Modify: `e-learning-frontend/src/components/layout/AppSidebar.vue`

- [ ] **Step 1: Create `TeacherLayout.vue`**

Create `e-learning-frontend/src/layouts/TeacherLayout.vue`:

```vue
<template>
  <div class="min-h-screen flex bg-gray-50 dark:bg-gray-950">
    <!-- Sidebar -->
    <aside
      class="w-[220px] min-h-screen bg-white dark:bg-gray-900 border-r border-gray-200 dark:border-gray-700 flex flex-col fixed top-0 left-0 z-50"
    >
      <!-- Logo -->
      <div class="p-5 border-b border-gray-200 dark:border-gray-700">
        <router-link to="/teacher/dashboard">
          <img src="/images/logo/logo.svg" alt="EduLearn" class="dark:hidden" width="120" />
          <img
            src="/images/logo/logo-dark.svg"
            alt="EduLearn"
            class="hidden dark:block"
            width="120"
          />
        </router-link>
        <p class="mt-2 text-xs text-blue-600 dark:text-blue-400 font-medium">Cổng giảng viên</p>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 p-4 overflow-y-auto">
        <p class="text-xs text-gray-400 uppercase mb-3 px-2 font-semibold tracking-wider">Menu</p>
        <ul class="space-y-1">
          <li v-for="item in menuItems" :key="item.path">
            <router-link
              :to="item.path"
              :class="[
                'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors',
                isActive(item.path)
                  ? 'bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-400'
                  : 'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800',
              ]"
            >
              <component :is="item.icon" class="w-5 h-5 flex-shrink-0" />
              <span>{{ item.name }}</span>
            </router-link>
          </li>
        </ul>
      </nav>

      <!-- User info + logout -->
      <div class="p-4 border-t border-gray-200 dark:border-gray-700">
        <div class="flex items-center gap-3 mb-3 px-1">
          <div
            class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center text-blue-700 dark:text-blue-300 text-sm font-bold flex-shrink-0"
          >
            {{ userInitial }}
          </div>
          <span
            class="text-sm font-medium text-gray-700 dark:text-gray-300 truncate"
            :title="userName"
          >
            {{ userName }}
          </span>
        </div>
        <button
          @click="handleLogout"
          class="w-full flex items-center gap-2 px-3 py-2 text-sm text-gray-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
        >
          <LogoutIcon class="w-4 h-4" />
          Đăng xuất
        </button>
      </div>
    </aside>

    <!-- Main content -->
    <div class="flex-1 ml-[220px]">
      <div class="p-6 max-w-screen-xl mx-auto">
        <router-view />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAdminAuthStore } from '@/stores/adminAuth.store'
import {
  GridIcon,
  BoxCubeIcon,
  BarChartIcon,
  UserCircleIcon,
  LogoutIcon,
} from '@/components/icons'

const route = useRoute()
const router = useRouter()
const adminStore = useAdminAuthStore()

const menuItems = [
  { name: 'Tổng quan', path: '/teacher/dashboard', icon: GridIcon },
  { name: 'Khóa học của tôi', path: '/teacher/courses', icon: BoxCubeIcon },
  { name: 'Thu nhập', path: '/teacher/earnings', icon: BarChartIcon },
  { name: 'Hồ sơ cá nhân', path: '/teacher/profile', icon: UserCircleIcon },
]

const isActive = (path: string) => route.path === path || route.path.startsWith(path + '/')
const userName = computed(() => adminStore.user?.name || 'Giảng viên')
const userInitial = computed(() => userName.value.charAt(0).toUpperCase())

async function handleLogout() {
  await adminStore.logout()
  router.push('/admin/login')
}
</script>
```

- [ ] **Step 2: Update router — add `/teacher` group, remove old `/admin/teacher/*`**

In `e-learning-frontend/src/router/index.js`:

1. Import `TeacherLayout` at the top (after AdminLayout import):
```js
import TeacherLayout from '@/layouts/TeacherLayout.vue'
```

2. Remove this block from the `/admin` children array:
```js
// Teacher portal
{
  path: 'teacher/earnings',
  name: 'teacher.earnings',
  component: () => import('@/views/teacher/EarningsPage.vue'),
  meta: { requiresAuth: true, guard: 'admin' },
},
```

3. Add a new top-level route group for `/teacher` (place it BEFORE the `// ── CLIENT ──` section):
```js
// ── TEACHER PORTAL ─────────────────────────────────────
{
  path: '/teacher',
  component: TeacherLayout,
  meta: { requiresAuth: true, guard: 'admin', role: 'teacher' },
  children: [
    { path: '', redirect: '/teacher/dashboard' },
    {
      path: 'dashboard',
      name: 'teacher.dashboard',
      component: () => import('@/views/teacher/TeacherDashboardPage.vue'),
    },
    {
      path: 'courses',
      name: 'teacher.courses',
      component: () => import('@/views/teacher/TeacherCoursesPage.vue'),
    },
    {
      path: 'earnings',
      name: 'teacher.earnings',
      component: () => import('@/views/teacher/EarningsPage.vue'),
    },
    {
      path: 'profile',
      name: 'teacher.profile',
      component: () => import('@/views/teacher/TeacherProfilePage.vue'),
    },
  ],
},
```

4. Add role guard inside `router.beforeEach` — add this block AFTER the permission guard block:
```js
// Role guard — required role on top of auth
if (adminStore && adminStore.isLoggedIn && to.meta.role) {
  const userRoles = adminStore.user?.roles || []
  if (!userRoles.includes(to.meta.role as string)) {
    return '/403'
  }
}
```

- [ ] **Step 3: Update `AdminLoginPage.vue` — redirect teachers to `/teacher/dashboard`**

Replace the `getFirstAccessibleRoute` function (around line 287):

```js
function getFirstAccessibleRoute(): string {
  // Teacher role → direct to teacher portal
  if (adminStore.user?.roles?.includes('teacher')) {
    return '/teacher/dashboard'
  }
  // Admin/super-admin → first accessible admin route
  for (const route of ADMIN_ROUTES) {
    if (adminStore.hasPermission(route.permission)) return route.path
  }
  return '/admin/dashboard'
}
```

- [ ] **Step 4: Update `AppSidebar.vue` — remove the `showOnlyForRoles: ['teacher']` group**

In `AppSidebar.vue`, remove the entire last menu group from `rawMenuGroups`:

```js
// Remove this entire object from rawMenuGroups:
{
  title: 'Thu nhập',
  showOnlyForRoles: ['teacher'],
  items: [
    {
      icon: BarChartIcon,
      name: 'Thu nhập của tôi',
      path: '/admin/teacher/earnings',
    },
  ],
},
```

Teachers no longer use the admin sidebar at all — they have their own `TeacherLayout`.

- [ ] **Step 5: Build to check for errors**

```bash
wsl.exe -d Ubuntu -- bash -c "export PATH=/home/vanthanh/.nvm/versions/node/v25.9.0/bin:$PATH && cd /home/vanthanh/DATN/e-learning/e-learning-frontend && npm run build 2>&1 | tail -10" | cat
```

Expected: `✓ built in X.XXs` with no errors. If there are import errors for `TeacherDashboardPage.vue` or `TeacherCoursesPage.vue` or `TeacherProfilePage.vue`, create empty placeholder Vue files first:

```vue
<!-- placeholder — replace in later tasks -->
<template><div>Loading...</div></template>
<script setup lang="ts"></script>
```

- [ ] **Step 6: Commit**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning && git add e-learning-frontend/src/layouts/TeacherLayout.vue e-learning-frontend/src/router/index.js e-learning-frontend/src/views/auth/AdminLoginPage.vue e-learning-frontend/src/components/layout/AppSidebar.vue && git commit -m 'feat(frontend): add TeacherLayout and separate /teacher router group with role-based login redirect'" | cat
```

---

## Task 4: Frontend — Teacher Dashboard page

**Files:**
- Create: `e-learning-frontend/src/composables/useTeacherDashboard.ts`
- Create: `e-learning-frontend/src/views/teacher/TeacherDashboardPage.vue`

- [ ] **Step 1: Create `useTeacherDashboard.ts`**

```typescript
import { ref } from 'vue'
import { commissionService } from '@/services/commission.service'

interface TeacherDashboardData {
  total_courses: number
  total_students: number
  total_earned: number
  available_balance: number
}

export function useTeacherDashboard() {
  const stats = ref<TeacherDashboardData>({
    total_courses: 0,
    total_students: 0,
    total_earned: 0,
    available_balance: 0,
  })
  const loading = ref(false)

  async function loadDashboard() {
    loading.value = true
    try {
      const res = await commissionService.getTeacherDashboard()
      stats.value = res.data.data
    } finally {
      loading.value = false
    }
  }

  return { stats, loading, loadDashboard }
}
```

- [ ] **Step 2: Create `TeacherDashboardPage.vue`**

```vue
<template>
  <div>
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Tổng quan</h1>

    <div v-if="loading" class="text-center py-12 text-gray-400">Đang tải...</div>

    <template v-else>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
          <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Khóa học</p>
          <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ stats.total_courses }}</p>
          <p class="text-xs text-gray-400 mt-1">khóa đã tạo</p>
        </div>
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
          <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Học viên</p>
          <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ stats.total_students.toLocaleString('vi-VN') }}</p>
          <p class="text-xs text-gray-400 mt-1">học viên đã đăng ký</p>
        </div>
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-green-200 dark:border-green-800 p-5 bg-green-50 dark:bg-green-900/10">
          <p class="text-xs text-green-600 uppercase font-semibold mb-1">Số dư khả dụng</p>
          <p class="text-3xl font-bold text-green-700 dark:text-green-400">
            {{ Number(stats.available_balance).toLocaleString('vi-VN') }} ₫
          </p>
          <p class="text-xs text-gray-400 mt-1">sẵn sàng rút</p>
        </div>
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-blue-200 dark:border-blue-800 p-5 bg-blue-50 dark:bg-blue-900/10">
          <p class="text-xs text-blue-600 uppercase font-semibold mb-1">Tổng đã kiếm</p>
          <p class="text-3xl font-bold text-blue-700 dark:text-blue-400">
            {{ Number(stats.total_earned).toLocaleString('vi-VN') }} ₫
          </p>
          <p class="text-xs text-gray-400 mt-1">tổng hoa hồng</p>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <router-link
          to="/teacher/earnings"
          class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 p-5 hover:border-blue-300 transition-colors group"
        >
          <h3 class="font-semibold text-gray-900 dark:text-white group-hover:text-blue-700 mb-1">Thu nhập của tôi</h3>
          <p class="text-sm text-gray-500">Xem lịch sử hoa hồng và yêu cầu rút tiền</p>
        </router-link>
        <router-link
          to="/teacher/courses"
          class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 p-5 hover:border-blue-300 transition-colors group"
        >
          <h3 class="font-semibold text-gray-900 dark:text-white group-hover:text-blue-700 mb-1">Khóa học của tôi</h3>
          <p class="text-sm text-gray-500">Xem danh sách các khóa học bạn giảng dạy</p>
        </router-link>
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import { useTeacherDashboard } from '@/composables/useTeacherDashboard'

const { stats, loading, loadDashboard } = useTeacherDashboard()
onMounted(() => loadDashboard())
</script>
```

- [ ] **Step 3: Build check**

```bash
wsl.exe -d Ubuntu -- bash -c "export PATH=/home/vanthanh/.nvm/versions/node/v25.9.0/bin:$PATH && cd /home/vanthanh/DATN/e-learning/e-learning-frontend && npm run build 2>&1 | tail -5" | cat
```

Expected: `✓ built in X.XXs`

- [ ] **Step 4: Commit**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning && git add e-learning-frontend/src/composables/useTeacherDashboard.ts e-learning-frontend/src/views/teacher/TeacherDashboardPage.vue && git commit -m 'feat(frontend): add teacher dashboard page with course and earnings stats'" | cat
```

---

## Task 5: Frontend — Teacher Courses page

**Files:**
- Create: `e-learning-frontend/src/composables/useTeacherCourses.ts`
- Create: `e-learning-frontend/src/views/teacher/TeacherCoursesPage.vue`

- [ ] **Step 1: Create `useTeacherCourses.ts`**

```typescript
import { ref, reactive } from 'vue'
import { commissionService } from '@/services/commission.service'

interface TeacherCourse {
  id: number
  name: string
  slug: string
  price: number
  sale_price: number | null
  status: number
  total_students: number
}

interface Pagination {
  current_page: number
  last_page: number
  per_page: number
  total: number
}

export function useTeacherCourses() {
  const courses = ref<TeacherCourse[]>([])
  const pagination = ref<Pagination>({ current_page: 1, last_page: 1, per_page: 15, total: 0 })
  const loading = ref(false)
  const filters = reactive({ page: 1, per_page: 15 })

  async function loadCourses() {
    loading.value = true
    try {
      const res = await commissionService.getTeacherCourses(filters)
      courses.value = res.data.data
      pagination.value = res.data.pagination
    } finally {
      loading.value = false
    }
  }

  function changePage(page: number) {
    filters.page = page
    loadCourses()
  }

  return { courses, pagination, loading, loadCourses, changePage }
}
```

- [ ] **Step 2: Create `TeacherCoursesPage.vue`**

```vue
<template>
  <div>
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Khóa học của tôi</h1>

    <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-400">
          <tr>
            <th class="px-5 py-3 text-left font-semibold">Tên khóa học</th>
            <th class="px-5 py-3 text-right font-semibold">Học viên</th>
            <th class="px-5 py-3 text-right font-semibold">Giá</th>
            <th class="px-5 py-3 text-center font-semibold">Trạng thái</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading">
            <td colspan="4" class="px-5 py-10 text-center text-gray-400">Đang tải...</td>
          </tr>
          <tr v-else-if="!courses.length">
            <td colspan="4" class="px-5 py-10 text-center text-gray-400">Chưa có khóa học nào.</td>
          </tr>
          <tr
            v-for="course in courses"
            :key="course.id"
            class="border-t border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/50"
          >
            <td class="px-5 py-3">
              <p class="font-medium text-gray-900 dark:text-white">{{ course.name }}</p>
              <p class="text-xs text-gray-400">{{ course.slug }}</p>
            </td>
            <td class="px-5 py-3 text-right text-gray-700 dark:text-gray-300">
              {{ course.total_students.toLocaleString('vi-VN') }}
            </td>
            <td class="px-5 py-3 text-right">
              <span v-if="course.sale_price" class="text-green-700 dark:text-green-400 font-medium">
                {{ Number(course.sale_price).toLocaleString('vi-VN') }} ₫
              </span>
              <span v-else class="text-gray-700 dark:text-gray-300 font-medium">
                {{ Number(course.price).toLocaleString('vi-VN') }} ₫
              </span>
            </td>
            <td class="px-5 py-3 text-center">
              <span
                :class="[
                  'inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium',
                  course.status === 1
                    ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                    : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
                ]"
              >
                {{ course.status === 1 ? 'Đã xuất bản' : 'Bản nháp' }}
              </span>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div
        v-if="pagination.last_page > 1"
        class="px-5 py-3 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between text-sm text-gray-500"
      >
        <span>Tổng {{ pagination.total }} khóa học</span>
        <div class="flex gap-1">
          <button
            v-for="page in pagination.last_page"
            :key="page"
            @click="changePage(page)"
            :class="[
              'px-3 py-1 rounded text-sm',
              page === pagination.current_page
                ? 'bg-blue-600 text-white'
                : 'hover:bg-gray-100 dark:hover:bg-gray-700',
            ]"
          >
            {{ page }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import { useTeacherCourses } from '@/composables/useTeacherCourses'

const { courses, pagination, loading, loadCourses, changePage } = useTeacherCourses()
onMounted(() => loadCourses())
</script>
```

- [ ] **Step 3: Build check**

```bash
wsl.exe -d Ubuntu -- bash -c "export PATH=/home/vanthanh/.nvm/versions/node/v25.9.0/bin:$PATH && cd /home/vanthanh/DATN/e-learning/e-learning-frontend && npm run build 2>&1 | tail -5" | cat
```

Expected: `✓ built in X.XXs`

- [ ] **Step 4: Commit**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning && git add e-learning-frontend/src/composables/useTeacherCourses.ts e-learning-frontend/src/views/teacher/TeacherCoursesPage.vue && git commit -m 'feat(frontend): add teacher courses page'" | cat
```

---

## Task 6: Frontend — Teacher Profile page

**Files:**
- Create: `e-learning-frontend/src/composables/useTeacherProfile.ts`
- Create: `e-learning-frontend/src/views/teacher/TeacherProfilePage.vue`

- [ ] **Step 1: Create `useTeacherProfile.ts`**

```typescript
import { ref } from 'vue'
import { useToast } from 'vue-toastification'
import { commissionService } from '@/services/commission.service'

interface TeacherProfile {
  id: number
  name: string
  description: string | null
  image: string | null
  bank_name: string | null
  bank_account_number: string | null
  bank_account_name: string | null
}

export function useTeacherProfile() {
  const profile = ref<TeacherProfile | null>(null)
  const loading = ref(false)
  const saving = ref(false)
  const toast = useToast()

  async function loadProfile() {
    loading.value = true
    try {
      const res = await commissionService.getTeacherProfile()
      profile.value = res.data.data
    } finally {
      loading.value = false
    }
  }

  async function saveProfile(data: {
    description?: string
    bank_name?: string
    bank_account_number?: string
    bank_account_name?: string
  }): Promise<boolean> {
    saving.value = true
    try {
      const res = await commissionService.updateTeacherProfile(data)
      profile.value = res.data.data
      toast.success('Cập nhật hồ sơ thành công!')
      return true
    } catch (err: unknown) {
      const e = err as { response?: { data?: { message?: string } } }
      toast.error(e.response?.data?.message || 'Có lỗi xảy ra.')
      return false
    } finally {
      saving.value = false
    }
  }

  return { profile, loading, saving, loadProfile, saveProfile }
}
```

- [ ] **Step 2: Create `TeacherProfilePage.vue`**

```vue
<template>
  <div>
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Hồ sơ cá nhân</h1>

    <div v-if="loading" class="text-center py-12 text-gray-400">Đang tải...</div>

    <div v-else-if="profile" class="max-w-2xl space-y-6">
      <!-- Basic info (read-only) -->
      <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
        <h2 class="font-semibold text-gray-900 dark:text-white mb-4">Thông tin cơ bản</h2>
        <div class="flex items-center gap-4 mb-4">
          <div class="w-16 h-16 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center text-2xl font-bold text-blue-700 dark:text-blue-300">
            {{ profile.name.charAt(0).toUpperCase() }}
          </div>
          <div>
            <p class="font-semibold text-gray-900 dark:text-white text-lg">{{ profile.name }}</p>
            <p class="text-sm text-gray-500">Giảng viên</p>
          </div>
        </div>
        <div class="mb-3">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Giới thiệu bản thân</label>
          <textarea
            v-model="form.description"
            rows="3"
            placeholder="Mô tả ngắn về bản thân, kinh nghiệm giảng dạy..."
            class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>
      </div>

      <!-- Bank info -->
      <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
        <h2 class="font-semibold text-gray-900 dark:text-white mb-1">Thông tin ngân hàng</h2>
        <p class="text-xs text-gray-500 mb-4">Dùng để Admin chuyển khoản khi duyệt yêu cầu rút tiền</p>
        <div class="space-y-3">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tên ngân hàng</label>
            <input
              v-model="form.bank_name"
              type="text"
              placeholder="VD: Vietcombank, Techcombank..."
              class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Số tài khoản</label>
            <input
              v-model="form.bank_account_number"
              type="text"
              placeholder="Số tài khoản ngân hàng"
              class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tên chủ tài khoản</label>
            <input
              v-model="form.bank_account_name"
              type="text"
              placeholder="VD: NGUYEN VAN A (viết hoa, không dấu)"
              class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>
        </div>
      </div>

      <div class="flex justify-end">
        <button
          @click="submit"
          :disabled="saving"
          class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition-colors disabled:opacity-50"
        >
          {{ saving ? 'Đang lưu...' : 'Lưu thay đổi' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { reactive, onMounted, watch } from 'vue'
import { useTeacherProfile } from '@/composables/useTeacherProfile'

const { profile, loading, saving, loadProfile, saveProfile } = useTeacherProfile()

const form = reactive({
  description: '',
  bank_name: '',
  bank_account_number: '',
  bank_account_name: '',
})

watch(profile, (p) => {
  if (p) {
    form.description = p.description || ''
    form.bank_name = p.bank_name || ''
    form.bank_account_number = p.bank_account_number || ''
    form.bank_account_name = p.bank_account_name || ''
  }
}, { immediate: true })

async function submit() {
  await saveProfile({ ...form })
}

onMounted(() => loadProfile())
</script>
```

- [ ] **Step 3: Build check**

```bash
wsl.exe -d Ubuntu -- bash -c "export PATH=/home/vanthanh/.nvm/versions/node/v25.9.0/bin:$PATH && cd /home/vanthanh/DATN/e-learning/e-learning-frontend && npm run build 2>&1 | tail -5" | cat
```

Expected: `✓ built in X.XXs`

- [ ] **Step 4: Commit**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning && git add e-learning-frontend/src/composables/useTeacherProfile.ts e-learning-frontend/src/views/teacher/TeacherProfilePage.vue && git commit -m 'feat(frontend): add teacher profile page with bio and bank info editing'" | cat
```

---

## Self-Review Checklist

- [x] **Spec coverage:**
  - ✅ Login chung `/admin/login` → redirect by role
  - ✅ `/teacher/dashboard` — stats page
  - ✅ `/teacher/courses` — teacher's own courses
  - ✅ `/teacher/earnings` — existing page, new route
  - ✅ `/teacher/profile` — bio + bank info
  - ✅ `TeacherLayout.vue` — separate layout with teacher sidebar
  - ✅ Backend endpoints for all 4 teacher portal pages
- [x] **No placeholders** — all code is complete
- [x] **Type consistency** — `TeacherProfile` interface matches backend response shape; `TeacherCourse` matches Course fields
- [x] **Teachers model field names** — uses `description` (not bio), `image` (not avatar), no direct email field
- [x] **YAGNI** — no edit/delete for courses (read-only list only); no avatar upload
