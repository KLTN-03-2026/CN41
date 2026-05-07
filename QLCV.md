# Bảng Theo Dõi Tiến Độ — E-Learning Marketplace

> Cập nhật lần cuối: 07/05/2026 | Deadline: 15/05/2026

---

## Tiến Độ Tổng Quan

```
Tổng thể: ████████████████████████████░  96%
```

---

## Tiến Độ Theo Module

### 1. Xác Thực (Auth)
| BE | ✅ Hoàn thành | Admin + Sinh viên: đăng nhập, đăng ký, xác minh email, reset mật khẩu, resend verification. **Hệ thống phân quyền (RBAC) đã bọc thép với Middleware permission cho từng API.** |
| FE | ✅ Hoàn thành | LoginPage, RegisterPage, AdminLoginPage, VerifyEmailPage, ForgotPasswordPage, ResetPasswordPage — có guard theo role. **Hệ thống Axios interceptor xử lý lỗi 403 chuyên nghiệp.** **07/05: Cải thiện toàn bộ UI Auth pages (login, register, forgot/reset password, verify email) + logo mới.** |

**Hoàn thành: 100%**

Còn lại:
- ~~Chưa có auth cho Giảng viên~~ (không nằm trong scope đề tài)

---

### 2. Quản Lý Khóa Học
| BE | ✅ Hoàn thành | CRUD đầy đủ, soft delete, toggle status, phân trang, bulk actions; cascade delete/restore xuống sections + lessons. **Middleware permission:courses.view/create/edit/delete.** |
| FE | ✅ Hoàn thành | CoursesPage (admin), CourseFormPage, CourseDetailPage (client); PaginationBar, xóa filter, validate danh mục. |

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
| BE | ✅ Hoàn thành | NestedSet, CRUD, bulk delete, soft delete. **Middleware permission:categories.view/create/edit/delete.** |
| FE | ✅ Hoàn thành | CategoriesPage admin. |

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
| BE | ✅ Hoàn thành | Tích hợp Google Gemini AI (Flash 2.0/Lite), cơ chế Fallback tự động, trích xuất PDF Tiếng Việt chuẩn. API sinh tối đa 20 câu hỏi chất lượng cao. **07/05: `QuizAttemptResource` bổ sung field `questions` (nội dung câu hỏi + options) vào response submit & attempts.** |
| FE | ✅ Hoàn thành | Admin UI sinh câu hỏi AI từ file upload hoặc tài liệu bài học, cho phép tùy chỉnh prompt và số lượng. **07/05: QuizPage học viên hoàn chỉnh — làm bài, nộp bài, hiển thị kết quả inline (highlight đúng/sai từng câu, không redirect). QuizHistoryPage — xem lịch sử + expand chi tiết đúng/sai từng lần làm.** |

**Hoàn thành: 100%**

Còn lại:
- Không còn

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
| BE | ✅ Hoàn thành | Bảng coupons, model, repository, module Coupons với đầy đủ CRUD API, bulk actions, toggle status. Tích hợp validation và tính discount trực tiếp vào API tạo đơn hàng (OrderController). **Middleware permission:coupons.view/create/edit/delete.** Feature test đạt 11/11 cases. |
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
| BE | ✅ Hoàn thành | CRUD, soft delete, bulk actions, toggle status (teachers), phân trang, filter, student detail (enrolled courses + orders), OrderSeeder. **Bảo mật: Role-scoping (Admin chỉ quản lý Student/Teacher), chặn can thiệp Super Admin, Middleware permission cho từng đối tượng.** |
| FE | ✅ Hoàn thành | StudentsPage + TeachersPage: bảng danh sách, tìm kiếm, modal thêm/sửa, xoá mềm, thùng rác, khôi phục, bulk actions, toggle status, phân trang, **modal xem chi tiết học viên**. **Sidebar: Hệ thống menu Người dùng đã được quy hoạch lại theo nhóm.** |

**Hoàn thành: 100%**

Còn lại:
- Không còn

---

### 13. Quản Lý Bài Viết (Posts)
| BE | ✅ Hoàn thành | Đã tạo module Posts; Migration & Models cho Categories, Tags, Posts, Comments (Polymorphic); Repositories; Admin & Client API đầy đủ. **Middleware permission cho tất cả API quản trị.** |
| FE | ✅ Hoàn thành | Đã hoàn thành cả Admin UI (Quản lý) và Client UI (BlogPage, PostDetailPage). |

**Hoàn thành: 100%**

---

### 14. Giao Diện (UI/UX)
| Tầng | Trạng thái | Ghi chú |
|------|-----------|---------|
| Logo & Brand | ✅ Hoàn thành | Cập nhật logo mới (auth-logo, logo-dark, logo-icon, logo.svg) 07/05 |
| Auth Pages | ✅ Hoàn thành | Redesign toàn bộ: LoginPage, RegisterPage, AdminLoginPage, ForgotPasswordPage, ResetPasswordPage, VerifyEmailPage, VerifyEmailResultPage 07/05 |
| Sidebar & Header | ✅ Hoàn thành | AppSidebar refactor, HeaderLogo cập nhật, ClientLayout cải thiện nav 07/05 |
| HomePage Client | ✅ Hoàn thành | 8 section hoàn chỉnh: Hero, Stats (count-up animation), FeaturedCategories, FeaturedCourses, WhyUs, FeaturedTeachers (API), LatestPosts, CTA 07/05 |

**Hoàn thành: 100%**

Còn lại:
- Không còn

---

## Tiến Độ Theo Tầng

| Tầng | Tiến độ | Thanh tiến độ |
|------|---------|--------------|
| **Backend** | **99%** | `█████████████████████████████` |
| **Frontend** | **99%** | `█████████████████████████████` |

### Chi tiết Backend:
- ✅ Hoàn thành: Auth, Course, Categories, Lessons/Sections, Students, Teachers, Users (Role-based API Security), Upload, Payment/VNPAY, Enrollment, Dashboard, Coupons, Posts, AI Quiz (Core Logic), **126/126 Feature Tests passed (100% stable)**
- ⬜ Chưa làm: Notifications (phần real-time)

### Chi tiết Frontend:
- ✅ Hoàn thành: Auth pages (redesign 07/05), Course pages, Category pages, Lessons manager, Student/Teacher/Coupon pages (CRUD đầy đủ), Upload UI, Enrollment flow, Payment FE, Dashboard charts thực, User Management Scoping UI, Sidebar Refactoring, AI Quiz Generator UI, **HomePage Client 8 sections (07/05)**, Logo & ClientLayout nav (07/05).
- ✅ Hoàn thành thêm: **Quiz UI Student (07/05)** — QuizPage làm bài + result inline, QuizHistoryPage expand chi tiết đúng/sai.
- 🔄 Đang làm: Notifications UI

---

## Ưu Tiên Tiếp Theo

| Ưu tiên | Module | Lý do |
|---------|--------|-------|
| ✅ Xong | Thanh Toán (VNPAY) BE & FE | Đã hoàn thành 26/04/2026 |
| ✅ Xong | Đăng Ký (Enrollment) | Tích hợp thành công cùng Payment flow |
| ✅ Xong | Dashboard & Thống Kê | Đã tích hợp API thực |
| ✅ Xong | Quản lý Học viên & Giảng viên FE | Đã hoàn thành 26/04/2026 |
| ✅ Xong | Mã Giảm Giá (Coupon) | Đã hoàn thành 26/04/2026 |
| ✅ Xong | HomePage Client (8 sections) | Đã hoàn thành 07/05/2026 |
| ✅ Xong | Redesign Auth Pages + Logo + ClientLayout | Đã hoàn thành 07/05/2026 |
| ✅ Xong | Quiz UI phía học viên | Đã hoàn thành 07/05/2026 — làm bài, result inline, lịch sử chi tiết |
| ✅ Xong | CourseDetailPage client | Đã hoàn chỉnh |
| 🟡 Trung | Progress tracking bài học | Cần kiểm tra LearnPage + video player |
| 🟢 Thấp | Thông báo real-time | Nice-to-have, low priority |

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