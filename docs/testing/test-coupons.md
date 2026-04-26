# 🎫 Test Checklist — Mã Giảm Giá (Coupons)

> **Route BE Admin:** `/api/v1/admin/coupons`
> **Route BE Client:** `/api/v1/coupons/validate`
> **Page FE Admin:** `/admin/coupons`
> **Page FE Client:** `/checkout`

---

## Chuẩn bị
- [x] Chạy `php artisan migrate:fresh --seed`
- [x] Backend chạy: `php artisan serve`
- [x] Frontend chạy: `npm run dev`
- [x] Đăng nhập tài khoản Admin (để test admin)
- [x] Đăng nhập tài khoản Student (để test checkout)

---

## 1. Quản lý Mã Giảm Giá (Admin)

### 1.1 Hiển thị danh sách
- [x] Truy cập `/admin/coupons` → Bảng hiển thị đúng (Mã, Loại/Giá trị, Đã dùng, Trạng thái, Thời hạn, Thao tác)
- [x] Hiển thị mã giảm giá kiểu in hoa (uppercase), font mono
- [x] Hiển thị đúng số lần đã dùng / giới hạn (VD: 2/10 hoặc 5/∞)
- [x] Hiện "Hết hạn" màu đỏ nếu `end_date` đã qua
- [x] Network: `GET /api/v1/admin/coupons` → 200

### 1.2 Thêm mã giảm giá
- [x] Click **"Thêm mã"** → Modal mở ra
- [x] Chọn loại "Phần trăm" → Hiện thêm ô "Giảm tối đa (VNĐ)"
- [x] Chọn loại "Cố định" → Ẩn ô "Giảm tối đa (VNĐ)"
- [x] Điền mã, giá trị, ngày bắt đầu/kết thúc → Click "Lưu" → Toast thành công
- [x] Điền mã đã tồn tại → Trả về lỗi 422 từ API và hiện thông báo đỏ dưới form
- [x] Network: `POST /api/v1/admin/coupons` → 201

### 1.3 Sửa mã giảm giá
- [x] Click icon **bút chì** → Modal mở, data điền sẵn (code, type, value, start_date, end_date)
- [x] Cập nhật thời hạn hoặc giá trị → Click "Lưu" → Toast thành công, danh sách cập nhật
- [x] Network: `PUT /api/v1/admin/coupons/{id}` → 200

### 1.4 Toggle trạng thái
- [x] Click nút **switch** → Đổi trạng thái Hoạt động ↔ Vô hiệu
- [x] Mã "Vô hiệu" sẽ không thể sử dụng ở màn Checkout
- [x] Network: `PATCH /api/v1/admin/coupons/{id}/toggle-status` → 200

### 1.5 Xóa, Khôi phục, Xóa vĩnh viễn
- [x] Click icon thùng rác → Xác nhận xóa → Mã bay vào tab "Thùng rác"
- [x] Sang tab "Thùng rác" → Hiện danh sách mã đã xóa
- [x] Click "Khôi phục" → Mã trở lại tab "Tất cả"
- [x] Click "Xóa vĩnh viễn" → Xóa hoàn toàn khỏi DB

### 1.6 Bulk Actions
- [x] Chọn nhiều mã → Click "Xóa" → Chuyển tất cả vào thùng rác
- [x] Sang tab thùng rác, chọn nhiều mã → Click "Khôi phục" → Trở lại tab tất cả

---

## 2. Áp dụng Mã Giảm Giá (Checkout - Student)

### 2.1 Hiển thị UI Checkout
- [x] Trong trang Thanh toán có khối "Mã giảm giá" ngay trên phần "Tổng thanh toán"
- [x] Gồm ô nhập text (tự in hoa) và nút "Áp dụng"

### 2.2 Áp dụng mã thành công
- [x] Nhập mã giảm giá Cố định hợp lệ (còn hạn, đủ điều kiện) → Cập nhật tổng thanh toán trừ đi số tiền cố định
- [x] Nhập mã giảm giá Phần trăm hợp lệ → Cập nhật tổng thanh toán trừ đi (Subtotal * % discount, không vượt quá max_discount)
- [x] Hiển thị hộp màu xanh báo hiệu đã áp dụng mã thành công + nút "Xóa mã"
- [x] Ở phần Tổng thanh toán có dòng "Mã giảm giá: -XX,XXX₫"

### 2.3 Xử lý lỗi áp dụng mã
- [x] Nhập mã sai / mã đã xóa / vô hiệu → Báo lỗi đỏ "Mã giảm giá không hợp lệ."
- [x] Nhập mã đúng nhưng đơn hàng không đạt `min_order_value` → Báo lỗi "Mã giảm giá yêu cầu đơn hàng tối thiểu XX,XXXđ."
- [x] Nhập mã đúng nhưng đã hết `usage_limit` → Báo lỗi giới hạn số lần sử dụng
- [x] Tổng thanh toán không bị trừ khi có lỗi

### 2.4 Hủy mã đã áp dụng
- [x] Click dấu "X" hoặc "Xóa mã" → Reset tổng tiền về ban đầu, xóa dòng giảm giá khỏi tóm tắt

### 2.5 Thanh toán đơn hàng có áp mã
- [x] Nhập mã thành công (VD: giảm 100k)
- [x] Nhấn "Thanh toán" → Backend xác nhận mã lại một lần nữa
- [x] Trả về URL VNPAY với số tiền đã được giảm (hoặc tạo đơn `free` nếu tiền giảm = 100% hóa đơn)
- [x] Đơn hàng sau khi thanh toán thành công → Cột `used_count` của Coupon tăng lên 1

---

## 3. Checklist Tổng Hợp

| Test | Kết quả | Ghi chú |
|------|---------|---------|
| 1.1 Danh sách Admin | ✅ | |
| 1.2 Thêm mã | ✅ | |
| 1.3 Sửa mã | ✅ | |
| 1.4 Bật/Tắt trạng thái | ✅ | |
| 1.5 Xóa / Khôi phục | ✅ | |
| 1.6 Bulk actions | ✅ | |
| 2.1 Giao diện nhập mã | ✅ | |
| 2.2 Áp mã thành công | ✅ | |
| 2.3 Báo lỗi mã (sai/hết hạn) | ✅ | |
| 2.4 Báo lỗi đơn tối thiểu | ✅ | |
| 2.5 Hủy áp mã | ✅ | |
| 2.6 Thanh toán (tích hợp Backend) | ✅ | |
