# Laravel Pennant Feature Flags Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Tích hợp Laravel Pennant với admin UI cho phép Super Admin bật/tắt 3 feature flags (`ai-quiz`, `hls-transcoding`, `payout-requests`) trực tiếp từ giao diện.

**Architecture:** `laravel/pennant` lưu trạng thái flag vào DB (bảng `features`). `FeatureFlagController` đặt trong `app/Http/Controllers/Admin/` và routes trong `routes/api.php` vì flags là cross-cutting concern. Frontend dùng toggle card UI trong `FeatureFlagsPage.vue` với optimistic update.

**Tech Stack:** `laravel/pennant` ^1.0, Spatie Permission, Vue 3 + TypeScript, Axios

---

## File Map

| File | Action | Vai trò |
|------|--------|---------|
| `e-learning-backend/` | | |
| `app/Http/Controllers/Admin/FeatureFlagController.php` | Tạo | GET list + PATCH toggle |
| `app/Providers/AppServiceProvider.php` | Sửa | Thêm Feature::define() |
| `routes/api.php` | Sửa | Thêm 2 admin routes |
| `Modules/Users/database/seeders/RolePermissionSeeder.php` | Sửa | Thêm 2 permissions |
| `Modules/Quiz/app/Http/Controllers/Admin/AdminQuizController.php` | Sửa | Thêm flag check generate() |
| `Modules/Upload/app/Services/UploadService.php` | Sửa | Thêm flag check uploadVideo() |
| `Modules/Commission/app/Http/Controllers/Admin/PayoutController.php` | Sửa | Thêm flag check x3 methods |
| `tests/Feature/Admin/FeatureFlagTest.php` | Tạo | Feature tests |
| `e-learning-frontend/` | | |
| `src/services/featureFlag.service.ts` | Tạo | HTTP calls |
| `src/composables/useFeatureFlags.ts` | Tạo | State + toggle logic |
| `src/views/admin/FeatureFlagsPage.vue` | Tạo | Toggle card UI |
| `src/router/index.js` | Sửa | Thêm route |
| `src/components/layout/AppSidebar.vue` | Sửa | Thêm menu item |
| `src/components/icons/index.ts` | Sửa | Export FlagIcon |

---

## Task 1: Cài đặt Laravel Pennant và migrate

**Files:**
- Auto-update: `e-learning-backend/composer.json`

- [ ] **Step 1: Cài package**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning/e-learning-backend && composer require laravel/pennant 2>&1" | cat
```

Expected: `Installing laravel/pennant (v1.x.x)`

- [ ] **Step 2: Publish config và migrate**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning/e-learning-backend && php artisan vendor:publish --provider='Laravel\Pennant\PennantServiceProvider' && php artisan migrate 2>&1" | cat
```

Expected: Migration chạy tạo bảng `features`.

- [ ] **Step 3: Xác nhận bảng features tồn tại**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning/e-learning-backend && php artisan tinker --execute=\"echo Schema::hasTable('features') ? 'OK' : 'MISSING';\" 2>&1" | cat
```

Expected: `OK`

---

## Task 2: Định nghĩa flags và thêm permissions

**Files:**
- Sửa: `e-learning-backend/app/Providers/AppServiceProvider.php`
- Sửa: `e-learning-backend/Modules/Users/database/seeders/RolePermissionSeeder.php`

- [ ] **Step 1: Thêm Feature::define() vào AppServiceProvider**

Mở `app/Providers/AppServiceProvider.php`. Thêm import và 3 define vào `boot()`:

```php
<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Laravel\Pennant\Feature;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Grant all permissions to super-admin.
        try {
            Gate::before(function ($user, $ability) {
                return $user->hasRole('super-admin') ? true : null;
            });
        } catch (\Exception $e) {
            //
        }

        Feature::define('ai-quiz', true);
        Feature::define('hls-transcoding', true);
        Feature::define('payout-requests', true);
    }
}
```

- [ ] **Step 2: Thêm 2 permissions vào RolePermissionSeeder**

Mở `Modules/Users/database/seeders/RolePermissionSeeder.php`. Thêm 2 dòng vào mảng `$permissions` sau `'system.logs.view', 'system.logs.delete',`:

```php
// Feature flags (super-admin only — assigned via Gate::before)
'feature_flags.view', 'feature_flags.update',
```

File sau khi sửa (phần permissions):

```php
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
    'orders.view', 'orders.edit', 'orders.export',
    'coupons.view', 'coupons.create', 'coupons.edit', 'coupons.delete',
    // Students
    'students.view', 'students.edit',
    // Posts / News
    'posts.view', 'posts.create', 'posts.edit', 'posts.delete',
    'tags.view', 'tags.create', 'tags.edit', 'tags.delete',
    'comments.view', 'comments.delete',
    // Commission module
    'payouts.view', 'payouts.approve', 'payouts.export',
    'teacher_earnings.view', 'teacher_earnings.export',
    'commission_settings.view', 'commission_settings.update',
    // Dashboard
    'dashboard.view',
    // System logs
    'system.logs.view', 'system.logs.delete',
    // Feature flags (super-admin only — assigned via Gate::before)
    'feature_flags.view', 'feature_flags.update',
];
```

- [ ] **Step 3: Chạy seeder để áp dụng permissions**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning/e-learning-backend && php artisan db:seed --class='Modules\Users\Database\Seeders\RolePermissionSeeder' 2>&1" | cat
```

Expected: Chạy không lỗi.

- [ ] **Step 4: Xác nhận permissions tồn tại trong DB**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning/e-learning-backend && php artisan tinker --execute=\"echo \Spatie\Permission\Models\Permission::where('name', 'like', 'feature_flags%')->count();\" 2>&1" | cat
```

Expected: `2`

---

## Task 3: TDD — FeatureFlagController

**Files:**
- Tạo: `e-learning-backend/tests/Feature/Admin/FeatureFlagTest.php`
- Tạo: `e-learning-backend/app/Http/Controllers/Admin/FeatureFlagController.php`
- Sửa: `e-learning-backend/routes/api.php`

- [ ] **Step 1: Viết failing tests**

Tạo `tests/Feature/Admin/FeatureFlagTest.php`:

```php
<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;
use Tests\TestCase;
use Tests\Traits\HasAdminUser;

class FeatureFlagTest extends TestCase
{
    use RefreshDatabase, HasAdminUser;

    protected function setUp(): void
    {
        parent::setUp();
        Feature::define('ai-quiz', true);
        Feature::define('hls-transcoding', true);
        Feature::define('payout-requests', true);
    }

    public function test_super_admin_can_list_feature_flags(): void
    {
        $this->setupAdmin();

        $response = $this->getJson('/api/v1/admin/feature-flags');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data');
    }

    public function test_list_returns_correct_flag_keys(): void
    {
        $this->setupAdmin();

        $response = $this->getJson('/api/v1/admin/feature-flags');

        $keys = collect($response->json('data'))->pluck('key')->toArray();
        $this->assertContains('ai-quiz', $keys);
        $this->assertContains('hls-transcoding', $keys);
        $this->assertContains('payout-requests', $keys);
    }

    public function test_super_admin_can_deactivate_flag(): void
    {
        $this->setupAdmin();

        $response = $this->patchJson('/api/v1/admin/feature-flags/ai-quiz', ['active' => false]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertFalse(Feature::active('ai-quiz'));
    }

    public function test_super_admin_can_activate_flag(): void
    {
        $this->setupAdmin();
        Feature::deactivate('ai-quiz');

        $response = $this->patchJson('/api/v1/admin/feature-flags/ai-quiz', ['active' => true]);

        $response->assertStatus(200);
        $this->assertTrue(Feature::active('ai-quiz'));
    }

    public function test_invalid_flag_key_returns_422(): void
    {
        $this->setupAdmin();

        $response = $this->patchJson('/api/v1/admin/feature-flags/unknown-flag', ['active' => false]);

        $response->assertStatus(422);
    }

    public function test_unauthenticated_cannot_access_feature_flags(): void
    {
        $response = $this->getJson('/api/v1/admin/feature-flags');

        $response->assertStatus(401);
    }
}
```

- [ ] **Step 2: Chạy tests để xác nhận fail đúng cách**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning/e-learning-backend && php artisan test tests/Feature/Admin/FeatureFlagTest.php --verbose 2>&1" | cat
```

Expected: Tất cả fail với "Route not found" hoặc "Class not found".

- [ ] **Step 3: Tạo FeatureFlagController**

Tạo `app/Http/Controllers/Admin/FeatureFlagController.php`:

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Pennant\Feature;

class FeatureFlagController extends Controller
{
    use ApiResponse;

    private const FLAGS = [
        'ai-quiz' => [
            'label'       => 'AI Quiz Generation',
            'description' => 'Sinh câu hỏi trắc nghiệm tự động từ PDF bằng Gemini AI',
        ],
        'hls-transcoding' => [
            'label'       => 'HLS Transcoding',
            'description' => 'Chuyển đổi video sang định dạng HLS sau khi upload',
        ],
        'payout-requests' => [
            'label'       => 'Yêu cầu rút tiền',
            'description' => 'Cho phép giảng viên gửi và admin xử lý yêu cầu rút tiền',
        ],
    ];

    public function index(): JsonResponse
    {
        $data = collect(self::FLAGS)->map(fn ($meta, $key) => [
            'key'         => $key,
            'label'       => $meta['label'],
            'description' => $meta['description'],
            'active'      => Feature::active($key),
        ])->values();

        return $this->success($data, 'Danh sách tính năng hệ thống');
    }

    public function update(Request $request, string $flag): JsonResponse
    {
        $request->validate([
            'active' => ['required', 'boolean'],
        ]);

        if (! array_key_exists($flag, self::FLAGS)) {
            return response()->json([
                'success' => false,
                'message' => 'Flag không hợp lệ.',
                'errors'  => ['flag' => ['Flag không tồn tại trong hệ thống.']],
            ], 422);
        }

        if ($request->boolean('active')) {
            Feature::activate($flag);
        } else {
            Feature::deactivate($flag);
        }

        return $this->success([
            'key'    => $flag,
            'active' => Feature::active($flag),
        ], 'Cập nhật tính năng thành công');
    }
}
```

- [ ] **Step 4: Thêm routes vào routes/api.php**

Mở `routes/api.php`. Thêm vào cuối file:

```php
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
```

- [ ] **Step 5: Chạy tests — tất cả phải pass**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning/e-learning-backend && php artisan test tests/Feature/Admin/FeatureFlagTest.php --verbose 2>&1" | cat
```

Expected:
```
PASS  Tests\Feature\Admin\FeatureFlagTest
✓ super admin can list feature flags
✓ list returns correct flag keys
✓ super admin can deactivate flag
✓ super admin can activate flag
✓ invalid flag key returns 422
✓ unauthenticated cannot access feature flags
```

- [ ] **Step 6: Pint style**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning/e-learning-backend && ./vendor/bin/pint app/Http/Controllers/Admin/FeatureFlagController.php app/Providers/AppServiceProvider.php routes/api.php tests/Feature/Admin/FeatureFlagTest.php 2>&1" | cat
```

- [ ] **Step 7: Commit**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning/e-learning-backend && git add app/Http/Controllers/Admin/FeatureFlagController.php app/Providers/AppServiceProvider.php routes/api.php tests/Feature/Admin/FeatureFlagTest.php Modules/Users/database/seeders/RolePermissionSeeder.php composer.json composer.lock && git commit -m 'feat(backend): add Pennant feature flags API with admin permissions' 2>&1" | cat
```

---

## Task 4: Áp dụng flag checks vào 3 controllers/services

**Files:**
- Sửa: `e-learning-backend/Modules/Quiz/app/Http/Controllers/Admin/AdminQuizController.php`
- Sửa: `e-learning-backend/Modules/Upload/app/Services/UploadService.php`
- Sửa: `e-learning-backend/Modules/Commission/app/Http/Controllers/Admin/PayoutController.php`

- [ ] **Step 1: Thêm tests cho flag check vào FeatureFlagTest.php**

Thêm 3 test methods vào `tests/Feature/Admin/FeatureFlagTest.php` (append vào cuối class):

```php
public function test_ai_quiz_generate_returns_503_when_flag_inactive(): void
{
    $this->setupAdmin();
    Feature::deactivate('ai-quiz');

    // Tìm lesson có quiz để test endpoint
    $lesson = \Modules\Lessons\Models\Lesson::factory()->create();
    $quiz = \Modules\Quiz\Models\Quiz::create(['lesson_id' => $lesson->id]);

    $response = $this->postJson("/api/v1/admin/lesson-quiz/{$quiz->id}/generate", [
        'source' => 'upload',
        'count'  => 5,
    ]);

    $response->assertStatus(503)
        ->assertJsonPath('success', false);
}

public function test_payout_approve_returns_503_when_flag_inactive(): void
{
    $this->setupAdmin();
    Feature::deactivate('payout-requests');

    $payout = \Modules\Commission\Models\TeacherPayout::factory()->create(['status' => 'pending']);

    $response = $this->patchJson("/api/v1/admin/payouts/{$payout->id}/approve");

    $response->assertStatus(503)
        ->assertJsonPath('success', false);
}

public function test_payout_approve_works_when_flag_active(): void
{
    $this->setupAdmin();
    Feature::activate('payout-requests');

    $teacher = \Modules\Teachers\Models\Teacher::factory()->create();
    $payout = \Modules\Commission\Models\TeacherPayout::factory()->create([
        'status'     => 'pending',
        'teacher_id' => $teacher->id,
        'amount'     => 100000,
    ]);

    $response = $this->patchJson("/api/v1/admin/payouts/{$payout->id}/approve");

    $response->assertStatus(200)
        ->assertJsonPath('success', true);
}
```

**Lưu ý:** Nếu các model chưa có factory, bỏ qua các test tích hợp phức tạp này và chỉ giữ test đơn giản kiểm tra 503 với mock. Ưu tiên test flag check hơn test toàn bộ business logic.

- [ ] **Step 2: Thêm flag check vào AdminQuizController::generate()**

Mở `Modules/Quiz/app/Http/Controllers/Admin/AdminQuizController.php`. Thêm import và check vào đầu method `generate()`:

```php
use Laravel\Pennant\Feature;

// Trong method generate():
public function generate(Request $request, int $id): JsonResponse
{
    if (! Feature::active('ai-quiz')) {
        return $this->error('Tính năng AI Quiz tạm thời ngừng hoạt động.', 503);
    }

    try {
        // ... code hiện tại giữ nguyên
    }
}
```

- [ ] **Step 3: Thêm flag check vào UploadService::uploadVideo()**

Mở `Modules/Upload/app/Services/UploadService.php`. Thêm import và bọc dispatch:

```php
use Laravel\Pennant\Feature;

// Sửa đoạn dispatch trong uploadVideo():
if (in_array($media->disk, ['local', 'public'])) {
    if (Feature::active('hls-transcoding')) {
        TranscodeToHlsJob::dispatch($media->id);
    }
}
```

**Lưu ý:** Khi flag tắt, video vẫn upload thành công — chỉ bỏ qua bước transcode. Không trả 503.

- [ ] **Step 4: Thêm flag check vào PayoutController — approve(), reject(), markPaid()**

Mở `Modules/Commission/app/Http/Controllers/Admin/PayoutController.php`. Thêm import và check vào đầu mỗi method:

```php
use Laravel\Pennant\Feature;

public function approve(Request $request, int $id): JsonResponse
{
    if (! Feature::active('payout-requests')) {
        return $this->error('Tính năng rút tiền tạm thời bị khóa.', 503);
    }

    // ... code hiện tại giữ nguyên
}

public function reject(Request $request, int $id): JsonResponse
{
    if (! Feature::active('payout-requests')) {
        return $this->error('Tính năng rút tiền tạm thời bị khóa.', 503);
    }

    // ... code hiện tại giữ nguyên
}

public function markPaid(int $id): JsonResponse
{
    if (! Feature::active('payout-requests')) {
        return $this->error('Tính năng rút tiền tạm thời bị khóa.', 503);
    }

    // ... code hiện tại giữ nguyên
}
```

- [ ] **Step 5: Chạy toàn bộ test suite**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning/e-learning-backend && php artisan test 2>&1" | cat
```

Expected: Không có regression. Pre-existing failures không tăng.

- [ ] **Step 6: Pint style**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning/e-learning-backend && ./vendor/bin/pint Modules/Quiz/app/Http/Controllers/Admin/AdminQuizController.php Modules/Upload/app/Services/UploadService.php Modules/Commission/app/Http/Controllers/Admin/PayoutController.php 2>&1" | cat
```

- [ ] **Step 7: Commit**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning/e-learning-backend && git add Modules/Quiz/app/Http/Controllers/Admin/AdminQuizController.php Modules/Upload/app/Services/UploadService.php Modules/Commission/app/Http/Controllers/Admin/PayoutController.php tests/Feature/Admin/FeatureFlagTest.php && git commit -m 'feat(backend): apply Pennant flag checks to quiz, HLS, payout controllers' 2>&1" | cat
```

---

## Task 5: Frontend — Service và Composable

**Files:**
- Tạo: `e-learning-frontend/src/services/featureFlag.service.ts`
- Tạo: `e-learning-frontend/src/composables/useFeatureFlags.ts`

- [ ] **Step 1: Tạo featureFlag.service.ts**

Tạo `src/services/featureFlag.service.ts`:

```typescript
import http from '@/plugins/axios'

export interface FeatureFlag {
  key: string
  label: string
  description: string
  active: boolean
}

export const featureFlagService = {
  index: () => http.get<{ success: boolean; data: FeatureFlag[] }>('/admin/feature-flags'),
  update: (flag: string, active: boolean) =>
    http.patch(`/admin/feature-flags/${flag}`, { active }),
}
```

- [ ] **Step 2: Tạo useFeatureFlags.ts**

Tạo `src/composables/useFeatureFlags.ts`:

```typescript
import { ref } from 'vue'
import { useToast } from 'vue-toastification'
import { featureFlagService, type FeatureFlag } from '@/services/featureFlag.service'

export function useFeatureFlags() {
  const toast = useToast()
  const flags = ref<FeatureFlag[]>([])
  const loading = ref(false)
  const toggling = ref<string | null>(null)

  async function loadFlags() {
    if (loading.value) return
    loading.value = true
    try {
      const res = await featureFlagService.index()
      flags.value = res.data.data
    } catch {
      toast.error('Không thể tải danh sách tính năng.')
    } finally {
      loading.value = false
    }
  }

  async function toggleFlag(key: string, active: boolean) {
    if (toggling.value) return
    toggling.value = key

    // Optimistic update
    const flag = flags.value.find((f) => f.key === key)
    if (!flag) return
    flag.active = active

    try {
      await featureFlagService.update(key, active)
      toast.success(`Đã ${active ? 'bật' : 'tắt'} ${flag.label}.`)
    } catch {
      // Revert
      flag.active = !active
      toast.error('Cập nhật thất bại. Vui lòng thử lại.')
    } finally {
      toggling.value = null
    }
  }

  return { flags, loading, toggling, loadFlags, toggleFlag }
}
```

- [ ] **Step 3: Kiểm tra TypeScript build**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning/e-learning-frontend && npm run build 2>&1" | cat
```

Expected: Build thành công, không có type errors.

---

## Task 6: Frontend — FeatureFlagsPage.vue

**Files:**
- Tạo: `e-learning-frontend/src/views/admin/FeatureFlagsPage.vue`

- [ ] **Step 1: Tạo FeatureFlagsPage.vue**

Tạo `src/views/admin/FeatureFlagsPage.vue`:

```vue
<script setup lang="ts">
import { onMounted } from 'vue'
import { useFeatureFlags } from '@/composables/useFeatureFlags'

const { flags, loading, toggling, loadFlags, toggleFlag } = useFeatureFlags()

onMounted(loadFlags)
</script>

<template>
  <div class="p-6 max-w-2xl">
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-800 dark:text-white/90">Quản lý tính năng hệ thống</h1>
      <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
        Bật/tắt các tính năng toàn hệ thống. Thay đổi có hiệu lực ngay lập tức.
      </p>
    </div>

    <!-- Loading skeleton -->
    <div v-if="loading" class="space-y-3">
      <div
        v-for="i in 3"
        :key="i"
        class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 p-5 animate-pulse"
      >
        <div class="flex items-center justify-between">
          <div class="space-y-2">
            <div class="h-4 w-40 bg-gray-200 dark:bg-gray-700 rounded" />
            <div class="h-3 w-64 bg-gray-100 dark:bg-gray-800 rounded" />
          </div>
          <div class="h-6 w-11 bg-gray-200 dark:bg-gray-700 rounded-full" />
        </div>
      </div>
    </div>

    <!-- Flag cards -->
    <div v-else class="space-y-3">
      <div
        v-for="flag in flags"
        :key="flag.key"
        class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 p-5 flex items-center justify-between gap-4"
      >
        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-2 mb-0.5">
            <span class="text-sm font-semibold text-gray-800 dark:text-white/90">{{ flag.label }}</span>
            <span
              :class="flag.active
                ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                : 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400'"
              class="text-xs px-2 py-0.5 rounded-full font-medium"
            >
              {{ flag.active ? 'Đang hoạt động' : 'Đã tắt' }}
            </span>
          </div>
          <p class="text-xs text-gray-500 dark:text-gray-400">{{ flag.description }}</p>
        </div>

        <!-- Toggle switch -->
        <button
          type="button"
          :disabled="toggling === flag.key"
          @click="toggleFlag(flag.key, !flag.active)"
          :class="flag.active ? 'bg-blue-500' : 'bg-gray-200 dark:bg-gray-700'"
          class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 focus:outline-none disabled:opacity-50 disabled:cursor-not-allowed"
          :title="flag.active ? 'Nhấn để tắt' : 'Nhấn để bật'"
        >
          <span
            :class="flag.active ? 'translate-x-5' : 'translate-x-0'"
            class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200"
          />
        </button>
      </div>
    </div>
  </div>
</template>
```

- [ ] **Step 2: Build để kiểm tra không có lỗi**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning/e-learning-frontend && npm run build 2>&1" | cat
```

Expected: Build thành công.

---

## Task 7: Frontend — Router và Sidebar

**Files:**
- Sửa: `e-learning-frontend/src/router/index.js`
- Sửa: `e-learning-frontend/src/components/layout/AppSidebar.vue`
- Sửa: `e-learning-frontend/src/components/icons/index.ts`

- [ ] **Step 1: Thêm FlagIcon vào icons/index.ts**

Mở `src/components/icons/index.ts`. Thêm import và export cho `FlagIcon` (file đã có sẵn tại `FlagIcon.vue`):

Thêm vào cuối phần import (sau dòng `import EyeIcon`):
```typescript
import FlagIcon from './FlagIcon.vue'
```

Thêm vào cuối phần export (sau `EyeIcon`):
```typescript
FlagIcon,
```

- [ ] **Step 2: Thêm route vào router/index.js**

Mở `src/router/index.js`. Tìm phần admin routes (children của `/admin`). Thêm route mới vào sau `commission-settings`:

```javascript
{
  path: 'feature-flags',
  name: 'admin.feature-flags',
  component: () => import('@/views/admin/FeatureFlagsPage.vue'),
  meta: { requiresAuth: true, guard: 'admin' },
},
```

- [ ] **Step 3: Thêm menu item vào AppSidebar.vue**

Mở `src/components/layout/AppSidebar.vue`.

**3a.** Thêm `FlagIcon` vào phần import icons (dòng 152-165):

```typescript
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
  FlagIcon,          // ← thêm dòng này
} from '@/components/icons'
```

**3b.** Tìm nhóm `'Hệ thống'` (dòng ~262) và thêm item Feature Flags:

```javascript
{
  title: 'Hệ thống',
  items: [
    {
      icon: FlagIcon,
      name: 'Tính năng hệ thống',
      path: '/admin/feature-flags',
      permission: 'feature_flags.view',
    },
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
```

- [ ] **Step 4: Chạy lint**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning/e-learning-frontend && npm run lint 2>&1" | cat
```

Expected: Không có lỗi lint.

- [ ] **Step 5: Build final**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning/e-learning-frontend && npm run build 2>&1" | cat
```

Expected: Build thành công.

- [ ] **Step 6: Commit frontend**

```bash
git add e-learning-frontend/src/services/featureFlag.service.ts e-learning-frontend/src/composables/useFeatureFlags.ts e-learning-frontend/src/views/admin/FeatureFlagsPage.vue e-learning-frontend/src/router/index.js e-learning-frontend/src/components/layout/AppSidebar.vue e-learning-frontend/src/components/icons/index.ts
git commit -m "feat(frontend): add feature flags admin page with toggle UI"
```

---

## Task 8: Manual smoke test

Kiểm tra end-to-end trên trình duyệt.

- [ ] **Step 1: Chạy backend và frontend**

```bash
# Terminal 1
cd e-learning-backend && php artisan serve

# Terminal 2
cd e-learning-frontend && npm run dev
```

- [ ] **Step 2: Đăng nhập Super Admin**

Truy cập `http://localhost:5173/admin/login`, đăng nhập với `superadmin@elearning.com` / `password`.

- [ ] **Step 3: Kiểm tra menu sidebar**

Trong sidebar, nhóm **"Hệ thống"** phải hiển thị item **"Tính năng hệ thống"** với icon cờ.

- [ ] **Step 4: Mở trang Feature Flags**

Truy cập `http://localhost:5173/admin/feature-flags`. Phải hiển thị 3 card:
- AI Quiz Generation — toggle ON
- HLS Transcoding — toggle ON
- Yêu cầu rút tiền — toggle ON

- [ ] **Step 5: Tắt AI Quiz và xác nhận**

Click toggle `AI Quiz Generation` → tắt. Badge phải đổi thành "Đã tắt". Thử sinh câu hỏi AI từ một bài học — phải nhận được lỗi 503.

- [ ] **Step 6: Đăng nhập admin thường và kiểm tra quyền**

Đăng xuất, đăng nhập với `admin@elearning.com` / `password`. Sidebar không được hiển thị item "Tính năng hệ thống".

---

## Dev Notes

- Pennant cache flag state theo request. Nếu test thấy trạng thái cũ: `Feature::flushCache()` trong `tearDown()` của test.
- `Feature::define()` trong AppServiceProvider chỉ set **default** khi chưa có record trong DB. Sau khi admin toggle, DB value ghi đè default.
- Khi chạy `php artisan migrate:fresh --seed`: flag state trong bảng `features` bị xóa, reset về default (tất cả `true`).
