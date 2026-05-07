# Tổng quan kiến trúc hệ thống E-Learning

## 1. Giới thiệu

E-Learning Marketplace là ứng dụng web thương mại điện tử khóa học trực tuyến, được xây dựng theo kiến trúc **SPA + REST API** với hai giao diện riêng biệt: cổng học viên (student-facing) và bảng quản trị (admin panel).

Hệ thống cho phép:
- Học viên đăng ký, mua khóa học, học bài, làm quiz
- Giảng viên tạo và quản lý nội dung khóa học
- Admin quản lý toàn bộ hệ thống (người dùng, đơn hàng, nội dung, phân quyền)

---

## 2. Stack công nghệ

| Lớp | Công nghệ | Phiên bản |
|-----|-----------|-----------|
| Backend API | Laravel | ^12.0 (PHP ^8.2) |
| Module system | Nwidart Laravel Modules | ^12.0 |
| Xác thực | Laravel Sanctum | ^4.0 |
| Phân quyền | Spatie Laravel Permission | ^6.24 |
| Danh mục cây | Kalnoy NestedSet | ^6.0 |
| Activity log | Spatie Laravel Activitylog | ^4.0 |
| Frontend | Vue 3 + TypeScript | ^3.5.29 |
| State management | Pinia | ^3.0.4 |
| HTTP client | Axios | ^1.13.6 |
| UI framework | Tailwind CSS + Flowbite | ^3.4.19 |
| Build tool | Vite | ^7.3.1 |
| Validation (FE) | Vee-validate + Zod | ^4.15.1 |
| Database | MySQL | 8.x |
| Queue | Laravel Queue (database driver) | - |
| AI service | Google Gemini API | gemini-2.0-flash |

---

## 3. Kiến trúc tổng thể

```
┌──────────────────────────────────────────────────────────────────┐
│                        FRONTEND (Vue 3 SPA)                      │
│                       http://localhost:5173                       │
│                                                                   │
│  ┌─────────────────────────┐   ┌──────────────────────────────┐  │
│  │   Admin Panel (/admin)  │   │  Student Portal (/)          │  │
│  │  - Dashboard            │   │  - Homepage, Courses          │  │
│  │  - Courses/Lessons CRUD │   │  - Learn Page (video/doc)     │  │
│  │  - Users/Roles          │   │  - Quiz                       │  │
│  │  - Orders/Coupons       │   │  - Payment (VNPay)            │  │
│  │  - Posts/Blog           │   │  - Profile, Orders            │  │
│  └─────────────────────────┘   └──────────────────────────────┘  │
│                         │                   │                     │
│                    AdminLayout          ClientLayout               │
│                    adminToken           studentToken               │
└────────────────────────────────────┬─────────────────────────────┘
                                     │ HTTPS / Axios
                                     │ Authorization: Bearer <token>
                                     ▼
┌──────────────────────────────────────────────────────────────────┐
│                    BACKEND API (Laravel 12)                       │
│                       http://localhost:8000                       │
│                         Prefix: /api/v1                          │
│                                                                   │
│  ┌────────────────────────────────────────────────────────────┐  │
│  │                  Global Middleware Stack                    │  │
│  │  CORS → ThrottleRequests → auth:admin / auth:api           │  │
│  │  EnsureEmailVerified (student action routes)               │  │
│  └────────────────────────────────────────────────────────────┘  │
│                                                                   │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────────────┐   │
│  │   Auth   │ │  Course  │ │  Lessons │ │      Quiz        │   │
│  │  Module  │ │  Module  │ │  Module  │ │ (AI Generation)  │   │
│  └──────────┘ └──────────┘ └──────────┘ └──────────────────┘   │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────────────┐   │
│  │ Payment  │ │  Posts   │ │ Coupons  │ │     Upload       │   │
│  │ (VNPay)  │ │  Module  │ │  Module  │ │  (Local / S3)    │   │
│  └──────────┘ └──────────┘ └──────────┘ └──────────────────┘   │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────────────┐   │
│  │  Users   │ │Students  │ │ Teachers │ │   Dashboard      │   │
│  │  Module  │ │  Module  │ │  Module  │ │    Module        │   │
│  └──────────┘ └──────────┘ └──────────┘ └──────────────────┘   │
│                                                                   │
│  Shared: BaseRepository · ApiResponse Trait · Exception Handler  │
└──────────────────────────────────────┬───────────────────────────┘
                                       │
                    ┌──────────────────┴──────────────────┐
                    │                                     │
                    ▼                                     ▼
          ┌─────────────────┐                  ┌──────────────────┐
          │  MySQL Database │                  │  Laravel Queue   │
          │  (e_learning)   │                  │  (database)      │
          │  27 tables      │                  │  Quiz generation │
          └─────────────────┘                  │  jobs            │
                                               └──────────────────┘
                                                        │
                                                        ▼
                                               ┌──────────────────┐
                                               │  Google Gemini   │
                                               │  AI API          │
                                               └──────────────────┘
```

---

## 4. Luồng request điển hình

### 4.1 Request từ Admin Panel

```
Browser (Admin)
    │
    ├─ 1. Gọi API: GET /api/v1/admin/courses
    │       Authorization: Bearer <adminToken>
    │
    │  Backend
    ├─ 2. CORS check (chỉ localhost:5173)
    ├─ 3. Middleware auth:admin → xác thực token qua guard "admin"
    │       Model: Modules\Users\Models\User
    ├─ 4. Middleware permission → kiểm tra quyền "courses.view" (Spatie)
    ├─ 5. AdminCourseController@index
    ├─ 6. CourseRepository::getFiltered() → Eloquent query MySQL
    ├─ 7. CourseResource transform (loại bỏ sensitive fields)
    └─ 8. ApiResponse::paginated() → JSON response
```

### 4.2 Request từ Student Portal

```
Browser (Student)
    │
    ├─ 1. Gọi API: POST /api/v1/orders
    │       Authorization: Bearer <studentToken>
    │
    │  Backend
    ├─ 2. CORS check
    ├─ 3. Middleware auth:api → xác thực token qua guard "api"
    │       Model: Modules\Students\Models\Student
    ├─ 4. Middleware email.verified → kiểm tra email_verified_at
    ├─ 5. OrderController@store
    ├─ 6. DB::transaction() → tạo Order + OrderItems
    ├─ 7. Gọi VNPay API → lấy payment URL
    └─ 8. Trả về payment_url cho frontend redirect
```

### 4.3 AI Quiz Generation (async)

```
Admin trigger: POST /api/v1/admin/quiz/{id}/generate
    │
    ├─ 1. Validate request (lesson_id, count)
    ├─ 2. Tạo bản ghi QuizGenerationJob (status: pending)
    ├─ 3. Dispatch GenerateQuizJob vào queue
    ├─ 4. Trả về 202 Accepted ngay lập tức
    │
    │  Queue Worker (background)
    ├─ 5. GenerateQuizJob::handle()
    ├─ 6. AIQuizService::generateQuestions() / generateFromPdfText()
    ├─ 7. POST đến Gemini API (timeout: 90s)
    ├─ 8. Parse JSON response
    ├─ 9. Lưu QuizQuestions vào DB
    └─ 10. Cập nhật QuizGenerationJob status: completed / failed
    │
    Frontend polling: GET /api/v1/admin/quiz/{id}/generation-status
    └─ Admin theo dõi tiến trình qua job status
```

---

## 5. Hệ thống xác thực (Dual Guard)

Dự án sử dụng **hai Sanctum guard độc lập**, không bao giờ lẫn lộn:

```
┌─────────────────────────────────────────────────────┐
│                  Guard "admin"                      │
│  Model: Modules\Users\Models\User (bảng: users)     │
│  Token key: adminToken (localStorage)               │
│  Middleware: auth:admin                             │
│  Roles: super-admin, admin, teacher (Spatie RBAC)   │
└─────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────┐
│                  Guard "api"                        │
│  Model: Modules\Students\Models\Student             │
│           (bảng: students)                          │
│  Token key: studentToken (localStorage)             │
│  Middleware: auth:api                               │
│  Extra: email.verified (xác minh email bắt buộc)   │
└─────────────────────────────────────────────────────┘
```

Token không có thời hạn hết hạn (`expiration: null`). Đăng xuất chỉ revoke token hiện tại, cho phép đa phiên.

---

## 6. Phân quyền (RBAC với Spatie)

Tất cả roles/permissions thuộc guard `admin`. Học viên không có hệ thống phân quyền (chỉ xác thực + email verified).

```
super-admin ──── Tất cả permissions
     │
admin ─────────── Tất cả trừ users.delete
     │
teacher ────────── courses.* | lessons.* | dashboard.view
```

Danh sách permissions:
```
users:      view / create / edit / delete
courses:    view / create / edit / delete
categories: view / create / edit / delete
lessons:    view / create / edit / delete
orders:     view / edit
students:   view / edit
dashboard:  view
```

---

## 7. Kiến trúc Backend — Modular Monolith

Backend được tổ chức theo mô hình **Modular Monolith** dùng `nwidart/laravel-modules`. Mỗi feature là một module độc lập, có đầy đủ Controller, Model, Repository, Routes, Migrations riêng.

### 13 Modules hiện có

| Module | Chức năng chính | Guard |
|--------|----------------|-------|
| `Auth` | Đăng ký/đăng nhập admin & student, verify email, reset password | public + api + admin |
| `Users` | Quản lý user admin, roles, activity logs | admin |
| `Students` | Quản lý học viên, profile | admin + api |
| `Teachers` | Quản lý giảng viên | admin + public |
| `Categories` | Danh mục khóa học (NestedSet — cây phân cấp) | admin + public |
| `Course` | CRUD khóa học, enroll, danh sách public | admin + api + public |
| `Lessons` | Sections, Lessons (video/doc/text/quiz), tiến độ học | admin + api |
| `Quiz` | Tạo quiz thủ công + AI generation, lịch sử làm bài | admin + api |
| `Payment` | Đơn hàng, tích hợp VNPay, quản lý doanh thu | admin + api |
| `Coupons` | Mã giảm giá, validate khi checkout | admin + api |
| `Posts` | Blog/bài viết, danh mục, tags, comments | admin + public |
| `Upload` | Upload file local, presigned URL (S3), stream video | admin |
| `Dashboard` | Thống kê tổng quan cho admin | admin |

### Cấu trúc module chuẩn

```
Modules/<Name>/
├── app/
│   ├── Http/
│   │   ├── Controllers/    ← Controller (inject Repository Interface)
│   │   ├── Requests/       ← FormRequest (validation + rules)
│   │   ├── Resources/      ← API Resource (transform output)
│   │   └── Middleware/
│   ├── Models/             ← Eloquent Model
│   └── Repositories/       ← Interface + Implementation
├── database/
│   └── migrations/
├── routes/
│   └── api.php
└── module.json
```

### Design patterns áp dụng

**Repository Pattern**: Controller không tương tác trực tiếp với Model mà qua Interface.

```
Controller → RepositoryInterface → Repository (extends BaseRepository) → Eloquent Model
```

**ApiResponse Trait**: Tất cả Controller `use ApiResponse` để đảm bảo format JSON nhất quán.

**Form Request Validation**: Mọi validation đều ở FormRequest, không bao giờ trong Controller.

---

## 8. Kiến trúc Frontend — Vue 3 SPA

Frontend là Single Page Application với hai layout chính:

```
src/
├── layouts/
│   ├── AdminLayout.vue      ← Sidebar + header cho admin panel
│   └── ClientLayout.vue     ← Header + footer cho student portal
│
├── views/                   ← Pages (lazy-loaded)
│   ├── admin/               ← 16 admin pages
│   ├── client/              ← 16 client pages
│   └── auth/                ← 7 auth pages
│
├── composables/             ← Logic tái sử dụng (18 composables)
├── services/                ← API calls (17 service files)
├── stores/                  ← Pinia state (auth stores + cart)
├── components/              ← UI components (~102 files)
├── types/                   ← TypeScript types (8 files)
└── router/                  ← Vue Router với navigation guards
```

### Luồng dữ liệu Frontend

```
View (thin orchestrator)
    │ gọi composable
    ▼
Composable (useXxx)          ← state + logic + side effects
    │ gọi service
    ▼
Service (xxx.service.ts)     ← Axios HTTP calls, không catch errors
    │
    ▼
Axios instance               ← interceptor: add token header, handle 401
    │
    ▼
Backend API /api/v1
```

### Navigation Guards (router)

```
beforeEach:
  1. Khởi tạo student/admin store nếu có token
  2. Kiểm tra email_verified_at cho student
  3. Redirect về login nếu route requiresAuth và chưa đăng nhập
  4. Redirect về dashboard nếu route requiresGuest và đã đăng nhập
```

---

## 9. Xử lý lỗi (Error Handling)

### Backend — Chuẩn hóa toàn bộ exception về JSON

File `bootstrap/app.php` đăng ký custom exception renderer cho mọi API route:

| Exception | HTTP Code | Ý nghĩa |
|-----------|-----------|---------|
| `ModelNotFoundException` | 404 | Resource không tìm thấy |
| `AuthenticationException` | 401 | Token không hợp lệ / chưa đăng nhập |
| `AccessDeniedHttpException` | 403 | Không có quyền |
| `ValidationException` | 422 | Dữ liệu đầu vào không hợp lệ |
| `MethodNotAllowedHttpException` | 405 | Sai HTTP method |
| `NotFoundHttpException` | 404 | Route không tồn tại |

Tất cả response đều theo format chuẩn:
```json
{
  "success": false,
  "message": "Mô tả lỗi bằng tiếng Việt",
  "data": null,
  "errors": { }
}
```

### Frontend — Catch tại store, không throw lên component

```
Service (throw) → Store (catch, return {success, message, errors}) → Component (kiểm tra result)
```

---

## 10. Bảo mật

| Biện pháp | Chi tiết |
|-----------|---------|
| Token-based auth | Sanctum Bearer token, không dùng cookie/session cho API |
| Dual guard | Admin token không dùng được trên student route và ngược lại |
| Email verification | Student phải verify email trước khi thực hiện hành động (enroll, quiz...) |
| Rate limiting | Login: 5 req/min · Register: 10 req/min · Reset password: 3 req/min |
| CORS | Chỉ whitelist `localhost:5173` |
| Slug validation | Regex `^[a-z0-9]+(?:-[a-z0-9]+)*$` ngăn XSS / path traversal |
| SQL injection | Toàn bộ query qua Eloquent ORM, không có raw SQL trong controller |
| File upload | Validate MIME type + kích thước, chỉ admin mới upload được |
| Password hashing | `bcrypt` với 12 rounds |
| Enum validation | Tất cả enum field được validate trước khi lưu DB |

---

## 11. Các tính năng nổi bật

### AI Quiz Generation (async)
Hệ thống tích hợp Google Gemini API để tự động sinh câu hỏi trắc nghiệm từ tên/mô tả bài học hoặc nội dung PDF. Việc gọi AI được xử lý **bất đồng bộ qua Laravel Queue** để không block HTTP request. Admin theo dõi tiến trình qua polling job status.

### VNPay Payment Integration
Tích hợp cổng thanh toán VNPay với đầy đủ flow: tạo đơn → redirect VNPay → nhận IPN callback (server-to-server) → cập nhật trạng thái đơn hàng. Hỗ trợ thanh toán lại đơn pending.

### Nested Category Tree
Danh mục khóa học được tổ chức dạng cây phân cấp nhiều cấp dùng NestedSet algorithm (`_lft`, `_rgt`, `depth`), cho phép truy vấn ancestors/descendants hiệu quả không cần đệ quy.

### Soft Delete toàn diện
Các resource quan trọng (Course, Lesson, Student) dùng soft delete. Khi xóa Course, hệ thống tự động cascade soft delete xuống Sections và Lessons. Restore Course tự động restore toàn bộ children.

### Media Streaming
Video được serve qua endpoint `/api/v1/media/{id}/stream` hỗ trợ xác thực qua cả Bearer header lẫn `?token=` query param (cần thiết vì thẻ `<video>` không thể gửi header).

---

## 12. Môi trường phát triển

### Cổng dịch vụ

| Service | URL |
|---------|-----|
| Backend API | http://localhost:8000 |
| Frontend SPA | http://localhost:5173 |
| MySQL | 127.0.0.1:3306 / db: `e_learning` |

### Khởi động nhanh

```bash
# Backend (Terminal 1)
cd e-learning-backend
php artisan serve          # HTTP server
php artisan queue:work     # Xử lý AI quiz jobs (cần thiết để quiz generation hoạt động)

# Frontend (Terminal 2)
cd e-learning-frontend
npm run dev

# Reset database
cd e-learning-backend
php artisan migrate:fresh --seed && php artisan storage:link
```

### Tài khoản test (sau khi seed)

| Role | Email | Password |
|------|-------|----------|
| Super Admin | superadmin@elearning.com | password |
| Admin | admin@elearning.com | password |
| Student | student@elearning.com | password |
