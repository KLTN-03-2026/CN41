---
description: "FE 3.1–3.2 — Client: Giỏ hàng + Checkout + Payment"
---

# FE 3.1–3.2 — Cart + Checkout + Payment

> **Phase F3 (phần 1)** — Tạo luồng mua hàng: giỏ hàng → checkout → thanh toán.
> Prerequisite: Backend Module Orders + Payment (VNPAY/MoMo) đã có.

---

## Context

```
Stack: Vue 3, Tailwind, flowbite-vue, lucide-vue-next, vue-toastification, Pinia cartStore.
Backend:
  - POST /api/v1/orders/apply-coupon
  - POST /api/v1/orders
  - POST /api/v1/payment/vnpay → { payment_url }
  - POST /api/v1/payment/momo  → { payment_url }
```

---

## Task F3.1 — CartPage.vue

Route: `/cart`

### Layout:
- **Bảng danh sách items:** thumbnail, tên, giá, nút xóa (`Trash2` icon lucide)
- **Empty state:** `ShoppingCart` icon + "Giỏ hàng trống" + nút "Xem khóa học"
- **Sidebar phải:** tóm tắt + coupon + nút Checkout

### Coupon:
- Input code + nút "Áp dụng" → `POST /api/v1/orders/apply-coupon`
- Thành công: hiện discount amount + `toast.success`
- Thất bại: `toast.error(message)`

### Tổng tiền:
- Hiển thị giá gốc, discount (nếu có), tổng sau giảm
- Dùng `formatCurrency`

### Nút "Thanh toán" → navigate `/checkout`

---

## Task F3.2 — CheckoutPage.vue

Route: `/checkout`

### Step 1: Xác nhận đơn hàng
- List courses + tổng tiền

### Step 2: Chọn phương thức thanh toán
- Nút VNPAY (có logo)
- Nút MoMo (có logo)
- Nếu total = 0: nút "Đăng ký miễn phí" → `POST /api/v1/orders` trực tiếp

### Logic:
```
Click VNPAY → POST /api/v1/payment/vnpay → nhận payment_url → window.location.href = payment_url
Click MoMo  → POST /api/v1/payment/momo  → tương tự
```

---

## API Files

### `src/api/orderApi.js`:
```js
applyCoupon(code, courseIds) → POST /api/v1/orders/apply-coupon
createOrder(data)           → POST /api/v1/orders
getMyOrders()               → GET /api/v1/orders
```

### `src/api/paymentApi.js`:
```js
createVnpay(orderId)  → POST /api/v1/payment/vnpay
createMomo(orderId)   → POST /api/v1/payment/momo
```

---

## ✅ Kiểm tra hoàn thành

- [ ] Giỏ hàng hiện danh sách đúng
- [ ] Xóa item hoạt động
- [ ] Empty state hoạt động
- [ ] Coupon apply thành công/thất bại
- [ ] Tổng tiền tính đúng (có discount)
- [ ] Checkout hiện order summary
- [ ] Redirect sang VNPAY/MoMo đúng
- [ ] Free order tạo trực tiếp

Tick F3.1 → F3.2 trong `QLCV/QLCV-FE.md`.
