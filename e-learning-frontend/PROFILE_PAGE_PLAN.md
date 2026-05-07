# Kế hoạch implement trang Profile (`/profile`)

## Tổng quan

Trang profile cho student với 2 tab:
- **Tab 1 — Thông tin cá nhân**: xem/sửa avatar, họ tên, email, ngày sinh
- **Tab 2 — Bảo mật**: đổi mật khẩu qua email xác nhận

---

## Backend

### 1. Upload avatar cho student

**File cần tạo/sửa:**
- `Modules/Students/app/Http/Controllers/StudentProfileController.php` ← tạo mới
- `Modules/Students/routes/api.php` ← thêm routes

**Route:**
```
POST /api/v1/profile/avatar   middleware: auth:api, email.verified
```

**Logic:**
- Nhận `file` (image: jpg/jpeg/png/webp, max 2MB)
- Lưu vào `storage/app/public/avatars/`
- Trả về URL đầy đủ qua `asset('storage/' . $path)`
- Nếu student đã có avatar cũ → xoá file cũ

**Response:**
```json
{ "success": true, "message": "Cập nhật avatar thành công.", "data": { "avatar": "http://..." } }
```

---

### 2. Cập nhật thông tin cá nhân

**File cần tạo:**
- `Modules/Students/app/Http/Requests/UpdateMyProfileRequest.php` ← tạo mới

**Route:**
```
PATCH /api/v1/profile   middleware: auth:api, email.verified
```

**Validation rules:**
```php
'name'          => 'sometimes|string|max:255'
'email'         => 'sometimes|email|max:255|unique:students,email,{currentId}'
'date_of_birth' => 'nullable|date|before:today'
```

**Logic:**
- Update student, trả về `StudentResource` đã cập nhật
- Store frontend cập nhật lại state `student`

**Response:**
```json
{ "success": true, "message": "Cập nhật thông tin thành công.", "data": { ...StudentResource } }
```

---

### 3. Đổi mật khẩu qua email

**File cần tạo:**
- `Modules/Students/app/Http/Requests/ChangePasswordRequest.php` ← tạo mới

**Route:**
```
POST /api/v1/profile/change-password   middleware: auth:api, email.verified
```

**Validation rules:**
```php
'current_password'          => 'required|string'
'new_password'              => 'required|string|min:8|max:100|confirmed'
'new_password_confirmation' => 'required'
```

**Logic:**
1. Xác thực `current_password` khớp với hash trong DB (`Hash::check`)
2. Nếu sai → trả về 422 với `errors.current_password`
3. Nếu đúng → gửi email reset password qua `Password::broker('students')->sendResetLink(['email' => $student->email])`
4. Trả về 200 — "Vui lòng kiểm tra email để xác nhận đổi mật khẩu."

**Bấm link trong mail → vào `/reset-password` hiện có** (không cần tạo thêm)

**Response:**
```json
{ "success": true, "message": "Vui lòng kiểm tra email để xác nhận đổi mật khẩu." }
```

---

## Frontend

### 4. Cập nhật Types

**File:** `src/types/auth.types.ts`

Thêm field `date_of_birth?: string` vào interface `Student`.

---

### 5. Profile Service

**File:** `src/services/profile.service.ts` ← tạo mới

```ts
export const profileService = {
  update: (data: Record<string, unknown>) =>
    http.patch('/profile', data),

  uploadAvatar: (file: File) => {
    const form = new FormData()
    form.append('file', file)
    return http.post('/profile/avatar', form, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
  },

  changePassword: (data: {
    current_password: string
    new_password: string
    new_password_confirmation: string
  }) => http.post('/profile/change-password', data),
}
```

---

### 6. Composable

**File:** `src/composables/useProfile.ts` ← tạo mới

**State:**
```ts
const saving = ref(false)
const uploadingAvatar = ref(false)
const activeTab = ref<'info' | 'security'>('info')
const form = reactive({ name, email, date_of_birth })
const passwordForm = reactive({ current_password, new_password, new_password_confirmation })
const errors = ref<Record<string, string[]>>({})
const passwordErrors = ref<Record<string, string[]>>({})
```

**Methods:**
- `loadProfile()` — lấy từ store (đã có từ fetchMe)
- `saveProfile()` — gọi `profileService.update()`, cập nhật store
- `handleAvatarChange(file)` — gọi `profileService.uploadAvatar()`, cập nhật store
- `submitChangePassword()` — gọi `profileService.changePassword()`, hiện toast thành công

---

### 7. ProfilePage.vue

**File:** `src/views/client/ProfilePage.vue` ← implement lại

**Layout:**
```
┌─────────────────────────────────────────────────┐
│  Avatar (click để đổi)  |  Tên  |  Email        │
├─────────────────────────────────────────────────┤
│  [Tab: Thông tin cá nhân]  [Tab: Bảo mật]       │
├─────────────────────────────────────────────────┤
│  Tab 1:                                          │
│    - Họ và tên (input)                           │
│    - Email (input)                               │
│    - Ngày sinh (date input)                      │
│    - Nút "Lưu thay đổi"                          │
│                                                  │
│  Tab 2:                                          │
│    - Mật khẩu hiện tại (input)                   │
│    - Mật khẩu mới (input)                        │
│    - Xác nhận mật khẩu mới (input)               │
│    - Link "Quên mật khẩu?"                       │
│    - Nút "Gửi xác nhận qua email"                │
└─────────────────────────────────────────────────┘
```

---

## Thứ tự implement

- [ ] **BE-1** — `StudentProfileController` + routes (upload avatar, update profile, change password)
- [ ] **BE-2** — `UpdateMyProfileRequest` + `ChangePasswordRequest`
- [ ] **FE-1** — Thêm `date_of_birth` vào `Student` type
- [ ] **FE-2** — Tạo `profile.service.ts`
- [ ] **FE-3** — Tạo `useProfile.ts` composable
- [ ] **FE-4** — Implement `ProfilePage.vue`

---

## Ghi chú

- Avatar cũ bị xoá khi upload avatar mới (tránh rác trong storage)
- Email thay đổi không cần verify lại (giữ đơn giản cho thesis)
- Flow đổi mật khẩu reuse hoàn toàn `Password::broker('students')` và trang `/reset-password` hiện có
- `auth:api` + `email.verified` trên tất cả route profile
