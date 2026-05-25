# E-Learning Marketplace Platform — Backend

> **Đồ án tốt nghiệp** — Trường Khoa học Máy tính, Khoa Công nghệ Thông tin
> Ngành: Công nghệ Phần mềm | Đại học Duy Tân

---

## Thông tin dự án

| Thông tin | Chi tiết |
|-----------|----------|
| **Tên đề tài** | Xây dựng hệ thống nền tảng học tập trực tuyến (E-learning Marketplace) tích hợp thanh toán trực tuyến |
| **Sinh viên** | Phan Văn Thành — MSSV: 28211102974 |
| **GVHD** | Trịnh Sử Trường Thi |
| **Thời gian** | 12/03/2026 – 15/05/2026 |

---

## Giới thiệu dự án

**E-Learning Marketplace** là một nền tảng giáo dục số toàn diện, đóng vai trò như một khu chợ trung gian kết nối **giảng viên** và **người học**. Hệ thống cho phép giảng viên đóng gói và phân phối khóa học dạng video (VOD), đồng thời giúp học viên học tập mọi lúc mọi nơi.

> **Lưu ý:** Repository này là phần **Backend**. Frontend đang được phát triển riêng.

### Tính năng đã triển khai

- **Quản lý khóa học Video (VOD)** — upload, tổ chức và phân phối bài giảng video
- **Hệ thống giỏ hàng & thanh toán** — tích hợp VNPAY/ZaloPay, xử lý giao dịch an toàn qua IPN webhook
- **AI Auto-Quiz (Gemini 2.5 Flash)** — tự động sinh câu hỏi trắc nghiệm từ PDF (fallback Gemini Flash Lite, lọc câu vi phạm, hỗ trợ chọn PDF theo chương)
- **Thông báo real-time (Laravel Reverb)** — WebSocket tự host, đẩy thông báo tức thời đến Admin và Giảng viên theo các sự kiện: đăng ký khóa học, yêu cầu rút tiền, duyệt khóa học, bình luận mới
- **Tự động hủy đơn hàng** — đơn "Chờ thanh toán" quá hạn tự động chuyển "Đã hủy" và gửi email thông báo
- **Dashboard thống kê** — theo dõi doanh thu, tiến độ học tập
- **Phân quyền đa vai trò** — Admin / Giảng viên / Học viên (RBAC, anti-privilege escalation)
- **Mã giảm giá (Coupon)** — tỉ lệ %, số tiền cố định, giới hạn lượt dùng, kiểm tra race condition

---

## Công nghệ sử dụng

### Backend
| Công nghệ | Mô tả |
|-----------|-------|
| **PHP 8.2+ / Laravel 12** | Framework chính — kiến trúc Modular (Nwidart Modules) |
| **MySQL 8.0** | Cơ sở dữ liệu quan hệ |
| **Laravel Sanctum** | Xác thực API token (hai guard riêng: `admin` / `api`) |
| **Laravel Reverb** | WebSocket server tự host (Pusher protocol) — thông báo real-time |
| **Laravel Horizon** | Dashboard giám sát queue — 3 supervisor (`default`, `ai`, `hls`); truy cập tại `/horizon` |
| **Redis** | Backend cho queue driver (`predis/predis`) — thay thế database queue |
| **Spatie Laravel Permission** | Quản lý phân quyền theo vai trò (RBAC) |

### Tích hợp & Dịch vụ
| Công nghệ | Mô tả |
|-----------|-------|
| **VNPAY / ZaloPay API** | Cổng thanh toán trực tuyến (IPN webhook, HMAC-SHA512) |
| **Google Gemini 2.5 Flash** | AI sinh câu hỏi trắc nghiệm từ PDF (fallback: Gemini 2.5 Flash Lite) |
| **Laravel Storage (Local)** | Lưu trữ file/video bài giảng trên server cục bộ (`storage/app/public`) |
| **pdftotext / gzuncompress** | Trích xuất văn bản từ tài liệu PDF |

### Quy trình phát triển
- Phương pháp **Agile/Scrum** — chia 3 Sprint

---

## Yêu cầu hệ thống

- PHP >= 8.2
- Composer >= 2.x
- Node.js >= 18.x & NPM
- MySQL >= 8.0
- Redis >= 7.0 (WSL Ubuntu: `sudo apt-get install redis-server`)
- Git
- `pdftotext` (poppler-utils) — tùy chọn, cải thiện độ chính xác trích xuất PDF cho AI Quiz

---

## Cài đặt

**1. Clone repository**
```bash
git clone git@github.com:ahryxx0602/e-learning-pj.git
cd e-learning-backend
```

**2. Cài đặt dependencies PHP**
```bash
composer install
```

**3. Cài đặt dependencies JavaScript**
```bash
npm install
npm run build
```

**4. Cấu hình môi trường**
```bash
cp .env.example .env
php artisan key:generate
```

**5. Cấu hình file `.env`**
```env
# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=elearning_db
DB_USERNAME=root
DB_PASSWORD=your_password
```

**6. Migrate & Seed database**
```bash
php artisan migrate --seed
```

**7. Tạo symbolic link cho storage**
```bash
php artisan storage:link
```

**8. Khởi chạy ứng dụng**
```bash
# Terminal 1 — Redis (một lần mỗi phiên WSL)
sudo service redis-server start

# Terminal 2 — Horizon: queue workers + dashboard (thay thế queue:work)
php artisan horizon

# Terminal 3 — API server
php artisan serve

# Terminal 4 — Reverb WebSocket server (bắt buộc để nhận thông báo real-time)
php artisan reverb:start

# Terminal 5 — Scheduler: tự động hủy đơn hàng quá hạn, cleanup file orphan
php artisan schedule:work
```

> Truy cập API: `http://localhost:8000`
> WebSocket: `ws://localhost:8080`
> **Horizon dashboard: `http://localhost:8000/horizon`** (cần đăng nhập admin trước)
>
> ⚠️ **Redis phải chạy trước khi khởi động Horizon.** Kiểm tra bằng `redis-cli ping` — phải trả về `PONG`.
>
> 💡 Trên Windows/WSL: chạy `./start.sh` từ thư mục gốc để mở tất cả 5 tiến trình cùng lúc.

**Cấu hình `.env` cần thiết:**
```env
# Database
DB_DATABASE=e_learning

# Broadcasting (Real-time notifications)
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=<generate_random>
REVERB_APP_KEY=<generate_random>
REVERB_APP_SECRET=<generate_random>
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http

# AI Quiz
GEMINI_API_KEY=<your_google_ai_key>

# Payment
VNPAY_TMN_CODE=<your_code>
VNPAY_HASH_SECRET=<your_secret>
```

---

## Cấu trúc dự án

```
e-learning-backend/
├── app/                            # Core application (base classes)
│   ├── Http/Controllers/           # Base Controller
│   ├── Models/                     # User model
│   └── Providers/                  # AppServiceProvider
│
├── Modules/                        # Feature modules (Nwidart Laravel Modules)
│   └── Auth/                       # Ví dụ Module Auth (Xác thực, Email)
│   │   ├── app/
│   │   │   ├── Events/             # Event classes (vd: UserRegistered)
│   │   │   ├── Listeners/          # Event Listeners (vd: SendWelcomeEmail)
│   │   │   ├── Notifications/      # Email/Database Notifications
│   │   │   ├── Http/Controllers/   # Controllers của module
│   │   │   └── Providers/          # Service providers
│   │   ├── config/                 # Cấu hình module
│   │   ├── database/
│   │   │   ├── migrations/
│   │   │   └── seeders/            # RoleAndAdminSeeder...
│   │   ├── resources/
│   │   │   ├── assets/             # JS, SCSS
│   │   │   └── views/              # Blade templates
│   │   ├── routes/
│   │   │   ├── api.php
│   │   │   └── web.php
│   │   └── module.json
│   └── .../
│   
├── config/                         # Cấu hình ứng dụng
│   ├── app.php
│   ├── auth.php
│   ├── permission.php              # Spatie permission config
│   ├── sanctum.php                 # API token auth config
│   └── ...
│
├── database/
│   ├── migrations/                 # Schema: users, cache, jobs, tokens, permissions
│   ├── factories/
│   └── seeders/
│
├── routes/
│   ├── api.php                     # API routes
│   ├── web.php                     # Web routes
│   └── console.php
│
├── resources/
│   ├── css/
│   ├── js/
│   └── views/                      # Blade templates chung
│
├── public/                         # Entry point (index.php)
├── storage/                        # Logs, cache, uploads
├── tests/
│   ├── Feature/                    # Kiểm thử tính năng (API, tích hợp)
│   └── Unit/                       # Kiểm thử đơn vị (logic nhỏ, helper)
│
├── modules_statuses.json           # Trạng thái các module (Auth, Course, Lessons, Quiz, Notifications, ...)
├── routes/
│   └── channels.php               # Khai báo private channel Reverb (admin.{id}, teacher.{id})
├── composer.json
├── package.json
├── vite.config.js
└── .env.example
```

---

## Bảo mật

Hệ thống sử dụng các cơ chế bảo mật mặc định của Laravel:

- **CSRF Protection** — chống tấn công Cross-Site Request Forgery
- **SQL Injection Prevention** — thông qua Eloquent ORM & Query Builder
- **XSS Protection** — Blade Template tự động escape output
- **Authentication & Authorization** — Laravel Sanctum + Spatie Permission (RBAC)
- **Anti-Privilege Escalation** — Chặn Admin can thiệp tài khoản Super Admin hoặc tự nâng quyền trái phép
- **Role-Scoping** — Giới hạn truy vấn người dùng (chỉ được quản lý Student/Teacher) cho các tài khoản không phải Super Admin
- **Automated Testing** — Hệ thống Feature Tests phủ kín các module quan trọng (225/225 cases passed)

---

## Phạm vi & Giới hạn

**Bao gồm:**
- Quản lý khóa học VOD, phân quyền đa vai trò
- Giỏ hàng, thanh toán trực tuyến (VNPAY/MoMo)
- AI Auto-Quiz từ tài liệu văn bản

**Không bao gồm:**
- Livestream / gọi video trực tiếp
- Hệ thống thi trắc nghiệm thời gian thực quy mô lớn
- AI phân tích dữ liệu học tập nâng cao

---

## Tác giả

**Phan Văn Thành**
- Email: phvanthanh06@gmail.com
- Phone: 0327461459
- Sinh viên năm 4 — Khoa học Máy tính, Đại học Duy Tân

---

## License

Dự án phát hành theo giấy phép [MIT](LICENSE).

Được thực hiện với mục đích học thuật — Đồ án tốt nghiệp Đại học Duy Tân, 2026.
