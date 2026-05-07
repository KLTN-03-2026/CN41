# Hệ thống xác thực (Authentication)

## 1. Tổng quan

Hệ thống dùng **Laravel Sanctum** với **hai guard hoàn toàn độc lập** — không bao giờ dùng lẫn token giữa admin và student:

| | Admin | Student |
|-|-------|---------|
| Guard | `admin` | `api` |
| Model | `Modules\Users\Models\User` | `Modules\Students\Models\Student` |
| Token name | `admin-token` | `student-token` |
| LocalStorage key | `adminToken` | `studentToken` |
| Phân quyền | Spatie RBAC (super-admin / admin / teacher) | Không có role |
| Xác minh email | Không bắt buộc | Bắt buộc trước khi thực hiện hành động |
| Reset password | Không có | Có (qua email, 60 phút TTL) |

Token không có thời hạn (`expiration: null`). Mỗi lần login tạo token mới, đăng xuất chỉ revoke token hiện tại — đa phiên đăng nhập được phép.

---

## 2. API Endpoints

### Admin Auth (`/api/v1/admin/auth`)

| Method | Endpoint | Middleware | Mô tả |
|--------|----------|-----------|-------|
| POST | `/admin/auth/login` | throttle:5,1 | Đăng nhập |
| POST | `/admin/auth/logout` | auth:admin | Đăng xuất |
| GET | `/admin/auth/me` | auth:admin | Thông tin tài khoản |

### Student Auth (`/api/v1/auth`)

| Method | Endpoint | Middleware | Mô tả |
|--------|----------|-----------|-------|
| POST | `/auth/register` | throttle:10,1 | Đăng ký |
| POST | `/auth/login` | throttle:5,1 | Đăng nhập |
| POST | `/auth/logout` | auth:api | Đăng xuất |
| GET | `/auth/me` | auth:api | Thông tin tài khoản |
| GET | `/auth/verify-email/{token}` | - | Xác minh email (link từ mail) |
| POST | `/auth/resend-verification` | throttle:3,1 | Gửi lại email xác minh |
| POST | `/auth/forgot-password` | throttle:3,1 | Quên mật khẩu |
| POST | `/auth/reset-password` | throttle:3,1 | Đặt lại mật khẩu |

---

## 3. Luồng đăng nhập Admin

```
POST /api/v1/admin/auth/login
Body: { "email": "...", "password": "..." }
  │
  ▼
Admin\AuthController::login()
  │
  ├── Validate: email (required), password (required)
  │
  ├── Auth::guard('admin')->attempt([email, password])
  │     └── Dùng provider 'admins' → model Users\User
  │
  ├── [Fail] → 401 "Email hoặc mật khẩu không đúng."
  │
  ├── [Success]
  │     ├── $user->createToken('admin-token') → Sanctum token
  │     ├── Log activity: "AdminLoggedIn" event
  │     └── Return 200: { token, user: { id, name, email, roles, permissions } }
  │
  └── [Throttle exceeded] → 429 Too Many Requests
```

**Response thành công:**
```json
{
  "success": true,
  "message": "Đăng nhập thành công",
  "data": {
    "token": "1|abc123...",
    "user": {
      "id": 1,
      "name": "Admin",
      "email": "admin@elearning.com",
      "roles": ["admin"],
      "permissions": ["courses.view", "courses.create", ...]
    }
  }
}
```

---

## 4. Luồng đăng ký Student

```
POST /api/v1/auth/register
Body: { "name": "...", "email": "...", "password": "...", "password_confirmation": "..." }
  │
  ▼
Student\AuthController::register()
  │
  ├── Validate:
  │     name: required, max:255
  │     email: required, email, unique:students
  │     password: min:8, max:100, confirmed
  │
  ├── DB::transaction():
  │     ├── Student::create([name, email, password: bcrypt])
  │     ├── Tạo email verification token:
  │     │     token = bin2hex(random_bytes(32))   // 64 ký tự hex
  │     │     expires_at = now()->addHours(24)
  │     │     Lưu vào student_email_verifications
  │     └── Dispatch SendVerificationEmail job (async)
  │
  ├── $student->createToken('student-token') → Sanctum token
  │
  └── Return 201: { token, student: { id, name, email } }
      (email_verified_at = null — chưa verify)
```

**Lưu ý:** Student nhận được token ngay sau đăng ký nhưng **chưa thể thực hiện hành động** (enroll, quiz, order...) cho đến khi verify email.

---

## 5. Luồng xác minh email Student

```
[Email inbox]
  │
  │  Link: GET /api/v1/auth/verify-email/{token}
  │        token = 64 ký tự hex (bin2hex random_bytes 32)
  ▼
Student\AuthController::verifyEmail()
  │
  ├── Tìm bản ghi trong student_email_verifications WHERE token = ?
  │
  ├── [Không tìm thấy] → redirect frontend?status=invalid
  │
  ├── [expires_at < now()] → redirect frontend?status=expired
  │
  ├── [student.email_verified_at != null] → redirect frontend?status=already
  │
  ├── [Hợp lệ]
  │     ├── $student->update(['email_verified_at' => now()])
  │     ├── Delete token record (one-time use)
  │     └── redirect frontend/verify-email/result?status=success
  │
  └── Frontend VerifyEmailResultPage hiển thị kết quả theo status
```

**Bảng `student_email_verifications`:**
```
student_id  →  FK students
token       →  64-char hex string (unique)
expires_at  →  now() + 24 giờ
```

---

## 6. Middleware `EnsureEmailVerified`

Áp dụng cho tất cả student action routes (enroll, quiz submit, order, cart...):

```php
// Nếu chưa verify email → 403 JSON (không redirect)
return response()->json([
    'success' => false,
    'message' => 'Tài khoản chưa được kích hoạt.',
    'data'    => null,
    'errors'  => [
        'email_not_verified' => true,
        'email'              => $student->email,
    ],
], 403);
```

Frontend nhận `errors.email_not_verified = true` → hiển thị UI "Gửi lại email xác minh".

---

## 7. Luồng đăng nhập Student

```
POST /api/v1/auth/login
Body: { "email": "...", "password": "..." }
  │
  ▼
Student\AuthController::login()
  │
  ├── Validate: email, password
  │
  ├── Auth::guard('api')->attempt([email, password])
  │
  ├── [Fail] → 401 "Email hoặc mật khẩu không đúng."
  │
  ├── [email_verified_at == null]
  │     └── 403: { message: "Chưa xác minh email", errors: { email_not_verified: true } }
  │
  ├── [Success]
  │     ├── $student->createToken('student-token')
  │     └── Return 200: { token, student: { id, name, email, avatar, email_verified_at } }
  │
  └── [Throttle] → 429
```

---

## 8. Luồng quên/reset mật khẩu (Student)

```
[Bước 1] POST /api/v1/auth/forgot-password
         Body: { "email": "..." }
  │
  ▼
  ├── Password::broker('students')->sendResetLink([email])
  │     ├── Tìm student theo email
  │     ├── [Không tìm thấy] → vẫn return 200 (tránh user enumeration)
  │     └── Lưu token vào password_reset_tokens, gửi email link
  │
  └── Return 200: { message: "Nếu email tồn tại, link đặt lại mật khẩu đã được gửi." }

[Bước 2] POST /api/v1/auth/reset-password
         Body: { "token": "...", "email": "...", "password": "...", "password_confirmation": "..." }
  │
  ▼
  ├── Password::broker('students')->reset(credentials, callback)
  │     ├── Validate token (60 phút TTL, throttle 60 giây/lần)
  │     ├── [Invalid/Expired] → 422 "Token không hợp lệ hoặc đã hết hạn."
  │     ├── [Valid] → update password, delete token
  │     └── Return 200: { message: "Đặt lại mật khẩu thành công." }
  │
  └── [Throttle 3 req/min] → 429
```

**Bảo mật:** `forgot-password` luôn trả về 200 cho cả email hợp lệ lẫn không hợp lệ — ngăn kẻ tấn công dò email tồn tại.

---

## 9. Navigation Guard (Frontend)

File [src/router/index.js](../../e-learning-frontend/src/router/index.js) xử lý điều hướng:

```
router.beforeEach(to, from):
  │
  ├── [Có studentToken]
  │     ├── Fetch student info nếu chưa có (studentStore.fetchMe())
  │     └── [email_verified_at == null]
  │           └── Redirect /verify-email
  │               (trừ: /verify-email, /verify-email/result, /login, /register)
  │
  ├── [Có adminToken + đến trang admin]
  │     └── Fetch admin info nếu chưa có
  │
  ├── [route.meta.requiresAuth]
  │     ├── guard = 'admin' + không có adminToken → /admin/login
  │     └── guard = 'student' + không có studentToken → /login?redirect=...
  │
  └── [route.meta.requiresGuest]
        ├── guard = 'admin' + có adminToken → /admin/dashboard
        └── guard = 'student' + có studentToken → /
```

**Route meta conventions:**
```js
{ requiresAuth: true,  guard: 'admin'   }  // trang admin cần đăng nhập
{ requiresAuth: true,  guard: 'student' }  // trang student cần đăng nhập
{ requiresGuest: true, guard: 'admin'   }  // chỉ guest admin (/admin/login)
{ requiresGuest: true, guard: 'student' }  // chỉ guest student (/login, /register)
```

---

## 10. Pinia Auth Stores

### `adminAuth.store.ts`
```ts
state: { token, user, loading }
actions:
  login(email, password)     → POST /admin/auth/login → lưu token vào localStorage
  logout()                   → POST /admin/auth/logout → xóa token
  fetchMe()                  → GET /admin/auth/me → cập nhật user + roles
```

### `studentAuth.store.ts`
```ts
state: { token, student, loading }
actions:
  login(email, password)     → POST /auth/login → lưu token
  register(data)             → POST /auth/register → lưu token (chưa verified)
  logout()                   → POST /auth/logout → xóa token
  fetchMe()                  → GET /auth/me → cập nhật student info
  verifyEmailCheck()         → kiểm tra email_verified_at trong store
```

Cả hai store đều:
- Lưu token vào `localStorage` khi login
- Xóa token khỏi `localStorage` khi logout
- Return `{ success: boolean, message?, errors? }` — không throw lên component

---

## 11. Axios Interceptors

```
Request interceptor:
  → Thêm header: Authorization: Bearer <token>
     (lấy từ localStorage theo guard tương ứng)

Response interceptor:
  → [HTTP 401] → Xóa token, redirect về trang login
  → [Khác]    → Throw error để store/composable xử lý
```
