# 📋 PROMPT QUẢN LÝ CÔNG VIỆC — E-Learning Backend

> File này có 2 phần:
> - **Phần A**: Prompt dùng với Claude trong VSCode để hỏi/cập nhật tiến độ
> - **Phần B**: File TODO tự theo dõi (tick tay)

---

## PHẦN A — PROMPT DÙNG VỚI CLAUDE TRONG VSCODE

### 🔁 Prompt dùng MỖI KHI bắt đầu làm việc (buổi sáng / mở VSCode)

```
Tôi đang làm đồ án tốt nghiệp: E-Learning Marketplace API
Stack: Laravel 12, HMVC (nwidart/laravel-modules), Sanctum, Spatie Permission, Cloudinary, VNPAY/MoMo, OpenAI GPT-4o-mini
Frontend tách riêng: Vue.js + Tailwind (repo khác)

Tiến độ hiện tại của tôi:
[ ] PHASE 0 — Setup
  [x] Cài package core (sanctum, spatie, nestedset, laravel-modules)
  [x] BaseRepository + RepositoryInterface (incl. findOrFail, deleteMany, actionMany)
  [x] ApiResponse trait + CORS config
  [x] 2 guard trong auth.php (api/admin)
  [ ] Custom Artisan command: make:module-repository

[ ] PHASE 1 — Auth
  [ ] Module Auth — Admin login/logout/me
  [ ] Module Users — CRUD + Spatie Role/Permission + Seeder
  [ ] Module Auth — Student register/login/verify-email/forgot-password

[ ] PHASE 2 — Nội dung
  [ ] Module Categories — nested set CRUD
  [ ] Module Teachers — CRUD
  [ ] Module Courses — CRUD Admin + Public API
  [ ] Tích hợp Cloudinary — upload video & document
  [ ] Module Lessons — CRUD + lock logic

[ ] PHASE 3 — Thương mại
  [ ] Module Orders — tạo đơn & giỏ hàng
  [ ] Module Coupons — CRUD + validate
  [ ] Payment VNPAY — tạo link + Webhook
  [ ] Payment MoMo — tạo link + Webhook

[ ] PHASE 4 — Nâng cao
  [ ] Module Progress — theo dõi tiến độ xem video
  [ ] Module Quiz — AI Auto-Quiz (OpenAI + Spatie PDF-to-Text)
  [ ] Module Dashboard — thống kê Admin

Hôm nay tôi muốn làm: [ĐIỀN VÀO ĐÂY — ví dụ: "PHASE 0 TASK 1: BaseRepository + ApiResponse"]

Hãy:
1. Nhắc lại ngắn gọn context của task hôm nay (dựa vào danh sách trên)
2. Bắt đầu thực hiện task đó luôn
```

---

### ✅ Prompt báo cáo hoàn thành 1 task

```
Tôi vừa hoàn thành task: [TÊN TASK]

Kết quả:
- [Mô tả ngắn những gì đã làm, ví dụ: "Đã tạo BaseRepository, ApiResponse trait, config CORS"]
- [Vấn đề gặp phải nếu có]

Bước tiếp theo theo kế hoạch là: [TÊN TASK TIẾP THEO]
Hãy bắt đầu task tiếp theo đó cho tôi.
```

---

### 🐛 Prompt khi gặp lỗi

```
Tôi đang làm task: [TÊN TASK]
Gặp lỗi sau:

[PASTE TOÀN BỘ ERROR MESSAGE VÀO ĐÂY]

File liên quan: [tên file nếu biết]
Tôi đã thử: [những gì đã thử nếu có]

Hãy phân tích lỗi và đưa ra cách fix cụ thể.
```

---

### 🔍 Prompt review code trước khi sang task mới

```
Tôi vừa xong task: [TÊN TASK]
Hãy review nhanh code sau và cho biết:
1. Có vấn đề gì về security không?
2. Có thiếu validation nào không?
3. Response format có đúng chuẩn ApiResponse không?
4. Có gì cần cải thiện trước khi sang task tiếp không?

[PASTE CODE CẦN REVIEW]
```

---

### 📊 Prompt kiểm tra tiến độ cuối ngày

```
Hôm nay tôi đã hoàn thành các task sau trong dự án E-Learning Backend:
- [task 1]
- [task 2]

Dựa vào lộ trình sprint bên dưới, hãy:
1. Tính % hoàn thành tổng thể
2. Nhận xét tiến độ có đúng kế hoạch không (deadline 15/05/2026)
3. Gợi ý task nên làm đầu tiên ngày mai

Lộ trình sprint:
- Sprint 1 (22/03 - 07/04): Phase 0 + Phase 1 + Phase 2 một phần
- Sprint 2 (08/04 - 25/04): Phase 2 còn lại
- Sprint 3 (26/04 - 13/05): Phase 3 + Phase 4
- Testing & Fix Bug: 14/05
- Báo cáo: 15/05
```

---

### ⚡ Prompt hỏi nhanh không cần context dài

```
Context: Laravel 12, HMVC (nwidart/laravel-modules), API-only, Sanctum, Spatie Permission.
Task hiện tại: [TÊN TASK]
Câu hỏi: [CÂU HỎI CỦA BẠN]
```

---

## PHẦN B — FILE TODO TỰ THEO DÕI

> Dùng để tick tay sau mỗi khi hoàn thành.
> Cập nhật cột "Hoàn thành" bằng ngày thực tế.

---

### 🟢 PHASE 0 — Setup & Nền tảng (~1 ngày)

| # | Task | Trạng thái | Hoàn thành |
|---|------|-----------|------------|
| 0.1 | Cài package core (sanctum, spatie, nestedset, modules) | ✅ Done | 15/03/2026 |
| 0.2 | Fix migration duplicate + publish config Sanctum | ✅ Done | 15/03/2026 |
| 0.3 | Publish config laravel-modules + composer.json autoload | ✅ Done | 15/03/2026 |
| 0.4 | Tạo BaseRepository + RepositoryInterface (findOrFail, deleteMany, actionMany, clamp perPage) | ✅ Done | 15/03/2026 |
| 0.5 | Tạo ApiResponse trait (success, error, paginated) | ✅ Done | 15/03/2026 |
| 0.6 | Cấu hình CORS (cho localhost:5173, supports_credentials) | ✅ Done | 15/03/2026 |
| 0.7 | Cấu hình 2 guard trong auth.php (api→students, admin→admins) | ✅ Done | 15/03/2026 |
| 0.8 | Tạo Artisan command: make:module-repository | ⬜ Todo | |

---

### 🔵 PHASE 1 — Auth & Users (~3 ngày | 22/03 - 25/03)

| # | Task | Trạng thái | Hoàn thành |
|---|------|-----------|------------|
| 1.1 | Module Auth — Admin: login / logout / me | ⬜ Todo | |
| 1.2 | Module Users — Migration + Model + Seeder (roles, permissions) | ⬜ Todo | |
| 1.3 | Module Users — CRUD API + gán Spatie Role | ⬜ Todo | |
| 1.4 | Module Auth — Student: register / login / verify-email / forgot-password | ⬜ Todo | |

---

### 🟣 PHASE 2 — Nội dung (~5 ngày | 26/03 - 01/04)

| # | Task | Trạng thái | Hoàn thành |
|---|------|-----------|------------|
| 2.1 | Module Categories — nested set CRUD (Admin + Public) | ⬜ Todo | |
| 2.2 | Module Teachers — CRUD Admin + Public | ⬜ Todo | |
| 2.3 | Module Courses — Migration + Model + Admin CRUD | ⬜ Todo | |
| 2.4 | Module Courses — Public API (list, show, filter, search) + CourseResource | ⬜ Todo | |
| 2.5 | Tích hợp Cloudinary — upload video & document endpoint | ⬜ Todo | |
| 2.6 | Module Lessons — Migration (videos, documents, lessons) | ⬜ Todo | |
| 2.7 | Module Lessons — Admin CRUD + reorder | ⬜ Todo | |
| 2.8 | Module Lessons — Public API + lock logic (chưa mua → video_url null) | ⬜ Todo | |

---

### 🟡 PHASE 3 — Thương mại (~5 ngày | 02/04 - 08/04)

| # | Task | Trạng thái | Hoàn thành |
|---|------|-----------|------------|
| 3.1 | Module Orders — Migration (orders, order_details, students_course) | ⬜ Todo | |
| 3.2 | Module Orders — Tạo đơn hàng + my-courses API | ⬜ Todo | |
| 3.3 | Module Coupons — Migration + CRUD Admin | ⬜ Todo | |
| 3.4 | Module Coupons — Validate & apply khi checkout | ⬜ Todo | |
| 3.5 | Payment VNPAY — VnpayService + tạo link thanh toán | ⬜ Todo | |
| 3.6 | Payment VNPAY — Webhook callback + mở khóa khóa học | ⬜ Todo | |
| 3.7 | Payment MoMo — MomoService + tạo link thanh toán | ⬜ Todo | |
| 3.8 | Payment MoMo — Webhook callback + mở khóa khóa học | ⬜ Todo | |

---

### 🔴 PHASE 4 — Tính năng nâng cao (~3 ngày | 09/04 - 12/04)

| # | Task | Trạng thái | Hoàn thành |
|---|------|-----------|------------|
| 4.1 | Module Progress — Migration lesson_progress | ⬜ Todo | |
| 4.2 | Module Progress — API cập nhật & lấy tiến độ | ⬜ Todo | |
| 4.3 | Module Quiz — Migration quizzes + quiz_questions | ⬜ Todo | |
| 4.4 | Module Quiz — QuizAiService (OpenAI + PDF extract) | ⬜ Todo | |
| 4.5 | Module Quiz — Admin generate-quiz API + Client get-quiz API | ⬜ Todo | |
| 4.6 | Module Dashboard — Stats tổng quan | ⬜ Todo | |
| 4.7 | Module Dashboard — Revenue theo ngày/tháng + Top courses | ⬜ Todo | |

---

### 🔧 GIAI ĐOẠN CUỐI (13/05 - 15/05)

| # | Task | Trạng thái | Hoàn thành |
|---|------|-----------|------------|
| 5.1 | Testing toàn bộ API bằng Postman | ⬜ Todo | |
| 5.2 | Fix bug từ testing | ⬜ Todo | |
| 5.3 | Export Postman Collection lưu vào repo | ⬜ Todo | |
| 5.4 | Viết API Documentation (README cập nhật) | ⬜ Todo | |
| 5.5 | Nộp báo cáo | ⬜ Todo | |

---

## 📊 Tổng tiến độ

| Phase | Tổng task | Hoàn thành | % |
|-------|-----------|------------|---|
| Phase 0 | 8 | 7 | 87.5% |
| Phase 1 | 4 | 0 | 0% |
| Phase 2 | 8 | 0 | 0% |
| Phase 3 | 8 | 0 | 0% |
| Phase 4 | 7 | 0 | 0% |
| Giai đoạn cuối | 5 | 0 | 0% |
| **Tổng** | **40** | **7** | **17.5%** |

---

## 📅 Mốc thời gian cần nhớ

| Mốc | Ngày | Ghi chú |
|-----|------|---------|
| Bắt đầu code | 22/03/2026 | Sprint 1 |
| Xong Phase 1+2 | 07/04/2026 | Sprint 1 kết thúc |
| Xong Phase 2 hoàn toàn | 25/04/2026 | Sprint 2 kết thúc |
| Xong Phase 3+4 | 13/05/2026 | Sprint 3 kết thúc |
| Testing & Fix | 14/05/2026 | |
| **Deadline nộp** | **15/05/2026** | |

---

*Cập nhật lần cuối: 15/03/2026 — Phase 0 Task 1 hoàn thành*