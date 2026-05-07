# Phân quyền (RBAC)

## 1. Tổng quan

Hệ thống phân quyền dùng **Spatie Laravel Permission** với guard `admin`. Chỉ áp dụng cho staff admin — học viên không có roles/permissions.

Ba role mặc định:

| Role | Mô tả |
|------|-------|
| `super-admin` | Toàn quyền, không bị chặn bởi permission check |
| `admin` | Toàn quyền trừ `users.delete` |
| `teacher` | Giới hạn trong phạm vi giảng dạy của mình |

---

## 2. Danh sách permissions (35 quyền)

| Nhóm | Permissions |
|------|------------|
| users | `users.view` `users.create` `users.edit` `users.delete` |
| roles | `roles.view` `roles.create` `roles.edit` `roles.delete` |
| courses | `courses.view` `courses.create` `courses.edit` `courses.delete` |
| categories | `categories.view` `categories.create` `categories.edit` `categories.delete` |
| lessons | `lessons.view` `lessons.create` `lessons.edit` `lessons.delete` |
| quizzes | `quizzes.view` `quizzes.create` `quizzes.edit` `quizzes.delete` |
| orders | `orders.view` `orders.edit` |
| coupons | `coupons.view` `coupons.create` `coupons.edit` `coupons.delete` |
| students | `students.view` `students.edit` |
| posts | `posts.view` `posts.create` `posts.edit` `posts.delete` |
| tags | `tags.view` `tags.create` `tags.edit` `tags.delete` |
| comments | `comments.view` `comments.edit` `comments.delete` |
| dashboard | `dashboard.view` |
| system | `system.logs` |

---

## 3. Phân quyền theo role

### super-admin

Tất cả 35 permissions. Spatie tự bypass permission check cho `super-admin`.

### admin

Tất cả permissions **trừ** `users.delete`.

### teacher

```
courses.view, courses.create, courses.edit, courses.delete
lessons.view, lessons.create, lessons.edit, lessons.delete
quizzes.view, quizzes.create, quizzes.edit, quizzes.delete
dashboard.view
categories.view
tags.view
students.view
posts.view
orders.view
```

Teacher chỉ thấy và quản lý **khóa học của mình** nhờ scope `ScopesToTeacher`:

```php
// Trait ScopesToTeacher (app/Traits/ScopesToTeacher.php)
// Áp dụng trong CourseRepository, LessonRepository, ...
if (auth('admin')->user()->hasRole('teacher')) {
    $query->where('teacher_id', auth('admin')->id());
}
```

---

## 4. Áp dụng permission trong routes

```php
// Modules/Course/routes/api.php
Route::middleware(['auth:admin'])->prefix('admin')->group(function () {

    Route::get('courses', [AdminCourseController::class, 'index'])
        ->middleware('permission:courses.view');

    Route::post('courses', [AdminCourseController::class, 'store'])
        ->middleware('permission:courses.create');

    Route::patch('courses/{id}', [AdminCourseController::class, 'update'])
        ->middleware('permission:courses.edit');

    Route::delete('courses/{id}', [AdminCourseController::class, 'destroy'])
        ->middleware('permission:courses.delete');
});
```

Vi phạm permission → `AccessDeniedHttpException` → Exception handler trả 403 JSON:

```json
{
  "success": false,
  "message": "Bạn không có quyền thực hiện hành động này.",
  "data": null
}
```

Spatie được cấu hình `display_permission_in_exception: false` — tên permission không bị leak trong response.

---

## 5. API Quản lý Roles

| Method | Endpoint | Permission | Mô tả |
|--------|----------|-----------|-------|
| GET | `/api/v1/admin/roles` | `roles.view` | Danh sách roles |
| GET | `/api/v1/admin/permissions` | `roles.view` | Tất cả permissions |
| POST | `/api/v1/admin/roles` | `roles.create` | Tạo role mới |
| GET | `/api/v1/admin/roles/{id}` | `roles.view` | Chi tiết role + permissions |
| PATCH | `/api/v1/admin/roles/{id}` | `roles.edit` | Cập nhật role + permissions |
| DELETE | `/api/v1/admin/roles/{id}` | `roles.delete` | Xóa role |

**Ràng buộc:**
- Không thể sửa role `super-admin`
- Không thể xóa role còn user đang dùng

---

## 6. Lấy roles/permissions trong response

`GET /api/v1/admin/auth/me` trả về roles và permissions của user:

```json
{
  "success": true,
  "data": {
    "id": 2,
    "name": "Admin User",
    "email": "admin@elearning.com",
    "roles": ["admin"],
    "permissions": [
      "courses.view", "courses.create", "courses.edit", "courses.delete",
      "users.view", "users.create", "users.edit",
      "..."
    ]
  }
}
```

---

## 7. Kiểm tra quyền trên Frontend

### v-permission directive

```html
<!-- Ẩn nút nếu không có quyền 'courses.delete' -->
<button v-permission="'courses.delete'" @click="deleteCourse">
  Xóa khóa học
</button>
```

### Kiểm tra trong logic

```ts
const adminStore = useAdminAuthStore()

// Kiểm tra permission
const canDelete = adminStore.user?.permissions.includes('courses.delete')

// Kiểm tra role
const isTeacher = adminStore.user?.roles.includes('teacher')
```

---

## 8. Seeder khởi tạo

```bash
# Chạy sau migrate để tạo roles + permissions
php artisan db:seed --class=RolePermissionSeeder

# Hoặc toàn bộ seeder
php artisan migrate:fresh --seed
```

`RolePermissionSeeder` tạo:
1. Tất cả permissions (35 quyền)
2. Roles: `super-admin`, `admin`, `teacher`
3. Gán permissions cho từng role
4. Gán role `super-admin` cho user `superadmin@elearning.com`
5. Gán role `admin` cho user `admin@elearning.com`
