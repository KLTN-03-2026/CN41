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
| 2.6 | Đăng ký thành công → lưu `studentToken` → redirect `/` | ✅ PASS |
| 2.7 | Email trùng → 422 + hiện lỗi server | ✅ PASS |
| 2.8 | Email có khoảng trắng → FE/server xử lý | ✅ PASS |
| 2.9 | Sai mật khẩu → Alert + 401 | ✅ PASS |
| 2.10 | Đăng nhập thành công → 200 + `studentToken` | ✅ PASS |

---

## ✅ Phần 3: Kết quả Module 3, 4, 5 (Đã Hoàn Thành 10/04)

### Module 3 — Quên mật khẩu
- Trang `ForgotPasswordPage.vue` và `ResetPasswordPage.vue` đã được tạo.
- Tích hợp logic gửi mail đặt lại mật khẩu và cập nhật mật khẩu mới qua token.
- Backend đã bổ sung throttle middleware để bảo mật route reset mật khẩu.

### Module 4 — Xác nhận Email
- Trang `VerifyEmailPage.vue` đã được tạo để hiển thị thông báo chưa xác thực.
- Bổ sung nút "Gửi lại email xác nhận" và logic logic resend verification.
- Route Guard toàn cục đã được thiết lập để chặn truy cập student vào các trang yêu cầu auth nếu email chưa được verify.

### Module 5 — Ghi nhớ đăng nhập (Remember Me)
- Đã thêm checkbox "Ghi nhớ đăng nhập" vào `LoginPage.vue` (Student) và `AdminLoginPage.vue` (Admin).
- `studentAuth.store.ts` và `adminAuth.store.ts` đã hỗ trợ phân biệt lưu token vào `localStorage` (nếu chọn Remember Me) hoặc `sessionStorage` (nếu không chọn).
- `axios.js` plugin đã cập nhật để tự động lấy token từ cả 2 vùng lưu trữ.

---

## 🐛 Phần 4: Bug Đã Phát Hiện & Đã Fix (Quá trình thực hiện)

### Bug 2.1 — Sai message validation
- **Fix:** Chaining `min(1)` (Required) trước `min(N)` (Length) để hiện đúng thứ tự lỗi.

| 2.13 | Thông tin UI — đã verify hiện badge hoặc thông báo | ✅ PASS |
| 2.14 | Logout → xóa `studentToken` → redirect `/login` | ✅ PASS |

---

## 🐛 Phần 4: Bug Đã Phát Hiện & Đã Fix (Quá trình thực hiện)

### Bug 2.1 — Sai message validation
- **Fix:** Chaining `min(1)` (Required) trước `min(N)` (Length) để hiện đúng thứ tự lỗi.

### Bug 2.10 — 429 Too Many Requests
- **Fix:** Loại bỏ logic fallback admin trong trang login của student. Student login chỉ nhắm đúng endpoint của mình.

### Lỗi Zod Schema tại AdminLoginPage
- **Fix:** Xử lý lỗi TS `error` không hợp lệ trong object khởi tạo `z.string()` (Zod yêu cầu `required_error`). Đã chuyển về validation fluent `min(1, '...')`.

### Bug: 500 Internal Server Error tại Logout (Cần Null Check)
- **Vấn đề:** Khi logout trong môi trường Testing (`actingAs`), `currentAccessToken()` trả về `null` gây crash.
- **Fix:** Đã bổ sung kiểm tra null cho `currentAccessToken()` trong cả `Admin/AuthController` và `Student/AuthController`. Đảm bảo logout an toàn ngay cả khi token không tồn tại (linh hoạt cho cả Web và Automated Test).

---

## 🏁 Tổng kết
Hệ thống Authentication đã đầy đủ tính năng theo yêu cầu, đảm bảo cả UX (Remember Me, Validation) và Security (Throttle, Route Guard). Các bài test tự động (Feature Tests) đã được bổ sung cho cả Admin và Student để đảm bảo tính ổn định lâu dài.
