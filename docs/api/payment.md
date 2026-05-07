# API Reference — Payment & Orders

Base URL: `http://localhost:8000/api/v1`

---

## Student (auth:api + email.verified)

### POST `/orders`

Tạo đơn hàng và lấy URL thanh toán VNPay.

**Request Body:**
```json
{
  "course_ids": [3, 5],
  "coupon_code": "SALE20"
}
```

**Validation:**
| Field | Rules |
|-------|-------|
| `course_ids` | required, array, min:1 |
| `course_ids.*` | integer, exists:courses,id, published, chưa enroll |
| `coupon_code` | nullable, string, max:50 |

**Response 200 — Khóa có phí:**
```json
{
  "success": true,
  "message": "Tạo đơn hàng thành công",
  "data": {
    "order_code": "ORD-1746612345",
    "subtotal": 1000000,
    "discount_amount": 200000,
    "total_amount": 800000,
    "payment_url": "https://sandbox.vnpayment.vn/paymentv2/vpcpay.html?..."
  }
}
```

**Response 200 — Khóa miễn phí (total = 0):**
```json
{
  "data": {
    "order_code": "ORD-1746612346",
    "total_amount": 0,
    "payment_url": null,
    "status": "paid"
  }
}
```

---

### GET `/my-orders`

**Query params:** `page`, `per_page`, `status` (pending|paid|failed|refunded)

**Response 200:** Danh sách đơn hàng + phân trang.

---

### GET `/my-orders/{orderCode}`

Chi tiết đơn hàng. Chỉ xem được đơn của chính mình.

**Response 200:**
```json
{
  "data": {
    "order_code": "ORD-1746612345",
    "status": "paid",
    "subtotal": "1000000.00",
    "discount_amount": "200000.00",
    "total_amount": "800000.00",
    "coupon_code": "SALE20",
    "payment_method": "vnpay",
    "paid_at": "2026-05-07T10:30:00Z",
    "items": [
      { "course_id": 3, "name": "Laravel từ cơ bản", "price": "500000.00" },
      { "course_id": 5, "name": "Vue 3 nâng cao", "price": "500000.00" }
    ]
  }
}
```

---

### POST `/orders/{orderCode}/retry-payment`

Thanh toán lại đơn `pending` hoặc `failed`.

**Response 200:**
```json
{
  "data": {
    "payment_url": "https://sandbox.vnpayment.vn/..."
  }
}
```

**Response 422:** Đơn không ở trạng thái cho phép retry.

---

## VNPay Callbacks (Public — không cần auth)

### GET `/payment/vnpay/return`

VNPay redirect user về sau khi thanh toán. Controller xử lý kết quả rồi redirect về frontend:

```
/payment/result?status=success&order_code=ORD-xxx
/payment/result?status=failed&order_code=ORD-xxx
```

---

### GET `/payment/vnpay/ipn`

VNPay gọi server-to-server để thông báo kết quả thanh toán. Controller verify HMAC-SHA512, cập nhật order, enroll student.

**Response phải trả về cho VNPay:**
```json
{ "RspCode": "00", "Message": "Confirm Success" }
```

---

## Admin

### GET `/admin/orders`

**Middleware:** `auth:admin`, `permission:orders.view`

**Query params:** `search` (order_code/student email), `status`, `page`, `per_page`

---

### GET `/admin/orders/{id}`

Chi tiết đơn hàng + student info + items + transactions.

---

### PATCH `/admin/orders/{id}/status`

```json
{ "status": "refunded" }
```

---

### GET `/admin/orders/stats/revenue`

Thống kê doanh thu.

**Query params:** `period` (day|week|month|year), `from`, `to`

**Response 200:**
```json
{
  "data": {
    "total_revenue": 15000000,
    "total_orders": 45,
    "paid_orders": 40,
    "chart": [
      { "date": "2026-05-01", "revenue": 500000, "orders": 3 }
    ]
  }
}
```

---

### DELETE `/admin/orders/{id}`

Soft delete đơn hàng.

---

### PATCH `/admin/orders/{id}/restore`

---

### GET `/admin/orders/trashed`

---

### DELETE `/admin/orders/bulk-delete`

```json
{ "ids": [1, 2, 3] }
```
