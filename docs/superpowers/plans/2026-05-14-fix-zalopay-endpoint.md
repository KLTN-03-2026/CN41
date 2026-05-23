# Fix ZaloPay Sandbox Endpoint Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Cập nhật ZaloPay sandbox endpoint từ `sb.zalopay.vn` (deprecated, NXDOMAIN) sang `sandbox.zalopay.com.vn` để checkout ZaloPay hoạt động.

**Architecture:** ZaloPay đã migrate domain sandbox. Chỉ cần cập nhật biến môi trường `ZALOPAY_ENDPOINT` trong `.env` và `.env.example`. Config file fallback đã được cập nhật ở commit trước. Sau khi đổi URL, test bằng cách gọi `createOrder` với `payment_method=zalopay`.

**Tech Stack:** Laravel 12, ZaloPay Sandbox API (`sandbox.zalopay.com.vn`), PHP `Http::post()`

---

## Root Cause Analysis

| Vấn đề | Chi tiết |
|--------|---------|
| Domain cũ | `sb.zalopay.vn` — NXDOMAIN (không có bất kỳ DNS record nào: A, AAAA, CNAME, MX) |
| Domain mới | `sandbox.zalopay.com.vn` — resolves to `118.102.5.66`, HTTP 200 ✓ |
| Tại sao `.env` chưa được sửa | Claude Code không có quyền ghi vào `.env` (permission restriction) |
| Config fallback | `Modules/Payment/config/zalopay.php` đã dùng URL mới làm default, nhưng `.env` override nó bằng URL cũ |

---

## Files sẽ thay đổi

| File | Thay đổi |
|------|---------|
| `e-learning-backend/.env` | Cập nhật `ZALOPAY_ENDPOINT` — **thực hiện thủ công bởi user** |
| `e-learning-backend/.env.example` | Thêm ZaloPay entries với URL mới |

---

## Task 1: Cập nhật `.env` (thực hiện thủ công)

> ⚠️ File `.env` bị restricted — Claude Code không thể ghi. User phải thực hiện bước này.

**Files:** Modify `e-learning-backend/.env`

- [ ] **Step 1: Mở `.env` và thay dòng ZALOPAY_ENDPOINT**

Tìm dòng:
```
ZALOPAY_ENDPOINT=https://sb.zalopay.vn/v001/tpe/createorder
```

Thay bằng:
```
ZALOPAY_ENDPOINT=https://sandbox.zalopay.com.vn/v001/tpe/createorder
```

Hoặc chạy lệnh này trong terminal (WSL):
```bash
cd /home/vanthanh/DATN/e-learning/e-learning-backend
sed -i 's|https://sb.zalopay.vn/v001/tpe/createorder|https://sandbox.zalopay.com.vn/v001/tpe/createorder|' .env
```

- [ ] **Step 2: Xác nhận `.env` đã được cập nhật**

```bash
grep ZALOPAY_ENDPOINT /home/vanthanh/DATN/e-learning/e-learning-backend/.env
```

Expected output:
```
ZALOPAY_ENDPOINT=https://sandbox.zalopay.com.vn/v001/tpe/createorder
```

---

## Task 2: Cập nhật `.env.example`

**Files:** Modify `e-learning-backend/.env.example`

- [ ] **Step 1: Thêm ZaloPay entries vào cuối `.env.example`**

Thêm vào cuối file:
```
# ZaloPay Sandbox
ZALOPAY_APP_ID=2553
ZALOPAY_KEY1=
ZALOPAY_KEY2=
ZALOPAY_ENDPOINT=https://sandbox.zalopay.com.vn/v001/tpe/createorder
ZALOPAY_CALLBACK_URL=http://localhost:8000/api/v1/payment/zalopay/callback
ZALOPAY_REDIRECT_URL=http://localhost:8000/api/v1/payment/zalopay/redirect
```

- [ ] **Step 2: Verify file**

```bash
tail -8 /home/vanthanh/DATN/e-learning/e-learning-backend/.env.example
```

---

## Task 3: Clear config cache và verify kết nối

**Files:** Không có file thay đổi, chỉ chạy artisan commands.

- [ ] **Step 1: Clear config cache**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning/e-learning-backend && php artisan config:clear 2>&1" | cat
```

Expected:
```
INFO  Configuration cache cleared successfully.
```

- [ ] **Step 2: Xác nhận runtime config đúng URL mới**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning/e-learning-backend && php artisan tinker --execute=\"echo config('zalopay.endpoint');\" 2>&1" | cat
```

Expected output:
```
https://sandbox.zalopay.com.vn/v001/tpe/createorder
```

- [ ] **Step 3: Test HTTP connectivity từ PHP**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning/e-learning-backend && php artisan tinker --execute=\"
use Illuminate\Support\Facades\Http;
try {
    \\\$r = Http::timeout(10)->post(config('zalopay.endpoint'), ['test' => 1]);
    echo 'HTTP ' . \\\$r->status();
} catch (\Illuminate\Http\Client\ConnectionException \\\$e) {
    echo 'CONNECTION FAILED: ' . \\\$e->getMessage();
}
\" 2>&1" | cat
```

Expected output (endpoint đang hoạt động, trả lỗi business chứ không phải DNS):
```
HTTP 200
```

---

## Task 4: Test end-to-end ZaloPay checkout

**Files:** Không có file thay đổi.

- [ ] **Step 1: Chạy ZalopayCallbackTest để đảm bảo callback flow không bị ảnh hưởng**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning/e-learning-backend && php artisan test tests/Feature/Payment/ZalopayCallbackTest.php 2>&1" | cat
```

Expected:
```
PASS  Tests\Feature\Payment\ZalopayCallbackTest
✓ callback success updates order and enrolls student
✓ callback rejects invalid mac
✓ callback idempotent when order already paid
✓ callback returns error when order not found
✓ callback does not double enroll student
```

- [ ] **Step 2: Manual test — Checkout ZaloPay trên frontend**

1. Đăng nhập student account: `student@elearning.com` / `password`
2. Vào trang khóa học, thêm vào giỏ hàng
3. Checkout → chọn **ZaloPay**
4. Nhấn "Thanh toán"

Expected: Trình duyệt redirect sang trang ZaloPay sandbox (`sandbox.zalopay.com.vn`), **không còn 503**.

- [ ] **Step 3: Commit**

```bash
git add e-learning-backend/Modules/Payment/config/zalopay.php \
        e-learning-backend/Modules/Payment/app/Services/ZalopayService.php \
        e-learning-backend/Modules/Payment/app/Http/Controllers/OrderController.php \
        e-learning-backend/.env.example
git commit -m "fix(payment): migrate ZaloPay endpoint to sandbox.zalopay.com.vn"
```

---

## Tóm tắt các thay đổi đã thực hiện (trước kế hoạch này)

Các file đã được sửa trong session này (không cần làm lại):

| File | Thay đổi |
|------|---------|
| `ZalopayService.php` | Bắt `ConnectionException`, throw `\Exception` thay vì để 500 crash |
| `OrderController.php` | Wrap `createPaymentUrl()` trong try-catch ở cả `store()` và `retryPayment()` |
| `config/zalopay.php` | Cập nhật fallback URL sang `sandbox.zalopay.com.vn` |

Việc còn lại duy nhất là **Task 1 (user tự sửa `.env`)** → sau đó Task 3 và 4.
