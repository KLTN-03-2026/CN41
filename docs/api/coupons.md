# API Reference — Coupons

Base URL: `http://localhost:8000/api/v1`

---

## Admin

### GET `/admin/coupons`

**Middleware:** `auth:admin`, `permission:coupons.view`

**Query params:** `search` (code), `status`, `type`, `page`, `per_page`

---

### POST `/admin/coupons`

**Request Body:**
```json
{
  "code": "SALE20",
  "type": "percentage",
  "value": 20,
  "min_order_value": 300000,
  "max_discount": 200000,
  "usage_limit": 100,
  "start_date": "2026-05-01 00:00:00",
  "end_date": "2026-12-31 23:59:59",
  "status": 1,
  "description": "Giảm 20% tối đa 200k cho đơn từ 300k"
}
```

**Validation:**
| Field | Rules |
|-------|-------|
| `code` | required, string, max:50, unique:coupons — tự động uppercase |
| `type` | required, in:fixed,percentage |
| `value` | required, numeric, min:0 |
| `min_order_value` | nullable, numeric, min:0 |
| `max_discount` | nullable, numeric, min:0 (chỉ có nghĩa khi type=percentage) |
| `usage_limit` | nullable, integer, min:1 (null = không giới hạn) |
| `start_date` | nullable, date |
| `end_date` | nullable, date, >= start_date |
| `status` | nullable, in:0,1 |

> `code` được tự động uppercase trước khi validate (`prepareForValidation`).

---

### GET `/admin/coupons/{id}`

Chi tiết mã + `used_count` (số lần đã dùng).

---

### PATCH `/admin/coupons/{id}`

Tương tự POST. `code` validate unique bỏ qua id hiện tại.

---

### DELETE `/admin/coupons/{id}`

Soft delete.

---

### PATCH `/admin/coupons/{id}/toggle-status`

**Response 200:** `{ "data": { "status": 0 } }`

---

### GET `/admin/coupons/trashed`

---

### POST `/admin/coupons/{id}/restore`

---

### DELETE `/admin/coupons/{id}/force-delete`

---

### DELETE `/admin/coupons/bulk-delete`

```json
{ "ids": [1, 2, 3] }
```

Validation: các id phải chưa bị soft delete.

---

### POST `/admin/coupons/bulk-restore`

```json
{ "ids": [1, 2] }
```

---

## Public

### GET `/coupons/available`

Danh sách mã giảm giá đang hoạt động (status=1, trong thời hạn, còn lượt dùng).

**Response 200:**
```json
{
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

---

## Student (auth:api + email.verified)

### POST `/coupons/validate`

Kiểm tra mã và tính số tiền giảm trước khi checkout.

**Request Body:**
```json
{
  "code": "SALE20",
  "subtotal": 500000
}
```

**Response 200 — Hợp lệ:**
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
    "description": "Giảm 20% tối đa 200k"
  }
}
```

**Response 422 — Không hợp lệ:**
```json
{
  "success": false,
  "message": "Mã giảm giá đã hết lượt sử dụng.",
  "data": null
}
```

Các thông báo lỗi có thể:
- `Mã giảm giá không tồn tại.`
- `Mã giảm giá chưa đến ngày áp dụng.`
- `Mã giảm giá đã hết hạn.`
- `Mã giảm giá đã hết lượt sử dụng.`
- `Đơn hàng chưa đạt giá trị tối thiểu {min_order_value}đ.`
- `Mã giảm giá hiện không hoạt động.`
