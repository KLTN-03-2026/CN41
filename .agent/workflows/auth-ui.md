---
description: "FE 1 — Auth UI: Admin Login, Student Register/Login, Route Guards"
---

# FE 1 — Auth UI

> **Phase F1** — Tạo các trang đăng nhập/đăng ký cho Admin và Student.
> Prerequisite: Phase F0 đã hoàn thành.

---

## Context chung

```
Stack: Vue 3, Tailwind CSS, Pinia, Vue Router, Lucide icons (lucide-vue-next),
       VeeValidate + Zod (@vee-validate/zod), Vue Toastification.
Backend API:
  - POST /api/v1/admin/auth/login → { success, data: { token, user: {id,name,email} } }
  - POST /api/v1/auth/register    → { success, data: { token, student: {id,name,email} } }
  - POST /api/v1/auth/login       → { success, data: { token, student: {id,name,email} } }
```

---

## Task F1.1 — AdminLoginPage.vue

Tạo `src/pages/auth/AdminLoginPage.vue`:

1. **Layout:** Màn hình chia đôi
   - Cột trái (hidden mobile, hiện desktop ~40%): background primary-600, logo + tagline
   - Cột phải (~60%): form login căn giữa

2. **Form:**
   - Logo / tên "E-Learning Admin" ở trên
   - Input Email (class `input-field`) + icon `Mail` từ lucide-vue-next
   - Input Password + icon `Lock` + nút toggle show/hide password (`Eye`/`EyeOff` từ lucide)
   - Nút "Đăng nhập" (btn-primary, full width) với loading spinner khi submit
   - Error message toàn form nếu 401

3. **Logic submit:**
   - Validate: email required, password required (dùng VeeValidate + Zod)
   - Gọi `adminAuthStore.login(email, password)`
   - Thành công → redirect `/admin/dashboard` + `toast.success('Đăng nhập thành công!')`
   - Thất bại → hiện error message từ API

4. Nếu đã có `adminToken` → tự redirect `/admin/dashboard`

5. **Icons:** `import { Mail, Lock, Eye, EyeOff, BookOpen } from 'lucide-vue-next'`

---

## Task F1.2 — RegisterPage.vue (Student)

Tạo `src/pages/auth/RegisterPage.vue`:

1. **Form VeeValidate + Zod schema:**
   - `name`: required, min 2
   - `email`: required, email format
   - `password`: required, min 6
   - `password_confirmation`: phải khớp password

2. **Icons:** `User`, `Mail`, `Lock`, `Eye`, `EyeOff` từ lucide-vue-next

3. **Submit:**
   - `studentAuthStore.register(values)`
   - Thành công → redirect `/` + `toast.success('Đăng ký thành công!')`
   - Lỗi 422 → map errors vào từng field
   - Lỗi khác → `toast.error(message)`

4. **Design:**
   - Card căn giữa màn hình, `max-w-md`
   - Input có icon bên trái (relative + absolute positioning)
   - Password toggle Eye/EyeOff
   - Loading spinner trong button khi submit
   - Link: "Đã có tài khoản? Đăng nhập"

---

## Task F1.3 — LoginPage.vue (Student)

Tạo `src/pages/auth/LoginPage.vue`:

1. **Form VeeValidate + Zod:**
   - `email`: required, email
   - `password`: required

2. **Submit:**
   - `studentAuthStore.login(email, password)`
   - Thành công → redirect đến `$route.query.redirect` hoặc `/`
   - Thất bại → hiện error inline

3. **Design:** Giống RegisterPage, card căn giữa, icon input, password toggle

4. Link: "Chưa có tài khoản? Đăng ký" + "Quên mật khẩu?"

5. Nếu đã đăng nhập → redirect `/`

---

## Task F1.4 — Route Guards hoàn chỉnh

Cập nhật `src/router/index.js`:

1. `/admin/*` kiểm tra `adminToken` — không có → redirect `/admin/login`
2. `/my-courses`, `/cart`, `/checkout`, `/profile` kiểm tra `studentToken` — không có → redirect `/login?redirect=...`
3. Guest routes: admin đã login → `/admin/dashboard`, student đã login → `/`
4. Sai role → redirect `/403`

---

## ✅ Kiểm tra hoàn thành

- [ ] Admin login hoạt động, redirect đúng
- [ ] Student register + login hoạt động, toast hiện đúng
- [ ] Route guards chặn đúng trang cần auth
- [ ] Validation form hiện lỗi đúng field
- [ ] Toggle password hoạt động
- [ ] Loading spinner hiện khi submit

Sau khi xong, tick F1.1 → F1.4 trong `QLCV/QLCV-FE.md`.
