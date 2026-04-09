# 📘 API Endpoints Documentation

> **Base URL:** `/api/v1`
> **Auth method:** Bearer Token (Laravel Sanctum)
> **Response format:** JSON chuẩn `ApiResponse`

---

## 📦 Response Format Chuẩn

### ✅ Success Response

```json
{
  "success": true,
  "message": "Thành công",
  "data": { ... }
}
```

### ❌ Error Response

```json
{
  "success": false,
  "message": "Có lỗi xảy ra",
  "data": null,
  "errors": { ... }
}
```

### 📄 Paginated Response

```json
{
  "success": true,
  "message": "Thành công",
  "data": [ ... ],
  "pagination": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 15,
    "total": 72,
    "from": 1,
    "to": 15
  }
}
```

---

## 🔐 Module: Auth

Module xử lý xác thực cho Admin (guard: `admin`) và Student (guard: `api`).

### 🛡️ Admin Auth

### 1. Đăng nhập Admin

| Thuộc tính     | Giá trị                          |
|----------------|----------------------------------|
| **Method**     | `POST`                           |
| **URL**        | `/api/v1/admin/auth/login`       |
| **Auth**       | ❌ Không cần (Public)             |
| **Middleware**  | `api`, `throttle:5,1`           |
| **Controller** | `AuthController@login`           |
| **Request**    | `AdminLoginRequest`              |

#### 📥 Request Body

| Field      | Type   | Required | Rules                          |
|------------|--------|----------|--------------------------------|
| `email`    | string | ✅       | required, email, max:255       |
| `password` | string | ✅       | required, string, min:6, max:100 |

#### 📤 Response — `200 OK`

```json
{
  "success": true,
  "message": "Đăng nhập thành công.",
  "data": {
    "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
    "user": {
      "id": 1,
      "name": "Admin",
      "email": "admin@example.com"
    }
  }
}
```

#### ❌ Response — `401 Unauthorized`

```json
{
  "success": false,
  "message": "Email hoặc mật khẩu không đúng.",
  "data": null
}
```

#### ❌ Response — `422 Validation Error`

**Trường hợp 1:** Thiếu cả email và password

```json
{
  "success": false,
  "message": "Dữ liệu không hợp lệ.",
  "errors": {
    "email": ["Email không được để trống."],
    "password": ["Mật khẩu không được để trống."]
  }
}
```

**Trường hợp 2:** Chỉ thiếu email (không gửi field `email`)

```json
{
  "success": false,
  "message": "Dữ liệu không hợp lệ.",
  "errors": {
    "email": ["Email không được để trống."]
  }
}
```

**Trường hợp 3:** Email sai định dạng (ví dụ: `"abc"`, `"abc@"`, ...)

```json
{
  "success": false,
  "message": "Dữ liệu không hợp lệ.",
  "errors": {
    "email": ["Email không đúng định dạng."]
  }
}
```

**Trường hợp 4:** Email vượt quá 255 ký tự

```json
{
  "success": false,
  "message": "Dữ liệu không hợp lệ.",
  "errors": {
    "email": ["Email không được vượt quá 255 ký tự."]
  }
}
```

**Trường hợp 5:** Chỉ thiếu password (không gửi field `password`)

```json
{
  "success": false,
  "message": "Dữ liệu không hợp lệ.",
  "errors": {
    "password": ["Mật khẩu không được để trống."]
  }
}
```

**Trường hợp 6:** Password quá ngắn (dưới 6 ký tự)

```json
{
  "success": false,
  "message": "Dữ liệu không hợp lệ.",
  "errors": {
    "password": ["Mật khẩu phải có ít nhất 6 ký tự."]
  }
}
```

**Trường hợp 7:** Password quá dài (vượt 100 ký tự)

```json
{
  "success": false,
  "message": "Dữ liệu không hợp lệ.",
  "errors": {
    "password": ["Mật khẩu không được vượt quá 100 ký tự."]
  }
}
```

---

### 2. Đăng xuất Admin

| Thuộc tính     | Giá trị                          |
|----------------|----------------------------------|
| **Method**     | `POST`                           |
| **URL**        | `/api/v1/admin/auth/logout`      |
| **Auth**       | ✅ Bearer Token (guard: admin)    |
| **Middleware**  | `api`, `auth:admin`             |
| **Controller** | `AuthController@logout`          |

#### 📥 Request Header

```
Authorization: Bearer {token}
```

#### 📤 Response — `200 OK`

```json
{
  "success": true,
  "message": "Đăng xuất thành công.",
  "data": null
}
```

#### ❌ Response — `401 Unauthorized`

```json
{
  "success": false,
  "message": "Unauthenticated.",
  "data": null
}
```

---

### 3. Lấy thông tin Admin hiện tại

| Thuộc tính     | Giá trị                          |
|----------------|----------------------------------|
| **Method**     | `GET`                            |
| **URL**        | `/api/v1/admin/auth/me`          |
| **Auth**       | ✅ Bearer Token (guard: admin)    |
| **Middleware**  | `api`, `auth:admin`             |
| **Controller** | `AuthController@me`              |

#### 📥 Request Header

```
Authorization: Bearer {token}
```

#### 📤 Response — `200 OK`

```json
{
  "success": true,
  "message": "Thành công",
  "data": {
    "id": 1,
    "name": "Admin",
    "email": "admin@example.com",
    "created_at": "2026-03-17T09:00:00.000000Z"
  }
}
```

---

### 🎓 Student Auth

---

### 4. Đăng ký Student

| Thuộc tính     | Giá trị                          |
|----------------|----------------------------------|
| **Method**     | `POST`                           |
| **URL**        | `/api/v1/auth/register`          |
| **Auth**       | ❌ Không cần (Public)             |
| **Middleware**  | `api`, `throttle:10,1`          |
| **Controller** | `StudentAuthController@register` |
| **Request**    | `RegisterRequest`                |

#### 📥 Request Body

| Field                   | Type   | Required | Rules                                      |
|-------------------------|--------|----------|--------------------------------------------|
| `name`                  | string | ✅       | required, string, max:255                  |
| `email`                 | string | ✅       | required, email, max:255, unique:students  |
| `password`              | string | ✅       | required, string, min:8, max:100, confirmed|
| `password_confirmation` | string | ✅       | required, string                           |

#### 📤 Response — `201 Created`

```json
{
  "success": true,
  "message": "Đăng ký thành công. Vui lòng kiểm tra email để xác thực tài khoản.",
  "data": {
    "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
    "student": {
      "id": 1,
      "name": "Nguyễn Văn A",
      "email": "student@example.com",
      "email_verified_at": null
    }
  }
}
```

#### ❌ Response — `422 Validation Error`

```json
{
  "success": false,
  "message": "Dữ liệu không hợp lệ.",
  "errors": {
    "name": ["Tên không được để trống."],
    "email": ["Email đã được sử dụng."],
    "password": ["Mật khẩu tối thiểu 8 ký tự."],
    "password_confirmation": ["Xác nhận mật khẩu không được để trống."]
  }
}
```

---

### 5. Đăng nhập Student

| Thuộc tính     | Giá trị                          |
|----------------|----------------------------------|
| **Method**     | `POST`                           |
| **URL**        | `/api/v1/auth/login`             |
| **Auth**       | ❌ Không cần (Public)             |
| **Middleware**  | `api`, `throttle:5,1`           |
| **Controller** | `StudentAuthController@login`    |
| **Request**    | `LoginRequest`                   |

#### 📥 Request Body

| Field      | Type   | Required | Rules                            |
|------------|--------|----------|----------------------------------|
| `email`    | string | ✅       | required, email, max:255         |
| `password` | string | ✅       | required, string, min:6, max:100 |

#### 📤 Response — `200 OK`

```json
{
  "success": true,
  "message": "Đăng nhập thành công.",
  "data": {
    "token": "2|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
    "student": {
      "id": 1,
      "name": "Nguyễn Văn A",
      "email": "student@example.com",
      "email_verified_at": "2026-03-18T10:30:00.000000Z"
    }
  }
}
```

#### ❌ Response — `401 Unauthorized`

```json
{
  "success": false,
  "message": "Email hoặc mật khẩu không đúng.",
  "data": null
}
```

#### ❌ Response — `422 Validation Error`

```json
{
  "success": false,
  "message": "Dữ liệu không hợp lệ.",
  "errors": {
    "email": ["Email không được để trống."],
    "password": ["Mật khẩu không được để trống."]
  }
}
```

---

### 6. Xác thực Email Student

| Thuộc tính     | Giá trị                                    |
|----------------|--------------------------------------------|
| **Method**     | `GET`                                      |
| **URL**        | `/api/v1/auth/verify-email/{token}`        |
| **Auth**       | ❌ Không cần (Public)                       |
| **Middleware**  | `api`                                     |
| **Controller** | `StudentAuthController@verifyEmail`        |
| **Constraint** | `token` phải là chuỗi hex 64 ký tự        |

#### 📥 Path Parameters

| Param   | Type   | Mô tả                                                      |
|---------|--------|-------------------------------------------------------------|
| `token` | string | Token xác thực email (hex, 64 ký tự, lấy từ DB hoặc email) |

#### 📤 Response — `200 OK`

```json
{
  "success": true,
  "message": "Xác thực email thành công.",
  "data": null
}
```

#### ❌ Response — `400 Bad Request`

**Token không hợp lệ:**
```json
{
  "success": false,
  "message": "Token xác thực không hợp lệ.",
  "data": null
}
```

**Token đã hết hạn:**
```json
{
  "success": false,
  "message": "Token xác thực đã hết hạn. Vui lòng đăng ký lại.",
  "data": null
}
```

**Email đã xác thực trước đó:**
```json
{
  "success": false,
  "message": "Email đã được xác thực trước đó.",
  "data": null
}
```

#### ❌ Response — `404 Not Found`

```json
{
  "success": false,
  "message": "Tài khoản không tồn tại.",
  "data": null
}
```

---

### 7. Lấy thông tin Student hiện tại

| Thuộc tính     | Giá trị                          |
|----------------|----------------------------------|
| **Method**     | `GET`                            |
| **URL**        | `/api/v1/auth/me`                |
| **Auth**       | ✅ Bearer Token (guard: api)      |
| **Middleware**  | `api`, `auth:api`               |
| **Controller** | `StudentAuthController@me`       |

#### 📥 Request Header

```
Authorization: Bearer {token}
```

#### 📤 Response — `200 OK`

```json
{
  "success": true,
  "message": "Thành công",
  "data": {
    "id": 1,
    "name": "Nguyễn Văn A",
    "email": "student@example.com",
    "avatar": null,
    "email_verified_at": "2026-03-18T10:30:00.000000Z",
    "created_at": "2026-03-18T10:25:00.000000Z"
  }
}
```

#### ❌ Response — `401 Unauthorized`

```json
{
  "success": false,
  "message": "Unauthenticated.",
  "data": null
}
```

---

### 8. Đăng xuất Student

| Thuộc tính     | Giá trị                          |
|----------------|----------------------------------|
| **Method**     | `POST`                           |
| **URL**        | `/api/v1/auth/logout`            |
| **Auth**       | ✅ Bearer Token (guard: api)      |
| **Middleware**  | `api`, `auth:api`               |
| **Controller** | `StudentAuthController@logout`   |

#### 📥 Request Header

```
Authorization: Bearer {token}
```

#### 📤 Response — `200 OK`

```json
{
  "success": true,
  "message": "Đăng xuất thành công.",
  "data": null
}
```

#### ❌ Response — `401 Unauthorized`

```json
{
  "success": false,
  "message": "Unauthenticated.",
  "data": null
}
```

---

### 9. Quên mật khẩu Student

| Thuộc tính     | Giá trị                                |
|----------------|----------------------------------------|
| **Method**     | `POST`                                 |
| **URL**        | `/api/v1/auth/forgot-password`         |
| **Auth**       | ❌ Không cần (Public)                   |
| **Middleware**  | `api`, `throttle:3,1`                 |
| **Controller** | `StudentAuthController@forgotPassword` |
| **Request**    | `ForgotPasswordRequest`                |

#### 📥 Request Body

| Field   | Type   | Required | Rules                                    |
|---------|--------|----------|------------------------------------------|
| `email` | string | ✅       | required, email, max:255, exists:students|

#### 📤 Response — `200 OK`

```json
{
  "success": true,
  "message": "Nếu email tồn tại trong hệ thống, link đặt lại mật khẩu sẽ được gửi về email.",
  "data": null
}
```

#### ❌ Response — `422 Validation Error`

```json
{
  "success": false,
  "message": "Dữ liệu không hợp lệ.",
  "errors": {
    "email": ["Email không tồn tại trong hệ thống."]
  }
}
```

#### ❌ Response — `429 Too Many Requests`

```json
{
  "success": false,
  "message": "Vui lòng chờ trước khi gửi lại."
}
```

---

### 10. Đặt lại mật khẩu Student

| Thuộc tính     | Giá trị                                |
|----------------|----------------------------------------|
| **Method**     | `POST`                                 |
| **URL**        | `/api/v1/auth/reset-password`          |
| **Auth**       | ❌ Không cần (Public)                   |
| **Middleware**  | `api`                                 |
| **Controller** | `StudentAuthController@resetPassword`  |
| **Request**    | `ResetPasswordRequest`                 |

#### 📥 Request Body

| Field                   | Type   | Required | Rules                                        |
|-------------------------|--------|----------|----------------------------------------------|
| `token`                 | string | ✅       | required, string                             |
| `email`                 | string | ✅       | required, email, max:255, exists:students    |
| `password`              | string | ✅       | required, string, min:8, max:100, confirmed  |
| `password_confirmation` | string | ✅       | required, string                             |

#### 📤 Response — `200 OK`

```json
{
  "success": true,
  "message": "Đặt lại mật khẩu thành công.",
  "data": null
}
```

#### ❌ Response — `400 Bad Request`

**Token không hợp lệ:**
```json
{
  "success": false,
  "message": "Token không hợp lệ hoặc đã hết hạn.",
  "data": null
}
```

**Email không tồn tại:**
```json
{
  "success": false,
  "message": "Email không tồn tại trong hệ thống.",
  "data": null
}
```

#### ❌ Response — `422 Validation Error`

```json
{
  "success": false,
  "message": "Dữ liệu không hợp lệ.",
  "errors": {
    "token": ["Token không được để trống."],
    "email": ["Email không đúng định dạng."],
    "password": ["Mật khẩu tối thiểu 8 ký tự."],
    "password_confirmation": ["Xác nhận mật khẩu không được để trống."]
  }
}
```

---

### 11. Gửi lại email xác thực Student

| Thuộc tính     | Giá trị                                        |
|----------------|------------------------------------------------|
| **Method**     | `POST`                                         |
| **URL**        | `/api/v1/auth/resend-verification`             |
| **Auth**       | ❌ Không cần (Public)                           |
| **Middleware**  | `api`, `throttle:3,1`                         |
| **Controller** | `StudentAuthController@resendVerification`     |
| **Request**    | `ResendVerificationRequest`                    |

#### 📥 Request Body

| Field   | Type   | Required | Rules                                    |
|---------|--------|----------|------------------------------------------|
| `email` | string | ✅       | required, email, max:255, exists:students|

#### 📤 Response — `200 OK`

```json
{
  "success": true,
  "message": "Email xác thực đã được gửi lại. Vui lòng kiểm tra hộp thư.",
  "data": null
}
```

#### ❌ Response — `400 Bad Request`

```json
{
  "success": false,
  "message": "Email đã được xác thực trước đó.",
  "data": null
}
```

#### ❌ Response — `422 Validation Error`

```json
{
  "success": false,
  "message": "Dữ liệu không hợp lệ.",
  "errors": {
    "email": ["Email không tồn tại trong hệ thống."]
  }
}
```

#### ❌ Response — `500 Internal Server Error`

```json
{
  "success": false,
  "message": "Không thể gửi email. Vui lòng thử lại sau.",
  "data": null
}
```

---

## 👥 Module: Users

Module quản lý người dùng (CRUD + quản lý Role). Tất cả endpoints yêu cầu xác thực Admin.

> **Prefix chung:** `/api/v1/admin/users`
> **Middleware:** `api`, `auth:admin`
> **Controller:** `UsersController`
> **Soft Delete:** ✅ Có

---

### 1. Danh sách Users (có phân trang)

| Thuộc tính     | Giá trị                          |
|----------------|----------------------------------|
| **Method**     | `GET`                            |
| **URL**        | `/api/v1/admin/users`            |
| **Auth**       | ✅ Bearer Token (guard: admin)    |
| **Controller** | `UsersController@index`          |
| **Route Name** | `admin.users.index`              |

#### 📥 Query Parameters

| Param      | Type    | Default | Mô tả                  |
|------------|---------|---------|-------------------------|
| `per_page` | integer | 15      | Số bản ghi mỗi trang   |
| `page`     | integer | 1       | Trang hiện tại          |

#### 📤 Response — `200 OK` (Paginated)

```json
{
  "success": true,
  "message": "Thành công",
  "data": [
    {
      "id": 1,
      "name": "Admin",
      "email": "admin@example.com",
      "avatar": null,
      "email_verified_at": null,
      "created_at": "2026-03-17T09:00:00.000000Z",
      "updated_at": "2026-03-17T09:00:00.000000Z",
      "deleted_at": null
    }
  ],
  "pagination": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 1,
    "from": 1,
    "to": 1
  }
}
```

---

### 2. Chi tiết User

| Thuộc tính     | Giá trị                          |
|----------------|----------------------------------|
| **Method**     | `GET`                            |
| **URL**        | `/api/v1/admin/users/{id}`       |
| **Auth**       | ✅ Bearer Token (guard: admin)    |
| **Controller** | `UsersController@show`           |
| **Route Name** | `admin.users.show`               |

#### 📥 Path Parameters

| Param | Type    | Mô tả     |
|-------|---------|------------|
| `id`  | integer | User ID    |

#### 📤 Response — `200 OK`

```json
{
  "success": true,
  "message": "Thành công",
  "data": {
    "id": 1,
    "name": "Admin",
    "email": "admin@example.com",
    "avatar": null,
    "email_verified_at": null,
    "created_at": "2026-03-17T09:00:00.000000Z",
    "updated_at": "2026-03-17T09:00:00.000000Z",
    "deleted_at": null,
    "roles": [
      {
        "id": 1,
        "name": "admin",
        "guard_name": "admin"
      }
    ],
    "permissions": []
  }
}
```

#### ❌ Response — `404 Not Found`

```json
{
  "success": false,
  "message": "Không tìm thấy dữ liệu.",
  "data": null
}
```

---

### 3. Tạo mới User

| Thuộc tính     | Giá trị                          |
|----------------|----------------------------------|
| **Method**     | `POST`                           |
| **URL**        | `/api/v1/admin/users`            |
| **Auth**       | ✅ Bearer Token (guard: admin)    |
| **Controller** | `UsersController@store`          |
| **Request**    | `StoreUsersRequest`              |
| **Route Name** | `admin.users.store`              |

#### 📥 Request Body

| Field      | Type   | Required | Rules                                    |
|------------|--------|----------|------------------------------------------|
| `name`     | string | ✅       | required, string, max:255                |
| `email`    | string | ✅       | required, email, max:255, unique:users   |
| `password` | string | ✅       | required, string, min:8, max:100         |
| `avatar`   | string | ❌       | nullable, string, max:255               |
| `role`     | string | ❌       | nullable, string, exists:roles,name     |

#### 📤 Response — `201 Created`

```json
{
  "success": true,
  "message": "User đã được tạo thành công.",
  "data": {
    "id": 2,
    "name": "Nguyễn Văn A",
    "email": "nguyenvana@example.com",
    "avatar": null,
    "email_verified_at": null,
    "created_at": "2026-03-17T10:00:00.000000Z",
    "updated_at": "2026-03-17T10:00:00.000000Z",
    "deleted_at": null,
    "roles": [
      {
        "id": 2,
        "name": "student",
        "guard_name": "admin"
      }
    ]
  }
}
```

#### ❌ Response — `422 Validation Error`

```json
{
  "success": false,
  "message": "Dữ liệu không hợp lệ.",
  "errors": {
    "name": ["Tên không được để trống."],
    "email": ["Email đã được sử dụng."],
    "password": ["Mật khẩu tối thiểu 8 ký tự."]
  }
}
```

---

### 4. Cập nhật User

| Thuộc tính     | Giá trị                          |
|----------------|----------------------------------|
| **Method**     | `PUT` / `PATCH`                  |
| **URL**        | `/api/v1/admin/users/{id}`       |
| **Auth**       | ✅ Bearer Token (guard: admin)    |
| **Controller** | `UsersController@update`         |
| **Request**    | `UpdateUsersRequest`             |
| **Route Name** | `admin.users.update`             |

#### 📥 Path Parameters

| Param | Type    | Mô tả     |
|-------|---------|------------|
| `id`  | integer | User ID    |

#### 📥 Request Body (tất cả optional)

| Field      | Type   | Required | Rules                                           |
|------------|--------|----------|-------------------------------------------------|
| `name`     | string | ❌       | sometimes, string, max:255                      |
| `email`    | string | ❌       | sometimes, email, max:255, unique:users (trừ id)|
| `password` | string | ❌       | sometimes, string, min:8, max:100               |
| `avatar`   | string | ❌       | nullable, string, max:255                       |
| `role`     | string | ❌       | nullable, string, exists:roles,name             |

#### 📤 Response — `200 OK`

```json
{
  "success": true,
  "message": "User đã được cập nhật thành công.",
  "data": {
    "id": 2,
    "name": "Nguyễn Văn B",
    "email": "nguyenvanb@example.com",
    "avatar": "avatar.jpg",
    "email_verified_at": null,
    "created_at": "2026-03-17T10:00:00.000000Z",
    "updated_at": "2026-03-17T11:00:00.000000Z",
    "deleted_at": null,
    "roles": [
      {
        "id": 1,
        "name": "admin",
        "guard_name": "admin"
      }
    ]
  }
}
```

---

### 5. Xoá User (Soft Delete)

| Thuộc tính     | Giá trị                          |
|----------------|----------------------------------|
| **Method**     | `DELETE`                         |
| **URL**        | `/api/v1/admin/users/{id}`       |
| **Auth**       | ✅ Bearer Token (guard: admin)    |
| **Controller** | `UsersController@destroy`        |
| **Route Name** | `admin.users.destroy`            |

#### 📥 Path Parameters

| Param | Type    | Mô tả     |
|-------|---------|------------|
| `id`  | integer | User ID    |

#### 📤 Response — `200 OK`

```json
{
  "success": true,
  "message": "User đã được xoá thành công.",
  "data": null
}
```

---

### 6. Gán Role cho User

| Thuộc tính     | Giá trị                                  |
|----------------|------------------------------------------|
| **Method**     | `POST`                                   |
| **URL**        | `/api/v1/admin/users/{id}/assign-role`   |
| **Auth**       | ✅ Bearer Token (guard: admin)            |
| **Controller** | `UsersController@assignRole`             |

#### 📥 Path Parameters

| Param | Type    | Mô tả     |
|-------|---------|------------|
| `id`  | integer | User ID    |

#### 📥 Request Body

| Field  | Type   | Required | Rules                          |
|--------|--------|----------|--------------------------------|
| `role` | string | ✅       | required, string, exists:roles,name |

#### 📤 Response — `200 OK`

```json
{
  "success": true,
  "message": "Gán role thành công.",
  "data": {
    "id": 2,
    "name": "Nguyễn Văn A",
    "email": "nguyenvana@example.com",
    "avatar": null,
    "roles": [
      {
        "id": 1,
        "name": "admin",
        "guard_name": "admin"
      },
      {
        "id": 2,
        "name": "student",
        "guard_name": "admin"
      }
    ]
  }
}
```

#### ❌ Response — `422 Validation Error`

```json
{
  "success": false,
  "message": "Dữ liệu không hợp lệ.",
  "errors": {
    "role": ["Role không hợp lệ."]
  }
}
```

---

### 7. Thu hồi Role của User

| Thuộc tính     | Giá trị                                  |
|----------------|------------------------------------------|
| **Method**     | `POST`                                   |
| **URL**        | `/api/v1/admin/users/{id}/revoke-role`   |
| **Auth**       | ✅ Bearer Token (guard: admin)            |
| **Controller** | `UsersController@revokeRole`             |

#### 📥 Path Parameters

| Param | Type    | Mô tả     |
|-------|---------|------------|
| `id`  | integer | User ID    |

#### 📥 Request Body

| Field  | Type   | Required | Rules                          |
|--------|--------|----------|--------------------------------|
| `role` | string | ✅       | required, string, exists:roles,name |

#### 📤 Response — `200 OK`

```json
{
  "success": true,
  "message": "Thu hồi role thành công.",
  "data": {
    "id": 2,
    "name": "Nguyễn Văn A",
    "email": "nguyenvana@example.com",
    "avatar": null,
    "roles": []
  }
}
```

---

### 8. Danh sách Users đã xoá (Thùng rác)

| Thuộc tính     | Giá trị                                    |
|----------------|--------------------------------------------|
| **Method**     | `GET`                                      |
| **URL**        | `/api/v1/admin/users/trashed`              |
| **Auth**       | ✅ Bearer Token (guard: admin)              |
| **Controller** | `UsersController@trashed`                  |

#### 📥 Query Parameters

| Param      | Type    | Default | Mô tả                |
|------------|---------|---------|----------------------|
| `per_page` | integer | 15      | Số bản ghi mỗi trang |
| `page`     | integer | 1       | Trang hiện tại       |

#### 📤 Response — `200 OK` (Paginated)

```json
{
  "success": true,
  "message": "Thành công",
  "data": [
    {
      "id": 3,
      "name": "Nguyễn Văn A",
      "email": "nguyenvana@example.com",
      "avatar": null,
      "deleted_at": "2026-03-17T12:00:00.000000Z",
      "roles": [{ "id": 2, "name": "student" }]
    }
  ],
  "pagination": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 1,
    "from": 1,
    "to": 1
  }
}
```

---

### 9. Khôi phục một User (Restore)

| Thuộc tính     | Giá trị                                    |
|----------------|--------------------------------------------|
| **Method**     | `POST`                                     |
| **URL**        | `/api/v1/admin/users/{id}/restore`         |
| **Auth**       | ✅ Bearer Token (guard: admin)              |
| **Controller** | `UsersController@restore`                  |

#### 📥 Path Parameters

| Param | Type    | Mô tả     |
|-------|---------|------------|
| `id`  | integer | User ID    |

#### 📤 Response — `200 OK`

```json
{
  "success": true,
  "message": "User đã được khôi phục thành công.",
  "data": null
}
```

#### ❌ Response — `404 Not Found`

```json
{
  "success": false,
  "message": "Không tìm thấy dữ liệu.",
  "data": null
}
```

---

### 10. Khôi phục nhiều Users (Bulk Restore)

| Thuộc tính     | Giá trị                                    |
|----------------|--------------------------------------------|
| **Method**     | `POST`                                     |
| **URL**        | `/api/v1/admin/users/bulk-restore`         |
| **Auth**       | ✅ Bearer Token (guard: admin)              |
| **Controller** | `UsersController@bulkRestore`              |
| **Request**    | `BulkRestoreUsersRequest`                  |

#### 📥 Request Body

| Field    | Type    | Required | Rules                    |
|----------|---------|----------|--------------------------|
| `ids`    | array   | ✅       | required, array, min:1   |
| `ids.*`  | integer | ✅       | required, integer        |

```json
{
  "ids": [2, 3, 4]
}
```

#### 📤 Response — `200 OK`

```json
{
  "success": true,
  "message": "Đã khôi phục 3 user thành công.",
  "data": {
    "restored_count": 3,
    "restored_ids": [2, 3, 4]
  }
}
```

#### ❌ Response — `422 Validation Error`

```json
{
  "success": false,
  "message": "Dữ liệu không hợp lệ.",
  "errors": {
    "ids": ["Phải chọn ít nhất 1 user."]
  }
}
```

---

### 11. Xoá vĩnh viễn một User (Force Delete)

| Thuộc tính     | Giá trị                                      |
|----------------|----------------------------------------------|
| **Method**     | `DELETE`                                     |
| **URL**        | `/api/v1/admin/users/{id}/force-delete`      |
| **Auth**       | ✅ Bearer Token (guard: admin)                |
| **Controller** | `UsersController@forceDelete`                |

> Xoá vĩnh viễn khỏi DB. Hoạt động với cả user chưa bị soft-delete và đã bị soft-delete.

#### 📥 Path Parameters

| Param | Type    | Mô tả     |
|-------|---------|------------|
| `id`  | integer | User ID    |

#### 📤 Response — `200 OK`

```json
{
  "success": true,
  "message": "User đã bị xoá vĩnh viễn.",
  "data": null
}
```

#### ❌ Response — `404 Not Found`

```json
{
  "success": false,
  "message": "Không tìm thấy dữ liệu.",
  "data": null
}
```

---

### 12. Xoá nhiều Users cùng lúc (Bulk Delete)

| Thuộc tính     | Giá trị                                    |
|----------------|--------------------------------------------|
| **Method**     | `DELETE`                                   |
| **URL**        | `/api/v1/admin/users/bulk-delete`          |
| **Auth**       | ✅ Bearer Token (guard: admin)              |
| **Controller** | `UsersController@bulkDelete`               |
| **Request**    | `BulkDeleteUsersRequest`                   |

#### 📥 Request Body

| Field    | Type    | Required | Rules                                    |
|----------|---------|----------|------------------------------------------|
| `ids`    | array   | ✅       | required, array, min:1                   |
| `ids.*`  | integer | ✅       | required, integer, exists:users,id       |

```json
{
  "ids": [2, 3, 4]
}
```

#### 📤 Response — `200 OK`

```json
{
  "success": true,
  "message": "Đã xoá 3 user thành công.",
  "data": {
    "deleted_count": 3,
    "deleted_ids": [2, 3, 4]
  }
}
```

#### ❌ Response — `422 Validation Error`

```json
{
  "success": false,
  "message": "Dữ liệu không hợp lệ.",
  "errors": {
    "ids": ["Phải chọn ít nhất 1 user."],
    "ids.0": ["Một hoặc nhiều user không tồn tại."]
  }
}
```

---

### 13. Xoá vĩnh viễn nhiều Users (Bulk Force Delete)

| Thuộc tính     | Giá trị                                      |
|----------------|----------------------------------------------|
| **Method**     | `DELETE`                                     |
| **URL**        | `/api/v1/admin/users/bulk-force-delete`      |
| **Auth**       | ✅ Bearer Token (guard: admin)                |
| **Controller** | `UsersController@bulkForceDelete`            |
| **Request**    | `BulkForceDeleteUsersRequest`                |

> Xoá vĩnh viễn nhiều users khỏi DB. Bao gồm cả user đã soft-delete và chưa soft-delete.

#### 📥 Request Body

| Field    | Type    | Required | Rules                              |
|----------|---------|----------|------------------------------------|
| `ids`    | array   | ✅       | required, array, min:1             |
| `ids.*`  | integer | ✅       | required, integer                  |

```json
{
  "ids": [2, 3, 4]
}
```

#### 📤 Response — `200 OK`

```json
{
  "success": true,
  "message": "Đã xoá vĩnh viễn 3 user.",
  "data": {
    "deleted_count": 3,
    "deleted_ids": [2, 3, 4]
  }
}
```

#### ❌ Response — `422 Validation Error`

```json
{
  "success": false,
  "message": "Dữ liệu không hợp lệ.",
  "errors": {
    "ids": ["Phải chọn ít nhất 1 user."]
  }
}
```

---

### 14. Thực hiện Action hàng loạt (Bulk Action)

| Thuộc tính     | Giá trị                                    |
|----------------|--------------------------------------------|
| **Method**     | `POST`                                     |
| **URL**        | `/api/v1/admin/users/bulk-action`          |
| **Auth**       | ✅ Bearer Token (guard: admin)              |
| **Controller** | `UsersController@bulkAction`               |
| **Request**    | `BulkActionUsersRequest`                   |

#### 📥 Request Body

| Field    | Type    | Required | Rules                                         |
|----------|---------|----------|-----------------------------------------------|
| `ids`    | array   | ✅       | required, array, min:1                        |
| `ids.*`  | integer | ✅       | required, integer, exists:users,id            |
| `action` | string  | ✅       | required, in: `activate`, `deactivate`        |

```json
{
  "ids": [2, 3, 4],
  "action": "activate"
}
```

#### 📤 Response — `200 OK`

```json
{
  "success": true,
  "message": "Đã thực hiện 'activate' cho 3 user thành công.",
  "data": {
    "affected_count": 3,
    "affected_ids": [2, 3, 4]
  }
}
```

#### ❌ Response — `422 Validation Error`

```json
{
  "success": false,
  "message": "Dữ liệu không hợp lệ.",
  "errors": {
    "action": ["action phải là: activate hoặc deactivate."]
  }
}
```

---

### 15. Gán Role cho nhiều Users (Bulk Assign Role)

| Thuộc tính     | Giá trị                                    |
|----------------|--------------------------------------------|
| **Method**     | `POST`                                     |
| **URL**        | `/api/v1/admin/users/bulk-assign-role`     |
| **Auth**       | ✅ Bearer Token (guard: admin)              |
| **Controller** | `UsersController@bulkAssignRole`           |
| **Request**    | `BulkAssignRoleRequest`                    |

#### 📥 Request Body

| Field    | Type    | Required | Rules                                    |
|----------|---------|----------|------------------------------------------|
| `ids`    | array   | ✅       | required, array, min:1                   |
| `ids.*`  | integer | ✅       | required, integer, exists:users,id       |
| `role`   | string  | ✅       | required, string, exists:roles,name      |

```json
{
  "ids": [2, 3, 4],
  "role": "student"
}
```

#### 📤 Response — `200 OK`

```json
{
  "success": true,
  "message": "Đã gán role 'student' cho 3 user thành công.",
  "data": {
    "affected_count": 3
  }
}
```

#### ❌ Response — `422 Validation Error`

```json
{
  "success": false,
  "message": "Dữ liệu không hợp lệ.",
  "errors": {
    "role": ["Role không hợp lệ."]
  }
}
```

---

---

## 👨‍🏫 Module: Teachers

Module quản lý giảng viên. Admin có toàn quyền CRUD + bulk actions. Public xem danh sách active và chi tiết theo slug.

---

### 1. [Admin] Danh sách Teachers

| Thuộc tính     | Giá trị                                  |
|----------------|------------------------------------------|
| **Method**     | `GET`                                    |
| **URL**        | `/api/v1/admin/teachers`                 |
| **Auth**       | ✅ Bearer Token (guard: admin)            |
| **Controller** | `TeachersController@index`               |

#### 📥 Query Params

| Param      | Type    | Mô tả                              |
|------------|---------|-------------------------------------|
| `search`   | string  | Tìm theo tên                        |
| `status`   | integer | Lọc theo trạng thái (0=inactive, 1=active) |
| `per_page` | integer | Số bản ghi mỗi trang (default: 15, max: 100) |

#### 📤 Response — `200 OK`

```json
{
  "success": true,
  "message": "Thành công",
  "data": [
    {
      "id": 1,
      "name": "Nguyễn Văn A",
      "slug": "nguyen-van-a",
      "description": "Giảng viên lập trình web",
      "exp": 5.0,
      "image": "https://example.com/avatar.jpg",
      "status": 1,
      "courses_count": 3,
      "created_at": "2026-03-18T00:00:00.000000Z",
      "updated_at": "2026-03-18T00:00:00.000000Z"
    }
  ],
  "pagination": { "current_page": 1, "last_page": 1, "per_page": 15, "total": 1, "from": 1, "to": 1 }
}
```

---

### 2. [Admin] Tạo Teacher

| Thuộc tính     | Giá trị                                  |
|----------------|------------------------------------------|
| **Method**     | `POST`                                   |
| **URL**        | `/api/v1/admin/teachers`                 |
| **Auth**       | ✅ Bearer Token (guard: admin)            |
| **Controller** | `TeachersController@store`               |
| **Request**    | `StoreTeachersRequest`                   |

#### 📥 Request Body

| Field           | Type    | Required | Rules                                      |
|-----------------|---------|----------|--------------------------------------------|
| `name`          | string  | ✅       | max:100                                    |
| `slug`          | string  | ✅       | max:100, unique, kebab-case regex          |
| `description`   | string  | ❌       | nullable, max:5000                         |
| `exp`           | numeric | ❌       | nullable, min:0, max:100                   |
| `image`         | string  | ❌       | nullable, url, max:2048                    |
| `date_of_birth` | date    | ❌       | nullable, before:today, after:1900-01-01   |
| `status`        | integer | ❌       | nullable, in:0,1                           |

#### 📤 Response — `201 Created`

```json
{
  "success": true,
  "message": "Tạo giảng viên thành công.",
  "data": { "id": 1, "name": "Nguyễn Văn A", "slug": "nguyen-van-a", ... }
}
```

---

### 3. [Admin] Chi tiết Teacher

| Thuộc tính     | Giá trị                                  |
|----------------|------------------------------------------|
| **Method**     | `GET`                                    |
| **URL**        | `/api/v1/admin/teachers/{id}`            |
| **Auth**       | ✅ Bearer Token (guard: admin)            |
| **Controller** | `TeachersController@show`                |

#### 📤 Response — `200 OK`

```json
{
  "success": true,
  "message": "Thành công",
  "data": {
    "id": 1,
    "name": "Nguyễn Văn A",
    "slug": "nguyen-van-a",
    "description": "...",
    "exp": 5.0,
    "image": "https://example.com/avatar.jpg",
    "status": 1,
    "courses": [],
    "created_at": "2026-03-18T00:00:00.000000Z",
    "updated_at": "2026-03-18T00:00:00.000000Z"
  }
}
```

---

### 4. [Admin] Cập nhật Teacher

| Thuộc tính     | Giá trị                                  |
|----------------|------------------------------------------|
| **Method**     | `PUT / PATCH`                            |
| **URL**        | `/api/v1/admin/teachers/{id}`            |
| **Auth**       | ✅ Bearer Token (guard: admin)            |
| **Controller** | `TeachersController@update`              |
| **Request**    | `UpdateTeachersRequest`                  |

> Các field giống `StoreTeachersRequest` nhưng dùng `sometimes` (chỉ validate field được gửi lên). `slug` unique bỏ qua chính nó.

---

### 5. [Admin] Xóa mềm Teacher

| Thuộc tính     | Giá trị                                  |
|----------------|------------------------------------------|
| **Method**     | `DELETE`                                 |
| **URL**        | `/api/v1/admin/teachers/{id}`            |
| **Auth**       | ✅ Bearer Token (guard: admin)            |
| **Controller** | `TeachersController@destroy`             |

---

### 6. [Admin] Danh sách Teachers đã xóa (Trashed)

| Thuộc tính     | Giá trị                                  |
|----------------|------------------------------------------|
| **Method**     | `GET`                                    |
| **URL**        | `/api/v1/admin/teachers/trashed`         |
| **Auth**       | ✅ Bearer Token (guard: admin)            |
| **Controller** | `TeachersController@trashed`             |

---

### 7. [Admin] Toggle trạng thái Teacher

| Thuộc tính     | Giá trị                                        |
|----------------|------------------------------------------------|
| **Method**     | `PATCH`                                        |
| **URL**        | `/api/v1/admin/teachers/{id}/toggle-status`    |
| **Auth**       | ✅ Bearer Token (guard: admin)                  |
| **Controller** | `TeachersController@toggleStatus`              |

#### 📤 Response — `200 OK`

```json
{
  "success": true,
  "message": "Cập nhật trạng thái thành công.",
  "data": { "id": 1, "status": 0 }
}
```

---

### 8. [Admin] Khôi phục Teacher

| Thuộc tính     | Giá trị                                  |
|----------------|------------------------------------------|
| **Method**     | `POST`                                   |
| **URL**        | `/api/v1/admin/teachers/{id}/restore`    |
| **Auth**       | ✅ Bearer Token (guard: admin)            |
| **Controller** | `TeachersController@restore`             |

---

### 9. [Admin] Xóa vĩnh viễn Teacher

| Thuộc tính     | Giá trị                                      |
|----------------|----------------------------------------------|
| **Method**     | `DELETE`                                     |
| **URL**        | `/api/v1/admin/teachers/{id}/force-delete`   |
| **Auth**       | ✅ Bearer Token (guard: admin)                |
| **Controller** | `TeachersController@forceDelete`             |

---

### 10. [Admin] Bulk Soft Delete Teachers

| Thuộc tính     | Giá trị                                        |
|----------------|------------------------------------------------|
| **Method**     | `DELETE`                                       |
| **URL**        | `/api/v1/admin/teachers/bulk-delete`           |
| **Auth**       | ✅ Bearer Token (guard: admin)                  |
| **Controller** | `TeachersController@bulkDelete`                |
| **Request**    | `BulkDeleteTeachersRequest`                    |

#### 📥 Request Body

| Field   | Type  | Required | Rules                                        |
|---------|-------|----------|----------------------------------------------|
| `ids`   | array | ✅       | min:1, max:100, each: integer, exists:teachers,id |

---

### 11. [Admin] Bulk Restore Teachers

| Thuộc tính     | Giá trị                                        |
|----------------|------------------------------------------------|
| **Method**     | `POST`                                         |
| **URL**        | `/api/v1/admin/teachers/bulk-restore`          |
| **Auth**       | ✅ Bearer Token (guard: admin)                  |
| **Controller** | `TeachersController@bulkRestore`               |
| **Request**    | `BulkRestoreTeachersRequest`                   |

---

### 12. [Admin] Bulk Force Delete Teachers

| Thuộc tính     | Giá trị                                          |
|----------------|--------------------------------------------------|
| **Method**     | `DELETE`                                         |
| **URL**        | `/api/v1/admin/teachers/bulk-force-delete`       |
| **Auth**       | ✅ Bearer Token (guard: admin)                    |
| **Controller** | `TeachersController@bulkForceDelete`             |
| **Request**    | `BulkForceDeleteTeachersRequest`                 |

---

### 13. [Public] Danh sách Teachers

| Thuộc tính     | Giá trị                                  |
|----------------|------------------------------------------|
| **Method**     | `GET`                                    |
| **URL**        | `/api/v1/teachers`                       |
| **Auth**       | ❌ Không cần (Public)                     |
| **Controller** | `TeachersController@publicIndex`         |

> Chỉ trả về teachers có `status = 1`. Hỗ trợ `search`, `per_page`.

---

### 14. [Public] Chi tiết Teacher theo slug

| Thuộc tính     | Giá trị                                  |
|----------------|------------------------------------------|
| **Method**     | `GET`                                    |
| **URL**        | `/api/v1/teachers/{slug}`                |
| **Auth**       | ❌ Không cần (Public)                     |
| **Controller** | `TeachersController@publicShow`          |

#### 📤 Response — `200 OK`

```json
{
  "success": true,
  "message": "Thành công",
  "data": {
    "id": 1,
    "name": "Nguyễn Văn A",
    "slug": "nguyen-van-a",
    "description": "...",
    "exp": 5.0,
    "image": "https://example.com/avatar.jpg",
    "status": 1,
    "courses": [
      {
        "id": 1,
        "name": "Laravel từ cơ bản đến nâng cao",
        "slug": "laravel-co-ban",
        "thumbnail": "https://example.com/thumb.jpg",
        "price": 299000,
        "sale_price": 199000
      }
    ],
    "created_at": "2026-03-18T00:00:00.000000Z",
    "updated_at": "2026-03-18T00:00:00.000000Z"
  }
}
```

---

## 📊 Tổng hợp Endpoints

### 🔐 Auth Module

| #  | Method | URL                                     | Auth        | Mô tả                        |
|----|--------|-----------------------------------------|-------------|-------------------------------|
| 1  | `POST` | `/api/v1/admin/auth/login`              | ❌ Public    | Đăng nhập Admin               |
| 2  | `POST` | `/api/v1/admin/auth/logout`             | ✅ Admin     | Đăng xuất Admin               |
| 3  | `GET`  | `/api/v1/admin/auth/me`                 | ✅ Admin     | Thông tin Admin hiện tại      |
| 4  | `POST` | `/api/v1/auth/register`                 | ❌ Public    | Đăng ký Student               |
| 5  | `POST` | `/api/v1/auth/login`                    | ❌ Public    | Đăng nhập Student             |
| 6  | `GET`  | `/api/v1/auth/verify-email/{token}`     | ❌ Public    | Xác thực email Student        |
| 7  | `GET`  | `/api/v1/auth/me`                       | ✅ Student   | Thông tin Student hiện tại    |
| 8  | `POST` | `/api/v1/auth/logout`                   | ✅ Student   | Đăng xuất Student             |
| 9  | `POST` | `/api/v1/auth/forgot-password`          | ❌ Public    | Gửi link reset mật khẩu      |
| 10 | `POST` | `/api/v1/auth/reset-password`           | ❌ Public    | Đặt lại mật khẩu             |
| 11 | `POST` | `/api/v1/auth/resend-verification`      | ❌ Public    | Gửi lại email xác thực       |

### 👥 Users Module

| #  | Method   | URL                                          | Auth     | Mô tả                         |
|----|----------|----------------------------------------------|----------|-------------------------------|
| 12 | `GET`    | `/api/v1/admin/users`                        | ✅ Admin  | Danh sách Users (phân trang)  |
| 13 | `GET`    | `/api/v1/admin/users/{id}`                   | ✅ Admin  | Chi tiết User                 |
| 14 | `POST`   | `/api/v1/admin/users`                        | ✅ Admin  | Tạo mới User                  |
| 15 | `PUT`    | `/api/v1/admin/users/{id}`                   | ✅ Admin  | Cập nhật User                 |
| 16 | `DELETE` | `/api/v1/admin/users/{id}`                   | ✅ Admin  | Xoá User (soft delete)        |
| 17 | `POST`   | `/api/v1/admin/users/{id}/restore`           | ✅ Admin  | Khôi phục 1 User              |
| 18 | `DELETE` | `/api/v1/admin/users/{id}/force-delete`      | ✅ Admin  | Xoá vĩnh viễn 1 User          |
| 19 | `POST`   | `/api/v1/admin/users/{id}/assign-role`       | ✅ Admin  | Gán role cho User             |
| 20 | `POST`   | `/api/v1/admin/users/{id}/revoke-role`       | ✅ Admin  | Thu hồi role của User         |
| 21 | `GET`    | `/api/v1/admin/users/trashed`                | ✅ Admin  | Danh sách Users đã xoá        |
| 22 | `POST`   | `/api/v1/admin/users/bulk-restore`           | ✅ Admin  | Khôi phục nhiều Users         |
| 23 | `DELETE` | `/api/v1/admin/users/bulk-delete`            | ✅ Admin  | Xoá nhiều Users (soft)        |
| 24 | `DELETE` | `/api/v1/admin/users/bulk-force-delete`      | ✅ Admin  | Xoá vĩnh viễn nhiều Users     |
| 25 | `POST`   | `/api/v1/admin/users/bulk-action`            | ✅ Admin  | Action hàng loạt (activate…)  |
| 26 | `POST`   | `/api/v1/admin/users/bulk-assign-role`       | ✅ Admin  | Gán role cho nhiều Users      |

### 🗂️ Categories Module

| #  | Method   | URL                                                   | Auth        | Mô tả                             |
|----|----------|-------------------------------------------------------|-------------|-----------------------------------|
| 27 | `GET`    | `/api/v1/admin/categories`                            | ✅ Admin    | Danh sách Categories (phân trang) |
| 28 | `GET`    | `/api/v1/admin/categories/tree`                       | ✅ Admin    | Cây danh mục (nested)             |
| 29 | `GET`    | `/api/v1/admin/categories/flat-tree`                  | ✅ Admin    | Danh sách flat có depth           |
| 30 | `GET`    | `/api/v1/admin/categories/trashed`                    | ✅ Admin    | Danh sách đã xoá (thùng rác)      |
| 31 | `POST`   | `/api/v1/admin/categories`                            | ✅ Admin    | Tạo mới Category                  |
| 32 | `GET`    | `/api/v1/admin/categories/{id}`                       | ✅ Admin    | Chi tiết Category                 |
| 33 | `PUT`    | `/api/v1/admin/categories/{id}`                       | ✅ Admin    | Cập nhật Category                 |
| 34 | `DELETE` | `/api/v1/admin/categories/{id}`                       | ✅ Admin    | Xoá Category (soft delete)        |
| 35 | `POST`   | `/api/v1/admin/categories/{id}/move`                  | ✅ Admin    | Di chuyển sang parent mới         |
| 36 | `GET`    | `/api/v1/admin/categories/{id}/ancestors`             | ✅ Admin    | Lấy ancestors (breadcrumb)        |
| 37 | `GET`    | `/api/v1/admin/categories/{id}/descendants`           | ✅ Admin    | Lấy descendants (con cháu)        |
| 38 | `PATCH`  | `/api/v1/admin/categories/{id}/toggle-status`         | ✅ Admin    | Toggle active/inactive            |
| 39 | `POST`   | `/api/v1/admin/categories/{id}/restore`               | ✅ Admin    | Khôi phục 1 Category              |
| 40 | `DELETE` | `/api/v1/admin/categories/{id}/force-delete`          | ✅ Admin    | Xoá vĩnh viễn 1 Category          |
| 41 | `DELETE` | `/api/v1/admin/categories/bulk-delete`                | ✅ Admin    | Xoá nhiều Categories (soft)       |
| 42 | `POST`   | `/api/v1/admin/categories/bulk-restore`               | ✅ Admin    | Khôi phục nhiều Categories        |
| 43 | `DELETE` | `/api/v1/admin/categories/bulk-force-delete`          | ✅ Admin    | Xoá vĩnh viễn nhiều Categories    |
| 44 | `GET`    | `/api/v1/categories/tree`                             | ❌ Public   | Cây danh mục active (Public)      |
| 45 | `GET`    | `/api/v1/categories/{slug}`                           | ❌ Public   | Chi tiết danh mục theo slug       |

---

## 🗂️ Module: Categories

Module quản lý danh mục khóa học dạng cây (Nested Set). Hỗ trợ Admin CRUD đầy đủ và Public API chỉ đọc.

> **Prefix Admin:** `/api/v1/admin/categories`
> **Prefix Public:** `/api/v1/categories`
> **Middleware Admin:** `api`, `auth:admin`
> **Controller:** `CategoriesController`
> **Soft Delete:** ✅ Có
> **Nested Set:** ✅ Dùng `kalnoy/nestedset`

---

### 1. Danh sách Categories (flat, phân trang)

| Thuộc tính     | Giá trị                               |
|----------------|---------------------------------------|
| **Method**     | `GET`                                 |
| **URL**        | `/api/v1/admin/categories`            |
| **Auth**       | ✅ Bearer Token (guard: admin)         |
| **Controller** | `CategoriesController@index`          |
| **Route Name** | `admin.categories.index`              |

#### 📥 Query Parameters

| Param      | Type    | Default | Mô tả                |
|------------|---------|---------|----------------------|
| `per_page` | integer | 15      | Số bản ghi mỗi trang |
| `page`     | integer | 1       | Trang hiện tại       |

#### 📤 Response — `200 OK` (Paginated)

```json
{
  "success": true,
  "message": "Thành công",
  "data": [
    {
      "id": 1,
      "name": "Lập trình",
      "slug": "lap-trinh",
      "description": null,
      "icon": null,
      "status": 1,
      "order": 0,
      "depth": 0,
      "is_root": true,
      "parent_id": null,
      "created_at": "2026-03-18T10:00:00.000000Z",
      "updated_at": "2026-03-18T10:00:00.000000Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 5,
    "from": 1,
    "to": 5
  }
}
```

---

### 2. Cây danh mục dạng nested (Admin)

| Thuộc tính     | Giá trị                               |
|----------------|---------------------------------------|
| **Method**     | `GET`                                 |
| **URL**        | `/api/v1/admin/categories/tree`       |
| **Auth**       | ✅ Bearer Token (guard: admin)         |
| **Controller** | `CategoriesController@tree`           |

#### 📤 Response — `200 OK`

```json
{
  "success": true,
  "message": "Lấy cây danh mục thành công.",
  "data": [
    {
      "id": 1,
      "name": "Lập trình",
      "slug": "lap-trinh",
      "status": 1,
      "depth": 0,
      "is_root": true,
      "children": [
        {
          "id": 2,
          "name": "PHP",
          "slug": "php",
          "depth": 1,
          "is_root": false,
          "children": []
        }
      ]
    }
  ]
}
```

---

### 3. Danh sách flat có depth (cho dropdown chọn parent)

| Thuộc tính     | Giá trị                                    |
|----------------|--------------------------------------------|
| **Method**     | `GET`                                      |
| **URL**        | `/api/v1/admin/categories/flat-tree`       |
| **Auth**       | ✅ Bearer Token (guard: admin)              |
| **Controller** | `CategoriesController@flatTree`            |

#### 📤 Response — `200 OK`

```json
{
  "success": true,
  "message": "Lấy danh sách danh mục thành công.",
  "data": [
    { "id": 1, "name": "Lập trình", "depth": 0 },
    { "id": 2, "name": "PHP", "depth": 1 },
    { "id": 3, "name": "Laravel", "depth": 2 }
  ]
}
```

---

### 4. Tạo mới Category

| Thuộc tính     | Giá trị                               |
|----------------|---------------------------------------|
| **Method**     | `POST`                                |
| **URL**        | `/api/v1/admin/categories`            |
| **Auth**       | ✅ Bearer Token (guard: admin)         |
| **Controller** | `CategoriesController@store`          |
| **Request**    | `StoreCategoriesRequest`              |
| **Route Name** | `admin.categories.store`              |

#### 📥 Request Body

| Field         | Type    | Required | Rules                                                         |
|---------------|---------|----------|---------------------------------------------------------------|
| `name`        | string  | ✅       | required, string, max:255                                     |
| `slug`        | string  | ✅       | required, string, max:255, unique, regex:`^[a-z0-9-]+$`      |
| `description` | string  | ❌       | nullable, string, max:1000                                    |
| `icon`        | string  | ❌       | nullable, string, max:255                                     |
| `status`      | integer | ❌       | nullable, in:0,1 (default: 1)                                 |
| `order`       | integer | ❌       | nullable, integer, min:0                                      |
| `parent_id`   | integer | ❌       | nullable, exists:categories (chỉ non-deleted)                 |

#### 📤 Response — `201 Created`

```json
{
  "success": true,
  "message": "Danh mục đã được tạo thành công.",
  "data": {
    "id": 5,
    "name": "JavaScript",
    "slug": "javascript",
    "status": 1,
    "depth": 1,
    "is_root": false,
    "parent_id": 1
  }
}
```

---

### 5. Chi tiết Category

| Thuộc tính     | Giá trị                               |
|----------------|---------------------------------------|
| **Method**     | `GET`                                 |
| **URL**        | `/api/v1/admin/categories/{id}`       |
| **Auth**       | ✅ Bearer Token (guard: admin)         |
| **Controller** | `CategoriesController@show`           |
| **Route Name** | `admin.categories.show`               |

#### 📤 Response — `200 OK`

```json
{
  "success": true,
  "message": "Thành công",
  "data": {
    "id": 2,
    "name": "PHP",
    "slug": "php",
    "ancestors": [{ "id": 1, "name": "Lập trình" }],
    "children": [{ "id": 3, "name": "Laravel" }]
  }
}
```

---

### 6. Cập nhật Category

| Thuộc tính     | Giá trị                               |
|----------------|---------------------------------------|
| **Method**     | `PUT` / `PATCH`                       |
| **URL**        | `/api/v1/admin/categories/{id}`       |
| **Auth**       | ✅ Bearer Token (guard: admin)         |
| **Controller** | `CategoriesController@update`         |
| **Request**    | `UpdateCategoriesRequest`             |
| **Route Name** | `admin.categories.update`             |

#### 📥 Request Body (tất cả optional)

| Field         | Type    | Required | Rules                                           |
|---------------|---------|----------|-------------------------------------------------|
| `name`        | string  | ❌       | sometimes, string, max:255                      |
| `slug`        | string  | ❌       | sometimes, string, unique (trừ id hiện tại)     |
| `description` | string  | ❌       | nullable, string, max:1000                      |
| `icon`        | string  | ❌       | nullable, string, max:255                       |
| `status`      | integer | ❌       | nullable, in:0,1                                |
| `order`       | integer | ❌       | nullable, integer, min:0                        |
| `parent_id`   | integer | ❌       | nullable, exists:categories (chỉ non-deleted)   |

---

### 7. Xoá Category (Soft Delete)

| Thuộc tính     | Giá trị                               |
|----------------|---------------------------------------|
| **Method**     | `DELETE`                              |
| **URL**        | `/api/v1/admin/categories/{id}`       |
| **Auth**       | ✅ Bearer Token (guard: admin)         |
| **Controller** | `CategoriesController@destroy`        |
| **Route Name** | `admin.categories.destroy`            |

#### 📤 Response — `200 OK`

```json
{
  "success": true,
  "message": "Danh mục đã được xoá thành công.",
  "data": null
}
```

---

### 8. Di chuyển Category (Move)

| Thuộc tính     | Giá trị                                  |
|----------------|------------------------------------------|
| **Method**     | `POST`                                   |
| **URL**        | `/api/v1/admin/categories/{id}/move`     |
| **Auth**       | ✅ Bearer Token (guard: admin)            |
| **Controller** | `CategoriesController@move`              |
| **Request**    | `MoveCategoryRequest`                    |

#### 📥 Request Body

| Field       | Type    | Required | Rules                                         |
|-------------|---------|----------|-----------------------------------------------|
| `parent_id` | integer | ❌       | nullable, exists:categories (chỉ non-deleted) |

> Gửi `parent_id: null` để đưa category lên root.

#### 📤 Response — `200 OK`

```json
{
  "success": true,
  "message": "Đã di chuyển danh mục thành công.",
  "data": { ... }
}
```

#### ❌ Response — `422` (Circular reference)

```json
{
  "success": false,
  "message": "Không thể di chuyển danh mục vào con của nó.",
  "data": null
}
```

---

### 9. Lấy Ancestors (Breadcrumb)

| Thuộc tính     | Giá trị                                      |
|----------------|----------------------------------------------|
| **Method**     | `GET`                                        |
| **URL**        | `/api/v1/admin/categories/{id}/ancestors`    |
| **Auth**       | ✅ Bearer Token (guard: admin)                |
| **Controller** | `CategoriesController@ancestors`             |

#### 📤 Response — `200 OK`

```json
{
  "success": true,
  "message": "Thành công",
  "data": [
    { "id": 1, "name": "Lập trình", "depth": 0 },
    { "id": 2, "name": "PHP", "depth": 1 }
  ]
}
```

---

### 10. Lấy Descendants (Con cháu)

| Thuộc tính     | Giá trị                                       |
|----------------|-----------------------------------------------|
| **Method**     | `GET`                                         |
| **URL**        | `/api/v1/admin/categories/{id}/descendants`   |
| **Auth**       | ✅ Bearer Token (guard: admin)                 |
| **Controller** | `CategoriesController@descendants`            |

---

### 11. Toggle Status (Active/Inactive)

| Thuộc tính     | Giá trị                                          |
|----------------|--------------------------------------------------|
| **Method**     | `PATCH`                                          |
| **URL**        | `/api/v1/admin/categories/{id}/toggle-status`    |
| **Auth**       | ✅ Bearer Token (guard: admin)                    |
| **Controller** | `CategoriesController@toggleStatus`              |

#### 📤 Response — `200 OK`

```json
{
  "success": true,
  "message": "Danh mục đã được kích hoạt.",
  "data": { "id": 1, "status": 1, ... }
}
```

---

### 12. Danh sách đã xoá (Thùng rác)

| Thuộc tính     | Giá trị                                    |
|----------------|--------------------------------------------|
| **Method**     | `GET`                                      |
| **URL**        | `/api/v1/admin/categories/trashed`         |
| **Auth**       | ✅ Bearer Token (guard: admin)              |
| **Controller** | `CategoriesController@trashed`             |

---

### 13. Khôi phục một Category

| Thuộc tính     | Giá trị                                         |
|----------------|-------------------------------------------------|
| **Method**     | `POST`                                          |
| **URL**        | `/api/v1/admin/categories/{id}/restore`         |
| **Auth**       | ✅ Bearer Token (guard: admin)                   |
| **Controller** | `CategoriesController@restore`                  |

---

### 14. Xoá vĩnh viễn một Category

| Thuộc tính     | Giá trị                                           |
|----------------|---------------------------------------------------|
| **Method**     | `DELETE`                                          |
| **URL**        | `/api/v1/admin/categories/{id}/force-delete`      |
| **Auth**       | ✅ Bearer Token (guard: admin)                     |
| **Controller** | `CategoriesController@forceDelete`                |

---

### 15. Xoá nhiều Categories (Bulk Delete)

| Thuộc tính     | Giá trị                                        |
|----------------|------------------------------------------------|
| **Method**     | `DELETE`                                       |
| **URL**        | `/api/v1/admin/categories/bulk-delete`         |
| **Auth**       | ✅ Bearer Token (guard: admin)                  |
| **Controller** | `CategoriesController@bulkDelete`              |
| **Request**    | `BulkDeleteCategoriesRequest`                  |

#### 📥 Request Body

| Field   | Type    | Required | Rules                                        |
|---------|---------|----------|----------------------------------------------|
| `ids`   | array   | ✅       | required, array, min:1, max:100              |
| `ids.*` | integer | ✅       | integer, exists:categories,id                |

#### 📤 Response — `200 OK`

```json
{
  "success": true,
  "message": "Đã xoá 3 danh mục thành công.",
  "data": { "deleted_count": 3, "deleted_ids": [1, 2, 3] }
}
```

---

### 16. Khôi phục nhiều Categories (Bulk Restore)

| Thuộc tính     | Giá trị                                         |
|----------------|-------------------------------------------------|
| **Method**     | `POST`                                          |
| **URL**        | `/api/v1/admin/categories/bulk-restore`         |
| **Auth**       | ✅ Bearer Token (guard: admin)                   |
| **Controller** | `CategoriesController@bulkRestore`              |
| **Request**    | `BulkRestoreCategoriesRequest`                  |

#### 📤 Response — `200 OK`

```json
{
  "success": true,
  "message": "Đã khôi phục 3 danh mục thành công.",
  "data": { "restored_count": 3, "restored_ids": [1, 2, 3] }
}
```

---

### 17. Xoá vĩnh viễn nhiều Categories (Bulk Force Delete)

| Thuộc tính     | Giá trị                                              |
|----------------|------------------------------------------------------|
| **Method**     | `DELETE`                                             |
| **URL**        | `/api/v1/admin/categories/bulk-force-delete`         |
| **Auth**       | ✅ Bearer Token (guard: admin)                        |
| **Controller** | `CategoriesController@bulkForceDelete`               |
| **Request**    | `BulkForceDeleteCategoriesRequest`                   |

---

### 18. [Public] Cây danh mục (chỉ active)

| Thuộc tính     | Giá trị                               |
|----------------|---------------------------------------|
| **Method**     | `GET`                                 |
| **URL**        | `/api/v1/categories/tree`             |
| **Auth**       | ❌ Không cần (Public)                  |
| **Controller** | `CategoriesController@publicTree`     |

#### 📤 Response — `200 OK`

```json
{
  "success": true,
  "message": "Thành công",
  "data": [
    {
      "id": 1,
      "name": "Lập trình",
      "slug": "lap-trinh",
      "children": [
        { "id": 2, "name": "PHP", "slug": "php", "children": [] }
      ]
    }
  ]
}
```

---

### 19. [Public] Chi tiết danh mục theo slug

| Thuộc tính     | Giá trị                               |
|----------------|---------------------------------------|
| **Method**     | `GET`                                 |
| **URL**        | `/api/v1/categories/{slug}`           |
| **Auth**       | ❌ Không cần (Public)                  |
| **Controller** | `CategoriesController@publicShow`     |

#### 📤 Response — `200 OK`

```json
{
  "success": true,
  "message": "Thành công",
  "data": {
    "id": 2,
    "name": "PHP",
    "slug": "php",
    "ancestors": [{ "id": 1, "name": "Lập trình" }],
    "children": [{ "id": 3, "name": "Laravel" }]
  }
}
```

#### ❌ Response — `404 Not Found`

```json
{
  "success": false,
  "message": "Danh mục không tồn tại.",
  "data": null
}
```

---

## 📚 Module: Courses

Module quản lý khóa học. Admin CRUD + Soft Delete + Bulk Actions. Public API cho danh sách/chi tiết. Client API cho khóa học đã mua.

> **Admin Prefix:** `/api/admin/courses`
> **Public Prefix:** `/api/v1/courses`
> **Client Prefix:** `/api/v1/my-courses`
> **Middleware Admin:** `api`, `auth:admin`
> **Middleware Client:** `api`, `auth:api`
> **Controller:** `CourseController`
> **Soft Delete:** ✅ Có

---

### 🛡️ Admin Endpoints

### 1. Danh sách Courses (Admin)

| Thuộc tính     | Giá trị                          |
|----------------|----------------------------------|
| **Method**     | `GET`                            |
| **URL**        | `/api/admin/courses`             |
| **Auth**       | ✅ Bearer Token (guard: admin)    |
| **Controller** | `CourseController@index`         |

#### 📥 Query Parameters

| Param        | Type    | Default | Mô tả                                     |
|--------------|---------|---------|---------------------------------------------|
| `search`     | string  | —       | Tìm kiếm theo tên khóa học                 |
| `status`     | integer | —       | Filter status (0=draft, 1=published)        |
| `teacher_id` | integer | —       | Filter theo giảng viên                      |
| `category_id`| integer | —       | Filter theo danh mục (qua pivot)            |
| `level`      | string  | —       | Filter theo trình độ (beginner/intermediate/advanced) |
| `per_page`   | integer | 15      | Số bản ghi mỗi trang (max: 100)            |

#### 📤 Response — `200 OK` (Paginated)

```json
{
  "success": true,
  "message": "Thành công",
  "data": [
    {
      "id": 1,
      "name": "Laravel 12 Từ Cơ Bản Đến Nâng Cao",
      "slug": "laravel-12-tu-co-ban-den-nang-cao",
      "description": "...",
      "thumbnail": "https://...",
      "price": "599000.00",
      "sale_price": "399000.00",
      "level": "beginner",
      "total_lessons": 0,
      "total_students": 0,
      "rating": 0,
      "status": 1,
      "teacher": { "id": 1, "name": "...", "slug": "..." },
      "categories": [{ "id": 1, "name": "...", "slug": "..." }],
      "created_at": "2026-04-07T...",
      "updated_at": "2026-04-07T..."
    }
  ],
  "pagination": { "total": 8, "per_page": 15, "current_page": 1, "last_page": 1 }
}
```

---

### 2. Tạo mới Course

| Thuộc tính     | Giá trị                          |
|----------------|----------------------------------|
| **Method**     | `POST`                           |
| **URL**        | `/api/admin/courses`             |
| **Auth**       | ✅ Bearer Token (guard: admin)    |
| **Controller** | `CourseController@store`         |
| **Request**    | `StoreCourseRequest`             |

#### 📥 Request Body

| Field          | Type    | Required | Rules                                          |
|----------------|---------|----------|-------------------------------------------------|
| `name`         | string  | ✅       | required, string, max:255                       |
| `slug`         | string  | ✅       | required, unique:courses, regex:/^[a-z0-9-]+$/  |
| `teacher_id`   | integer | ✅       | required, exists:teachers,id                    |
| `category_id`  | integer | ❌       | nullable, exists:categories,id                  |
| `category_ids` | array   | ❌       | nullable, array of exists:categories,id         |
| `description`  | string  | ❌       | nullable, string, max:10000                     |
| `thumbnail`    | string  | ❌       | nullable, url, max:2048                         |
| `price`        | numeric | ✅       | required, numeric, min:0                        |
| `sale_price`   | numeric | ❌       | nullable, numeric, min:0, lte:price             |
| `level`        | string  | ✅       | required, in:beginner,intermediate,advanced     |
| `status`       | integer | ❌       | nullable, in:0,1                                |

#### 📤 Response — `201 Created`

```json
{
  "success": true,
  "message": "Khóa học đã được tạo thành công.",
  "data": { "id": 1, "name": "...", "slug": "...", "teacher": {...}, "categories": [...] }
}
```

---

### 3. Chi tiết Course (Admin)

| Thuộc tính     | Giá trị                          |
|----------------|----------------------------------|
| **Method**     | `GET`                            |
| **URL**        | `/api/admin/courses/{id}`        |
| **Auth**       | ✅ Bearer Token (guard: admin)    |
| **Controller** | `CourseController@show`          |

---

### 4. Cập nhật Course

| Thuộc tính     | Giá trị                          |
|----------------|----------------------------------|
| **Method**     | `PUT/PATCH`                      |
| **URL**        | `/api/admin/courses/{id}`        |
| **Auth**       | ✅ Bearer Token (guard: admin)    |
| **Controller** | `CourseController@update`        |
| **Request**    | `UpdateCourseRequest`            |

> Giống StoreCourseRequest nhưng tất cả fields đều `sometimes`. Slug unique ignore current id.

---

### 5. Xoá Course (Soft Delete)

| Thuộc tính     | Giá trị                          |
|----------------|----------------------------------|
| **Method**     | `DELETE`                         |
| **URL**        | `/api/admin/courses/{id}`        |
| **Auth**       | ✅ Bearer Token (guard: admin)    |
| **Controller** | `CourseController@destroy`       |

---

### 6. Toggle Status (Draft/Published)

| Thuộc tính     | Giá trị                                 |
|----------------|------------------------------------------|
| **Method**     | `PATCH`                                  |
| **URL**        | `/api/admin/courses/{id}/toggle-status`  |
| **Auth**       | ✅ Bearer Token (guard: admin)            |
| **Controller** | `CourseController@toggleStatus`          |

---

### 7. Danh sách đã xoá (Thùng rác)

| Thuộc tính     | Giá trị                          |
|----------------|----------------------------------|
| **Method**     | `GET`                            |
| **URL**        | `/api/admin/courses/trashed`     |
| **Auth**       | ✅ Bearer Token (guard: admin)    |
| **Controller** | `CourseController@trashed`       |

---

### 8. Khôi phục Course

| Thuộc tính     | Giá trị                              |
|----------------|---------------------------------------|
| **Method**     | `POST`                                |
| **URL**        | `/api/admin/courses/{id}/restore`     |
| **Auth**       | ✅ Bearer Token (guard: admin)         |
| **Controller** | `CourseController@restore`            |

---

### 9. Xoá vĩnh viễn Course

| Thuộc tính     | Giá trị                                  |
|----------------|-------------------------------------------|
| **Method**     | `DELETE`                                  |
| **URL**        | `/api/admin/courses/{id}/force-delete`    |
| **Auth**       | ✅ Bearer Token (guard: admin)             |
| **Controller** | `CourseController@forceDelete`            |

---

### 10. Bulk Delete (Soft Delete nhiều)

| Thuộc tính     | Giá trị                              |
|----------------|---------------------------------------|
| **Method**     | `DELETE`                              |
| **URL**        | `/api/admin/courses/bulk-delete`      |
| **Auth**       | ✅ Bearer Token (guard: admin)         |
| **Controller** | `CourseController@bulkDelete`         |
| **Request**    | `BulkDeleteCourseRequest`             |

#### 📥 Request Body

| Field   | Type  | Required | Rules                                  |
|---------|-------|----------|----------------------------------------|
| `ids`   | array | ✅       | required, array, min:1, max:100        |
| `ids.*` | int   | ✅       | required, integer, exists:courses,id   |

---

### 11. Bulk Restore (Khôi phục nhiều)

| Thuộc tính     | Giá trị                              |
|----------------|---------------------------------------|
| **Method**     | `POST`                                |
| **URL**        | `/api/admin/courses/bulk-restore`     |
| **Auth**       | ✅ Bearer Token (guard: admin)         |
| **Controller** | `CourseController@bulkRestore`        |
| **Request**    | `BulkRestoreCourseRequest`            |

---

### 12. Bulk Force Delete (Xoá vĩnh viễn nhiều)

| Thuộc tính     | Giá trị                                  |
|----------------|-------------------------------------------|
| **Method**     | `DELETE`                                  |
| **URL**        | `/api/admin/courses/bulk-force-delete`    |
| **Auth**       | ✅ Bearer Token (guard: admin)             |
| **Controller** | `CourseController@bulkForceDelete`        |
| **Request**    | `BulkForceDeleteCourseRequest`            |

---

### 🌐 Public Endpoints

### 13. Danh sách Courses (Public)

| Thuộc tính     | Giá trị                          |
|----------------|----------------------------------|
| **Method**     | `GET`                            |
| **URL**        | `/api/v1/courses`                |
| **Auth**       | ❌ Không cần (Public)             |
| **Controller** | `CourseController@publicIndex`   |

#### 📥 Query Parameters

| Param        | Type    | Default | Mô tả                                     |
|--------------|---------|---------|---------------------------------------------|
| `search`     | string  | —       | Tìm kiếm theo tên                          |
| `category_id`| integer | —       | Filter theo danh mục                        |
| `level`      | string  | —       | Filter theo trình độ                        |
| `per_page`   | integer | 15      | Số bản ghi mỗi trang (max: 100)            |

> Chỉ trả về khóa học đã published (`status=1`).

---

### 14. Chi tiết Course (Public)

| Thuộc tính     | Giá trị                          |
|----------------|----------------------------------|
| **Method**     | `GET`                            |
| **URL**        | `/api/v1/courses/{slug}`         |
| **Auth**       | ❌ Không cần (Public)             |
| **Controller** | `CourseController@publicShow`    |

> Tìm theo slug. Chỉ trả về khóa học đã published. Kèm teacher + categories.

---

### 15. Danh sách bài giảng của khóa học (Public)

| Thuộc tính     | Giá trị                              |
|----------------|---------------------------------------|
| **Method**     | `GET`                                 |
| **URL**        | `/api/v1/courses/{slug}/lessons`      |
| **Auth**       | ❌ Không cần (Public, nhưng check auth nếu có) |
| **Controller** | `CourseController@publicLessons`      |

> **Lưu ý:** Sẽ hoàn thiện khi Module Lessons được tạo. Hiện tại trả `is_purchased` + placeholder lessons array.

---

### 🎓 Client Endpoints (Student đã đăng nhập)

### 16. Khóa học đã mua

| Thuộc tính     | Giá trị                          |
|----------------|----------------------------------|
| **Method**     | `GET`                            |
| **URL**        | `/api/v1/my-courses`             |
| **Auth**       | ✅ Bearer Token (guard: api)      |
| **Controller** | `CourseController@myCourses`     |

#### 📥 Query Parameters

| Param      | Type    | Default | Mô tả                    |
|------------|---------|---------|---------------------------|
| `per_page` | integer | 15      | Số bản ghi mỗi trang     |

#### 📤 Response — `200 OK` (Paginated)

> Trả về danh sách khóa học student đã enroll, kèm teacher + categories.

---

## 📤 Module: Upload (Media Service)

Module xử lý upload file (video, document, image) cho Admin. Hỗ trợ 2 flow: Local (dev) và S3 Presigned URL (production).

> **Prefix chung:** `/api/admin/upload`
> **Middleware:** `api`, `auth:admin`
> **Controller:** `UploadController`
> **Service:** `UploadService` (app/Services/ — dùng chung)
> **Soft Delete:** ❌ Không — xóa thật cả DB lẫn file vật lý

---

### 🖥️ Local Flow (FILESYSTEM_DISK=local)

### 1. Upload Video

| Thuộc tính     | Giá trị                              |
|----------------|---------------------------------------|
| **Method**     | `POST`                                |
| **URL**        | `/api/admin/upload/video`             |
| **Auth**       | ✅ Bearer Token (guard: admin)         |
| **Controller** | `UploadController@uploadVideo`        |
| **Request**    | `UploadVideoRequest`                  |

#### 📥 Request Body (multipart/form-data)

| Field  | Type | Required | Rules                                                   |
|--------|------|----------|---------------------------------------------------------|
| `file` | file | ✅       | required, file, mimetypes:video/mp4,video/webm,video/quicktime, max:512000 (500MB) |

#### 📤 Response — `201 Created`

```json
{
  "success": true,
  "message": "Upload video thành công.",
  "data": {
    "id": 1,
    "type": "video",
    "status": "ready",
    "original_name": "bai-giang-01.mp4",
    "url": "http://localhost:8000/storage/videos/uuid.mp4",
    "mime_type": "video/mp4",
    "size": 52428800,
    "size_mb": 50.0,
    "duration": 1234,
    "width": 1920,
    "height": 1080,
    "bitrate": 2500000,
    "codec": "h264",
    "created_at": "2026-04-07T08:00:00.000000Z"
  }
}
```

---

### 2. Upload Document

| Thuộc tính     | Giá trị                              |
|----------------|---------------------------------------|
| **Method**     | `POST`                                |
| **URL**        | `/api/admin/upload/document`          |
| **Auth**       | ✅ Bearer Token (guard: admin)         |
| **Controller** | `UploadController@uploadDocument`     |
| **Request**    | `UploadDocumentRequest`               |

#### 📥 Request Body (multipart/form-data)

| Field  | Type | Required | Rules                                      |
|--------|------|----------|--------------------------------------------|
| `file` | file | ✅       | required, file, mimes:pdf,doc,docx,txt, max:20480 (20MB) |

#### 📤 Response — `201 Created`

```json
{
  "success": true,
  "message": "Upload tài liệu thành công.",
  "data": {
    "id": 2,
    "type": "document",
    "status": "ready",
    "original_name": "tai-lieu.pdf",
    "url": "http://localhost:8000/storage/documents/uuid.pdf",
    "mime_type": "application/pdf",
    "size": 1048576,
    "size_mb": 1.0,
    "duration": null,
    "width": null,
    "height": null,
    "bitrate": null,
    "codec": null,
    "created_at": "2026-04-07T08:00:00.000000Z"
  }
}
```

---

### 3. Upload Image

| Thuộc tính     | Giá trị                              |
|----------------|---------------------------------------|
| **Method**     | `POST`                                |
| **URL**        | `/api/admin/upload/image`             |
| **Auth**       | ✅ Bearer Token (guard: admin)         |
| **Controller** | `UploadController@uploadImage`        |

#### 📥 Request Body (multipart/form-data)

| Field    | Type   | Required | Rules                                              |
|----------|--------|----------|----------------------------------------------------|
| `file`   | file   | ✅       | required, image, mimes:jpg,jpeg,png,webp, max:5120 (5MB) |
| `folder` | string | ❌       | nullable, in:images,thumbnails,avatars,banners (default: images) |

#### 📤 Response — `201 Created`

> Cùng format như Upload Video/Document, với `type: "image"`.

---

### ☁️ S3 Flow (FILESYSTEM_DISK=s3)

### 4. Tạo Presigned URL

| Thuộc tính     | Giá trị                              |
|----------------|---------------------------------------|
| **Method**     | `POST`                                |
| **URL**        | `/api/admin/upload/presigned`         |
| **Auth**       | ✅ Bearer Token (guard: admin)         |
| **Controller** | `UploadController@presigned`          |
| **Request**    | `PresignedUploadRequest`              |

#### 📥 Request Body (JSON)

| Field      | Type    | Required | Rules                                    |
|------------|---------|----------|------------------------------------------|
| `type`     | string  | ✅       | required, in:video,document,image        |
| `filename` | string  | ✅       | required, string, max:255               |
| `mime_type`| string  | ✅       | required, string, max:100               |
| `size`     | integer | ✅       | required, integer, min:1, max:5368709120 (5GB) |
| `duration` | integer | ❌       | nullable, integer, min:1 (chỉ video)    |
| `width`    | integer | ❌       | nullable, integer, min:1 (chỉ video)    |
| `height`   | integer | ❌       | nullable, integer, min:1 (chỉ video)    |

#### 📤 Response — `200 OK`

```json
{
  "success": true,
  "message": "Presigned URL đã được tạo.",
  "data": {
    "presigned_url": "https://s3.amazonaws.com/bucket/videos/uuid.mp4?...",
    "media_file_id": 3,
    "expires_at": "2026-04-07T08:15:00+00:00"
  }
}
```

---

### 5. Xác nhận Upload S3

| Thuộc tính     | Giá trị                              |
|----------------|---------------------------------------|
| **Method**     | `POST`                                |
| **URL**        | `/api/admin/upload/{id}/confirm`      |
| **Auth**       | ✅ Bearer Token (guard: admin)         |
| **Controller** | `UploadController@confirm`            |

#### 📥 Path Parameters

| Param | Type    | Mô tả                                         |
|-------|---------|------------------------------------------------|
| `id`  | integer | MediaFile ID (phải có status = pending)        |

#### 📤 Response — `200 OK`

```json
{
  "success": true,
  "message": "Xác nhận upload thành công.",
  "data": {
    "id": 3,
    "type": "video",
    "status": "ready",
    "original_name": "bai-giang-02.mp4",
    "url": "https://s3.amazonaws.com/bucket/videos/uuid.mp4",
    "mime_type": "video/mp4",
    "size": 104857600,
    "size_mb": 100.0,
    "duration": 3600,
    "width": 1920,
    "height": 1080,
    "bitrate": null,
    "codec": null,
    "created_at": "2026-04-07T08:00:00.000000Z"
  }
}
```

#### ❌ Response — `422 Unprocessable Entity`

```json
{
  "success": false,
  "message": "File chưa tồn tại trên storage. Upload lại hoặc thử lại sau.",
  "data": null
}
```

#### ❌ Response — `404 Not Found`

> Khi `id` không tồn tại hoặc status đã là `ready`.

---

### 🗑️ Xóa file (dùng chung cả 2 flow)

### 6. Xóa Media File

| Thuộc tính     | Giá trị                              |
|----------------|---------------------------------------|
| **Method**     | `DELETE`                              |
| **URL**        | `/api/admin/upload/{id}`              |
| **Auth**       | ✅ Bearer Token (guard: admin)         |
| **Controller** | `UploadController@destroy`            |

#### 📥 Path Parameters

| Param | Type    | Mô tả        |
|-------|---------|---------------|
| `id`  | integer | MediaFile ID  |

#### 📤 Response — `200 OK`

```json
{
  "success": true,
  "message": "Đã xóa file thành công.",
  "data": null
}
```

#### ❌ Response — `422 Unprocessable Entity`

```json
{
  "success": false,
  "message": "Không thể xóa: file đang được dùng bởi 3 bài giảng.",
  "data": null
}
```

#### ❌ Response — `404 Not Found`

```json
{
  "success": false,
  "message": "MediaFile không tìm thấy.",
  "data": null
}
```

---

## 📚 Module: Lessons (Sections + Lessons)

Module quản lý cấu trúc khóa học theo 2 cấp: **Chương (Section)** → **Bài giảng (Lesson)**.

> **Admin Prefix:** `/api/admin`
> **Client Prefix:** `/api/v1`
> **Controllers:** `SectionController`, `LessonController`
> **Soft Delete:** ✅ Cả Section và Lesson

---

## 📂 Module: Sections (Chương)

---

### 🛠️ Admin Endpoints — Sections

---

### S1. Danh sách chương của khóa học

| Thuộc tính     | Giá trị                                      |
|----------------|----------------------------------------------|
| **Method**     | `GET`                                        |
| **URL**        | `/api/admin/courses/{course_id}/sections`    |
| **Auth**       | ✅ Bearer Token (guard: admin)                |
| **Controller** | `SectionController@index`                    |

#### 📥 Query Parameters

| Param      | Type    | Default | Mô tả                                |
|------------|---------|---------|--------------------------------------|
| `status`   | integer | —      | Filter: `0` (draft), `1` (published) |
| `per_page` | integer | 15      | Số bản ghi mỗi trang                 |

#### 📤 Response — `200 OK` (Paginated)

```json
{
  "success": true,
  "message": "Lấy danh sách chương thành công.",
  "data": [
    {
      "id": 1,
      "course_id": 1,
      "title": "Chương 1: Giới thiệu",
      "description": "Tổng quan khóa học",
      "order": 0,
      "status": 1
    }
  ],
  "pagination": { ... }
}
```

---

### S2. Tạo chương mới

| Thuộc tính     | Giá trị                                      |
|----------------|----------------------------------------------|
| **Method**     | `POST`                                       |
| **URL**        | `/api/admin/courses/{course_id}/sections`    |
| **Auth**       | ✅ Bearer Token (guard: admin)                |
| **Request**    | `StoreSectionRequest`                        |
| **Controller** | `SectionController@store`                    |

#### 📥 Request Body

| Field         | Type    | Required | Rules                    |
|---------------|---------|----------|--------------------------|
| `title`       | string  | ✅       | required, string, max:255 |
| `description` | string  | ❌       | nullable, string         |
| `order`       | integer | ❌       | nullable, integer, min:0 |
| `status`      | integer | ❌       | nullable, in:0,1         |

#### 📤 Response — `201 Created`

```json
{
  "success": true,
  "message": "Tạo chương thành công.",
  "data": { ... }
}
```

---

### S3. Chi tiết chương (kèm danh sách bài giảng)

| Thuộc tính     | Giá trị                          |
|----------------|----------------------------------|
| **Method**     | `GET`                            |
| **URL**        | `/api/admin/sections/{id}`       |
| **Auth**       | ✅ Bearer Token (guard: admin)    |
| **Controller** | `SectionController@show`         |

#### 📤 Response — `200 OK`

```json
{
  "success": true,
  "message": "Lấy chi tiết chương thành công.",
  "data": {
    "id": 1,
    "course_id": 1,
    "title": "Chương 1: Giới thiệu",
    "description": "...",
    "order": 0,
    "status": 1,
    "lessons": [ ... ]
  }
}
```

---

### S4. Cập nhật chương

| Thuộc tính     | Giá trị                          |
|----------------|----------------------------------|
| **Method**     | `PUT/PATCH`                      |
| **URL**        | `/api/admin/sections/{id}`       |
| **Auth**       | ✅ Bearer Token (guard: admin)    |
| **Request**    | `UpdateSectionRequest`           |
| **Controller** | `SectionController@update`       |

---

### S5. Xóa chương (soft delete)

| Thuộc tính     | Giá trị                          |
|----------------|----------------------------------|
| **Method**     | `DELETE`                         |
| **URL**        | `/api/admin/sections/{id}`       |
| **Auth**       | ✅ Bearer Token (guard: admin)    |
| **Controller** | `SectionController@destroy`      |

> **Side effect:** Lessons trong chương sẽ có `section_id = null` (SET NULL, không bị xóa).

---

### S6. Toggle trạng thái chương

| Thuộc tính     | Giá trị                                      |
|----------------|----------------------------------------------|
| **Method**     | `PATCH`                                      |
| **URL**        | `/api/admin/sections/{id}/toggle-status`     |
| **Auth**       | ✅ Bearer Token (guard: admin)                |
| **Controller** | `SectionController@toggleStatus`             |

---

### S7. Danh sách chương đã xóa (thùng rác)

| Thuộc tính     | Giá trị                              |
|----------------|--------------------------------------|
| **Method**     | `GET`                                |
| **URL**        | `/api/admin/sections/trashed`        |
| **Auth**       | ✅ Bearer Token (guard: admin)        |
| **Controller** | `SectionController@trashed`          |

---

### S8. Khôi phục chương

| Thuộc tính     | Giá trị                                  |
|----------------|------------------------------------------|
| **Method**     | `POST`                                   |
| **URL**        | `/api/admin/sections/{id}/restore`       |
| **Auth**       | ✅ Bearer Token (guard: admin)            |
| **Controller** | `SectionController@restore`              |

---

### S9. Xóa vĩnh viễn chương

| Thuộc tính     | Giá trị                                      |
|----------------|----------------------------------------------|
| **Method**     | `DELETE`                                     |
| **URL**        | `/api/admin/sections/{id}/force-delete`      |
| **Auth**       | ✅ Bearer Token (guard: admin)                |
| **Controller** | `SectionController@forceDelete`              |

---

### S10. Sắp xếp thứ tự chương (reorder)

| Thuộc tính     | Giá trị                              |
|----------------|--------------------------------------|
| **Method**     | `POST`                               |
| **URL**        | `/api/admin/sections/reorder`        |
| **Auth**       | ✅ Bearer Token (guard: admin)        |
| **Controller** | `SectionController@reorder`          |

#### 📥 Request Body

```json
{
  "orders": [
    { "id": 1, "order": 0 },
    { "id": 2, "order": 1 }
  ]
}
```

> **Constraint:** Tất cả sections phải thuộc cùng 1 course — nếu không → 422.

---

### 🌐 Public Endpoint — Sections

---

### S11. Curriculum khóa học (sections + lessons)

| Thuộc tính     | Giá trị                                    |
|----------------|--------------------------------------------|
| **Method**     | `GET`                                      |
| **URL**        | `/api/v1/courses/{slug}/curriculum`        |
| **Auth**       | ❌ Không cần (Public)                       |
| **Controller** | `SectionController@curriculum`             |

#### 📤 Response — `200 OK`

```json
{
  "success": true,
  "message": "Lấy curriculum thành công.",
  "data": {
    "course_id": 1,
    "sections": [
      {
        "id": 1,
        "title": "Chương 1: Giới thiệu",
        "description": "Tổng quan",
        "order": 0,
        "lessons": [
          {
            "id": 1,
            "title": "Bài 1.1 — Giới thiệu",
            "slug": "bai-1-1-gioi-thieu",
            "type": "video",
            "order": 0,
            "is_preview": true,
            "duration": 300
          }
        ]
      }
    ]
  }
}
```

> Không trả `content`, `video_id`, `document_id`. Chỉ sections + lessons `status=1`.

---

## 📖 Module: Lessons (Bài giảng)

> **Admin Prefix:** `/api/admin`
> **Client Prefix:** `/api/v1`
> **Controller:** `LessonController`
> **Soft Delete:** ✅ Có

---

### 🛠️ Admin Endpoints

---

### 1. Danh sách bài giảng của khóa học

| Thuộc tính     | Giá trị                                    |
|----------------|--------------------------------------------|
| **Method**     | `GET`                                      |
| **URL**        | `/api/admin/courses/{course_id}/lessons`   |
| **Auth**       | ✅ Bearer Token (guard: admin)               |
| **Middleware** | `api`, `auth:admin`                        |
| **Controller** | `LessonController@index`                   |

#### 📥 Query Parameters

| Param      | Type    | Default | Mô tả                       |
|------------|---------|---------|-----------------------------|
| `status`   | integer | —      | Filter: `0` (draft), `1` (published) |
| `type`     | string  | —      | Filter: `video`, `document`, `text` |
| `per_page` | integer | 15      | Số bản ghi mỗi trang        |

#### 📤 Response — `200 OK` (Paginated)

```json
{
  "success": true,
  "message": "Lấy danh sách bài giảng thành công.",
  "data": [
    {
      "id": 1,
      "course_id": 1,
      "title": "Giới thiệu khóa học",
      "slug": "gioi-thieu-khoa-hoc",
      "type": "video",
      "content": "Nội dung bài giảng...",
      "order": 0,
      "is_preview": true,
      "duration": 300,
      "status": 1
    }
  ],
  "pagination": { ... }
}
```

---

### 2. Tạo bài giảng mới

| Thuộc tính     | Giá trị                                    |
|----------------|--------------------------------------------|
| **Method**     | `POST`                                     |
| **URL**        | `/api/admin/courses/{course_id}/lessons`   |
| **Auth**       | ✅ Bearer Token (guard: admin)               |
| **Controller** | `LessonController@store`                   |
| **Request**    | `StoreLessonRequest`                       |

#### 📥 Request Body

| Field         | Type    | Required | Rules                                     |
|---------------|---------|----------|-------------------------------------------|
| `section_id`  | integer | ❌       | nullable, exists:sections,id              |
| `title`       | string  | ✅       | required, string, max:255                 |
| `type`        | string  | ✅       | required, in:video,document,text          |
| `content`     | string  | ❌       | nullable, string                          |
| `video_id`    | integer | ❌       | nullable, exists:media,id                 |
| `document_id` | integer | ❌       | nullable, exists:media,id                 |
| `order`       | integer | ❌       | nullable, integer, min:0                  |
| `is_preview`  | boolean | ❌       | nullable, boolean (default: false)        |
| `duration`    | integer | ❌       | nullable, integer, min:0                  |
| `status`      | integer | ❌       | nullable, in:0,1 (default: 0)             |

> **Lưu ý:** Nếu truyền `section_id`, section đó phải thuộc đúng `course_id` trong URL — nếu không → 422.

#### 📤 Response — `201 Created`

```json
{
  "success": true,
  "message": "Tạo bài giảng thành công.",
  "data": { ... }
}
```

> **Side effect:** Tự động increment `total_lessons` trên Course.

---

### 3. Chi tiết bài giảng

| Thuộc tính     | Giá trị                          |
|----------------|----------------------------------|
| **Method**     | `GET`                            |
| **URL**        | `/api/admin/lessons/{id}`        |
| **Auth**       | ✅ Bearer Token (guard: admin)    |
| **Controller** | `LessonController@show`          |

#### 📤 Response — `200 OK`

```json
{
  "success": true,
  "message": "Lấy chi tiết bài giảng thành công.",
  "data": {
    "id": 1,
    "course_id": 1,
    "title": "Giới thiệu khóa học",
    "slug": "gioi-thieu-khoa-hoc",
    "type": "video",
    "content": "Nội dung...",
    "video_id": 5,
    "document_id": null,
    "order": 0,
    "is_preview": true,
    "duration": 300,
    "status": 1,
    "created_at": "2026-04-07T10:00:00.000000Z",
    "updated_at": "2026-04-07T10:00:00.000000Z"
  }
}
```

---

### 4. Cập nhật bài giảng

| Thuộc tính     | Giá trị                          |
|----------------|----------------------------------|
| **Method**     | `PUT/PATCH`                      |
| **URL**        | `/api/admin/lessons/{id}`        |
| **Auth**       | ✅ Bearer Token (guard: admin)    |
| **Controller** | `LessonController@update`        |
| **Request**    | `UpdateLessonRequest`            |

#### 📤 Response — `200 OK`

```json
{
  "success": true,
  "message": "Cập nhật bài giảng thành công.",
  "data": { ... }
}
```

---

### 5. Xóa bài giảng (soft delete)

| Thuộc tính     | Giá trị                          |
|----------------|----------------------------------|
| **Method**     | `DELETE`                         |
| **URL**        | `/api/admin/lessons/{id}`        |
| **Auth**       | ✅ Bearer Token (guard: admin)    |
| **Controller** | `LessonController@destroy`       |

#### 📤 Response — `200 OK`

```json
{
  "success": true,
  "message": "Xóa bài giảng thành công.",
  "data": null
}
```

> **Side effect:** Tự động decrement `total_lessons` trên Course.

---

### 6. Toggle trạng thái bài giảng

| Thuộc tính     | Giá trị                                    |
|----------------|--------------------------------------------|
| **Method**     | `PATCH`                                    |
| **URL**        | `/api/admin/lessons/{id}/toggle-status`    |
| **Auth**       | ✅ Bearer Token (guard: admin)               |
| **Controller** | `LessonController@toggleStatus`            |

#### 📤 Response — `200 OK`

```json
{
  "success": true,
  "message": "Cập nhật trạng thái thành công.",
  "data": {
    "id": 1,
    "status": 1
  }
}
```

---

### 7. Danh sách bài giảng đã xóa (thùng rác)

| Thuộc tính     | Giá trị                              |
|----------------|--------------------------------------|
| **Method**     | `GET`                                |
| **URL**        | `/api/admin/lessons/trashed`         |
| **Auth**       | ✅ Bearer Token (guard: admin)        |
| **Controller** | `LessonController@trashed`           |

#### 📤 Response — `200 OK` (Paginated)

---

### 8. Khôi phục bài giảng

| Thuộc tính     | Giá trị                              |
|----------------|--------------------------------------|
| **Method**     | `POST`                               |
| **URL**        | `/api/admin/lessons/{id}/restore`    |
| **Auth**       | ✅ Bearer Token (guard: admin)        |
| **Controller** | `LessonController@restore`           |

#### 📤 Response — `200 OK`

```json
{
  "success": true,
  "message": "Khôi phục bài giảng thành công.",
  "data": null
}
```

> **Side effect:** Tự động increment lại `total_lessons` trên Course.

---

### 9. Xóa vĩnh viễn bài giảng

| Thuộc tính     | Giá trị                                    |
|----------------|--------------------------------------------|
| **Method**     | `DELETE`                                   |
| **URL**        | `/api/admin/lessons/{id}/force-delete`     |
| **Auth**       | ✅ Bearer Token (guard: admin)               |
| **Controller** | `LessonController@forceDelete`             |

#### 📤 Response — `200 OK`

```json
{
  "success": true,
  "message": "Xóa vĩnh viễn bài giảng thành công.",
  "data": null
}
```

---

### 10. Sắp xếp thứ tự bài giảng (reorder)

| Thuộc tính     | Giá trị                              |
|----------------|--------------------------------------|
| **Method**     | `POST`                               |
| **URL**        | `/api/admin/lessons/reorder`         |
| **Auth**       | ✅ Bearer Token (guard: admin)        |
| **Controller** | `LessonController@reorder`           |

#### 📥 Request Body

```json
{
  "orders": [
    { "id": 1, "order": 0 },
    { "id": 2, "order": 1 },
    { "id": 3, "order": 2 }
  ]
}
```

| Field            | Type    | Required | Rules                          |
|------------------|---------|----------|--------------------------------|
| `orders`         | array   | ✅       | required, array                |
| `orders.*.id`    | integer | ✅       | required, exists:lessons,id    |
| `orders.*.order` | integer | ✅       | required, integer, min:0       |

#### 📤 Response — `200 OK`

```json
{
  "success": true,
  "message": "Sắp xếp bài giảng thành công.",
  "data": null
}
```

---

### 11. Cập nhật trạng thái nhiều bài giảng (Bulk Action)

| Thuộc tính     | Giá trị                               |
|----------------|---------------------------------------|
| **Method**     | `POST`                                |
| **URL**        | `/api/v1/admin/lessons/bulk-action`   |
| **Auth**       | ✅ Bearer Token (guard: admin)         |
| **Controller** | `LessonController@bulkAction`         |
| **Request**    | `BulkActionLessonsRequest`            |

#### 📥 Request Body

```json
{
  "ids": [1, 2, 3],
  "action": "activate"
}
```

| Field    | Type   | Required | Rules                            |
|----------|--------|----------|----------------------------------|
| `ids`    | array  | ✅       | required, array, exists:lessons,id|
| `action` | string | ✅       | required, in:activate,deactivate |

#### 📤 Response — `200 OK`

```json
{
  "success": true,
  "message": "Cập nhật trạng thái thành công.",
  "data": null
}
```

---

### 12. Xóa nhiều bài giảng (Bulk Delete - Soft Delete)

| Thuộc tính     | Giá trị                               |
|----------------|---------------------------------------|
| **Method**     | `DELETE`                              |
| **URL**        | `/api/v1/admin/lessons/bulk-delete`   |
| **Auth**       | ✅ Bearer Token (guard: admin)         |
| **Controller** | `LessonController@bulkDelete`         |
| **Request**    | `BulkDeleteLessonsRequest`            |

#### 📥 Request Body

```json
{
  "ids": [1, 2, 3]
}
```

#### 📤 Response — `200 OK`

```json
{
  "success": true,
  "message": "Xóa bài giảng thành công.",
  "data": null
}
```

> **Side effect:** Tự động decrement `total_lessons` trên Course cho các bài giảng được xóa.

---

### 13. Khôi phục nhiều bài giảng (Bulk Restore)

| Thuộc tính     | Giá trị                               |
|----------------|---------------------------------------|
| **Method**     | `POST`                                |
| **URL**        | `/api/v1/admin/lessons/bulk-restore`  |
| **Auth**       | ✅ Bearer Token (guard: admin)         |
| **Controller** | `LessonController@bulkRestore`        |
| **Request**    | `BulkRestoreLessonsRequest`           |

#### 📥 Request Body

```json
{
  "ids": [1, 2, 3]
}
```

#### 📤 Response — `200 OK`

```json
{
  "success": true,
  "message": "Khôi phục bài giảng thành công.",
  "data": null
}
```

> **Side effect:** Tự động increment `total_lessons` trên Course tương ứng.

---

### 14. Xóa vĩnh viễn nhiều bài giảng (Bulk Force Delete)

| Thuộc tính     | Giá trị                                 |
|----------------|-----------------------------------------|
| **Method**     | `DELETE`                                |
| **URL**        | `/api/v1/admin/lessons/bulk-force-delete` |
| **Auth**       | ✅ Bearer Token (guard: admin)           |
| **Controller** | `LessonController@bulkForceDelete`      |
| **Request**    | `BulkForceDeleteLessonsRequest`         |

#### 📥 Request Body

```json
{
  "ids": [1, 2, 3]
}
```

#### 📤 Response — `200 OK`

```json
{
  "success": true,
  "message": "Xóa vĩnh viễn bài giảng thành công.",
  "data": null
}
```

---

### 🌐 Public Endpoint

> **Lưu ý:** Endpoint curriculum đã chuyển sang `SectionController@curriculum` — xem **S11** ở phần Sections phía trên.

---

### 11. ~~Danh sách bài giảng công khai~~ → Thay bằng Curriculum (S11)

Endpoint `/api/v1/courses/{slug}/lessons` (CourseController@publicLessons) vẫn còn hoạt động nhưng **deprecated** — FE nên dùng endpoint curriculum mới:

```
GET /api/v1/courses/{slug}/curriculum  →  SectionController@curriculum
```

Endpoint mới trả cấu trúc đầy đủ theo section thay vì flat list.

---

### 👤 Client Endpoints (auth:api — học viên)

---

### 12. Danh sách bài giảng kèm tiến độ (my lessons)

| Thuộc tính     | Giá trị                                    |
|----------------|--------------------------------------------|
| **Method**     | `GET`                                      |
| **URL**        | `/api/v1/my-courses/{slug}/lessons`        |
| **Auth**       | ✅ Bearer Token (guard: api)                |
| **Middleware** | `api`, `auth:api`                          |
| **Controller** | `LessonController@myLessons`               |

#### 📤 Response — `200 OK`

```json
{
  "success": true,
  "message": "Lấy danh sách bài giảng thành công.",
  "data": {
    "sections": [
      {
        "id": 1,
        "title": "Chương 1: Giới thiệu",
        "order": 0,
        "lessons": [
          {
            "id": 1,
            "title": "Bài 1.1 — Giới thiệu",
            "slug": "bai-1-1-gioi-thieu",
            "type": "video",
            "order": 0,
            "is_preview": true,
            "duration": 300,
            "progress": {
              "is_completed": true,
              "watched_seconds": 300,
              "completed_at": "2026-04-08T10:00:00.000000Z"
            }
          }
        ]
      }
    ],
    "orphan_lessons": []
  }
}
```

> `orphan_lessons`: bài giảng chưa được gán vào chương nào (`section_id = null`).

#### ❌ Response — `403 Forbidden`

```json
{
  "success": false,
  "message": "Bạn chưa mua khóa học này.",
  "data": null
}
```

---

### 13. Cập nhật tiến độ học bài giảng

| Thuộc tính     | Giá trị                                    |
|----------------|--------------------------------------------|
| **Method**     | `POST`                                     |
| **URL**        | `/api/v1/lessons/{id}/progress`            |
| **Auth**       | ✅ Bearer Token (guard: api)                |
| **Middleware** | `api`, `auth:api`                          |
| **Controller** | `LessonController@updateProgress`          |

#### 📥 Request Body

| Field             | Type    | Required | Rules                    |
|-------------------|---------|----------|--------------------------|
| `watched_seconds` | integer | ✅       | required, integer, min:0 |
| `is_completed`    | boolean | ❌       | nullable, boolean        |

> **Lưu ý:** `completed_at` chỉ được set lần đầu khi `is_completed=true`. Không ghi đè nếu đã có.

#### 📤 Response — `200 OK`

```json
{
  "success": true,
  "message": "Cập nhật tiến độ thành công.",
  "data": {
    "lesson_id": 1,
    "course_id": 1,
    "is_completed": true,
    "watched_seconds": 280,
    "completed_at": "2026-04-07T12:00:00.000000Z"
  }
}
```

#### ❌ Response — `403 Forbidden`

```json
{
  "success": false,
  "message": "Bạn chưa mua khóa học này.",
  "data": null
}
```

---

### 14. Tổng quan tiến độ khóa học

| Thuộc tính     | Giá trị                                    |
|----------------|--------------------------------------------|
| **Method**     | `GET`                                      |
| **URL**        | `/api/v1/courses/{slug}/progress`          |
| **Auth**       | ✅ Bearer Token (guard: api)                |
| **Middleware** | `api`, `auth:api`                          |
| **Controller** | `LessonController@courseProgress`          |

#### 📤 Response — `200 OK`

```json
{
  "success": true,
  "message": "Lấy tiến độ học thành công.",
  "data": {
    "course_id": 1,
    "total_lessons": 10,
    "completed_lessons": 3,
    "percent": 30,
    "sections": [
      {
        "id": 1,
        "title": "Chương 1: Giới thiệu",
        "order": 0,
        "total": 3,
        "completed": 2,
        "lessons": [
          {
            "id": 1,
            "title": "Bài 1.1 — Giới thiệu",
            "is_completed": true,
            "watched_seconds": 300
          },
          {
            "id": 2,
            "title": "Bài 1.2 — Cài đặt",
            "is_completed": false,
            "watched_seconds": 120
          }
        ]
      }
    ]
  }
}
```

#### ❌ Response — `403 Forbidden`

```json
{
  "success": false,
  "message": "Bạn chưa mua khóa học này.",
  "data": null
}
```

---

## 📊 Tổng hợp Endpoints

- **Authentication:** Sử dụng Laravel Sanctum với Bearer Token. Token được tạo khi đăng nhập/đăng ký thành công.
- **Guard Admin:** Sử dụng guard `admin` (cấu hình trong `config/auth.php`).
- **Guard Student:** Sử dụng guard `api` (default Sanctum guard cho Student).
- **Soft Delete:** Module Users, Teachers, Courses, Lessons hỗ trợ soft delete — bản ghi bị xoá vẫn còn trong DB với `deleted_at` ≠ null.
- **Role/Permission:** Sử dụng package `spatie/laravel-permission` để quản lý role và permission.
- **Email Verification:** Student cần xác thực email qua token hex 64 ký tự, có hạn 24 giờ.
- **Password Reset:** Sử dụng Laravel Password Broker (broker: `students`).
- **Throttle:**
  - Admin Login: 5 request/phút
  - Student Register: 10 request/phút
  - Student Login: 5 request/phút
  - Forgot Password: 3 request/phút
  - Resend Verification: 3 request/phút
- **Validation:** Tất cả validation errors trả về status `422` với messages tiếng Việt.
- **Nested Set (Categories):** Dùng `kalnoy/nestedset`. `parent_id` chỉ chấp nhận category chưa bị soft-delete. `move` guard circular reference — không thể di chuyển vào chính nó hoặc descendant.
- **Teachers:** Soft delete + bulk actions. Public API chỉ trả về teachers active (`status=1`). `publicShow` dùng slug, bao gồm danh sách courses của giảng viên đó.
- **Courses:** Soft delete + bulk actions. 3 migrations (courses, categories_courses pivot, students_course). Admin CRUD đầy đủ. Public API chỉ trả published. Hỗ trợ filter theo search, category, level, teacher, status. `publicLessons()` trả lessons lock/unlock theo trạng thái mua.
- **Upload (Media Service):** Hỗ trợ 2 flow: Local (dev, getID3 extract metadata) và S3 Presigned URL (production, FE gửi metadata). File lưu tên UUID, không expose `path`. `reference_count` kiểm tra trước khi xóa. Artisan `media:prune-orphans` dọn file rác hàng ngày 3:00 sáng.
- **Sections:** Bảng mới `sections` tạo cấp trung gian Course → Section → Lesson. Admin CRUD đầy đủ (11 endpoints: CRUD + toggle + trashed + restore + force-delete + reorder). Public 1 endpoint `curriculum` trả cấu trúc đầy đủ theo chương. Xóa section → lessons không bị xóa (SET NULL).
- **Lessons:** 4 migrations (sections, lessons, lesson_progress, add_section_id). Bảng lessons có thêm `section_id nullable`. Admin CRUD 10 endpoints. Public curriculum chuyển sang SectionController. Client 3 endpoints (myLessons group theo section, updateProgress, courseProgress group theo section). Soft delete có side-effect increment/decrement `total_lessons` trên Course.
- **Total endpoints:** 92 (11 Auth + 15 Users + 19 Categories + 16 Courses + 6 Upload + 14 Lessons + 11 Sections)

