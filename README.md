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

- 🎬 **Quản lý khóa học Video (VOD)** — Upload, tổ chức và phân phối bài giảng video
- 💳 **Giỏ hàng & Thanh toán trực tuyến** — Tích hợp VNPAY/MoMo, xử lý giao dịch an toàn
- 🤖 **AI Auto-Quiz (Gemini AI)** — Tự động sinh câu hỏi trắc nghiệm thông minh từ tài liệu PDF (hỗ trợ Tiếng Việt 100%, cơ chế fallback tự động).
- 📊 **Dashboard thống kê** — Theo dõi doanh thu, tiến độ học tập
- 🔐 **Bảo mật & Phân quyền nâng cao** — RBAC chuyên sâu, Role-scoping (Admin chỉ quản lý Student/Teacher), chặn leo thang đặc quyền (Anti-Privilege Escalation)
- 🧪 **Kiểm thử tự động** — Hệ thống Feature Tests đạt độ ổn định cao (126/126 passed)
- 🏷️ **Mã giảm giá (Coupon)** và thông báo real-time

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
| **[e-learning-frontend](./e-learning-frontend)** | SPA giao diện người dùng gồm Admin Dashboard (TailAdmin) và Client UI (Flowbite). Hỗ trợ Dark Mode. | 📄 [Frontend README](./e-learning-frontend/README.md) |

---

## 🛠️ Công nghệ sử dụng

### Backend (`e-learning-backend/`) — [Xem chi tiết](./e-learning-backend/README.md)

| Công nghệ | Mô tả |
|-----------|--------|
| **PHP 8.1+ / Laravel 11** | Framework chính — Kiến trúc Modular (Nwidart Modules) |
| **MySQL 8.0** | Cơ sở dữ liệu quan hệ |
| **Laravel Sanctum** | Xác thực API token |
| **Spatie Laravel Permission** | Quản lý phân quyền theo vai trò (RBAC) |

### Frontend (`e-learning-frontend/`) — [Xem chi tiết](./e-learning-frontend/README.md)

| Công nghệ | Mô tả |
|-----------|--------|
| **Vue.js 3 + Vite + TypeScript** | SPA framework chính |
| **Vue Router 4 + Pinia** | Routing & State management |
| **Tailwind CSS v3** | Styling framework (hỗ trợ Dark Mode) |
| **TailAdmin Vue** | Admin dashboard UI template |
| **Flowbite Vue** | Client-side UI components |
| **VeeValidate + Zod** | Form validation |
| **Axios** | HTTP client kết nối Backend API |
| **Video.js** | Video player cho khóa học |
| **vue3-apexcharts** | Biểu đồ thống kê Dashboard |

### Tích hợp & Dịch vụ

| Công nghệ | Mô tả |
|-----------|--------|
| **VNPAY / MoMo API** | Cổng thanh toán trực tuyến |

---

## ⚙️ Cài đặt & Chạy

### Yêu cầu hệ thống

- PHP >= 8.1 + Composer >= 2.x
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
php artisan queue:work --queue=ai --timeout=${QUEUE_AI_TIMEOUT:-130} --tries=1   # Tab 3 — Worker AI quiz
php artisan schedule:work                                                  # Tab 4 — Scheduler (cleanup)
```

> Backend chạy tại: `http://localhost:8000`
>
> ⚠️ **Tab 3 (AI worker) là bắt buộc** khi dùng tính năng sinh câu hỏi AI — nếu không bật, job sẽ nằm mãi ở trạng thái `pending`.

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
php artisan queue:work --queue=ai --timeout=${QUEUE_AI_TIMEOUT:-130} --tries=1   # Worker AI quiz generation
php artisan schedule:work                                                  # Scheduler (cleanup jobs/files)
php artisan module:migrate Quiz                                            # Migrate module cụ thể
php artisan migrate --seed                                                 # Migrate toàn bộ + seed
php artisan test                                                           # Chạy feature tests
```

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
