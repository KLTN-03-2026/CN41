# Hướng dẫn Deploy Production

## 1. Yêu cầu server

| Thành phần | Yêu cầu |
|-----------|---------|
| OS | Ubuntu 22.04+ |
| PHP | 8.2+ với extensions: mbstring, xml, curl, gd, zip, pdo_mysql, redis |
| Composer | 2.x |
| Node.js | 20+ (chỉ cần khi build frontend) |
| MySQL | 8.x |
| Nginx hoặc Apache | Web server |
| Supervisor | Quản lý queue worker |
| SSL | Let's Encrypt (Certbot) |

---

## 2. Chuẩn bị server

```bash
# Cài PHP 8.2
sudo apt install php8.2 php8.2-fpm php8.2-mysql php8.2-mbstring \
  php8.2-xml php8.2-curl php8.2-gd php8.2-zip php8.2-redis

# Cài Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Cài Node.js 20
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install nodejs

# Cài Supervisor
sudo apt install supervisor
```

---

## 3. Deploy Backend

```bash
# Clone code
git clone https://github.com/KLTN-03-2026/CN41.git /var/www/e-learning
cd /var/www/e-learning/e-learning-backend

# Cài dependencies (không có dev packages)
composer install --no-dev --optimize-autoloader

# Cấu hình .env
cp .env.example .env
php artisan key:generate
# Chỉnh sửa .env với giá trị production (xem environment.md)

# Tạo database
mysql -u root -p -e "CREATE DATABASE e_learning CHARACTER SET utf8mb4;"

# Migrate (không seed ở production)
php artisan migrate --force

# Seed roles/permissions và admin user đầu tiên
php artisan db:seed --class=RolePermissionSeeder --force
php artisan db:seed --class=AdminUserSeeder --force

# Storage symlink
php artisan storage:link

# Tối ưu (cache config, routes, views)
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

---

## 4. Deploy Frontend

```bash
cd /var/www/e-learning/e-learning-frontend

# Tạo .env production
cat > .env << EOF
VITE_APP_NAME="E-Learning Marketplace"
VITE_API_URL=https://api.yourdomain.com/api/v1
VITE_FRONTEND_URL=https://yourdomain.com
EOF

# Cài dependencies và build
npm install
npm run build
# → Output: dist/
```

---

## 5. Cấu hình Nginx

### Backend API

```nginx
server {
    listen 80;
    server_name api.yourdomain.com;
    root /var/www/e-learning/e-learning-backend/public;
    index index.php;

    # Redirect HTTP → HTTPS
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl;
    server_name api.yourdomain.com;
    root /var/www/e-learning/e-learning-backend/public;
    index index.php;

    ssl_certificate /etc/letsencrypt/live/api.yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/api.yourdomain.com/privkey.pem;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Upload lớn (video)
    client_max_body_size 512M;

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### Frontend SPA

```nginx
server {
    listen 443 ssl;
    server_name yourdomain.com;
    root /var/www/e-learning/e-learning-frontend/dist;
    index index.html;

    ssl_certificate /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;

    # SPA routing — tất cả route đều về index.html
    location / {
        try_files $uri $uri/ /index.html;
    }

    # Cache static assets
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff2)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

---

## 6. Cấu hình Queue Worker (Supervisor)

Tạo file `/etc/supervisor/conf.d/laravel-queue.conf`:

```ini
[program:laravel-queue-default]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/e-learning/e-learning-backend/artisan queue:work database --queue=ai,default --tries=1 --timeout=120 --sleep=3
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/e-learning/e-learning-backend/storage/logs/queue.log
stopwaitsecs=130
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-queue-default:*
sudo supervisorctl status
```

---

## 7. CORS — Cập nhật cho production

Trong `e-learning-backend/config/cors.php`:

```php
'allowed_origins' => [
    'https://yourdomain.com',
],
```

Sau đó clear cache:
```bash
php artisan config:cache
```

---

## 8. Scheduled Tasks (Cron)

Thêm vào crontab của user `www-data`:

```bash
sudo crontab -u www-data -e
```

```cron
* * * * * cd /var/www/e-learning/e-learning-backend && php artisan schedule:run >> /dev/null 2>&1
```

Lệnh này kích hoạt Laravel Scheduler, trong đó có:
- `media:prune-orphans` — chạy mỗi ngày lúc 03:00 (dọn file orphaned)

---

## 9. Quy trình deploy code mới

```bash
cd /var/www/e-learning

# Pull code mới
git pull origin main

# Backend
cd e-learning-backend
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache

# Khởi động lại queue worker để load code mới
sudo supervisorctl restart laravel-queue-default:*

# Frontend
cd ../e-learning-frontend
npm install
npm run build

# Khởi động lại PHP-FPM
sudo systemctl reload php8.2-fpm
```

---

## 10. Checklist trước khi go-live

- [ ] `APP_DEBUG=false` trong `.env`
- [ ] `APP_ENV=production`
- [ ] SSL certificate đã cài (HTTPS)
- [ ] CORS `allowed_origins` đúng domain production
- [ ] VNPay URL đổi sang production (`vnpayment.vn`, không phải `sandbox`)
- [ ] `GEMINI_API_KEY` hợp lệ
- [ ] Mail SMTP cấu hình đúng, test gửi mail xác minh email
- [ ] Queue worker đang chạy (`supervisorctl status`)
- [ ] Cron đã được thêm
- [ ] `storage:link` đã chạy
- [ ] Backup database trước khi migrate
- [ ] `php artisan config:cache && route:cache && view:cache` đã chạy
