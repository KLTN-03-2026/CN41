# Tài liệu Test — E-Learning Platform

Mỗi file test bao gồm:
- **QLCV** — Danh sách việc cần làm ở FE và BE (⬜ chưa xong)
- **Test cases** — Các trường hợp cần kiểm thử thủ công
- **Checklist** — Tick kết quả khi test

---

## Danh sách module

### Auth & Tài khoản
- [test-auth.md](test-auth.md) — Đăng nhập, đăng ký, quên mật khẩu, xác nhận email, remember me

### Admin
- [test-categories.md](test-categories.md) — Quản lý danh mục (CRUD, nested, toggle)
- [test-courses-admin.md](test-courses-admin.md) — Quản lý khóa học (CRUD, bulk, trash)
- [test-sections-lessons.md](test-sections-lessons.md) — Quản lý chương & bài học
- [test-teachers-admin.md](test-teachers-admin.md) — Quản lý giảng viên
- [test-students-admin.md](test-students-admin.md) — Quản lý học viên
- [test-upload.md](test-upload.md) — Upload video, tài liệu, ảnh; stream video

### Client (Học viên)
- [test-courses-client.md](test-courses-client.md) — Xem danh sách, chi tiết, enroll khóa học
- [test-my-courses.md](test-my-courses.md) — Khóa học đã đăng ký, tiến độ
- [test-learn-page.md](test-learn-page.md) — Trang học: video player, tài liệu, progress
- [test-payment.md](test-payment.md) — Thanh toán VNPAY: tạo đơn, callback, webhook, lịch sử

---

## 📊 Báo cáo kiểm thử mới nhất (Hệ thống)

| Module | Tài liệu báo cáo | Trạng thái |
|---|---|---|
| **Auth** | [test-auth-report.md](../report-testing/test-auth-report.md) | ✅ 100% |
| **Categories** | [test-categories-report.md](../report-testing/test-categories-report.md) | ✅ Backend Verified |
| **Courses (Admin)** | [test-courses-admin-report.md](../report-testing/test-courses-admin-report.md) | ✅ Backend Verified |
| **Sections & Lessons** | [test-sections-lessons-report.md](../report-testing/test-sections-lessons-report.md) | ✅ 100% |

---

## Môi trường test

| | Giá trị |
|---|---|
| Frontend | `http://localhost:5173` |
| Backend API | `http://localhost:8000/api/v1` |
| Admin login | `admin@elearning.com` / `password` |
| Student test | Tạo tại `/register` |
| Mail (local) | `MAIL_MAILER=log` → xem `storage/logs/laravel.log` |

---

## Quy ước kết quả

| Ký hiệu | Ý nghĩa |
|---|---|
| ⬜ | Chưa test |
| ✅ | Pass |
| ❌ | Fail — ghi ghi chú lỗi |
| ⚠️ | Partial — một phần hoạt động |
| 🚧 | Chức năng chưa làm xong (xem QLCV) |
