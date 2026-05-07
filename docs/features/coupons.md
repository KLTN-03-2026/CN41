# Mã giảm giá (Coupons)

## 1. Tổng quan

Module `Coupons` quản lý mã giảm giá áp dụng khi checkout. Hỗ trợ hai loại giảm giá: **cố định** (fixed) và **phần trăm** (percentage). Có cơ chế bảo vệ **race condition** khi nhiều học viên dùng cùng một mã có giới hạn số lần.

---

## 2. Cấu trúc dữ liệu

### Bảng `coupons`

| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `id` | bigint | PK |
| `code` | varchar(50) unique | Mã nhập khi checkout (VD: `SALE20`) |
| `type` | enum | `fixed` = giảm số tiền cố định, `percentage` = giảm theo % |
| `value` | decimal(12,2) | Giá trị giảm (VND hoặc %) |
| `min_order_value` | decimal(12,2) | Đơn tối thiểu để áp mã |
| `max_discount` | decimal(12,2) nullable | Giảm tối đa (áp dụng cho type=percentage) |
| `usage_limit` | int nullable | Tổng số lần dùng cho phép (null = không giới hạn) |
| `used_count` | int default 0 | Số lần đã dùng (atomic increment) |
| `start_date` | datetime | Ngày bắt đầu hiệu lực |
| `end_date` | datetime | Ngày hết hạn |
| `status` | tinyint default 1 | 1 = active, 0 = inactive |
| `description` | text | Mô tả hiển thị cho học viên |
| `deleted_at` | timestamp | Soft delete |

---

## 3. Logic tính giảm giá

```php
// Coupon::calculateDiscount(float $subtotal): float

if ($this->type === 'percentage') {
    $discount = $subtotal * ($this->value / 100);

    // Clamp theo max_discount nếu có
    if ($this->max_discount) {
        $discount = min($discount, $this->max_discount);
    }
} else {
    // fixed
    $discount = $this->value;
}

// Không được giảm nhiều hơn giá trị đơn hàng
return min($discount, $subtotal);
```

**Kiểm tra hợp lệ (`isValid()`):**
```
status = 1
AND start_date <= now() <= end_date
AND (usage_limit IS NULL OR used_count < usage_limit)
AND subtotal >= min_order_value
```

---

## 4. API Endpoints

### Admin

| Method | Endpoint | Permission | Mô tả |
|--------|----------|-----------|-------|
| GET | `/api/v1/admin/coupons` | coupons.view | Danh sách (filter, search, phân trang) |
| POST | `/api/v1/admin/coupons` | coupons.create | Tạo coupon |
| GET | `/api/v1/admin/coupons/{id}` | coupons.view | Chi tiết |
| PATCH | `/api/v1/admin/coupons/{id}` | coupons.edit | Cập nhật |
| DELETE | `/api/v1/admin/coupons/{id}` | coupons.delete | Soft delete |
| PATCH | `/api/v1/admin/coupons/{id}/toggle-status` | coupons.edit | Bật/tắt |
| GET | `/api/v1/admin/coupons/trashed` | coupons.view | Danh sách đã xóa |
| PATCH | `/api/v1/admin/coupons/{id}/restore` | coupons.edit | Khôi phục |
| DELETE | `/api/v1/admin/coupons/{id}/force-delete` | coupons.delete | Xóa vĩnh viễn |
| DELETE | `/api/v1/admin/coupons/bulk-delete` | coupons.delete | Xóa hàng loạt |
| PATCH | `/api/v1/admin/coupons/bulk-restore` | coupons.edit | Khôi phục hàng loạt |

### Student

| Method | Endpoint | Middleware | Mô tả |
|--------|----------|-----------|-------|
| GET | `/api/v1/coupons/available` | public | Danh sách mã đang hoạt động |
| POST | `/api/v1/coupons/validate` | auth:api + email.verified | Kiểm tra mã + tính discount |

---

## 5. Luồng áp mã khi checkout

```
Student nhập mã trên CheckoutPage
  │
  │  POST /api/v1/coupons/validate
  │  Body: { "code": "SALE20", "subtotal": 500000 }
  ▼
CouponsController::validateCoupon()
  │
  ├── Tìm coupon theo code (case-insensitive)
  ├── Kiểm tra isValid(subtotal)
  ├── [Không hợp lệ] → 422 với thông báo cụ thể:
  │     "Mã không tồn tại" / "Đã hết lượt dùng" / "Chưa đến ngày áp dụng" /
  │     "Đã hết hạn" / "Đơn hàng chưa đạt giá trị tối thiểu"
  │
  └── [Hợp lệ] → Return:
        { discount_amount: 100000, final_price: 400000, coupon_code: "SALE20" }

Student xác nhận → POST /api/v1/orders (kèm coupon_code)
  │
  └── Xem luồng chi tiết tại payment-vnpay.md
```

---

## 6. Race Condition Protection

**Vấn đề:** Nếu `usage_limit = 1` và 2 request đến đồng thời, cả hai đều thấy `used_count = 0 < 1` và đều cho phép dùng, dẫn đến mã bị dùng 2 lần.

**Giải pháp:** Dùng `SELECT ... FOR UPDATE` (pessimistic locking) trong `DB::transaction()`:

```php
// Trong OrderController::store() — khi áp coupon
DB::transaction(function () use ($couponCode, $subtotal) {
    // Lock row — các request khác phải chờ transaction này kết thúc
    $coupon = Coupon::lockForUpdate()->where('code', $couponCode)->first();

    if (!$coupon->isValid($subtotal)) {
        throw new ValidationException('Mã giảm giá không hợp lệ.');
    }

    // Atomic increment — an toàn vì đang trong lock
    $coupon->increment('used_count');

    return $coupon->calculateDiscount($subtotal);
});
```

**Kết quả:** Trong test concurrency, khi 2 request cùng dùng mã `usage_limit = 1`:
- Request 1: thành công, `used_count` = 1
- Request 2: thất bại với lỗi "Đã hết lượt dùng" (đọc `used_count = 1` sau khi lock giải phóng)

---

## 7. Ví dụ response

### POST `/api/v1/coupons/validate` — Hợp lệ

```json
{
  "success": true,
  "message": "Mã giảm giá hợp lệ",
  "data": {
    "code": "SALE20",
    "type": "percentage",
    "value": 20,
    "discount_amount": 100000,
    "final_price": 400000,
    "description": "Giảm 20% tối đa 200k cho đơn từ 300k"
  }
}
```

### POST `/api/v1/coupons/validate` — Không hợp lệ

```json
{
  "success": false,
  "message": "Mã giảm giá đã hết lượt sử dụng.",
  "data": null
}
```

### GET `/api/v1/coupons/available` — Danh sách mã

```json
{
  "success": true,
  "data": [
    {
      "code": "WELCOME10",
      "type": "percentage",
      "value": 10,
      "min_order_value": "0.00",
      "max_discount": "50000.00",
      "end_date": "2026-12-31T23:59:59Z",
      "description": "Giảm 10% cho lần mua đầu tiên"
    }
  ]
}
```
