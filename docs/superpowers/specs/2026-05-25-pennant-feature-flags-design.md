# Laravel Pennant Feature Flags — Admin UI Design

## Goal

Tích hợp Laravel Pennant để quản lý 3 feature flags hệ thống (`ai-quiz`, `hls-transcoding`, `payout-requests`) với admin UI cho phép Super Admin bật/tắt trực tiếp trên giao diện.

## Architecture

**Backend:** `laravel/pennant` lưu trạng thái flag vào bảng `features` (DB driver). Controller đặt trong `app/Http/Controllers/Admin/` vì feature flags là cross-cutting concern không thuộc module nào. Hai permission mới (`feature_flags.view`, `feature_flags.update`) gán riêng cho `super-admin` qua Spatie.

**Frontend:** Trang `FeatureFlagsPage.vue` đơn giản — danh sách 3 card với toggle switch. Optimistic UI update, revert nếu API lỗi. Menu item ẩn với admin không có quyền.

**Flag scope:** Global only — không có per-user override. Khi flag tắt, API trả 503 với message tiếng Việt.

---

## Tech Stack

- `laravel/pennant` ^1.0 — feature flag engine, DB driver
- Spatie Laravel Permission — gán permission cho super-admin
- Vue 3 + TypeScript — toggle UI
- Axios — PATCH endpoint

---

## Backend Components

### 1. Package

```bash
composer require laravel/pennant
php artisan vendor:publish --provider="Laravel\Pennant\FennantServiceProvider"
php artisan migrate   # tạo bảng features
```

### 2. Flag Definitions

Định nghĩa trong `app/Providers/AppServiceProvider.php` trong method `boot()`:

```php
use Laravel\Pennant\Feature;

Feature::define('ai-quiz', true);
Feature::define('hls-transcoding', true);
Feature::define('payout-requests', true);
```

Tất cả bật mặc định (`true`). Pennant lưu override vào DB khi admin thay đổi.

### 3. Controller

**File:** `app/Http/Controllers/Admin/FeatureFlagController.php`

```
GET  /api/v1/admin/feature-flags          → index()  — danh sách 3 flag + trạng thái
PATCH /api/v1/admin/feature-flags/{flag}  → update() — bật/tắt flag (validate flag name)
```

`index()` trả về:
```json
{
  "success": true,
  "data": [
    { "key": "ai-quiz",          "label": "AI Quiz Generation",    "description": "Sinh câu hỏi trắc nghiệm tự động từ PDF", "active": true },
    { "key": "hls-transcoding",  "label": "HLS Transcoding",       "description": "Chuyển đổi video sang định dạng HLS",      "active": true },
    { "key": "payout-requests",  "label": "Yêu cầu rút tiền",      "description": "Cho phép giảng viên gửi yêu cầu rút tiền", "active": false }
  ]
}
```

`update()` nhận `{ "active": true|false }`, validate `$flag` phải thuộc whitelist `['ai-quiz', 'hls-transcoding', 'payout-requests']`.

### 4. Routes

File: `routes/api.php` (root, không phải module)

```php
Route::prefix('api/v1/admin')
    ->middleware(['auth:admin'])
    ->group(function () {
        Route::get('feature-flags', [FeatureFlagController::class, 'index'])
            ->middleware('permission:feature_flags.view');
        Route::patch('feature-flags/{flag}', [FeatureFlagController::class, 'update'])
            ->middleware('permission:feature_flags.update');
    });
```

### 5. Flag Checks — 3 điểm áp dụng

| File | Method | Flag | Response khi tắt |
|------|--------|------|-----------------|
| `Modules/Quiz/app/Http/Controllers/Admin/QuizController.php` | `generate()` | `ai-quiz` | 503 + `"Tính năng AI Quiz tạm thời ngừng hoạt động."` |
| `Modules/Upload/app/Http/Controllers/Admin/MediaController.php` | `store()` | `hls-transcoding` | Skip dispatch `TranscodeToHlsJob` (không lỗi, chỉ bỏ qua transcode) |
| `Modules/Commission/app/Http/Controllers/Admin/PayoutController.php` | `approve()`, `reject()`, `markPaid()` | `payout-requests` | 503 + `"Tính năng rút tiền tạm thời bị khóa."` |

**Note đặc biệt cho `hls-transcoding`:** Khi flag tắt, video vẫn upload thành công nhưng không dispatch HLS job — video chỉ phát được ở định dạng gốc. Không trả 503 vì upload vẫn hợp lệ.

```php
// Ví dụ áp dụng flag trong QuizController
if (!Feature::active('ai-quiz')) {
    return $this->error('Tính năng AI Quiz tạm thời ngừng hoạt động.', 503);
}
```

### 6. Permissions

Thêm vào `RolePermissionSeeder`:

```php
$newPermissions = [
    'feature_flags.view',
    'feature_flags.update',
];
// Gán vào super-admin (super-admin đã có all permissions — seeder tự gán)
```

Chạy sau deploy: `php artisan db:seed --class=RolePermissionSeeder`

---

## Frontend Components

### 1. Service

**File:** `src/services/featureFlag.service.ts`

```ts
export const featureFlagService = {
  index: () => http.get('/admin/feature-flags'),
  update: (flag: string, active: boolean) =>
    http.patch(`/admin/feature-flags/${flag}`, { active }),
}
```

### 2. Composable

**File:** `src/composables/useFeatureFlags.ts`

State:
- `flags: ref<FeatureFlag[]>` — danh sách flags
- `loading: ref<boolean>` — loading danh sách
- `toggling: ref<string | null>` — key của flag đang toggle (null nếu không có)

Methods:
- `loadFlags()` — fetch từ API
- `toggleFlag(key, active)` — optimistic update → PATCH → revert nếu lỗi + toast error

### 3. Page Component

**File:** `src/views/admin/FeatureFlagsPage.vue`

Layout:
```
Header: "Quản lý tính năng hệ thống" + mô tả ngắn
Body: danh sách card, mỗi card gồm:
  - Icon + Label (bold)
  - Description (text-sm text-gray-500)
  - Toggle switch (bên phải, disabled khi toggling)
  - Badge "Đang hoạt động" (green) / "Đã tắt" (gray)
```

Loading state: skeleton 3 card khi fetch lần đầu.
Error state: toast error nếu load thất bại.

### 4. Router

**File:** `src/router/index.js`

```js
{
  path: '/admin/feature-flags',
  component: () => import('@/views/admin/FeatureFlagsPage.vue'),
  meta: { requiresAuth: true, guard: 'admin', permission: 'feature_flags.view' }
}
```

### 5. Sidebar

**File:** `src/components/layout/AppSidebar.vue`

Thêm vào nhóm **"Hệ thống"** (cùng nhóm với Roles, Activity Logs):

```js
{
  name: 'Tính năng hệ thống',
  path: '/admin/feature-flags',
  icon: ToggleLeftIcon,  // Lucide icon
  permission: 'feature_flags.view',
}
```

---

## Data Flow

```
Admin click toggle
  → useFeatureFlags.toggleFlag(key, !current)
  → Optimistic update: flags[key].active = !current (UI cập nhật ngay)
  → featureFlagService.update(key, !current)
  → API PATCH /admin/feature-flags/{flag}
    → FeatureFlagController::update()
    → Feature::activate()/deactivate() [Pennant ghi DB]
    → return 200 success
  ← Nếu lỗi: revert flags[key].active + toast.error()
```

---

## Error Handling

| Tình huống | Hành vi |
|-----------|---------|
| Load flags thất bại | Toast error, hiện retry button |
| Toggle thất bại | Revert toggle về trạng thái cũ, toast error |
| Flag key không hợp lệ (PATCH) | 422 validation error |
| Không có quyền | 403, menu item ẩn trên sidebar |
| Flag tắt → user gọi AI quiz | 503 + message tiếng Việt |

---

## Testing

### Backend

**File:** `tests/Feature/Admin/FeatureFlagTest.php`

Test cases:
- `test_super_admin_can_list_feature_flags` — GET trả 200 + 3 flags
- `test_regular_admin_cannot_access_feature_flags` — GET trả 403
- `test_super_admin_can_deactivate_flag` — PATCH `ai-quiz` với `active: false` → 200
- `test_super_admin_can_activate_flag` — PATCH `ai-quiz` với `active: true` → 200
- `test_invalid_flag_key_returns_422` — PATCH `unknown-flag` → 422
- `test_ai_quiz_returns_503_when_flag_inactive` — deactivate flag → gọi generate → 503
- `test_payout_approve_returns_503_when_flag_inactive` — deactivate flag → approve → 503

### Frontend

Không cần unit test riêng — composable đủ đơn giản, covered bởi feature test backend.

---

## Files Modified / Created

| File | Action |
|------|--------|
| `app/Http/Controllers/Admin/FeatureFlagController.php` | Tạo mới |
| `app/Providers/AppServiceProvider.php` | Thêm Feature::define() vào boot() |
| `routes/api.php` | Thêm 2 routes |
| `Modules/Quiz/app/Http/Controllers/Admin/QuizController.php` | Thêm flag check |
| `Modules/Upload/app/Http/Controllers/Admin/MediaController.php` | Thêm flag check |
| `Modules/Commission/app/Http/Controllers/Admin/PayoutController.php` | Thêm flag check x3 |
| `database/seeders/RolePermissionSeeder.php` | Thêm 2 permissions |
| `tests/Feature/Admin/FeatureFlagTest.php` | Tạo mới |
| `src/services/featureFlag.service.ts` | Tạo mới |
| `src/composables/useFeatureFlags.ts` | Tạo mới |
| `src/views/admin/FeatureFlagsPage.vue` | Tạo mới |
| `src/router/index.js` | Thêm route |
| `src/components/layout/AppSidebar.vue` | Thêm menu item |

---

## Out of Scope

- Per-user / per-teacher flag overrides
- Flag history / audit log (ai bật ai tắt)
- Thêm flag mới qua UI (chỉ định nghĩa trong code)
- Email thông báo khi flag thay đổi
- Môi trường production (chỉ cấu hình `local`)
