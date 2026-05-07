# Biến môi trường (.env)

## Backend — `e-learning-backend/.env`

### Cấu hình ứng dụng

```env
APP_NAME="E-Learning Marketplace"
APP_ENV=local                        # local | production
APP_KEY=                             # sinh bằng: php artisan key:generate
APP_DEBUG=true                       # false ở production
APP_URL=http://localhost:8000
APP_FRONTEND_URL=http://localhost:5173   # dùng trong email verification link
```

### Database

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=e_learning
DB_USERNAME=root
DB_PASSWORD=
```

### Queue

```env
QUEUE_CONNECTION=database   # jobs lưu trong bảng 'jobs' của MySQL
```

> Queue worker cần chạy riêng: `php artisan queue:work --queue=ai,default`

### Cache và Session

```env
CACHE_STORE=database        # hoặc redis nếu có
SESSION_DRIVER=database
SESSION_LIFETIME=120
```

### Mail (Email verification, Reset password)

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io      # dùng Mailtrap cho dev
MAIL_PORT=2525
MAIL_USERNAME=your_mailtrap_user
MAIL_PASSWORD=your_mailtrap_pass
MAIL_FROM_ADDRESS="noreply@elearning.com"
MAIL_FROM_NAME="${APP_NAME}"
```

> Cho production: thay bằng SMTP thực (Gmail, SendGrid, Mailgun...).

### Bảo mật

```env
BCRYPT_ROUNDS=12   # số rounds bcrypt cho password hashing
```

### AWS S3 (tùy chọn — dùng cho upload video lớn)

```env
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=ap-southeast-1
AWS_BUCKET=your-bucket-name
AWS_USE_PATH_STYLE_ENDPOINT=false

FILESYSTEM_DISK=local   # đổi sang 's3' nếu dùng S3
```

> Nếu không cấu hình S3, hệ thống vẫn hoạt động với local upload (lưu trong `storage/app/public/`).

### VNPay (Thanh toán)

```env
VNPAY_TMN_CODE=your_tmn_code
VNPAY_HASH_SECRET=your_hash_secret
VNPAY_URL=https://sandbox.vnpayment.vn/paymentv2/vpcpay.html
VNPAY_RETURN_URL=http://localhost:8000/api/v1/payment/vnpay/return
VNPAY_FRONTEND_RESULT_URL=http://localhost:5173/payment/result
```

> Môi trường sandbox: `sandbox.vnpayment.vn`. Production: `vnpayment.vn`.

### AI Quiz Generation (Google Gemini)

```env
GEMINI_API_KEY=your_gemini_api_key
```

> Lấy API key tại: [Google AI Studio](https://aistudio.google.com/). Nếu thiếu key, tính năng sinh quiz bằng AI sẽ báo lỗi khi gọi.

### Logging

```env
LOG_CHANNEL=stack
LOG_LEVEL=debug     # debug | info | warning | error
```

---

## Frontend — `e-learning-frontend/.env`

```env
VITE_APP_NAME="E-Learning Marketplace"
VITE_API_URL=/api/v1
VITE_FRONTEND_URL=http://localhost:5173
```

| Biến | Mô tả |
|------|-------|
| `VITE_API_URL` | Base URL cho Axios. `/api/v1` được proxy qua Vite đến `localhost:8000` |
| `VITE_FRONTEND_URL` | URL frontend, dùng khi cần build absolute URL phía client |

> Tất cả biến bắt đầu `VITE_` mới được expose ra browser. Không đặt secret vào file `.env` frontend.

---

## Bảng tổng hợp — Giá trị theo môi trường

| Biến | Local Dev | Production |
|------|-----------|-----------|
| `APP_ENV` | `local` | `production` |
| `APP_DEBUG` | `true` | `false` |
| `APP_URL` | `http://localhost:8000` | `https://api.yourdomain.com` |
| `APP_FRONTEND_URL` | `http://localhost:5173` | `https://yourdomain.com` |
| `QUEUE_CONNECTION` | `database` | `database` hoặc `redis` |
| `CACHE_STORE` | `database` | `redis` |
| `MAIL_MAILER` | `smtp` (Mailtrap) | `smtp` (provider thực) |
| `VNPAY_URL` | sandbox URL | production URL |
| `FILESYSTEM_DISK` | `local` | `s3` |
| `LOG_LEVEL` | `debug` | `error` |
| `BCRYPT_ROUNDS` | `10` | `12` |
| CORS `allowed_origins` | `localhost:5173` | domain production |
