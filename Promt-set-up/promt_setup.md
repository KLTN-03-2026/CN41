# 🤖 Prompt dùng cho Claude in VSCode — E-Learning Backend

> Copy toàn bộ phần trong từng block và paste vào Claude chat trong VSCode.
> Làm theo thứ tự từ trên xuống dưới.

---

## PHASE 0 — TASK 1: Fix migration lỗi + tạo BaseRepository & ApiResponse

```
Tôi đang xây dựng backend API cho dự án E-Learning Marketplace bằng Laravel 12.
Kiến trúc: HMVC (nwidart/laravel-modules), API-only (không dùng Blade), Sanctum token, Spatie Permission.

Tình trạng hiện tại:
- Đã cài: laravel/framework ^12.0, laravel/sanctum ^4.0, nwidart/laravel-modules ^12.0, spatie/laravel-permission ^6.24, kalnoy/nestedset ^6.0
- Chạy migrate bị lỗi "Table personal_access_tokens already exists" vì bảng đã có sẵn trong DB
- Chưa có module nào, chưa có migration nào khác

Hãy giúp tôi làm tuần tự các việc sau:

1. Fix lỗi migrate: hướng dẫn cách bỏ qua migration bị conflict (dùng --pretend hoặc đánh dấu đã chạy)

2. Tạo file app/Repositories/RepositoryInterface.php với các method: getAll, find, create, update, delete, paginate

3. Tạo file app/Repositories/BaseRepository.php implement RepositoryInterface dùng Eloquent

4. Tạo file app/Traits/ApiResponse.php với 3 method: success($data, $message, $code), error($message, $code, $errors), paginated($paginator, $message)
   - Response format chuẩn: { success: bool, message: string, data: any, pagination?: object }

5. Cấu hình config/cors.php cho phép origin: http://localhost:5173 (Vue.js dev server)

6. Cấu hình config/auth.php thêm 2 guard:
   - guard 'api' dùng sanctum, provider 'students' → model Modules\Students\Models\Student
   - guard 'admin' dùng sanctum, provider 'admins' → model Modules\Users\Models\User

Sau mỗi file hãy giải thích ngắn tại sao làm vậy.
```

---

## PHASE 0 — TASK 2: Tạo Custom Artisan Command cho Repository

```
Tôi đang dùng Laravel 12 + nwidart/laravel-modules (HMVC).
nwidart không có lệnh tạo Repository sẵn nên tôi cần tự tạo Artisan command.

Hãy tạo file app/Console/Commands/MakeModuleRepository.php:
- Tên lệnh: php artisan make:module-repository {name} {module}
- Tự động tạo 2 file trong Modules/{Module}/app/Repositories/:
  + {Name}RepositoryInterface.php (extends RepositoryInterface từ app/Repositories/)
  + {Name}Repository.php (extends BaseRepository, implements interface trên)
- {Name}Repository tự động có method getModel() trả về tên Model tương ứng
- Sau khi tạo xong in ra thông báo đường dẫn 2 file vừa tạo

Ví dụ chạy: php artisan make:module-repository Course Courses
Kết quả tạo ra:
- Modules/Courses/app/Repositories/CourseRepositoryInterface.php
- Modules/Courses/app/Repositories/CourseRepository.php
```

---

## PHASE 1 — TASK 1: Module Auth (Admin)

```
Tôi đang xây dựng E-Learning Marketplace API với Laravel 12 + nwidart/laravel-modules.
Kiến trúc: API-only, 2 guard: 'api' (Student) và 'admin' (User/Admin).
Đã có: BaseRepository, ApiResponse trait, CORS config.

Hãy tạo Module Auth cho phía Admin với các bước sau:

1. Tạo module: php artisan module:make Auth

2. Tạo migration cho bảng users:
   - id, name varchar(100), email varchar(100) unique, password varchar(255)
   - group_id int nullable (dùng cho sau, chưa cần FK ngay)
   - remember_token, timestamps

3. Tạo Model Modules/Auth/app/Models/AdminUser.php (hoặc để trong module Users sau):
   - $guard_name = 'admin'
   - HasApiTokens (Sanctum)
   - $fillable, $hidden

4. Tạo Modules/Auth/app/Http/Controllers/Admin/AuthController.php với 3 endpoint:
   - POST /api/v1/admin/auth/login    → validate email+password, trả token
   - POST /api/v1/admin/auth/logout   → revoke token hiện tại [middleware auth:admin]
   - GET  /api/v1/admin/auth/me       → trả thông tin admin đang đăng nhập [middleware auth:admin]

5. Tạo Modules/Auth/routes/api.php với các route trên

6. Tạo FormRequest: AdminLoginRequest với validate email required|email, password required|min:6

7. Đăng ký route trong AuthServiceProvider

Response format dùng ApiResponse trait:
{
  "success": true,
  "message": "Đăng nhập thành công",
  "data": { "token": "...", "user": { "id":1, "name":"...", "email":"..." } }
}

Nếu sai credentials trả 401: { "success": false, "message": "Email hoặc mật khẩu không đúng" }
```

---

## PHASE 1 — TASK 2: Module Users + Spatie Permission

```
Laravel 12 + nwidart/laravel-modules + spatie/laravel-permission ^6.24.
Module Auth (Admin login) đã xong. Guard 'admin' đã cấu hình.

Hãy tạo Module Users để quản lý tài khoản admin và phân quyền:

1. Tạo module: php artisan module:make Users

2. Migration bảng users (nếu chưa có từ bước trước) + bảng groups:
   groups: id, name varchar(100), description text nullable, timestamps

3. Model Modules/Users/app/Models/User.php:
   - implements HasRoles (Spatie), HasApiTokens (Sanctum)
   - $guard_name = 'admin'
   - relationship: belongsTo Group

4. Tạo DatabaseSeeder cho module Users:
   - Tạo 3 roles: super-admin, admin, teacher (guard_name: admin)
   - Tạo permissions: courses.view, courses.create, courses.edit, courses.delete, orders.view, students.view (guard_name: admin)
   - Gán tất cả permissions cho role super-admin
   - Tạo 1 user mặc định: name=Super Admin, email=admin@elearning.com, password=password, gán role super-admin

5. Repository:
   - Chạy: php artisan make:module-repository User Users
   - Thêm method vào UserRepositoryInterface: getAll với filter, findByEmail

6. Admin CRUD API tại /api/v1/admin/users [middleware: auth:admin]:
   - GET    /admin/users          → danh sách có phân trang + filter theo role
   - POST   /admin/users          → tạo user mới + gán role
   - GET    /admin/users/{id}     → chi tiết
   - PUT    /admin/users/{id}     → cập nhật
   - DELETE /admin/users/{id}     → xóa (không cho xóa chính mình)
   - POST   /admin/users/{id}/roles → gán role

7. Tạo UserResource: trả về id, name, email, roles (mảng tên role), created_at

Chú ý: dùng ApiResponse trait cho tất cả response.
```

---

## PHASE 1 — TASK 3: Module Auth (Student)

```
Laravel 12 + nwidart/laravel-modules. Guard 'api' dùng cho Student.
Module Auth Admin đã xong. Cần thêm auth cho phía học viên (Client).

Hãy thêm vào Module Auth phần Student với các tính năng:

1. Migration bảng students:
   id, name varchar(100), email varchar(100) unique, phone varchar(20) nullable,
   password varchar(255), avatar varchar(255) nullable, address text nullable,
   status tinyint default 1, email_verified_at timestamp nullable,
   remember_token varchar(100), timestamps

2. Model Modules/Students/app/Models/Student.php (hoặc tạo trong module Students):
   - HasApiTokens (Sanctum), MustVerifyEmail (optional)
   - $guard_name = 'api'
   - $hidden = ['password', 'remember_token']

3. Controller Modules/Auth/app/Http/Controllers/Client/AuthController.php:
   - POST /api/v1/auth/register    → tạo tài khoản, gửi email verify, trả token
   - POST /api/v1/auth/login       → đăng nhập, trả token
   - POST /api/v1/auth/logout      → revoke token [auth:api]
   - GET  /api/v1/auth/me          → thông tin student [auth:api]
   - POST /api/v1/auth/forgot-password → gửi email reset password
   - POST /api/v1/auth/reset-password  → đặt lại password với token

4. FormRequests:
   - StudentRegisterRequest: name required, email required|email|unique:students, password required|min:6|confirmed
   - StudentLoginRequest: email required|email, password required

5. Response đăng ký thành công (201):
{
  "success": true,
  "message": "Đăng ký thành công. Vui lòng kiểm tra email để xác thực tài khoản.",
  "data": { "token": "...", "student": { "id":1, "name":"...", "email":"..." } }
}

6. Thêm routes vào Modules/Auth/routes/api.php (tách nhóm client và admin rõ ràng)

Dùng Laravel built-in Mail + Queue cho email (config MAIL_MAILER trong .env).
Dùng ApiResponse trait cho tất cả response.
```

---

## PHASE 2 — TASK 1: Module Categories (Nested Set)

```
Laravel 12 + nwidart/laravel-modules + kalnoy/nestedset ^6.0.
Các module Auth, Users đã xong.

Hãy tạo Module Categories cho danh mục khóa học với cấu trúc cây (nested set):

1. Tạo module: php artisan module:make Categories

2. Migration bảng categories dùng nestedset:
   id, name varchar(200), slug varchar(200) unique,
   _lft int, _rgt int, parent_id int nullable, timestamps
   (kalnoy/nestedset yêu cầu _lft, _rgt, parent_id)

3. Model Modules/Categories/app/Models/Category.php:
   - use NodeTrait (kalnoy/nestedset)
   - $fillable = ['name', 'slug', 'parent_id']
   - Boot: tự động tạo slug từ name nếu slug trống

4. Repository:
   - php artisan make:module-repository Category Categories
   - Thêm methods: getTree() trả về cây đầy đủ, getTopLevel(), getWithChildren(id)

5. API Endpoints:

   PUBLIC (không cần auth):
   - GET /api/v1/categories         → danh sách dạng cây (nested)
   - GET /api/v1/categories/{slug}  → chi tiết 1 danh mục kèm con

   ADMIN [middleware: auth:admin]:
   - GET    /api/v1/admin/categories         → flat list có phân trang
   - POST   /api/v1/admin/categories         → tạo mới
   - GET    /api/v1/admin/categories/{id}    → chi tiết
   - PUT    /api/v1/admin/categories/{id}    → cập nhật
   - DELETE /api/v1/admin/categories/{id}    → xóa (kiểm tra có con không)

6. CategoryResource trả về: id, name, slug, parent_id, children (nếu có), depth

7. Seeder: tạo 5 danh mục mẫu (Lập trình, Thiết kế, Marketing, Kinh doanh, Ngoại ngữ)
   Một số danh mục có con (ví dụ: Lập trình → PHP, JavaScript, Python)

Dùng ApiResponse trait. Nếu xóa danh mục có con thì trả lỗi 422.
```

---

## PHASE 2 — TASK 2: Module Teachers + Courses

```
Laravel 12 + nwidart/laravel-modules. Module Categories đã xong.

Hãy tạo 2 module liên quan: Teachers và Courses.

=== MODULE TEACHERS ===

1. php artisan module:make Teachers

2. Migration bảng teachers:
   id, name varchar(100), slug varchar(100) unique, description text nullable,
   exp float default 0 (số năm kinh nghiệm), image varchar(255) nullable, timestamps

3. Model + Repository (php artisan make:module-repository Teacher Teachers)

4. Admin CRUD [auth:admin]:
   GET/POST /api/v1/admin/teachers
   GET/PUT/DELETE /api/v1/admin/teachers/{id}

5. Public API:
   GET /api/v1/teachers          → danh sách
   GET /api/v1/teachers/{slug}   → chi tiết kèm danh sách khóa học

=== MODULE COURSES ===

1. php artisan module:make Courses

2. Migration bảng courses:
   id, name varchar(255), slug varchar(255) unique, detail text,
   teacher_id int (FK teachers.id onDelete cascade),
   thumbnail varchar(255), price float default 0, sale_price float nullable,
   code varchar(100) nullable, durations float default 0,
   is_document tinyint default 0, supports text nullable,
   total_lessons int default 0, total_students int default 0,
   status tinyint default 0, timestamps

   Migration bảng categories_courses:
   id, category_id int, course_id int, timestamps
   (unique: category_id + course_id)

3. Model Course:
   - belongsTo Teacher
   - belongsToMany Category (qua categories_courses)
   - hasMany Lesson (sau)
   - belongsToMany Student (qua students_course — sau)
   - Scope: scopePublished → where status = 1

4. Repository + methods:
   - getPublished(filters): filter theo category_id, search (name), sort, paginate
   - findBySlug(slug): with teacher, categories
   - getByTeacher(teacherId)

5. API:
   PUBLIC:
   - GET /api/v1/courses              → list (filter: category_id, search, sort=newest|popular)
   - GET /api/v1/courses/{slug}       → chi tiết kèm teacher, categories, lesson count

   ADMIN [auth:admin]:
   - GET/POST /api/v1/admin/courses
   - GET/PUT/DELETE /api/v1/admin/courses/{id}
   - POST /api/v1/admin/courses/{id}/toggle-status → bật/tắt

6. CourseResource: id, name, slug, price, sale_price, thumbnail, teacher(TeacherResource),
   categories(CategoryResource[]), total_lessons, total_students, status, is_purchased (chỉ khi auth:api)

7. Seeder: 3 khóa học mẫu với đầy đủ thông tin

Dùng ApiResponse trait cho tất cả response.
```

---

## PHASE 2 — TASK 3: Upload Cloudinary + Module Lessons

```
Laravel 12 + nwidart/laravel-modules. Courses đã xong.

BƯỚC 1 — Cài Cloudinary:
composer require cloudinary-labs/cloudinary-laravel
php artisan vendor:publish --provider="CloudinaryLabs\CloudinaryLaravel\CloudinaryServiceProvider"

Thêm vào .env:
CLOUDINARY_URL=cloudinary://API_KEY:API_SECRET@CLOUD_NAME

Tạo Modules/Upload/app/Http/Controllers/Admin/UploadController.php với 2 endpoint [auth:admin]:
- POST /api/v1/admin/upload/video    → upload lên Cloudinary folder 'elearning/videos', resource_type: video
- POST /api/v1/admin/upload/document → upload lên Cloudinary folder 'elearning/documents', resource_type: raw
- Validate: video max 500MB, types: mp4/webm | document max 20MB, types: pdf/doc/docx
- Lưu metadata vào bảng videos (name, url, cloudinary_id) hoặc documents (name, url, size)
- Trả về: { video_id/document_id, url, name }

BƯỚC 2 — Module Lessons:
1. php artisan module:make Lessons

2. Migration bảng videos:
   id, name varchar(255), url varchar(500), cloudinary_id varchar(255) nullable,
   duration float nullable, timestamps

   Migration bảng documents:
   id, name varchar(255), url varchar(500), cloudinary_id varchar(255) nullable,
   size float nullable (MB), timestamps

   Migration bảng lessons:
   id, name varchar(255), slug varchar(255), course_id int (FK courses.id cascade),
   video_id int nullable (FK videos.id setNull), document_id int nullable (FK documents.id setNull),
   parent_id int nullable (để nhóm section), is_trial tinyint default 0,
   views int default 0, position int default 0, duration float nullable,
   description text nullable, timestamps

3. Model Lesson: belongsTo Course, Video, Document

4. Repository + methods: getByCourse(courseId), reorder(ids[])

5. API:
   ADMIN [auth:admin]:
   - GET    /api/v1/admin/courses/{courseId}/lessons  → danh sách bài giảng của khóa học
   - POST   /api/v1/admin/courses/{courseId}/lessons  → tạo bài giảng (nhận video_id, document_id)
   - PUT    /api/v1/admin/lessons/{id}                → cập nhật
   - DELETE /api/v1/admin/lessons/{id}                → xóa
   - POST   /api/v1/admin/courses/{courseId}/lessons/reorder → đổi thứ tự (nhận mảng ids)

   PUBLIC [auth:api để check đã mua chưa]:
   - GET /api/v1/courses/{slug}/lessons → list bài giảng
     + Nếu is_trial=1: trả đầy đủ kể cả video_url
     + Nếu chưa mua: trả info bài giảng nhưng video_url = null
     + Nếu đã mua: trả đầy đủ video_url

6. LessonResource: id, name, slug, position, duration, is_trial, video_url (có thể null), has_document

Dùng ApiResponse trait.
```

---

## PHASE 3 — TASK 1: Orders + Coupons

```
Laravel 12 + nwidart/laravel-modules. Module Courses + Lessons đã xong.
Guard 'api' dùng cho Student đã setup.

Hãy tạo hệ thống đơn hàng và mã giảm giá:

=== MODULE ORDERS ===

1. php artisan module:make Orders

2. Migrations:

   Bảng students_course (đã mua):
   id, student_id int, course_id int, created_at, updated_at
   unique(student_id, course_id)

   Bảng coupons:
   id, code varchar(100) unique, type enum('percent','fixed') default 'percent',
   value float not null, min_order float default 0,
   max_uses int default 0 (0=không giới hạn), used_count int default 0,
   starts_at timestamp nullable, expires_at timestamp nullable, timestamps

   Bảng orders:
   id, student_id int (FK students.id), coupon_id int nullable (FK coupons.id),
   total float, discount float default 0,
   payment_method varchar(50) nullable (vnpay|momo|free),
   payment_status varchar(50) default 'pending' (pending|paid|failed),
   transaction_id varchar(255) nullable, timestamps

   Bảng order_details:
   id, order_id int (FK orders.id cascade), course_id int,
   price float (giá lúc mua), timestamps

3. Models: Order (belongsTo Student, hasMany OrderDetail, belongsTo Coupon),
   OrderDetail (belongsTo Order, belongsTo Course),
   Coupon (hasMany Order)

4. API [middleware: auth:api]:
   - POST /api/v1/cart/validate          → kiểm tra danh sách course_ids hợp lệ, tính tổng tiền
   - POST /api/v1/orders/apply-coupon    → body: {code, course_ids[]} → validate coupon, trả discount amount
   - POST /api/v1/orders                 → tạo đơn: {course_ids[], coupon_code?}
     + Kiểm tra student chưa mua các khóa này
     + Nếu total=0 (khóa miễn phí hoặc giảm 100%): tạo order paid, mở khóa luôn
     + Nếu total>0: tạo order pending, trả order_id để tiếp tục qua payment
   - GET /api/v1/orders                  → lịch sử đơn hàng của student
   - GET /api/v1/my-courses              → danh sách khóa học đã mua (từ students_course)

5. Logic validate coupon:
   - Kiểm tra code tồn tại và status active
   - Kiểm tra thời gian (starts_at <= now <= expires_at)
   - Kiểm tra max_uses (nếu > 0 thì used_count < max_uses)
   - Kiểm tra min_order (total >= min_order)
   - Tính discount: type=percent → total * value/100, type=fixed → min(value, total)

Dùng DB::transaction khi tạo order. Dùng ApiResponse trait.
```

---

## PHASE 3 — TASK 2: Payment VNPAY + MoMo

```
Laravel 12 + nwidart/laravel-modules. Module Orders đã xong.

.env đã có:
VNPAY_TMN_CODE=
VNPAY_HASH_SECRET=
VNPAY_URL=https://sandbox.vnpayment.vn/paymentv2/vpcpay.html
VNPAY_RETURN_URL="${APP_URL}/api/v1/payment/vnpay/callback"
MOMO_PARTNER_CODE=
MOMO_ACCESS_KEY=
MOMO_SECRET_KEY=
MOMO_ENDPOINT=https://test-payment.momo.vn/v2/gateway/api/create

Hãy tạo Module Payment:

1. php artisan module:make Payment

2. Tạo app/Services/VnpayService.php (đặt ở app/ để dùng chung):
   - Method createPaymentUrl(order_id, amount, order_desc, return_url): build URL VNPAY
   - Method verifyCallback(request_data): xác thực vnp_SecureHash bằng HMAC SHA512
   - Đọc config từ config/services.php: services.vnpay.tmn_code, hash_secret, url

3. Tạo app/Services/MomoService.php:
   - Method createPayment(order_id, amount, redirect_url, ipn_url): gọi MoMo API
   - Method verifyCallback(request_data): xác thực signature bằng HMAC SHA256
   - Đọc config từ config/services.php: services.momo.*

4. Thêm vào config/services.php:
   'vnpay' => [ 'tmn_code'=>env('VNPAY_TMN_CODE'), 'hash_secret'=>env('VNPAY_HASH_SECRET'), 'url'=>env('VNPAY_URL'), 'return_url'=>env('VNPAY_RETURN_URL') ],
   'momo'  => [ 'partner_code'=>env('MOMO_PARTNER_CODE'), 'access_key'=>env('MOMO_ACCESS_KEY'), 'secret_key'=>env('MOMO_SECRET_KEY'), 'endpoint'=>env('MOMO_ENDPOINT') ]

5. Controller Modules/Payment/app/Http/Controllers/PaymentController.php:

   POST /api/v1/payment/vnpay [auth:api]:
   - Nhận order_id, lấy order thuộc student đang đăng nhập
   - Kiểm tra order status = pending
   - Gọi VnpayService::createPaymentUrl
   - Trả về { payment_url }

   GET /api/v1/payment/vnpay/callback (KHÔNG cần auth — VNPAY gọi về):
   - Xác thực hash với VnpayService::verifyCallback
   - Nếu vnp_ResponseCode = '00': cập nhật order paid, mở khóa khóa học (insert students_course), tăng total_students
   - Nếu thất bại: cập nhật order failed
   - Redirect về frontend: {FRONTEND_URL}/payment/result?status=success&order_id=X
   - Thêm FRONTEND_URL= http://localhost:5173 vào .env

   POST /api/v1/payment/momo [auth:api]:
   - Tương tự VNPAY, gọi MomoService::createPayment
   - Trả về { payment_url }

   POST /api/v1/payment/momo/callback (KHÔNG cần auth — MoMo gọi về):
   - Xác thực signature
   - resultCode = 0 là thành công
   - Logic mở khóa khóa học giống VNPAY callback

6. Tất cả logic mở khóa khóa học sau thanh toán đặt trong 1 private method unlockCourses(Order $order)
   dùng DB::transaction để đảm bảo atomic.

Thêm FRONTEND_URL vào .env và dùng config('app.frontend_url').
```

---

## PHASE 4 — TASK 1: Progress + AI Quiz + Dashboard

```
Laravel 12 + nwidart/laravel-modules. Payment đã xong.
.env đã có: OPENAI_API_KEY= và OPENAI_MODEL=gpt-4o-mini

Hãy làm 3 tính năng cuối cùng:

=== 1. MODULE PROGRESS ===

Migration bảng lesson_progress:
id, student_id int, lesson_id int, course_id int,
is_completed tinyint default 0, watched_seconds int default 0,
completed_at timestamp nullable, timestamps
unique(student_id, lesson_id)

API [auth:api]:
- POST /api/v1/lessons/{id}/progress
  body: { watched_seconds, is_completed }
  Logic: upsert vào lesson_progress, nếu is_completed=true set completed_at=now()
- GET /api/v1/courses/{slug}/progress
  Trả về: { total_lessons, completed_lessons, percent, lessons: [{id, name, is_completed, watched_seconds}] }

=== 2. MODULE QUIZ (AI Auto-Quiz) ===

composer require spatie/pdf-to-text

Migration quizzes: id, lesson_id int, title varchar(255), status tinyint default 0, timestamps
Migration quiz_questions: id, quiz_id int, question text, options json, answer varchar(10), timestamps

Tạo app/Services/QuizAiService.php:
- Method generate(string $text): string $text là nội dung PDF/TXT đã extract
  + Cắt $text còn tối đa 8000 ký tự
  + Gọi OpenAI API (https://api.openai.com/v1/chat/completions) với model từ config
  + System prompt: "Bạn là giáo viên. Tạo 10 câu hỏi trắc nghiệm từ tài liệu. Trả về JSON với format: {\"questions\":[{\"question\":\"...\",\"options\":[\"A. ...\",\"B. ...\",\"C. ...\",\"D. ...\"],\"answer\":\"A\"}]}"
  + Dùng Illuminate\Support\Facades\Http để gọi API, header Authorization: Bearer OPENAI_API_KEY
  + Parse response, trả về array questions

API ADMIN [auth:admin]:
- POST /api/v1/admin/lessons/{id}/generate-quiz
  + Upload file PDF/TXT hoặc nhận document_id đã có
  + Extract text bằng Spatie\PdfToText\Pdf hoặc file_get_contents (nếu txt)
  + Gọi QuizAiService::generate
  + Tạo Quiz record + lưu QuizQuestion records
  + Trả về danh sách câu hỏi vừa tạo

API CLIENT [auth:api — đã mua khóa học]:
- GET /api/v1/lessons/{id}/quiz → lấy quiz của bài giảng (không trả answer)

=== 3. MODULE DASHBOARD ===

API ADMIN [auth:admin]:
- GET /api/v1/admin/dashboard/stats
  Trả về:
  { total_students, total_courses, total_orders_paid, total_revenue,
    new_students_this_month, new_orders_this_month }

- GET /api/v1/admin/dashboard/revenue?period=daily|monthly&year=2026&month=3
  Trả về mảng [{ label: "01/03", revenue: 500000 }, ...]

- GET /api/v1/admin/dashboard/top-courses?limit=5
  Trả về top khóa học theo total_students: [{id, name, thumbnail, total_students, revenue}]

Dùng DB::select hoặc Eloquent aggregates. Dùng ApiResponse trait.
Không cần tạo Repository riêng cho Dashboard, dùng thẳng Eloquent trong Controller.
```

---

> 💡 Tip khi dùng với Claude in VSCode:
> - Paste 1 task một lúc, chờ xong rồi mới paste task tiếp theo
> - Nếu Claude hỏi thêm thông tin, trả lời rồi paste lại prompt gốc kèm thông tin bổ sung
> - Sau mỗi task nên chạy `php artisan optimize:clear` để clear cache