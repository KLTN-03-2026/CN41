# API Reference — Auth

Base URL: `http://localhost:8000/api/v1`

---

## Admin Auth

### POST `/admin/auth/login`

Đăng nhập tài khoản admin/teacher.

**Middleware:** `throttle:5,1`

**Request Body:**
```json
{
  "email": "admin@elearning.com",
  "password": "password"
}
```

**Validation:**
| Field | Rules |
|-------|-------|
| `email` | required, email, max:255 |
| `password` | required, string, min:6, max:100 |

**Response 200:**
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
      "permissions": ["courses.view", "courses.create", "..."]
    }
  }
}
```

**Response 401:** Sai email hoặc mật khẩu.
**Response 429:** Vượt quá 5 lần/phút.

---

### POST `/admin/auth/logout`

**Middleware:** `auth:admin`

**Response 200:**
```json
{ "success": true, "message": "Đăng xuất thành công", "data": null }
```

---

### GET `/admin/auth/me`

**Middleware:** `auth:admin`

**Response 200:** Trả về thông tin user hiện tại (giống login response).

---

## Student Auth

### POST `/auth/register`

**Middleware:** `throttle:10,1`

**Request Body:**
```json
{
  "name": "Nguyễn Văn A",
  "email": "student@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

**Validation:**
| Field | Rules |
|-------|-------|
| `name` | required, string, max:255 |
| `email` | required, email, max:255, unique:students |
| `password` | required, min:8, max:100, confirmed |

**Response 201:**
```json
{
  "success": true,
  "message": "Đăng ký thành công. Vui lòng kiểm tra email để xác minh tài khoản.",
  "data": {
    "token": "2|xyz456...",
    "student": {
      "id": 5,
      "name": "Nguyễn Văn A",
      "email": "student@example.com",
      "email_verified_at": null
    }
  }
}
```

> Token được cấp ngay nhưng cần xác minh email trước khi thực hiện hành động.

---

### POST `/auth/login`

**Middleware:** `throttle:5,1`

**Request Body:**
```json
{
  "email": "student@elearning.com",
  "password": "password"
}
```

**Response 200:**
```json
{
  "success": true,
  "data": {
    "token": "3|def789...",
    "student": {
      "id": 5,
      "name": "Student",
      "email": "student@elearning.com",
      "email_verified_at": "2026-03-18T10:00:00Z"
    }
  }
}
```

**Response 401:** Sai thông tin đăng nhập.
**Response 403:** Chưa xác minh email — `errors.email_not_verified: true`.

---

### GET `/auth/verify-email/{token}`

Xác minh email qua link trong email. Token là chuỗi hex 64 ký tự.

**Redirect về frontend với query param `?status=`:**
| Status | Ý nghĩa |
|--------|---------|
| `success` | Xác minh thành công |
| `invalid` | Token không tồn tại |
| `expired` | Token hết hạn (24h) |
| `already` | Email đã được xác minh trước đó |

---

### POST `/auth/resend-verification`

**Middleware:** `throttle:3,1`

**Request Body:**
```json
{ "email": "student@example.com" }
```

**Response 200:** Gửi lại email xác minh.

---

### POST `/auth/forgot-password`

**Middleware:** `throttle:3,1`

**Request Body:**
```json
{ "email": "student@example.com" }
```

**Response 200:** Luôn trả 200 (kể cả email không tồn tại — tránh user enumeration).

---

### POST `/auth/reset-password`

**Middleware:** `throttle:3,1`

**Request Body:**
```json
{
  "token": "reset-token-from-email",
  "email": "student@example.com",
  "password": "newpassword123",
  "password_confirmation": "newpassword123"
}
```

**Response 200:** Đặt lại mật khẩu thành công.
**Response 422:** Token không hợp lệ hoặc hết hạn (60 phút).

---

### POST `/auth/logout`

**Middleware:** `auth:api`

**Response 200:** Revoke token hiện tại.

---

### GET `/auth/me`

**Middleware:** `auth:api`

**Response 200:** Thông tin student hiện tại.
