# 🎓 E-Learning Marketplace Platform

> **Đồ án tốt nghiệp** — Khoa Khoa học Máy tính, Đại học Duy Tân
> Ngành: Công nghệ Phần mềm

---

## 📋 Thông tin dự án

| Thông tin | Chi tiết |
|-----------|----------|
| **Tên đề tài** | Xây dựng hệ thống nền tảng học tập trực tuyến (E-Learning Marketplace) tích hợp thanh toán trực tuyến |
| **Sinh viên** | Phan Văn Thành — MSSV: 28211102974 |
| **GVHD** | Trịnh Sử Trường Thi |
| **Thời gian** | 12/03/2026 – 15/05/2026 |

---

## 📖 Giới thiệu

**E-Learning Marketplace** là một nền tảng giáo dục số toàn diện, đóng vai trò như một khu chợ trung gian kết nối **giảng viên** và **người học**. Hệ thống cho phép giảng viên đóng gói và phân phối khóa học dạng video (VOD), đồng thời giúp học viên học tập mọi lúc mọi nơi.

### Tính năng chính

- 🎬 **Quản lý khóa học Video (VOD)** — Upload, tổ chức và phân phối bài giảng video; bảo vệ nội dung bằng watermark logo + email trên video/tài liệu
- 💳 **Giỏ hàng & Thanh toán trực tuyến** — Tích hợp VNPAY/ZaloPay, xử lý giao dịch an toàn qua IPN webhook
- 🤖 **AI Auto-Quiz (Gemini 2.5 Flash)** — Tự động sinh câu hỏi trắc nghiệm thông minh từ tài liệu PDF (hỗ trợ Tiếng Việt 100%, cơ chế fallback tự động, lọc câu hỏi vi phạm)
- 🔔 **Thông báo real-time (Laravel Reverb)** — WebSocket tự host, thông báo tức thời cho Admin và Giảng viên (đăng ký khóa học, yêu cầu rút tiền, duyệt khóa học, bình luận mới)
- 👨‍🏫 **Teacher Portal (Giảng viên)** — Dashboard thu nhập, quản lý khóa học/sections/lessons, bài viết (Quill editor), hồ sơ cá nhân + bảo mật tài khoản (đổi email/mật khẩu qua OTP), yêu cầu rút tiền
- 📊 **Dashboard thống kê** — Theo dõi doanh thu, tiến độ học tập (Admin + Giảng viên)
- 🔐 **Bảo mật & Phân quyền nâng cao** — RBAC chuyên sâu, Role-scoping (Admin chỉ quản lý Student/Teacher), chặn leo thang đặc quyền (Anti-Privilege Escalation)
- 🧪 **Kiểm thử tự động** — Hệ thống Feature Tests đạt độ ổn định cao (225/225 passed)
- 🏷️ **Mã giảm giá (Coupon)** — Hỗ trợ tỉ lệ %, số tiền cố định, giới hạn lượt dùng, kiểm tra race condition

---

## 🗂️ Cấu trúc dự án

```
e-learning/
├── e-learning-backend/     # Backend — Laravel 11 (PHP)
├── e-learning-frontend/    # Frontend — Vue.js 3 (Vite + TypeScript)
├── .gitignore
├── LICENSE
└── README.md
```

| Thành phần | Mô tả | Tài liệu |
|-----------|-------|-----------|
| **[e-learning-backend](./e-learning-backend)** | REST API server xử lý logic nghiệp vụ, xác thực, phân quyền, thanh toán. Kiến trúc Modular (Nwidart Modules). | 📄 [Backend README](./e-learning-backend/README.md) |
| **[e-learning-frontend](./e-learning-frontend)** | SPA giao diện người dùng gồm Admin Dashboard (TailAdmin), Teacher Portal và Client UI (Flowbite). Hỗ trợ Dark Mode, thông báo real-time. | 📄 [Frontend README](./e-learning-frontend/README.md) |

---

## 🛠️ Công nghệ sử dụng

### Backend (`e-learning-backend/`) — [Xem chi tiết](./e-learning-backend/README.md)

| Công nghệ | Mô tả |
|-----------|--------|
| **PHP 8.2+ / Laravel 12** | Framework chính — Kiến trúc Modular (Nwidart Modules) |
| **MySQL 8.0** | Cơ sở dữ liệu quan hệ |
| **Laravel Sanctum** | Xác thực API token (hai guard: admin / api) |
| **Laravel Reverb** | WebSocket server tự host — thông báo real-time |
| **Spatie Laravel Permission** | Quản lý phân quyền theo vai trò (RBAC) |

### Frontend (`e-learning-frontend/`) — [Xem chi tiết](./e-learning-frontend/README.md)

| Công nghệ | Mô tả |
|-----------|--------|
| **Vue.js 3 + Vite + TypeScript** | SPA framework chính — Composition API (`<script setup>`) |
| **Vue Router 4 + Pinia** | Routing (3 guards: admin/teacher/student) & State management |
| **Tailwind CSS v3** | Styling framework (hỗ trợ Dark Mode) |
| **TailAdmin Vue** | Admin dashboard UI template |
| **Flowbite Vue** | Client-side UI components |
| **VeeValidate + Zod** | Form validation |
| **Axios** | HTTP client kết nối Backend API |
| **Laravel Echo + pusher-js** | WebSocket client — thông báo real-time qua Reverb |
| **Video.js** | Video player với watermark bảo vệ nội dung |
| **@vueup/vue-quill** | Rich text editor (Giảng viên soạn bài viết) |
| **vue3-apexcharts** | Biểu đồ thống kê Dashboard |

### Tích hợp & Dịch vụ

| Công nghệ | Mô tả |
|-----------|--------|
| **VNPAY / ZaloPay API** | Cổng thanh toán trực tuyến (IPN webhook, HMAC-SHA512) |
| **Google Gemini 2.5 Flash** | AI sinh câu hỏi trắc nghiệm từ PDF (fallback: Gemini Flash Lite) |
| **Laravel Reverb** | WebSocket server (Pusher protocol) — thông báo real-time |

---

## ⚙️ Cài đặt & Chạy

### Yêu cầu hệ thống

- PHP >= 8.2 + Composer >= 2.x
- Node.js >= 18.x + NPM
- MySQL >= 8.0
- Git

### Backend

```bash
cd e-learning-backend

# Cài đặt dependencies
composer install

# Cấu hình môi trường
cp .env.example .env
php artisan key:generate

# Cấu hình database trong .env
# DB_DATABASE=e_learning
# DB_USERNAME=root
# DB_PASSWORD=your_password

# Migrate & Seed
php artisan migrate --seed
php artisan storage:link

# Khởi chạy (mỗi lệnh chạy ở tab terminal riêng)
php artisan serve                                                          # Tab 1 — API server
php artisan queue:work --queue=default --tries=3                          # Tab 2 — Worker email/payment
php artisan queue:work --queue=ai --timeout=130 --tries=1                 # Tab 3 — Worker AI quiz
php artisan reverb:start                                                   # Tab 4 — WebSocket server (thông báo real-time)
php artisan schedule:work                                                  # Tab 5 — Scheduler (cleanup)
```

> Backend chạy tại: `http://localhost:8000` | WebSocket tại: `ws://localhost:8080`
>
> ⚠️ **Tab 3 (AI worker) là bắt buộc** khi dùng tính năng sinh câu hỏi AI — nếu không bật, job sẽ nằm mãi ở trạng thái `pending`.
>
> ⚠️ **Tab 4 (Reverb) là bắt buộc** để nhận thông báo real-time — Admin và Giảng viên sẽ không nhận được push notification nếu không khởi chạy.

### Frontend

```bash
cd e-learning-frontend

# Cài đặt dependencies
npm install

# Cấu hình môi trường
cp .env.example .env

# Khởi chạy
npm run dev
```

> Frontend chạy tại: `http://localhost:5173`
> ⚠️ Backend API phải đang chạy tại `http://localhost:8000`

---

## 🔒 Bảo mật

- **CSRF Protection** — Chống tấn công Cross-Site Request Forgery
- **SQL Injection Prevention** — Eloquent ORM & Query Builder
- **XSS Protection** — Blade Template tự động escape output
- **Authentication & Authorization** — Laravel Sanctum + Spatie Permission (RBAC)
- **Anti-Privilege Escalation** — Ngăn chặn Admin thường can thiệp vào tài khoản Super Admin hoặc tự nâng quyền
- **Role-Scoping** — Tự động giới hạn phạm vi truy cập dữ liệu người dùng theo chức năng quản lý

---

## 📦 Scripts

### Backend
```bash
php artisan serve                                                          # Khởi chạy API server
php artisan queue:work --queue=default --tries=3                          # Worker email/payment
php artisan queue:work --queue=ai --timeout=130 --tries=1                 # Worker AI quiz generation
php artisan reverb:start                                                   # WebSocket server (real-time notifications)
php artisan schedule:work                                                  # Scheduler (cleanup jobs/files)
php artisan module:migrate Notifications                                   # Migrate module thông báo
php artisan migrate --seed                                                 # Migrate toàn bộ + seed
php artisan test                                                           # Chạy feature tests (225 cases)
```

> 💡 **Shortcut:** Chạy `./start.sh` (Linux/macOS/WSL) từ thư mục gốc để mở tất cả 6 tiến trình cùng lúc trong các cửa sổ terminal riêng.

### Frontend
```bash
npm run dev               # Dev server
npm run build             # Build production
npm run lint              # Kiểm tra code style
```

---

## 📌 Phạm vi & Giới hạn

**✅ Bao gồm:**
- Quản lý khóa học VOD, phân quyền đa vai trò
- Giỏ hàng, thanh toán trực tuyến (VNPAY/MoMo)
- AI Auto-Quiz từ tài liệu văn bản

**❌ Không bao gồm:**
- Livestream / gọi video trực tiếp
- Hệ thống thi trắc nghiệm thời gian thực quy mô lớn
- AI phân tích dữ liệu học tập nâng cao

---

## 👤 Tác giả

**Phan Văn Thành**
- 📧 Email: phvanthanh06@gmail.com
- 📱 Phone: 0327461459
- 🎓 Sinh viên năm 4 — Khoa Khoa học Máy tính, Đại học Duy Tân

---

## 📄 License

```
Academic Use License

Copyright (c) 2026 Phan Van Thanh
Duy Tan University — School of Computer Science
Software Engineering — Graduation Thesis
```

Dự án được phát triển với mục đích **học thuật** — Đồ án tốt nghiệp Đại học Duy Tân, 2026.

Xem chi tiết tại [LICENSE](LICENSE).
