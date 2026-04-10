# 🔐 Test Auth — Admin & Student

> **Chuẩn bị:** Backend chạy tại `http://localhost:8000`, Frontend tại `http://localhost:5173`.
> Mở DevTools F12 → Network tab (tick "Preserve log").

---

## Tài khoản test

| Role | Email | Password |
|------|-------|----------|
| Super Admin | superadmin@elearning.com | password |
| Admin | admin@elearning.com | password |
| Student | student@elearning.com | password |

---

## MODULE 1 — Admin Login

### Test 1.1: Form trống

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Mở `/admin/login` | Trang login: logo, email, password, nút Đăng nhập |
| 2 | Nhấn "Đăng nhập" không nhập gì | Lỗi inline: "Vui lòng nhập email" + "Vui lòng nhập mật khẩu" |
| 3 | Network tab | Không có request (VeeValidate chặn) |

### Test 1.2: Email sai format

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Email: `abc` | Lỗi: "Email không đúng định dạng" |
| 2 | Email: `abc@` | Lỗi: "Email không đúng định dạng" |
| 3 | Email: `abc@test.com` | Không lỗi |

### Test 1.3: Sai mật khẩu

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | `admin@elearning.com` / `wrongpass` | Alert đỏ: "Email hoặc mật khẩu không đúng." |
| 2 | Network | `POST /api/v1/admin/auth/login` → **401** |
| 3 | Form | Nút hết loading, form giữ email đã nhập |

### Test 1.4: Email không tồn tại

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | `notexist@test.com` / `password` | Alert đỏ: "Email hoặc mật khẩu không đúng." → **401** |

### Test 1.5: Password quá ngắn (< 6 ký tự)

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | `admin@elearning.com` / `12345` | Lỗi client: "Mật khẩu phải có ít nhất 6 ký tự" |
| 2 | Network | Không có request |

### Test 1.6: Đăng nhập thành công ✅

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | `admin@elearning.com` / `password` | Loading spinner |
| 2 | Kết quả | Redirect → `/admin/dashboard` |
| 3 | Network | `POST /api/v1/admin/auth/login` → **200**, response có `token` + `user` |
| 4 | LocalStorage | Key `adminToken` xuất hiện |
| 5 | Header | Hiển thị tên "Admin" góc phải |

### Test 1.7: Session Persist

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Sau login, refresh trang (F5) | Vẫn ở `/admin/dashboard`, không bị redirect |
| 2 | Mở tab mới → `/admin/courses` | Truy cập được |

### Test 1.8: Route Guard — Chưa login

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Xóa `adminToken` (Application → LocalStorage) | — |
| 2 | Truy cập `/admin/dashboard` | Redirect → `/admin/login` |
| 3 | Truy cập `/admin/courses` | Redirect → `/admin/login` |
| 4 | Truy cập `/admin/categories` | Redirect → `/admin/login` |

### Test 1.9: Đã login → vào lại trang login

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Đang login admin → truy cập `/admin/login` | Redirect ngay → `/admin/dashboard` |

### Test 1.10: Logout

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Click avatar/tên → "Đăng xuất" | — |
| 2 | Network | `POST /api/v1/admin/auth/logout` → **200** |
| 3 | LocalStorage | `adminToken` bị xóa |
| 4 | Redirect | → `/admin/login` |
| 5 | Nhấn Back browser | Không vào được dashboard (redirect lại login) |

### Test 1.11: Token hết hạn (giả lập)

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Mở Application → LocalStorage → sửa `adminToken` thành chuỗi random | — |
| 2 | Refresh trang | Redirect → `/admin/login` |
| 3 | Network | Request nào đó trả **401** → interceptor bắt → redirect |

---

## MODULE 2 — Student Register

### Test 2.1: Form trống

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Mở `/register` | Form 4 fields: tên, email, password, confirm |
| 2 | Submit không nhập gì | Lỗi inline tất cả fields |
| 3 | Network | Không có request |

### Test 2.2: Tên quá ngắn

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Tên: `A` (1 ký tự) | Lỗi: "Họ tên tối thiểu 2 ký tự" |
| 2 | Tên: `AB` | Không lỗi |

### Test 2.3: Email sai format

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Email: `test` | Lỗi: "Email không đúng định dạng" |
| 2 | Email: `test@test.com` | Không lỗi |

### Test 2.4: Password quá ngắn

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Password: `1234567` (7 ký tự) | Lỗi: "Mật khẩu tối thiểu 8 ký tự" |
| 2 | Password: `12345678` | Không lỗi |

### Test 2.5: Confirm password không khớp

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Password: `12345678`, Confirm: `12345679` | Lỗi: "Mật khẩu xác nhận không khớp" |
| 2 | Confirm đúng | Không lỗi |

### Test 2.6: Đăng ký thành công ✅

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | `Test User` / `newuser@test.com` / `12345678` / `12345678` | Toast "Đăng ký thành công!" |
| 2 | Network | `POST /api/v1/auth/register` → **201** |
| 3 | Response | Có `token` + `student` object |
| 4 | LocalStorage | `studentToken` xuất hiện |
| 5 | Redirect | → `/` (trang chủ) |

### Test 2.7: Email trùng

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Đăng ký lại email vừa dùng | Alert đỏ lỗi server |
| 2 | Network | **422** |
| 3 | Response | `errors.email: ["Email đã được sử dụng."]` |

### Test 2.8: Đăng ký với email có khoảng trắng

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Email: ` test@test.com` (có space đầu) | FE trim hoặc server báo lỗi format |

---

## MODULE 2 — Student Login

### Test 2.9: Sai mật khẩu

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Logout (xóa `studentToken`) | — |
| 2 | `student@elearning.com` / `wrongpass` | Alert: "Email hoặc mật khẩu không đúng." |
| 3 | Network | `POST /api/v1/auth/login` → **401** |

### Test 2.10: Đăng nhập thành công ✅

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | `student@elearning.com` / `password` | Toast "Đăng nhập thành công!" |
| 2 | Network | **200** |
| 3 | LocalStorage | `studentToken` xuất hiện |
| 4 | Redirect | → `/` |

### Test 2.11: Redirect sau login

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Xóa token → truy cập `/my-courses` | Redirect → `/login?redirect=/my-courses` |
| 2 | Đăng nhập thành công | Redirect → `/my-courses` (không phải `/`) |

### Test 2.12: Route Guard — Chưa login

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Xóa `studentToken` | — |
| 2 | `/my-courses` | Redirect → `/login?redirect=/my-courses` |
| 3 | `/cart` | Redirect → `/login?redirect=/cart` |
| 4 | `/profile` | Redirect → `/login?redirect=/profile` |
| 5 | `/courses` (public) | OK — không cần auth |
| 6 | `/courses/some-slug` (public) | OK — không cần auth |

### Test 2.13: Đã login → vào trang login/register

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Đang login student → `/login` | Redirect → `/` |
| 2 | Đang login student → `/register` | Redirect → `/` |

### Test 2.14: Student Logout

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Click menu → "Đăng xuất" | — |
| 2 | Network | `POST /api/v1/auth/logout` → **200** |
| 3 | LocalStorage | `studentToken` bị xóa |
| 4 | Redirect | → `/` hoặc `/login` |

### Test 2.15: Token hết hạn (giả lập)

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Sửa `studentToken` thành chuỗi random | — |
| 2 | Truy cập `/my-courses` | Redirect → `/login` |

---

## MODULE 3 — Quên mật khẩu (Forgot Password)

> **Lưu ý:** Cần cấu hình mail driver (`MAIL_MAILER=log` để test nhanh — xem log trong `storage/logs/laravel.log`).

### Test 3.1: Truy cập trang quên mật khẩu

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Mở `/forgot-password` | Form: field email + nút "Gửi link đặt lại mật khẩu" |
| 2 | Chưa login | Truy cập được (không cần auth) |
| 3 | Đã login student → `/forgot-password` | Redirect → `/` (không cần reset khi đã login) |

### Test 3.2: Submit email trống

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Submit không nhập email | Lỗi inline: "Vui lòng nhập email" |
| 2 | Network | Không có request |

### Test 3.3: Submit email sai format

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Nhập `abc` → Submit | Lỗi: "Email không đúng định dạng" |
| 2 | Network | Không có request |

### Test 3.4: Submit email không tồn tại

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Nhập `notexist@test.com` → Submit | Hiện thông báo thành công chung chung (bảo mật, không tiết lộ email có tồn tại không) |
| 2 | Network | `POST /api/v1/auth/forgot-password` → **200** (hoặc **404** tùy design) |

### Test 3.5: Gửi link reset thành công ✅

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Nhập `student@elearning.com` → Submit | Loading spinner → Alert xanh: "Email đặt lại mật khẩu đã được gửi. Vui lòng kiểm tra hộp thư." |
| 2 | Network | `POST /api/v1/auth/forgot-password` → **200** |
| 3 | Log (MAIL_MAILER=log) | `storage/logs/laravel.log` có entry email với link reset |
| 4 | Link trong email | Dạng `/reset-password?token=xxx&email=student@elearning.com` |

### Test 3.6: Gửi lại (Rate limit)

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Submit cùng email lần 2 trong vòng 60 giây | Lỗi: "Vui lòng chờ trước khi gửi lại." (429 hoặc xử lý FE) |

### Test 3.7: Truy cập form đặt lại mật khẩu — Token hợp lệ

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Mở link từ email | Form: email (readonly), password mới, xác nhận password |
| 2 | URL | Có `token` và `email` params |

### Test 3.8: Truy cập form đặt lại — Token không hợp lệ / hết hạn

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Mở `/reset-password?token=invalid&email=student@elearning.com` | Thông báo lỗi: "Link đặt lại mật khẩu không hợp lệ hoặc đã hết hạn." |
| 2 | Form | Không hiện form nhập mật khẩu |

### Test 3.9: Đặt lại mật khẩu — Validation

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Password: `1234567` (< 8 ký tự) | Lỗi: "Mật khẩu tối thiểu 8 ký tự" |
| 2 | Password khác Confirm | Lỗi: "Mật khẩu xác nhận không khớp" |
| 3 | Network | Không có request |

### Test 3.10: Đặt lại mật khẩu thành công ✅

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Nhập mật khẩu mới hợp lệ → Submit | Toast "Mật khẩu đã được đặt lại thành công!" |
| 2 | Network | `POST /api/v1/auth/reset-password` → **200** |
| 3 | Redirect | → `/login` |
| 4 | Đăng nhập | Dùng mật khẩu mới → thành công |
| 5 | Token cũ | Dùng lại link reset → lỗi "Token không hợp lệ" |

---

## MODULE 4 — Xác nhận tài khoản (Email Verification)

> **Lưu ý:** Cần bật email verification trong backend. Với `MAIL_MAILER=log`, link verification xuất hiện trong `storage/logs/laravel.log`.

### Test 4.1: Đăng ký mới — Trạng thái chưa xác nhận

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Đăng ký tài khoản mới thành công | Banner/thông báo: "Vui lòng kiểm tra email để xác nhận tài khoản" |
| 2 | DB | Cột `email_verified_at` = NULL |

### Test 4.2: Truy cập tính năng yêu cầu xác nhận — Chưa verify

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Chưa verify → truy cập `/my-courses` | Redirect hoặc hiện banner: "Tài khoản chưa được xác nhận" |
| 2 | Chưa verify → enroll khóa học | Lỗi hoặc prompt xác nhận email |
| 3 | Browse public pages (`/courses`) | Vẫn truy cập được |

### Test 4.3: Gửi lại email xác nhận

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Click "Gửi lại email xác nhận" | Loading → Alert: "Email xác nhận đã được gửi lại" |
| 2 | Network | `POST /api/v1/auth/email/resend-verification` → **200** |
| 3 | Log | Xuất hiện email mới trong laravel.log |

### Test 4.4: Gửi lại nhiều lần (Rate limit)

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Click "Gửi lại" nhiều lần liên tiếp | Sau N lần → lỗi 429 hoặc nút disable tạm thời |

### Test 4.5: Click link xác nhận — Hợp lệ ✅

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Mở link verification từ email | Trang xác nhận thành công: "Email đã được xác nhận!" |
| 2 | Network | `GET /api/v1/auth/email/verify/{id}/{hash}` → **200** |
| 3 | DB | `email_verified_at` có giá trị timestamp |
| 4 | Redirect | → `/` hoặc `/my-courses` |
| 5 | Tính năng | Có thể enroll khóa học, truy cập đầy đủ |

### Test 4.6: Link xác nhận hết hạn / đã dùng

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Click link lần 2 (đã verify trước đó) | Thông báo "Link đã được sử dụng" hoặc redirect thẳng vào app |
| 2 | Link giả (hash sai) | Lỗi: "Link xác nhận không hợp lệ" |

### Test 4.7: Tài khoản đã verify

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Tài khoản đã verify → không hiện banner xác nhận | Trải nghiệm bình thường |
| 2 | DB | `email_verified_at` ≠ NULL |

---

## MODULE 5 — Ghi nhớ đăng nhập (Remember Me)

### Test 5.1: Checkbox "Ghi nhớ đăng nhập" — Hiển thị

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Mở `/login` (student) | Có checkbox "Ghi nhớ đăng nhập" bên dưới form |
| 2 | Mặc định | Checkbox chưa tick |

### Test 5.2: Đăng nhập không tick Remember Me

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Login thành công, không tick Remember Me | `studentToken` lưu vào `sessionStorage` hoặc token có thời hạn ngắn |
| 2 | Đóng tab → mở lại | Phải đăng nhập lại (session không persist) |

### Test 5.3: Đăng nhập có tick Remember Me ✅

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Tick "Ghi nhớ đăng nhập" → Login | `studentToken` lưu vào `localStorage` |
| 2 | Network | Request có `remember: true` trong body |
| 3 | Đóng browser hoàn toàn → mở lại | Vẫn đăng nhập, không bị redirect về `/login` |
| 4 | Application → LocalStorage | `studentToken` vẫn còn |

### Test 5.4: Token Remember Me — Thời hạn dài hơn

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | So sánh token không remember (1 ngày) vs có remember (30 ngày) | Token có remember tồn tại lâu hơn trong DB hoặc JWT có `exp` khác |
| 2 | Kiểm tra backend | Config `SANCTUM_EXPIRATION` hoặc token `abilities` |

### Test 5.5: Logout khi có Remember Me

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Đang login với Remember Me → Logout | `localStorage` xóa `studentToken` |
| 2 | Mở lại browser | Phải đăng nhập lại (token đã revoke trên server) |

### Test 5.6: Admin — Remember Me (nếu có)

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Trang `/admin/login` | Kiểm tra có checkbox Remember Me không |
| 2 | Nếu có: tick → login → đóng browser → mở lại | Vẫn vào được `/admin/dashboard` |

---

## Checklist

| Test | Kết quả | Ghi chú |
|------|---------|---------|
| 1.1 Form trống | ✅ | |
| 1.2 Email sai format | ✅ | |
| 1.3 Sai mật khẩu | ✅ | |
| 1.4 Email không tồn tại | ✅ | |
| 1.5 Password quá ngắn | ✅ | |
| 1.6 Đăng nhập thành công | ✅ | |
| 1.7 Session persist | ✅ | |
| 1.8 Route guard | ✅ | |
| 1.9 Đã login → login page | ✅ | |
| 1.10 Logout | ✅ | |
| 1.11 Token hết hạn | ✅ | |
| 2.1-2.8 Register | ✅ | Đã fix lỗi validation persist và bổ sung báo lỗi 422 |
| 2.9-2.15 Student Login | ✅ | Đã sửa fallback đăng nhập kép (admin fallback) giải quyết 429 quá tải |
| 3.1 Trang forgot password | ✅ | Đã tạo ForgotPasswordPage.vue |
| 3.2 Email trống | ✅ | |
| 3.3 Email sai format | ✅ | |
| 3.4 Email không tồn tại | ✅ | |
| 3.5 Gửi link thành công | ✅ | Đã tích hợp auth.service |
| 3.6 Rate limit gửi lại | ✅ | Cooldown bằng Throttle middleware backend HTTP |
| 3.7 Form reset — token hợp lệ | ✅ | Đã tạo ResetPasswordPage.vue |
| 3.8 Token không hợp lệ/hết hạn | ✅ | Backend chặn |
| 3.9 Validation mật khẩu mới | ✅ | Trùng khớp password confirmation hoạt động tốt |
| 3.10 Đặt lại mật khẩu thành công | ✅ | Backend xử lý hoàn tất |
| 4.1 Trạng thái chưa verify | ✅ | |
| 4.2 Chặn tính năng chưa verify | ✅ | Route guard router.beforeEach chặn |
| 4.3 Gửi lại email xác nhận | ✅ | Xác nhận logic qua VerifyEmailPage.vue |
| 4.4 Rate limit gửi lại | ✅ | Đã sử dụng throttle |
| 4.5 Xác nhận email thành công | ✅ | Hoạt động qua GET API endpoint backend |
| 4.6 Link hết hạn / đã dùng | ✅ | Backend validation trả đúng response codes |
| 4.7 Tài khoản đã verify | ✅ | Route global fetchMe() fetch latest user info success |
| 5.1 Checkbox hiển thị | ✅ | Đã thêm vào Student, Admin |
| 5.2 Login không remember | ✅ | Dùng sessionStorage |
| 5.3 Login có remember | ✅ | Dùng localStorage |
| 5.4 Thời hạn token | ✅ | Backend JWT xử lý |
| 5.5 Logout với remember | ✅ | Đã xóa đồng thời sessionStorage và localStorage |
| 5.6 Admin remember me | ✅ | Đã thêm checkbox tại AdminLoginPage.vue |
