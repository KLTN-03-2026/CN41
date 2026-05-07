# Cài đặt môi trường phát triển (Local Dev)

## Yêu cầu hệ thống

| Công cụ | Phiên bản tối thiểu |
|---------|-------------------|
| PHP | 8.2+ |
| Composer | 2.x |
| Node.js | 20.19.0+ hoặc 22.12.0+ |
| MySQL | 8.x |
| Git | 2.x |

---

## 1. Clone repository

```bash
git clone https://github.com/KLTN-03-2026/CN41.git e-learning
cd e-learning
```

---

## 2. Cài đặt Backend

```bash
cd e-learning-backend

# Cài dependencies PHP
composer install

# Tạo file .env
cp .env.example .env

# Sinh APP_KEY
php artisan key:generate
```

Chỉnh sửa `.env` với thông tin database và các key cần thiết (xem [environment.md](environment.md)):

```env
APP_URL=http://localhost:8000
APP_FRONTEND_URL=http://localhost:5173

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=e_learning
DB_USERNAME=root
DB_PASSWORD=your_password

QUEUE_CONNECTION=database
MAIL_MAILER=smtp
# ... (xem environment.md để biết đầy đủ)
```

Tạo database và chạy migrate + seed:

```bash
# Tạo database (nếu chưa có)
mysql -u root -p -e "CREATE DATABASE e_learning CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Migrate + seed dữ liệu mẫu
php artisan migrate --seed

# Tạo symlink storage
php artisan storage:link
```

---

## 3. Cài đặt Frontend

```bash
cd ../e-learning-frontend

# Cài dependencies Node
npm install

# Tạo file .env
cp .env.example .env
```

File `.env` mặc định đã đúng cho local dev:

```env
VITE_APP_NAME="E-Learning Marketplace"
VITE_API_URL=/api/v1
VITE_FRONTEND_URL=http://localhost:5173
```

> Vite proxy `/api` và `/storage` về `http://localhost:8000` (cấu hình trong `vite.config.js`) nên không cần thay đổi gì thêm.

---

## 4. Cài đặt Git hooks (pre-commit)

Từ **thư mục root** của repo:

```bash
cd ..   # về e-learning/
npm install   # cài husky + lint-staged
```

Từ đây, mỗi lần `git commit` sẽ tự động chạy:
- `oxlint` → `eslint` → `prettier` (cho file `.vue`, `.ts`, `.js`)
- `pint` (cho file `.php`)
- Validate commit message format

---

## 5. Khởi động

Mở **3 terminal**:

**Terminal 1 — Backend API:**
```bash
cd e-learning-backend
php artisan serve
# → http://localhost:8000
```

**Terminal 2 — Queue Worker** (bắt buộc cho AI quiz generation):
```bash
cd e-learning-backend
php artisan queue:work --queue=ai,default
```

**Terminal 3 — Frontend:**
```bash
cd e-learning-frontend
npm run dev
# → http://localhost:5173
```

> **Shortcut:** Từ `e-learning-backend/`, chạy `composer dev` để khởi động tất cả cùng lúc (dùng `concurrently`):
> ```bash
> cd e-learning-backend
> composer dev
> # Chạy song song: php artisan serve + queue:listen + pail + npm run dev
> ```

---

## 6. Tài khoản test (sau khi seed)

| Role | Email | Password |
|------|-------|----------|
| Super Admin | superadmin@elearning.com | password |
| Admin | admin@elearning.com | password |
| Student | student@elearning.com | password |

---

## 7. Reset database

```bash
cd e-learning-backend
php artisan migrate:fresh --seed && php artisan storage:link
```

> **Cảnh báo:** Lệnh này xóa toàn bộ dữ liệu. Chỉ dùng trong môi trường dev.

---

## 8. Chạy tests

```bash
cd e-learning-backend

# Chạy tất cả tests
php artisan test

# Chạy parallel (nhanh hơn)
php artisan test --parallel

# Chạy một test cụ thể
php artisan test --filter=CategoryTest
php artisan test tests/Feature/Admin/CourseTest.php
```

Tests dùng SQLite in-memory (`DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:` trong `phpunit.xml`) — không ảnh hưởng database dev.

---

## 9. Xem logs

```bash
# Laravel logs
php artisan pail          # real-time log viewer (terminal)

# Hoặc xem qua web
# → http://localhost:8000/log-viewer  (opcodesio/log-viewer)

# Queue failed jobs
php artisan queue:failed
php artisan queue:retry all
```

---

## 10. Lệnh thường dùng

```bash
# Xóa cache
php artisan config:clear && php artisan cache:clear && php artisan route:clear

# Xem tất cả routes API
php artisan route:list --path=api

# Tạo module mới
php artisan module:make <ModuleName>

# Pint — format PHP code
./vendor/bin/pint

# Frontend lint
cd e-learning-frontend
npm run lint
```
