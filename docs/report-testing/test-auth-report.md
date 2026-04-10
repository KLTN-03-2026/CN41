# Báo Cáo Kiểm Thử — Tính Năng Auth (Admin & Student)

**Trạng thái kiểm thử:** Hoàn thành 100% (Module 1, 2, 3, 4, 5)
**Thời gian test lần 1:** 09/04/2026
**Thời gian test lần 2 + Fix:** 10/04/2026
**Cập nhật tính năng hoàn thiện:** 10/04/2026

Dựa trên Checklist trong `docs/testing/test-auth.md`.

---

## ✅ Phần 1: Kết quả Pass — Module 1 (Admin Login)

| Test | Mô tả | Kết quả |
|------|-------|---------|
| 1.1 | Form trống — validation chặn submit | ✅ PASS |
| 1.2 | Email sai format | ✅ PASS |
| 1.3 | Sai mật khẩu → Alert đỏ + 401 | ✅ PASS |
| 1.4 | Email không tồn tại → 401 | ✅ PASS |
| 1.5 | Password < 6 ký tự — validation client | ✅ PASS |
| 1.6 | Đăng nhập thành công → redirect `/admin/dashboard` + lưu `adminToken` | ✅ PASS |
| 1.7 | Session persist — refresh F5 vẫn ở dashboard | ✅ PASS |
| 1.8 | Route guard — chưa login → redirect `/admin/login` | ✅ PASS |
| 1.9 | Đã login → truy cập `/admin/login` → redirect về dashboard | ✅ PASS |
| 1.10 | Logout → xóa `adminToken` → redirect `/admin/login` | ✅ PASS |
| 1.11 | Token giả → 401 interceptor bắt → redirect `/admin/login` | ✅ PASS |

---

## ✅ Phần 2: Kết quả Pass — Module 2 (Student Register & Login)

| Test | Mô tả | Kết quả |
|------|-------|---------|
| 2.1 | Form trống — validation required | ✅ PASS |
| 2.2 | Tên quá ngắn (< 2 ký tự) | ✅ PASS |
| 2.3 | Email sai format | ✅ PASS |
| 2.4 | Password < 8 ký tự | ✅ PASS |
| 2.5 | Confirm password không khớp | ✅ PASS |
| 2.6 | Đăng ký thành công → hiện màn hình "Kiểm tra hộp thư" thay vì redirect | ✅ PASS |
| 2.7 | Email trùng → 422 + hiện lỗi server | ✅ PASS |
| 2.8 | Email có khoảng trắng → FE/server xử lý | ✅ PASS |
| 2.9 | Sai mật khẩu → Alert + 401 | ✅ PASS |
| 2.10 | Đăng nhập thành công → 200 + `studentToken` | ✅ PASS |
| 2.11 | Redirect sau login → về đúng trang trước đó (`?redirect=`) | ✅ PASS |
| 2.12 | Route guard — chưa login → redirect `/login?redirect=...` | ✅ PASS |
| 2.13 | Đã login → truy cập `/login`, `/register` → redirect `/` | ✅ PASS |
| 2.14 | Logout → xóa `studentToken` → redirect `/login` | ✅ PASS |
| 2.15 | Token giả → 401 → redirect `/login` | ✅ PASS |

---

## ✅ Phần 3: Kết quả — Module 3, 4, 5

### Module 3 — Quên mật khẩu

| Test | Mô tả | Kết quả |
|------|-------|---------|
| 3.1 | Truy cập trang quên mật khẩu | ✅ PASS |
| 3.2 | Submit email trống — validation chặn | ✅ PASS |
| 3.3 | Email sai format — validation chặn | ✅ PASS |
| 3.4 | Email không tồn tại — trả 200 (không lộ thông tin) | ✅ PASS |
| 3.5 | Gửi link thành công → Alert xanh + email gửi qua Mailtrap | ✅ PASS |
| 3.6 | Rate limit 60 giây — gửi lại bị throttle | ✅ PASS |
| 3.7 | Link reset hợp lệ → hiện form đặt mật khẩu mới | ✅ PASS |
| 3.8 | Token không hợp lệ / hết hạn → hiện thông báo lỗi | ✅ PASS |
| 3.9 | Validation mật khẩu mới (< 8 ký tự, không khớp) | ✅ PASS |
| 3.10 | Đặt lại mật khẩu thành công → redirect `/login`, đăng nhập được bằng mật khẩu mới | ✅ PASS |

### Module 4 — Xác nhận Email

| Test | Mô tả | Kết quả |
|------|-------|---------|
| 4.1 | Đăng ký xong → hiện màn hình "Kiểm tra hộp thư" với hướng dẫn 3 bước | ✅ PASS |
| 4.2 | Chưa verify → mọi trang (kể cả `/`) đều bị redirect `/verify-email` | ✅ PASS |
| 4.3 | Gửi lại email → loading → thông báo thành công + email vào Mailtrap | ✅ PASS |
| 4.4 | Rate limit gửi lại — cooldown 60 giây trên nút, throttle backend | ✅ PASS |
| 4.5 | Click link xác nhận → redirect `http://localhost:5173/verify-email/result?status=success` → trang UI đẹp | ✅ PASS |
| 4.6 | Link đã dùng → `?status=already` / Token sai → `?status=invalid` / Hết hạn → `?status=expired` | ✅ PASS |
| 4.7 | Tài khoản đã verify → trải nghiệm bình thường, không bị redirect | ✅ PASS |

### Module 5 — Ghi nhớ đăng nhập (Remember Me)

| Test | Mô tả | Kết quả |
|------|-------|---------|
| 5.1 | Checkbox "Ghi nhớ đăng nhập" hiển thị trên trang login student & admin | ✅ PASS |
| 5.2 | Không tick → token lưu `sessionStorage` → đóng tab mất session | ✅ PASS |
| 5.3 | Tick → token lưu `localStorage` → đóng browser vẫn còn | ✅ PASS |
| 5.4 | Thời hạn token — Sanctum `expiration: null` (không hết hạn mặc định) | ✅ PASS |
| 5.5 | Logout khi có Remember Me → xóa cả `localStorage` lẫn `sessionStorage` | ✅ PASS |
| 5.6 | Admin Remember Me — tick → `adminToken` vào `localStorage` | ✅ PASS |

---

## 🐛 Phần 4: Bug Đã Phát Hiện & Đã Fix

### Bug 2.1 — Zod hiện "Required" thay vì message tiếng Việt
- **Nguyên nhân:** `vee-validate` + `zod` khi field `undefined` chạy lỗi type trước khi chạy `min(1, 'msg')`.
- **Fix:** Thêm `required_error` vào constructor `z.string({ required_error: '...' })` cho tất cả field password, confirm password ở `RegisterPage.vue` và `ResetPasswordPage.vue`.

### Bug 2.10 — 429 Too Many Requests khi đăng nhập
- **Nguyên nhân:** Login page gọi cả 2 endpoint student + admin (fallback), đốt throttle `5,1` rất nhanh.
- **Fix:** Loại bỏ fallback admin trong trang login student — chỉ gọi đúng 1 endpoint.

### Bug 4.5 — Link xác thực redirect về `localhost:3000` thay vì `localhost:5173`
- **Nguyên nhân:** `config('app.frontend_url')` không được khai báo trong `config/app.php`, queue worker dùng config cache cũ.
- **Fix:** Thêm `'frontend_url' => env('APP_FRONTEND_URL', 'http://localhost:5173')` vào `config/app.php`, restart queue worker.

### Bug 4.5b — Nhấn "Đăng nhập ngay" tại `/verify-email/result?status=success` vào thẳng `/` thay vì `/login`
- **Nguyên nhân:** Sau đăng ký, student có `studentToken` (chưa verify) → router thấy token → `requiresGuest` redirect về `/` → guard chặn về `/verify-email` → loop.
- **Fix:** `onMounted` trong `VerifyEmailResultPage.vue` tự động `logout()` token cũ khi `status=success`, đảm bảo user đăng nhập lại sạch sẽ.

### Bug 4.2 — Route guard chỉ chặn trang `requiresAuth`, không chặn trang chủ `/`
- **Nguyên nhân:** Guard cũ dùng điều kiện `to.meta.requiresAuth && to.meta.guard === 'student'`.
- **Fix:** Mở rộng guard — chặn **mọi trang** nếu có token nhưng chưa verify, chỉ cho phép whitelist: `/verify-email`, `/verify-email/result`, `/login`, `/register`.

### Bug logout — 500 Internal Server Error
- **Nguyên nhân:** `currentAccessToken()` trả `null` trong môi trường test gây crash.
- **Fix:** Thêm null check cho `currentAccessToken()` trong cả `Admin/AuthController` và `Student/AuthController`.

---

## 📬 Phần 5: Cải tiến Gửi Mail (10/04/2026)

Toàn bộ logic gửi mail đã được refactor theo pattern **Event/Listener + ShouldQueue**:

| Tính năng | Pattern cũ | Pattern mới |
|-----------|-----------|-------------|
| Xác thực đăng ký | `Mail::send` đồng bộ trong controller | `StudentRegistered::dispatch()` → `SendVerificationEmail` (queue) |
| Gửi lại xác thực | `Mail::send` đồng bộ trong controller | `StudentRegistered::dispatch()` → tái dùng cùng Listener |
| Quên mật khẩu | `Notification` chạy đồng bộ | `StudentResetPasswordNotification implements ShouldQueue` |
| Mua hàng thành công | `Mail::send` đồng bộ trong IPN handler | `OrderPlaced::dispatch()` → `SendOrderConfirmationEmail` (queue) |

**Kết quả:** Không còn block HTTP response khi gửi mail. IPN callback VNPAY không bị timeout do mail chậm.

---

## 🏁 Tổng kết

Hệ thống Authentication đã đầy đủ tính năng theo yêu cầu, đảm bảo cả UX (Remember Me, Validation, Email Verification Flow) và Security (Throttle, Route Guard, Queue Mail). Tổng cộng **46/46 test cases PASS**.
