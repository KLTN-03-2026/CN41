# Bảng Theo Dõi Tiến Độ — E-Learning Marketplace

> Cập nhật lần cuối: 26/04/2026 | Deadline: 15/05/2026

---

## Tiến Độ Tổng Quan

```
Tổng thể: ████████████████████░░░░░░░  72%
```

---

## Tiến Độ Theo Module

### 1. Xác Thực (Auth)
| Tầng | Trạng thái | Ghi chú |
|------|-----------|---------|
| BE | ✅ Hoàn thành | Admin + Sinh viên: đăng nhập, đăng ký, xác minh email, reset mật khẩu, resend verification |
| FE | ✅ Hoàn thành | LoginPage, RegisterPage, AdminLoginPage, VerifyEmailPage, ForgotPasswordPage, ResetPasswordPage — có guard theo role |

**Hoàn thành: 100%**

Còn lại:
- ~~Chưa có auth cho Giảng viên~~ (không nằm trong scope đề tài)

---

### 2. Quản Lý Khóa Học
| Tầng | Trạng thái | Ghi chú |
|------|-----------|---------|
| BE | ✅ Hoàn thành | CRUD đầy đủ, soft delete, toggle status, phân trang, bulk actions; cascade delete/restore xuống sections + lessons |
| FE | ✅ Hoàn thành | CoursesPage (admin), CourseFormPage, CourseDetailPage (client); PaginationBar, xóa filter, validate danh mục |

**Hoàn thành: 93%**

Còn lại:
- Trang chi tiết khóa học phía client cần hoàn thiện UI
- Bộ lọc tìm kiếm phía client chưa đầy đủ
- Test 4.12–4.15 (thumbnail upload UI), 4.18–4.20 (slug unlock, tab nội dung), 4.25–4.26 (bulk select UI)

---

### 3. Quản Lý Chương & Bài Học
| Tầng | Trạng thái | Ghi chú |
|------|-----------|---------|
| BE | ✅ Hoàn thành | CRUD section + lesson, reorder, bulk actions, lesson progress tracking |
| FE | ✅ Hoàn thành | SectionsLessonsManager, LessonsManager, LearnPage (student) |

**Hoàn thành: 85%**

Còn lại:
- Drag-and-drop reorder chưa có UI
- Player video trên LearnPage cần kiểm tra lại

---

### 4. Quản Lý Danh Mục
| Tầng | Trạng thái | Ghi chú |
|------|-----------|---------|
| BE | ✅ Hoàn thành | NestedSet, CRUD, bulk delete, soft delete |
| FE | ✅ Hoàn thành | CategoriesPage admin |

**Hoàn thành: 90%**

Còn lại:
- Hiển thị danh mục dạng cây trên FE client chưa có

---

### 5. Đăng Ký Khóa Học
| Tầng | Trạng thái | Ghi chú |
|------|-----------|---------|
| BE | ✅ Hoàn thành | Đã tích hợp enroll qua free và qua thanh toán VNPAY (trong IPN) |
| FE | ✅ Hoàn thành | CartPage, CheckoutPage, MyCoursesPage hoàn thiện luồng enroll |

**Hoàn thành: 100%**

Còn lại:
- Đã hoàn thành toàn bộ luồng đăng ký (miễn phí + trả phí)

---

### 6. Thanh Toán (VNPAY)
| Tầng | Trạng thái | Ghi chú |
|------|-----------|---------|
| BE | ✅ Hoàn thành | Module Payment: Order, OrderItem, Transaction models; VNPAY gateway (VnpayService); IPN webhook; admin orders CRUD + stats; student my-orders; retry payment |
| FE | ✅ Hoàn thành | CheckoutPage, PaymentResultPage, MyOrdersPage, OrderDetailModal đã nối đủ API, test e2e thành công |

**Hoàn thành: 100%**

Còn lại:
- Tích hợp MoMo gateway (tùy chọn, low priority)

---

### 7. AI Auto-Quiz
| Tầng | Trạng thái | Ghi chú |
|------|-----------|---------|
| BE | ⬜ Chưa làm | Không có module Quiz, chưa tích hợp AI |
| FE | ⬜ Chưa làm | Không có trang quiz |

**Hoàn thành: 0%**

Còn lại:
- Thiết kế DB: bảng quiz_questions, quiz_answers
- Tích hợp Gemini/OpenAI API để sinh câu hỏi tự động
- API lấy/nộp bài quiz
- Trang làm bài quiz cho sinh viên

---

### 8. Dashboard & Thống Kê
| Tầng | Trạng thái | Ghi chú |
|------|-----------|---------|
| BE | ✅ Hoàn thành | Đã tạo module Dashboard, API thống kê trả về đầy đủ tổng quan, biểu đồ và top list |
| FE | ✅ Hoàn thành | DashboardPage đã gọi API dashboardService thực để lấy dữ liệu thay vì fake data |

**Hoàn thành: 100%**

Còn lại:
- Không còn (Đã hoàn tất cơ bản cho mục đích demo)

---

### 9. Upload (Video / Tài Liệu)
| Tầng | Trạng thái | Ghi chú |
|------|-----------|---------|
| BE | ✅ Hoàn thành | Upload local + S3 presigned URL, MediaFile model |
| FE | ✅ Hoàn thành | Tích hợp trong CourseFormPage và LessonsManager |

**Hoàn thành: 85%**

Còn lại:
- Progress bar upload video chưa mượt
- Xử lý lỗi upload timeout cần cải thiện

---

### 10. Mã Giảm Giá (Coupon)
| Tầng | Trạng thái | Ghi chú |
|------|-----------|---------|
| BE | ✅ Hoàn thành | Bảng coupons, model, repository, module Coupons với đầy đủ CRUD API, bulk actions, toggle status. Tích hợp validation và tính discount trực tiếp vào API tạo đơn hàng (OrderController). Feature test đạt 11/11 cases. |
| FE | ✅ Hoàn thành | Quản lý coupon cho Admin (CouponsPage) với CRUD, toggle, soft delete. Sinh viên có thể nhập, áp dụng và xóa coupon trong màn Checkout (CheckoutPage), tự động tính lại tổng tiền. |

**Hoàn thành: 100%**

Còn lại:
- Không còn

---

### 11. Thông Báo
| Tầng | Trạng thái | Ghi chú |
|------|-----------|---------|
| BE | 🔄 Đang làm | Chỉ có email (xác minh tài khoản, reset mật khẩu), chưa có real-time |
| FE | 🔄 Đang làm | NotificationMenu component có UI nhưng không có dữ liệu thực |

**Hoàn thành: 20%**

Còn lại:
- Bảng notifications trong DB
- API lấy danh sách thông báo
- Real-time (Laravel Echo + Pusher/Soketi)
- Nối NotificationMenu với API

---

### 12. Quản Lý Học Viên & Giảng Viên (Admin)
| Tầng | Trạng thái | Ghi chú |
|------|-----------|---------|
| BE | ✅ Hoàn thành | CRUD, soft delete, bulk actions, toggle status (teachers), phân trang, filter, student detail (enrolled courses + orders), OrderSeeder |
| FE | ✅ Hoàn thành | StudentsPage + TeachersPage: bảng danh sách, tìm kiếm, modal thêm/sửa, xoá mềm, thùng rác, khôi phục, bulk actions, toggle status, phân trang, **modal xem chi tiết học viên** (khóa học đã mua, đơn hàng, tổng chi tiêu) |

**Hoàn thành: 100%**

Còn lại:
- Không còn

---

## Tiến Độ Theo Tầng

| Tầng | Tiến độ | Thanh tiến độ |
|------|---------|--------------|
| **Backend** | **92%** | `█████████████████████████░░` |
| **Frontend** | **88%** | `████████████████████████░░░` |

### Chi tiết Backend:
- ✅ Hoàn thành: Auth, Course, Categories, Lessons/Sections, Students, Teachers, Users, Upload, Payment/VNPAY, Enrollment, Dashboard, **Coupons**, 113 Feature Tests passed (13/14 module)
- ⬜ Chưa làm: AI Quiz, Notifications (2/14 thiếu phần lớn)

### Chi tiết Frontend:
- ✅ Hoàn thành: Auth pages, Course pages, Category pages, Lessons manager, Student/Teacher/Coupon pages (CRUD đầy đủ), Upload UI, Enrollment flow, Payment FE, Dashboard charts thực
- 🔄 Đang làm: Notifications UI
- ⬜ Chưa làm: Quiz UI

---

## Ưu Tiên Tiếp Theo

| Ưu tiên | Module | Lý do |
|---------|--------|-------|
| ✅ Xong | Thanh Toán (VNPAY) BE & FE | Đã hoàn thành 26/04/2026 |
| ✅ Xong | Đăng Ký (Enrollment) | Tích hợp thành công cùng Payment flow |
| ✅ Xong | Dashboard & Thống Kê | Đã tích hợp API thực |
| ✅ Xong | Quản lý Học viên & Giảng viên FE | Đã hoàn thành 26/04/2026 |
| ✅ Xong | Mã Giảm Giá (Coupon) | Đã hoàn thành 26/04/2026 |
| 🔴 Cao | AI Auto-Quiz | USP của đề tài, cần làm sớm |
| 🟢 Thấp | Thông báo real-time | Nice-to-have |

---

## Cách Cập Nhật

Khi hoàn thành một task, nói với Claude:

```
"Đánh dấu [module] [BE/FE] hoàn thành"
```

hoặc

```
"Cập nhật QLCV: [mô tả việc vừa xong]"
```

Claude sẽ tự cập nhật trạng thái, % hoàn thành và tính lại tổng thể.

**Ví dụ:**
- `"Đánh dấu Thanh Toán BE hoàn thành"`
- `"Cập nhật QLCV: đã xong trang Dashboard với biểu đồ thực"`
- `"Cập nhật QLCV: AI Quiz đã có API sinh câu hỏi"`