# Tích hợp thanh toán VNPay

## 1. Tổng quan

Hệ thống tích hợp cổng thanh toán **VNPay** để xử lý mua khóa học trả phí. Sau khi thanh toán thành công, học viên được tự động enroll vào tất cả khóa học trong đơn.

Hỗ trợ:
- Tạo đơn hàng + tạo URL redirect VNPay
- Nhận IPN callback (server-to-server) từ VNPay
- Xử lý return URL (redirect sau khi thanh toán)
- Thanh toán lại đơn `pending` hoặc `failed`
- Áp mã giảm giá khi checkout

---

## 2. Cấu trúc dữ liệu

### Bảng `orders`

| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `id` | bigint | PK |
| `order_code` | varchar unique | Mã đơn hàng (dùng để tra cứu + VNPay ref) |
| `student_id` | bigint FK | Học viên đặt hàng |
| `subtotal` | decimal(10,2) | Tổng trước giảm giá |
| `discount_amount` | decimal(10,2) | Số tiền được giảm |
| `total_amount` | decimal(10,2) | Số tiền thực thanh toán |
| `coupon_code` | varchar nullable | Mã coupon đã dùng |
| `status` | enum | `pending` / `paid` / `failed` / `refunded` |
| `payment_method` | varchar | `vnpay` / `free` |
| `paid_at` | timestamp nullable | Thời điểm thanh toán thành công |

### Bảng `order_items`

| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `order_id` | bigint FK | |
| `course_id` | bigint FK | |
| `price` | decimal(10,2) | Giá tại thời điểm mua (snapshot) |

### Bảng `transactions`

| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `order_id` | bigint FK | |
| `vnpay_txn_no` | varchar | Mã giao dịch VNPay |
| `amount` | decimal(10,2) | Số tiền |
| `status` | varchar | `success` / `failed` |
| `vnpay_response` | json | Toàn bộ response từ VNPay |

---

## 3. API Endpoints

### Student

| Method | Endpoint | Middleware | Mô tả |
|--------|----------|-----------|-------|
| POST | `/api/v1/orders` | auth:api + email.verified | Tạo đơn hàng |
| GET | `/api/v1/my-orders` | auth:api + email.verified | Lịch sử đơn |
| GET | `/api/v1/my-orders/{orderCode}` | auth:api + email.verified | Chi tiết đơn |
| POST | `/api/v1/orders/{orderCode}/retry-payment` | auth:api + email.verified | Thanh toán lại |

### VNPay Callbacks (public — không cần auth)

| Method | Endpoint | Mô tả |
|--------|----------|-------|
| GET | `/api/v1/payment/vnpay/return` | VNPay redirect user về sau thanh toán |
| GET | `/api/v1/payment/vnpay/ipn` | VNPay gọi server-to-server (webhook) |

### Admin

| Method | Endpoint | Mô tả |
|--------|----------|-------|
| GET | `/api/v1/admin/orders` | Danh sách đơn hàng |
| GET | `/api/v1/admin/orders/{id}` | Chi tiết đơn |
| PATCH | `/api/v1/admin/orders/{id}/status` | Cập nhật trạng thái |
| GET | `/api/v1/admin/orders/stats/revenue` | Thống kê doanh thu |
| DELETE | `/api/v1/admin/orders/{id}` | Soft delete đơn |

---

## 4. Luồng thanh toán đầy đủ

```
[Bước 1] Student checkout
  │
  │  POST /api/v1/orders
  │  Body: { course_ids: [1, 2], coupon_code: "SALE20" }
  ▼
OrderController::store()
  │
  ├── Validate: course_ids (exists, not enrolled)
  │
  ├── DB::transaction():
  │     ├── Tính subtotal = sum(sale_price ?? price)
  │     │
  │     ├── [Có coupon]
  │     │     ├── Lock coupon row: Coupon::lockForUpdate()->find(...)
  │     │     ├── Validate: active, not expired, usage_limit chưa đạt
  │     │     ├── Tính discount_amount
  │     │     └── coupon.used_count++ (atomic increment)
  │     │
  │     ├── Tạo Order: { order_code, student_id, subtotal, discount, total, status: pending }
  │     └── Tạo OrderItems cho mỗi course
  │
  ├── [total_amount == 0] → Enroll ngay, return { payment_url: null, status: 'paid' }
  │
  └── [total_amount > 0]
        ├── Tạo VNPay payment URL (HMAC-SHA512 signature)
        └── Return { payment_url: "https://sandbox.vnpayment.vn/...", order_code }

[Bước 2] Student redirect đến VNPay, thanh toán

[Bước 3a] VNPay IPN — server-to-server
  │
  │  GET /api/v1/payment/vnpay/ipn?vnp_TxnRef=...&vnp_SecureHash=...
  ▼
VnpayController::ipn()
  │
  ├── Verify HMAC-SHA512 signature
  │     └── [Invalid] → return { RspCode: "97", Message: "Invalid Checksum" }
  │
  ├── Tìm Order theo vnp_TxnRef (order_code)
  │     └── [Không tìm thấy] → return { RspCode: "01", Message: "Order not found" }
  │
  ├── [Order đã paid] → return { RspCode: "02", Message: "Order already confirmed" }
  │
  ├── DB::transaction():
  │     ├── [vnp_ResponseCode == "00"] (thành công)
  │     │     ├── Order: status = 'paid', paid_at = now()
  │     │     ├── Tạo Transaction (status: success)
  │     │     └── Enroll student: students_course.insert([course_id, student_id, enrolled_at])
  │     │
  │     └── [Khác] (thất bại)
  │           ├── Order: status = 'failed'
  │           └── Tạo Transaction (status: failed)
  │
  └── Return { RspCode: "00", Message: "Confirm Success" }   ← ACK cho VNPay

[Bước 3b] VNPay return — redirect user
  │
  │  GET /api/v1/payment/vnpay/return?vnp_TxnRef=...&vnp_ResponseCode=...
  ▼
VnpayController::return()
  │
  ├── Gọi handleIpn() (xử lý giống IPN nếu IPN chưa chạy)
  └── Redirect → /payment/result?status=success&order_code=...
                           hoặc ?status=failed&order_code=...

[Bước 4] Frontend PaymentResultPage
  │
  └── Hiển thị kết quả, nút "Đến khóa học của tôi"
```

---

## 5. Mã giảm giá (Coupon) khi checkout

```
Coupon validation:
  ├── is_active = 1
  ├── starts_at <= now() <= expires_at
  ├── used_count < usage_limit  (hoặc usage_limit = null = unlimited)
  └── Loại giảm giá:
        discount_type = 'percent' → discount = subtotal * percent / 100
        discount_type = 'fixed'   → discount = fixed_amount
        Clamp: discount <= subtotal (không âm)
```

**Race condition protection:** Dùng `lockForUpdate()` (SELECT ... FOR UPDATE) khi đọc coupon trong transaction, đảm bảo không có 2 request đồng thời vượt `usage_limit`.

---

## 6. Thanh toán lại (Retry Payment)

```
POST /api/v1/orders/{orderCode}/retry-payment
  │
  ├── Tìm order của student (chỉ chủ đơn)
  ├── [status != 'pending' && status != 'failed'] → 422 "Không thể thanh toán lại"
  ├── Reset order: status = 'pending'
  └── Tạo VNPay URL mới → return { payment_url }
```

---

## 7. Cấu hình môi trường VNPay

```env
VNPAY_TMN_CODE=your_tmn_code
VNPAY_HASH_SECRET=your_hash_secret
VNPAY_URL=https://sandbox.vnpayment.vn/paymentv2/vpcpay.html
VNPAY_RETURN_URL=http://localhost:8000/api/v1/payment/vnpay/return
```

Môi trường production thay `sandbox.vnpayment.vn` → `vnpayment.vn`.

---

## 8. Ví dụ response

### POST `/api/v1/orders` — Tạo đơn thành công

```json
{
  "success": true,
  "message": "Tạo đơn hàng thành công",
  "data": {
    "order_code": "ORD-1746612345",
    "total_amount": 450000,
    "payment_url": "https://sandbox.vnpayment.vn/paymentv2/vpcpay.html?..."
  }
}
```

### GET `/api/v1/my-orders/{orderCode}` — Chi tiết đơn

```json
{
  "success": true,
  "data": {
    "order_code": "ORD-1746612345",
    "status": "paid",
    "subtotal": 500000,
    "discount_amount": 50000,
    "total_amount": 450000,
    "coupon_code": "SALE10",
    "paid_at": "2026-05-07T10:30:00Z",
    "items": [
      { "course_id": 3, "name": "Laravel từ cơ bản", "price": 500000 }
    ]
  }
}
```
