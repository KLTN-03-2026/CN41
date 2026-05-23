# Phân tích Use Case — E-Learning Marketplace

> **KLTN — Phan Văn Thành (28211102974)**
> Dùng để vẽ sơ đồ trong draw.io / StarUML / Lucidchart

---

## I. ACTORS VÀ QUAN HỆ

### Actors (4 chính)

| Actor | Mô tả | Xác thực |
|-------|--------|---------|
| **Guest** | Người dùng chưa xác thực — chưa đăng nhập, chưa có tài khoản (chỉ xem thông tin public) | Không |
| **Student** | Học viên đã đăng ký, đã đăng nhập và xác minh email | `auth:api` + `email.verified` |
| **Teacher** | Giảng viên — role trong Admin panel, chỉ thấy nội dung của mình | `auth:admin` + role `teacher` |
| **Admin** | Quản trị viên đầy đủ quyền | `auth:admin` + role `admin`/`super-admin` |

### Quan hệ kế thừa (Generalization)

```
Admin  ◁——— Teacher      (Teacher là Admin với quyền bị giới hạn)
```

> **Lưu ý vẽ:** Dùng mũi tên rỗng (generalization) từ Teacher → Admin.
>
> **Không kế thừa Guest → Student:** Guest là khách vãng lai chưa có tài khoản; Student là học viên đã đăng ký và đăng nhập — hai actor hoàn toàn độc lập. Student KHÔNG thực hiện UC02 (đăng nhập) vì họ đã xác thực rồi.

---

## II. USE CASE TỔNG QUÁT

### Hệ thống con (Subsystems / Boundary)

```
┌─────────────────────────────────────────────────────────┐
│                    E-Learning System                     │
│                                                          │
│  ┌─────────────┐  ┌──────────────┐  ┌────────────────┐  │
│  │   Public    │  │   Student    │  │  Admin Panel   │  │
│  │   Portal    │  │   Portal     │  │                │  │
│  └─────────────┘  └──────────────┘  └────────────────┘  │
└─────────────────────────────────────────────────────────┘
```

### Danh sách Use Case Tổng quát

| ID | Use Case | Guest | Student | Teacher | Admin |
|----|----------|:-----:|:-------:|:-------:|:-----:|
| UC01 | Đăng ký tài khoản | ✓ | | | |
| UC02 | Đăng nhập Student | ✓ | | | |
| UC02b | Đăng nhập Admin Panel | | | ✓ | ✓ |
| UC03 | Đăng xuất | | ✓ | ✓ | ✓ |
| UC04 | Xác minh email | | ✓ | | |
| UC05 | Đặt lại mật khẩu | | ✓ | | |
| UC06 | Xem danh sách khóa học | ✓ | ✓ | | |
| UC07 | Xem chi tiết khóa học | ✓ | ✓ | | |
| UC08 | Xem bài học preview | ✓ | ✓ | | |
| UC09 | Xem danh sách bài viết | ✓ | ✓ | | |
| UC10 | Xem chi tiết bài viết | ✓ | ✓ | | |
| UC11 | Bình luận bài viết | | ✓ | | |
| UC12 | Mua khóa học (VNPay) | | ✓ | | |
| UC13 | Đăng ký khóa học miễn phí | | ✓ | | |
| UC14 | Áp dụng mã giảm giá | | ✓ | | |
| UC15 | Xem lịch sử đơn hàng | | ✓ | | |
| UC16 | Thanh toán lại đơn hàng | | ✓ | | |
| UC17 | Học bài (video/tài liệu/text) | | ✓ | | |
| UC18 | Theo dõi tiến độ học | | ✓ | | |
| UC19 | Làm bài kiểm tra (Quiz) | | ✓ | | |
| UC20 | Xem kết quả Quiz | | ✓ | | |
| UC21 | Cập nhật hồ sơ cá nhân | | ✓ | | |
| UC22 | Quản lý khóa học | | | ✓ | ✓ |
| UC23 | Quản lý chương học (Section) | | | ✓ | ✓ |
| UC24 | Quản lý bài học (Lesson) | | | ✓ | ✓ |
| UC25 | Quản lý Quiz (Admin) | | | ✓ | ✓ |
| UC26 | Tạo Quiz bằng AI | | | ✓ | ✓ |
| UC27 | Upload file/video | | | ✓ | ✓ |
| UC28 | Quản lý danh mục | | | | ✓ |
| UC29 | Quản lý giảng viên | | | | ✓ |
| UC30 | Quản lý học viên | | | | ✓ |
| UC31 | Quản lý đơn hàng | | | | ✓ |
| UC32 | Quản lý mã giảm giá | | | | ✓ |
| UC33 | Quản lý bài viết (Blog) | | | | ✓ |
| UC34 | Quản lý bình luận | | | | ✓ |
| UC35 | Quản lý người dùng Admin | | | | ✓ |
| UC36 | Phân quyền Roles/Permissions | | | | ✓ |
| UC37 | Xem Dashboard thống kê | | | ✓ | ✓ |
| UC38 | Xem nhật ký hệ thống | | | | ✓ |

---

## III. USE CASE CHI TIẾT THEO CHỨC NĂNG

### UC-AUTH: Xác thực & Tài khoản

**Actors:** Guest, Student, Admin, Teacher

```
Guest   ──→ UC-A1:  Đăng ký tài khoản
Guest   ──→ UC-A3:  Đăng nhập Student
Admin/Teacher ──→ UC-A9:  Đăng nhập Admin Panel
Admin/Teacher ──→ UC-A10: Đăng xuất Admin Panel

Student ──→ UC-A2:  Xác minh email               (chỉ Student đã đăng ký mới có token)
                      <<extend>> UC-A8: Gửi lại email xác minh (nếu token hết hạn)
Student ──→ UC-A4:  Quên mật khẩu               (chỉ Student có tài khoản mới reset được)
                      <<include>> UC-A5: Nhận email reset
                      <<include>> UC-A6: Đặt lại mật khẩu mới
Student ──→ UC-A7:  Đăng xuất
Student ──→ UC-A11: Xem thông tin tài khoản (me)
Student ──→ UC-A12: Cập nhật hồ sơ
Student ──→ UC-A13: Đổi mật khẩu
Student ──→ UC-A14: Upload ảnh đại diện
```

---

### UC-COURSE: Khóa học (Public + Student)

```
Guest   ──→ UC-C1: Duyệt danh sách khóa học
                     <<include>> UC-C2: Lọc theo danh mục/cấp độ
                     <<include>> UC-C3: Tìm kiếm khóa học
Guest   ──→ UC-C4: Xem chi tiết khóa học
                     <<include>> UC-C5: Xem thông tin giảng viên
                     <<include>> UC-C6: Xem mục lục khóa học
Guest   ──→ UC-C7: Xem bài học preview (is_preview = true)
Student ──→ UC-C8: Đăng ký khóa học miễn phí
Student ──→ UC-C9: Xem khóa học của tôi
```

---

### UC-LEARN: Học tập

```
Student ──→ UC-L1: Xem danh sách bài học (đã đăng ký)
Student ──→ UC-L2: Xem bài học video
                     <<include>> UC-L3: Cập nhật tiến độ xem video
Student ──→ UC-L4: Xem bài học tài liệu (PDF)
Student ──→ UC-L5: Xem bài học dạng text
Student ──→ UC-L6: Đánh dấu hoàn thành bài học
Student ──→ UC-L7: Xem tiến độ khóa học (%)
```

---

### UC-PAY: Thanh toán

```
Student ──→ UC-P1:  Tạo đơn hàng mua khóa học
                      <<include>> UC-P2: Áp dụng mã giảm giá
                      <<include>> UC-P3: Chuyển hướng sang VNPay
                      <<extend>>  UC-P4: Nhận thông báo IPN (webhook)
                      <<include>> UC-P5: Enroll vào khóa học (sau thanh toán thành công)
Student ──→ UC-P6:  Xem lịch sử đơn hàng
Student ──→ UC-P7:  Xem chi tiết đơn hàng
Student ──→ UC-P8:  Thanh toán lại đơn hàng thất bại
Guest   ──→ UC-P9:  Xem mã giảm giá khả dụng
Student ──→ UC-P10: Kiểm tra mã giảm giá
```

---

### UC-QUIZ: Kiểm tra

```
Student ──→ UC-Q1: Xem bài kiểm tra của bài học
Student ──→ UC-Q2: Nộp bài kiểm tra
                     <<include>> UC-Q3: Tính điểm tự động
                     <<include>> UC-Q4: Xem kết quả & đáp án
Student ──→ UC-Q5: Xem lịch sử lần thi
Student ──→ UC-Q6: Thi lại (nếu còn lượt)
```

---

### UC-BLOG: Blog & Bài viết

```
Guest   ──→ UC-B1: Xem danh sách bài viết
                     <<include>> UC-B2: Lọc theo danh mục/tag
Guest   ──→ UC-B3: Xem chi tiết bài viết
                     <<include>> UC-B4: Xem bình luận
Student ──→ UC-B5: Đăng bình luận (chờ duyệt)
```

---

### UC-ADMIN-COURSE: Quản lý khóa học (Admin/Teacher)

```
Teacher/Admin ──→ UC-AC1:  Xem danh sách khóa học
Teacher/Admin ──→ UC-AC2:  Tạo khóa học mới
Teacher/Admin ──→ UC-AC3:  Sửa thông tin khóa học
Teacher/Admin ──→ UC-AC4:  Bật/tắt trạng thái khóa học
Teacher/Admin ──→ UC-AC5:  Xóa mềm khóa học
Teacher/Admin ──→ UC-AC6:  Quản lý chương học (Section)
                              <<include>> UC-AC7:  Thêm/sửa/xóa chương
                              <<include>> UC-AC8:  Sắp xếp thứ tự chương
Teacher/Admin ──→ UC-AC9:  Quản lý bài học (Lesson)
                              <<include>> UC-AC10: Thêm/sửa/xóa bài học
                              <<include>> UC-AC11: Đặt loại bài (video/doc/text/quiz)
                              <<include>> UC-AC12: Upload video/tài liệu
Teacher/Admin ──→ UC-AC13: Xem thùng rác (trashed)
Teacher/Admin ──→ UC-AC14: Khôi phục khóa học/bài học
Admin         ──→ UC-AC15: Xóa vĩnh viễn
Admin         ──→ UC-AC16: Thao tác hàng loạt (bulk)
```

---

### UC-ADMIN-QUIZ: Quản lý Quiz (Admin/Teacher)

```
Teacher/Admin ──→ UC-AQ1: Tạo/sửa/xóa Quiz
Teacher/Admin ──→ UC-AQ2: Tạo câu hỏi bằng AI (Gemini)
                             <<include>> UC-AQ3: Upload PDF tài liệu
                             <<include>> UC-AQ4: Theo dõi job AI (polling)
Teacher/Admin ──→ UC-AQ5: Sửa/xóa câu hỏi thủ công
Teacher/Admin ──→ UC-AQ6: Bật/tắt trạng thái Quiz
```

---

### UC-ADMIN-SYS: Quản trị hệ thống (Admin only)

```
Admin ──→ UC-AS1:  Quản lý danh mục khóa học
Admin ──→ UC-AS2:  Quản lý giảng viên (CRUD + soft delete)
Admin ──→ UC-AS3:  Quản lý học viên
Admin ──→ UC-AS4:  Quản lý đơn hàng & doanh thu
Admin ──→ UC-AS5:  Quản lý mã giảm giá
Admin ──→ UC-AS6:  Quản lý bài viết Blog
                     <<include>> UC-AS7: Duyệt/từ chối bình luận
Admin ──→ UC-AS8:  Quản lý người dùng Admin (User)
Admin ──→ UC-AS9:  Phân quyền (Roles & Permissions)
                     <<include>> UC-AS10: Tạo/sửa role
                     <<include>> UC-AS11: Gán quyền cho role
                     <<include>> UC-AS12: Gán role cho user
Admin ──→ UC-AS13: Xem Dashboard thống kê
Admin ──→ UC-AS14: Xem nhật ký hoạt động hệ thống
```

---

## IV. SƠ ĐỒ TUẦN TỰ (Sequence Diagrams)

### SD-01: Đăng ký & Xác minh Email

**Actors:** Student, Frontend, Backend, Email Server, Database

```
Student    → Frontend     : Nhập email, password, name
Frontend   → Backend      : POST /api/v1/auth/register
Backend    → Database     : Tạo Student (email_verified_at = null)
Backend    → Database     : Tạo token xác minh (expires 24h)
Backend    → Email Server : Gửi email chứa link /auth/verify-email/{token}
Backend    → Frontend     : 201 { success: true }
Frontend   → Student      : Thông báo "Kiểm tra email của bạn"

--- (Student mở email) ---

Student    → Frontend     : Click link xác minh
Frontend   → Backend      : GET /api/v1/auth/verify-email/{token}
Backend    → Database     : Tìm token, kiểm tra hết hạn
Backend    → Database     : Set email_verified_at = now(), xóa token
Backend    → Frontend     : 200 { success: true }
Frontend   → Student      : Chuyển hướng trang đăng nhập
```

---

### SD-02: Mua khóa học + Thanh toán VNPay

**Actors:** Student, Frontend, Backend, VNPay, Database

```
Student    → Frontend     : Chọn khóa học, nhấn "Mua ngay"
Student    → Frontend     : (Tùy chọn) Nhập mã giảm giá
Frontend   → Backend      : POST /api/v1/coupons/validate { code, course_id }
Backend    → Frontend     : 200 { discount_amount, final_price }

Frontend   → Backend      : POST /api/v1/orders { course_id, coupon_code? }
Backend    → Database     : Tạo Order (status = 'pending')
Backend    → Database     : Tạo Transaction (status = 'pending')
Backend    → VNPay        : Tạo payment URL (HMAC-SHA512)
Backend    → Frontend     : 200 { payment_url }
Frontend   → Student      : Chuyển hướng → VNPay Payment Page

--- (Student thanh toán trên VNPay) ---

VNPay      → Backend      : GET /api/v1/payment/vnpay/ipn (webhook)
Backend                   : Xác minh checksum HMAC-SHA512
Backend    → Database     : lockForUpdate() Order, kiểm tra status === 'pending'
Backend    → Database     : Update Transaction → 'completed'
Backend    → Database     : Update Order → 'completed'
Backend    → Database     : enrollStudent() — thêm vào students_course
Backend    → VNPay        : {"RspCode":"00"} (xác nhận nhận IPN)

VNPay      → Frontend     : GET /payment/vnpay/return?...
Backend    → Frontend     : Xác minh checksum, redirect
Frontend   → Student      : Trang "Thanh toán thành công"
```

---

### SD-03: Học bài & Theo dõi tiến độ

**Actors:** Student, Frontend, Backend, Database

```
Student    → Frontend     : Vào "Khóa học của tôi", chọn khóa
Frontend   → Backend      : GET /api/v1/my-courses/{slug}/lessons
Backend    → Database     : Kiểm tra enrollment
Backend    → Frontend     : Danh sách sections & lessons + trạng thái completed

Student    → Frontend     : Chọn bài học video
Frontend   → Backend      : GET /api/v1/my-courses/{slug}/lessons/{lesson_slug}
Backend    → Database     : Lấy lesson + media_file URL
Backend    → Frontend     : Lesson data + stream URL có token

Frontend   → Student      : Hiển thị video player
Student                   : Xem video...

Student    → Frontend     : Xem đến 80% video (progress event)
Frontend   → Backend      : POST /api/v1/lessons/{id}/progress { watch_percent: 80 }
Backend    → Database     : Cập nhật/tạo lesson_progress

Student    → Frontend     : Xem xong 100% video
Frontend   → Backend      : POST /api/v1/lessons/{id}/progress { completed: true }
Backend    → Database     : Đánh dấu lesson completed

Frontend   → Backend      : GET /api/v1/courses/{slug}/progress
Backend    → Database     : Tính tỷ lệ (completed_lessons / total_lessons)
Backend    → Frontend     : { progress_percent: 75 }
Frontend   → Student      : Cập nhật thanh tiến độ
```

---

### SD-04: Làm Quiz & Chấm điểm

**Actors:** Student, Frontend, Backend, Database

```
Student    → Frontend     : Vào bài học loại "quiz"
Frontend   → Backend      : GET /api/v1/lessons/{lessonId}/quiz
Backend    → Database     : Lấy quiz + questions (trộn ngẫu nhiên nếu shuffle)
Backend    → Frontend     : { quiz, questions } (không có correct_answer)
Frontend   → Student      : Hiển thị form câu hỏi + đếm giờ

Student                   : Trả lời các câu hỏi...
Student    → Frontend     : Nhấn "Nộp bài"
Frontend   → Backend      : POST /api/v1/quizzes/{id}/submit { answers: [{question_id, answer}] }
Backend    → Database     : Lấy đáp án đúng từ DB
Backend                   : Tính điểm (correct / total * 100)
Backend                   : Kiểm tra pass_score
Backend    → Database     : Lưu QuizAttempt (score, passed, answers)
Backend    → Frontend     : { score, passed, correct_answers, explanation }
Frontend   → Student      : Hiển thị kết quả + đáp án đúng/sai

--- (Nếu muốn thi lại) ---
Student    → Frontend     : Nhấn "Thi lại"
Frontend   → Backend      : GET /api/v1/quizzes/{id}/attempts
Backend    → Frontend     : Danh sách lần thi (kiểm tra max_attempts)
Frontend   → Student      : Cho phép thi lại nếu còn lượt
```

---

### SD-05: Admin tạo khóa học + thêm bài học

**Actors:** Admin/Teacher, Frontend, Backend, Database, Storage

```
Admin      → Frontend     : Nhấn "Tạo khóa học mới"
Frontend   → Backend      : POST /api/v1/admin/courses { title, slug, teacher_id, ... }
Backend    → Database     : Tạo Course (status = 0 - draft)
Backend    → Frontend     : 201 { course }

Admin      → Frontend     : Upload thumbnail
Frontend   → Backend      : POST /api/v1/admin/upload/image { file }
Backend    → Storage      : Lưu file vào storage/
Backend    → Database     : Tạo MediaFile record
Backend    → Frontend     : { file_url }

Admin      → Frontend     : Thêm chương học
Frontend   → Backend      : POST /api/v1/admin/courses/{id}/sections { title, order }
Backend    → Database     : Tạo Section
Backend    → Frontend     : 201 { section }

Admin      → Frontend     : Thêm bài học vào chương
Frontend   → Backend      : POST /api/v1/admin/courses/{id}/lessons { section_id, title, type: 'video' }
Backend    → Database     : Tạo Lesson (status = 0)
Backend    → Frontend     : 201 { lesson }

Admin      → Frontend     : Upload video cho bài học
Frontend   → Backend      : POST /api/v1/admin/upload/video { file }
Backend    → Storage      : Stream lưu video (max 500MB)
Backend    → Database     : Tạo MediaFile
Backend    → Frontend     : { video_url, media_id }

Admin      → Frontend     : Gán video vào bài học
Frontend   → Backend      : PATCH /api/v1/admin/lessons/{id} { video_id: media_id }
Backend    → Database     : Update Lesson

Admin      → Frontend     : Xuất bản khóa học
Frontend   → Backend      : PATCH /api/v1/admin/courses/{id}/toggle-status
Backend    → Database     : Set status = 1 (published)
Backend    → Frontend     : 200 { success }
```

---

### SD-06: Tạo Quiz bằng AI (Gemini)

**Actors:** Admin/Teacher, Frontend, Backend, Queue Worker, Gemini AI, Database

```
Admin      → Frontend     : Vào bài học, nhấn "Tạo Quiz AI"
Admin      → Frontend     : Chọn file PDF tài liệu
Frontend   → Backend      : POST /api/v1/admin/lesson-quiz/{lessonId}/generate { pdf_id }
Backend    → Database     : Tạo QuizGenerationJob (status = 'pending')
Backend    → Queue        : Dispatch GenerateQuizJob
Backend    → Frontend     : 202 { job_id }

Frontend              : Bắt đầu polling mỗi 2 giây...
Frontend   → Backend  : GET /api/v1/admin/lesson-quiz/jobs/{job_id}
Backend    → Frontend  : { status: 'pending' }

--- (Queue Worker xử lý async) ---
Worker     → Database     : Update job → status = 'processing'
Worker     → Gemini AI    : Gửi prompt + nội dung PDF
Gemini AI  → Worker       : Trả về JSON câu hỏi (10 câu)
Worker     → Database     : Lưu Questions vào DB
Worker     → Database     : Update job → status = 'done'

--- (Frontend polling tiếp) ---
Frontend   → Backend      : GET /api/v1/admin/lesson-quiz/jobs/{job_id}
Backend    → Frontend     : { status: 'done' }
Frontend   → Admin        : Hiển thị danh sách câu hỏi vừa tạo
Admin      → Frontend     : Chỉnh sửa/xác nhận câu hỏi
Frontend   → Backend      : PATCH /api/v1/admin/quiz-questions/{id}
```

---

## V. SƠ ĐỒ HOẠT ĐỘNG (Activity Diagrams)

### AD-01: Đăng ký tài khoản

```
[Bắt đầu]
    ↓
Nhập thông tin đăng ký (name, email, password)
    ↓
Validate dữ liệu
    ↓
[Email đã tồn tại?] ──Yes──→ Hiển thị lỗi → Nhập lại
    ↓ No
Tạo tài khoản (email_verified_at = null)
    ↓
Gửi email xác minh
    ↓
Thông báo "Kiểm tra email"
    ↓
[Người dùng click link email?]
    ├── Yes → [Token còn hạn?]
    │               ├── Yes → Xác minh thành công → Chuyển hướng đăng nhập
    │               └── No  → Gửi lại link → (quay lại chờ click)
    └── No  → (Token hết hạn sau 24h) → Yêu cầu gửi lại
[Kết thúc]
```

---

### AD-02: Thanh toán VNPay

```
[Bắt đầu]
    ↓
Student chọn khóa học → Nhấn "Mua"
    ↓
[Đã đăng ký rồi?] ──Yes──→ Thông báo "Đã có khóa học này" → [Kết thúc]
    ↓ No
[Nhập mã giảm giá?]
    ├── Có → Validate coupon
    │           ├── Hợp lệ   → Tính giá sau giảm
    │           └── Không hợp lệ → Lỗi "Mã không hợp lệ" → (quay lại)
    └── Không
    ↓
Tạo Order (status = pending)
    ↓
Chuyển hướng VNPay
    ↓
Student thanh toán trên VNPay
    ↓
VNPay gửi IPN callback
    ↓
[Xác minh checksum HMAC-SHA512?]
    ├── Fail → Log lỗi → [Kết thúc lỗi]
    └── Pass
    ↓
[Order status = pending?] ──No──→ Bỏ qua (idempotent) → [Kết thúc]
    ↓ Yes
[Thanh toán thành công?]
    ├── Yes → Update Order = completed
    │          Update Transaction = completed
    │          Enroll student vào khóa học
    │          → Thông báo thành công
    └── No  → Update Order = failed
               → Thông báo thất bại
[Kết thúc]
```

---

### AD-03: Học bài & Cập nhật tiến độ

```
[Bắt đầu]
    ↓
Vào khóa học đã đăng ký
    ↓
Chọn bài học từ danh sách
    ↓
[Loại bài học?]
    ├── Video      → Load video player + stream URL
    │                ↓
    │               Xem video
    │                ↓
    │               [Tiến độ >= 80%?] ──Yes──→ Đánh dấu hoàn thành
    │                                 ──No──→  Tiếp tục xem
    │
    ├── Tài liệu   → Hiển thị PDF viewer
    │                ↓
    │               Xem xong → Đánh dấu hoàn thành (thủ công)
    │
    └── Text       → Hiển thị nội dung text
                     ↓
                    Đọc xong → Đánh dấu hoàn thành (thủ công)
    ↓
Cập nhật tiến độ khóa học
    ↓
[Hoàn thành tất cả bài học?] ──Yes──→ Tiến độ = 100% → [Kết thúc]
    ↓ No
[Chọn bài tiếp theo?]
    ├── Yes → (Quay lại "Chọn bài học")
    └── No  → Thoát
[Kết thúc]
```

---

### AD-04: Làm Quiz

```
[Bắt đầu]
    ↓
Vào bài học loại Quiz
    ↓
[Đã dùng hết lượt thi?] ──Yes──→ Thông báo "Hết lượt thi" → [Kết thúc]
    ↓ No
Tải câu hỏi (không có đáp án đúng)
    ↓
Bắt đầu đếm giờ (nếu có time_limit)
    ↓
Trả lời câu hỏi
    ↓
[Hết giờ HOẶC nhấn "Nộp bài"?] ──No──→ (Tiếp tục trả lời)
    ↓ Yes
Submit câu trả lời
    ↓
Server chấm điểm
    ↓
[Đạt pass_score?]
    ├── Yes → Hiển thị "Đạt" + điểm + đáp án đúng
    └── No  → Hiển thị "Chưa đạt" + điểm + đáp án đúng
    ↓
[Muốn thi lại?]
    ├── Yes → [Còn lượt thi?]
    │             ├── Yes → (Quay lại đầu)
    │             └── No  → Thông báo "Hết lượt" → [Kết thúc]
    └── No  → Thoát
[Kết thúc]
```

---

### AD-05: Admin quản lý khóa học

```
[Bắt đầu]
    ↓
Đăng nhập Admin Panel
    ↓
Vào quản lý khóa học
    ↓
[Hành động?]
    ├── Tạo mới   → Nhập thông tin → Upload thumbnail → Lưu (status = draft)
    │               ↓
    │              Thêm Sections → Thêm Lessons → Upload nội dung
    │               ↓
    │              Xuất bản (toggle-status = 1)
    │
    ├── Sửa        → Chọn khóa học → Cập nhật thông tin → Lưu
    │
    ├── Xóa mềm   → Soft delete ──→ Vào thùng rác
    │               ↓
    │              [Muốn xóa vĩnh viễn?]
    │                  ├── Yes → Force delete
    │                  └── No  → Khôi phục (restore)
    │
    └── Bulk       → Chọn nhiều → Bulk delete / Bulk restore / Bulk status
[Kết thúc]
```

---

### AD-06: Tạo Quiz bằng AI

```
[Bắt đầu]
    ↓
Admin vào bài học → Nhấn "Tạo Quiz AI"
    ↓
Chọn file PDF tài liệu
    ↓
Gửi yêu cầu → Tạo QuizGenerationJob (status = pending)
    ↓
Dispatch job vào Queue
    ↓
Frontend bắt đầu polling (mỗi 2 giây)
    ↓
[Job status?]
    ├── pending/processing → Hiển thị loading → Tiếp tục polling
    ├── failed             → Hiển thị lỗi → [Kết thúc lỗi]
    └── done
    ↓
Hiển thị danh sách câu hỏi AI tạo ra
    ↓
[Admin chỉnh sửa câu hỏi?]
    ├── Yes → Sửa/xóa từng câu hỏi → Lưu
    └── No  → Giữ nguyên
    ↓
Bật trạng thái Quiz (publish)
    ↓
[Kết thúc]
```

---

## VI. HƯỚNG DẪN VẼ TRONG DRAW.IO / STARUML

### Ký hiệu Use Case Diagram

| Ký hiệu | Hình vẽ | Ý nghĩa |
|---------|---------|---------|
| Actor | Hình người (stick figure) | Người/hệ thống bên ngoài |
| Use Case | Hình oval | Chức năng hệ thống |
| System Boundary | Hình chữ nhật | Phạm vi hệ thống |
| Association | Đường thẳng | Actor sử dụng Use Case |
| Include | Mũi tên nét đứt + `<<include>>` | UC này luôn gọi UC kia |
| Extend | Mũi tên nét đứt + `<<extend>>` | UC kia mở rộng UC này (tùy điều kiện) |
| Generalization | Mũi tên rỗng | Kế thừa (con → cha) |

### Gợi ý tách sơ đồ trong báo cáo

| Tên sơ đồ | Nội dung | Trang |
|-----------|----------|-------|
| UC-01 Tổng quát | 4 actors + 38 use case gộp nhóm | 1 trang |
| UC-02 Auth & Tài khoản | UC-AUTH đầy đủ | 1 trang |
| UC-03 Học tập & Quiz | UC-COURSE + UC-LEARN + UC-QUIZ | 1 trang |
| UC-04 Thanh toán | UC-PAY đầy đủ | 1 trang |
| UC-05 Admin/Teacher | UC-ADMIN-COURSE + UC-ADMIN-QUIZ | 1 trang |
| UC-06 Hệ thống | UC-ADMIN-SYS | 1 trang |
| SD-01 đến SD-06 | 6 sequence diagrams | 6 trang |
| AD-01 đến AD-06 | 6 activity diagrams | 6 trang |

### Quan hệ Actor quan trọng cần vẽ

```
Student     ────────────────────────────────── Guest
   (generalization, mũi tên rỗng từ Student → Guest)

Teacher     ────────────────────────────────── Admin
   (generalization, mũi tên rỗng từ Teacher → Admin)
```

---

## VII. DANH SÁCH LUỒNG SỰ KIỆN CHÍNH (Event Flow Summary)

| Luồng | Actors | Bước chính |
|-------|--------|------------|
| Đăng ký & xác minh | Guest, Email Server | Register → Token → Email → Verify |
| Đăng nhập | Guest/Admin | Login → Token → Lưu localStorage |
| Mua khóa học | Student, VNPay | Order → VNPay → IPN → Enroll |
| Học bài | Student | Enroll check → Load lesson → Progress update |
| Làm quiz | Student | Load questions → Submit → Auto-grade → Result |
| Tạo khóa học | Admin/Teacher | Create course → Add sections → Add lessons → Publish |
| Tạo Quiz AI | Admin/Teacher | Request → Queue job → Gemini API → Poll → Result |
| Phân quyền | Admin | Create role → Assign permissions → Assign to user |
