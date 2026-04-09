---
description: "FE 3.3–3.5 — PaymentResult + MyCoursesPage + ProfilePage"
---

# FE 3.3–3.5 — Payment Result + My Courses + Profile

> **Phase F3 (phần 2)** — Trang kết quả thanh toán, khóa học đã mua, hồ sơ cá nhân.
> Prerequisite: Cart + Checkout + Payment đã hoạt động.

---

## Context

```
Stack: Vue 3, Tailwind, lucide-vue-next, FwbTabs, FwbBadge, FwbProgress từ flowbite-vue.
```

---

## Task F3.3 — PaymentResultPage.vue

Route: `/payment/result`
Query params: `?status=success|failed&order_id=X`

- **Success:** `CheckCircle` icon (green) + "Thanh toán thành công!" + nút "Vào học ngay" → `/my-courses`
- **Failed:** `XCircle` icon (red) + "Thanh toán thất bại" + nút "Thử lại" → `/cart`

---

## Task F3.4 — MyCoursesPage.vue

Route: `/my-courses`

- `GET /api/v1/my-courses` → danh sách khóa học đã mua
- Grid CourseCard nhưng thêm `FwbProgress` bar % hoàn thành
- Click → `/courses/{slug}/learn`
- Empty state: "Bạn chưa có khóa học nào" + nút CTA

---

## Task F3.5 — ProfilePage.vue

Route: `/profile`

Dùng `FwbTabs` từ flowbite-vue:

### Tab 1 — "Thông tin cá nhân":
- Form sửa name, phone, avatar
- VeeValidate + Zod validation
- Submit → `toast.success`

### Tab 2 — "Lịch sử đơn hàng":
- Table orders
- `FwbBadge` cho status: pending (amber) / paid (green) / failed (red)
- Dùng `formatCurrency` + `formatDate`

### Tab 3 — "Đổi mật khẩu":
- Form: old_password + new_password + new_password_confirmation
- VeeValidate + Zod validation
- Submit → `toast.success` hoặc hiện lỗi từ API

---

## ✅ Kiểm tra hoàn thành

- [ ] Payment result hiện đúng theo status
- [ ] My courses hiện danh sách + progress bar
- [ ] Profile tab thông tin cá nhân cập nhật đúng
- [ ] Profile tab đơn hàng hiện badge status
- [ ] Profile tab đổi mật khẩu hoạt động
- [ ] Responsive trên mobile

Tick F3.3 → F3.5 trong `QLCV/QLCV-FE.md`.
