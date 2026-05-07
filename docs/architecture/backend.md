# Kiến trúc Backend

## 1. Tổng quan

Backend được xây dựng trên **Laravel 12** theo mô hình **Modular Monolith** dùng package `nwidart/laravel-modules`. Thay vì tất cả code nằm trong `app/`, mỗi tính năng được đóng gói thành module độc lập trong `Modules/`.

**Lý do chọn Modular Monolith thay vì Microservices:**
- Đơn giản hóa deploy (1 process, 1 database)
- Dễ refactor trong giai đoạn phát triển nhanh
- Vẫn đảm bảo separation of concerns giữa các domain

---

## 2. Cấu trúc thư mục

```
e-learning-backend/
├── app/                          ← Shared infrastructure
│   ├── Http/Controllers/         ← Base Controller (chỉ extends Laravel)
│   ├── Repositories/
│   │   ├── BaseRepository.php    ← Default CRUD implementation
│   │   └── RepositoryInterface.php
│   ├── Traits/
│   │   ├── ApiResponse.php       ← JSON response format chuẩn
│   │   ├── HasActivityLog.php    ← Spatie activity log integration
│   │   └── ScopesToTeacher.php   ← Scope lọc theo teacher
│   ├── Events/                   ← AdminLoggedIn, PaymentSuccessful, ...
│   ├── Listeners/                ← LogActivityListener
│   └── Providers/
│       └── AppServiceProvider.php
│
├── Modules/                      ← 13 feature modules
│   ├── Auth/
│   ├── Categories/
│   ├── Coupons/
│   ├── Course/
│   ├── Dashboard/
│   ├── Lessons/
│   ├── Payment/
│   ├── Posts/
│   ├── Quiz/
│   ├── Students/
│   ├── Teachers/
│   ├── Upload/
│   └── Users/
│
├── config/
│   ├── auth.php                  ← Định nghĩa guards (admin, api)
│   ├── sanctum.php               ← Token expiration = null
│   ├── cors.php                  ← Whitelist localhost:5173
│   ├── queue.php                 ← Database driver
│   └── permission.php            ← Spatie config (guard: admin)
│
├── bootstrap/
│   └── app.php                   ← Middleware + Exception handler
│
└── database/
    └── seeders/
        ├── DatabaseSeeder.php
        └── RolePermissionSeeder.php
```

---

## 3. Cấu trúc module chuẩn

Mỗi module tuân theo cấu trúc nhất quán:

```
Modules/<Name>/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── AdminXxxController.php   ← CRUD cho admin panel
│   │   │   └── XxxController.php        ← Public/student API
│   │   ├── Requests/
│   │   │   ├── StoreXxxRequest.php      ← Validation cho tạo mới
│   │   │   └── UpdateXxxRequest.php     ← Validation cho cập nhật
│   │   ├── Resources/
│   │   │   ├── XxxResource.php          ← Transform single item
│   │   │   └── XxxCollection.php        ← Transform list
│   │   └── Middleware/                  ← Module-specific middleware
│   ├── Models/
│   │   └── Xxx.php
│   └── Repositories/
│       ├── XxxRepositoryInterface.php
│       └── XxxRepository.php
├── database/
│   ├── migrations/
│   └── seeders/
├── routes/
│   └── api.php
├── Providers/
│   └── XxxServiceProvider.php           ← Đăng ký binding + routes
└── module.json
```

---

## 4. Repository Pattern

### Mục đích
- Tách biệt business logic khỏi data access
- Controller không phụ thuộc vào Eloquent trực tiếp
- Dễ test (mock interface thay vì mock Eloquent)

### BaseRepository — Các method có sẵn

```php
// Đọc
getAll(columns, relations): Collection
find(id, columns, relations): ?Model
findOrFail(id, columns, relations): Model          // throws ModelNotFoundException
paginate(perPage, columns, relations): LengthAwarePaginator

// Ghi
create(data): Model
update(id, data): Model
delete(id): bool                                    // soft delete nếu model có SoftDeletes
deleteMany(ids): int

// Soft delete management
paginateTrashed(perPage): LengthAwarePaginator
restore(id): bool
restoreMany(ids): int
forceDeleteById(id): bool
forceDeleteMany(ids): int

// Bulk action
actionMany(ids, action): int
// actions: 'activate', 'deactivate', 'publish', 'unpublish', 'archive'
```

**Clamp per_page:** mọi phương thức phân trang đều clamp `$perPage = max(1, min($perPage, 100))` để tránh abuse.

### Cách dùng trong module

```php
// 1. Định nghĩa interface
interface CourseRepositoryInterface extends RepositoryInterface {
    public function getFiltered(array $filters, int $perPage): LengthAwarePaginator;
    public function findBySlug(string $slug): Course;
}

// 2. Implement
class CourseRepository extends BaseRepository implements CourseRepositoryInterface {
    public function __construct(Course $model) {
        parent::__construct($model);
    }

    public function getFiltered(array $filters, int $perPage): LengthAwarePaginator {
        return $this->model->newQuery()
            ->when($filters['search'] ?? null, fn($q, $s) => $q->where('name', 'like', "%$s%"))
            ->when($filters['status'] ?? null, fn($q, $s) => $q->where('status', $s))
            ->paginate(max(1, min($perPage, 100)));
    }
}

// 3. Bind trong ServiceProvider
$this->app->bind(CourseRepositoryInterface::class, CourseRepository::class);

// 4. Inject trong Controller
public function __construct(private CourseRepositoryInterface $repository) {}
```

---

## 5. ApiResponse Trait

Tất cả controllers `use ApiResponse` để đảm bảo format JSON nhất quán:

```php
// Response thành công — single item
$this->success(new CourseResource($course), 'Tạo thành công', 201);

// Response thành công — danh sách phân trang
$this->paginated($paginator, 'Danh sách khóa học');

// Response lỗi
$this->error('Không tìm thấy khóa học.', 404);
$this->error('Dữ liệu không hợp lệ.', 422, $errors);
```

**Format chuẩn:**
```json
// success()
{ "success": true,  "message": "...", "data": {...} }

// paginated()
{ "success": true,  "message": "...", "data": [...],
  "pagination": { "current_page", "last_page", "per_page", "total", "from", "to" } }

// error()
{ "success": false, "message": "...", "data": null, "errors": {...} }
```

---

## 6. Form Request Validation

Mọi validation đều ở FormRequest, **không bao giờ** trong Controller:

```php
class StoreCourseRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'        => 'required|string|max:255',
            'slug'        => 'required|unique:courses,slug|regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
            'teacher_id'  => 'required|exists:teachers,id',
            'price'       => 'required|numeric|min:0',
            'sale_price'  => 'nullable|numeric|lte:price',
            'level'       => 'required|in:beginner,intermediate,advanced',
        ];
    }

    public function messages(): array
    {
        return [
            'slug.regex' => 'Slug chỉ được chứa chữ thường, số và dấu gạch ngang.',
        ];
    }

    // Bắt buộc override — trả JSON thay vì redirect
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

---

## 7. API Resource

Resources transform model output — **không bao giờ trả raw model**:

```php
class CourseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'slug'        => $this->slug,
            'thumbnail'   => $this->thumbnail
                ? asset('storage/' . $this->thumbnail)  // raw path → full URL
                : null,
            'price'       => $this->price,
            'sale_price'  => $this->sale_price,
            'level'       => $this->level,
            'status'      => $this->status,
            'teacher'     => $this->whenLoaded('teacher', fn() =>
                new TeacherResource($this->teacher)
            ),
            'categories'  => $this->whenLoaded('categories',
                CategoryResource::collection($this->categories)
            ),
            // KHÔNG có: password, remember_token, raw file paths
        ];
    }
}
```

**Quy tắc bắt buộc:**
- Raw file path → `asset('storage/' . $path)`
- Relationships → `$this->whenLoaded(...)` (không eager load ngầm)
- Loại bỏ: `password`, `remember_token`, `email_verified_at`, pivot internals

---

## 8. Exception Handler

File `bootstrap/app.php` chuẩn hóa toàn bộ exception về JSON cho `/api/*`:

| Exception | HTTP | Message |
|-----------|------|---------|
| `ModelNotFoundException` | 404 | `{Model} không tìm thấy.` |
| `AuthenticationException` | 401 | Chưa đăng nhập hoặc token không hợp lệ. |
| `AccessDeniedHttpException` | 403 | Không có quyền thực hiện hành động này. |
| `ValidationException` | 422 | Dữ liệu không hợp lệ. + errors |
| `MethodNotAllowedHttpException` | 405 | Phương thức HTTP không được phép. |
| `NotFoundHttpException` | 404 | Endpoint không tồn tại. |

---

## 9. Middleware Stack

| Alias | Class | Áp dụng cho |
|-------|-------|------------|
| `auth:admin` | Sanctum | Tất cả `/api/v1/admin/**` |
| `auth:api` | Sanctum | Student action routes |
| `email.verified` | `EnsureEmailVerified` | Student: enroll, quiz, order... |
| `throttle:5,1` | Laravel | Admin login, student login |
| `throttle:10,1` | Laravel | Student register |
| `throttle:3,1` | Laravel | Forgot password, resend verify |
| `permission:{name}` | Spatie | Các route cần permission cụ thể |

---

## 10. Queue

Driver: **database** (jobs lưu trong bảng `jobs`).

Job hiện có:
- `GenerateQuizJob` — chạy trên queue `ai`, timeout 120s, tries 1, WithoutOverlapping

```bash
# Khởi động queue worker
php artisan queue:work --queue=ai,default

# Hoặc chạy tất cả queue
php artisan queue:work
```

---

## 11. Activity Logging

Dùng `spatie/laravel-activitylog`. Model có `use HasActivityLog` sẽ tự động log create/update/delete.

Events thủ công: `AdminLoggedIn`, `PaymentSuccessful` → `LogActivityListener`.

Xem logs qua:
- Admin panel: `/admin/system-logs`
- `GET /api/v1/admin/activity-logs`
- Laravel Log Viewer (`opcodesio/log-viewer`): `/log-viewer`

---

## 12. Scheduled Tasks

Định nghĩa trong `bootstrap/app.php`:

```php
$schedule->command('media:prune-orphans')->dailyAt('03:00');
```

`media:prune-orphans`: xóa các `media_files` không còn được reference bởi lesson nào (orphaned uploads).
