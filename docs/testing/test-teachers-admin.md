# 👨‍🏫 Test Checklist — Quản Lý Giảng Viên (Admin)

> **Route BE:** `/api/v1/admin/teachers`
> **Page FE:** `/admin/teachers`
> **Auth:** Admin (guard: admin)

---

## Chuẩn bị
- [x] Chạy `php artisan migrate:fresh --seed`
- [x] Backend chạy: `php artisan serve`
- [x] Frontend chạy: `npm run dev`
- [x] Đăng nhập tài khoản Admin

---

## 1. Danh sách giảng viên

### 1.1 Hiển thị bảng
- [x] Truy cập `/admin/teachers` → Bảng danh sách hiển thị đúng
- [x] Các cột: Checkbox, Giảng viên (avatar + tên + slug), Kinh nghiệm, Trạng thái (switch), Ngày tạo, Thao tác
- [x] Avatar hiển thị chữ cái đầu tên nếu không có ảnh
- [x] Kinh nghiệm hiện dấu `—` nếu trống
- [x] Network: `GET /api/v1/admin/teachers` → 200

### 1.2 Loading skeleton
- [x] Khi đang tải dữ liệu → Hiện skeleton animation (5 dòng)
- [x] Sau khi tải xong → Skeleton biến mất, hiện data thực

### 1.3 Empty state
- [x] Nếu chưa có giảng viên nào → Hiện "Chưa có giảng viên nào."

---

## 2. Tìm kiếm & Lọc

### 2.1 Tìm kiếm
- [x] Nhập từ khoá vào ô tìm kiếm → Kết quả lọc đúng theo tên
- [x] Tìm kiếm có debounce (không gọi API mỗi ký tự)
- [x] Xoá ô tìm kiếm → Hiện lại toàn bộ danh sách

### 2.2 Lọc trạng thái
- [x] Chọn "Đang hoạt động" → Chỉ hiện giảng viên status = 1
- [x] Chọn "Vô hiệu hoá" → Chỉ hiện giảng viên status = 0
- [x] Chọn "Tất cả trạng thái" → Hiện tất cả

---

## 3. Phân trang

- [x] Có > 15 giảng viên → PaginationBar hiển thị
- [x] Click sang trang 2 → Dữ liệu tải đúng trang
- [x] Có ≤ 15 → Không hiện thanh phân trang

---

## 4. Thêm giảng viên

- [x] Click **"Thêm giảng viên"** → Modal mở ra
- [x] Điền đầy đủ: Tên, Mô tả, Kinh nghiệm → Click **"Lưu"** → Toast thành công, modal đóng, danh sách cập nhật
- [x] Bỏ trống tên → Trình duyệt chặn submit (HTML required)
- [x] Network: `POST /api/v1/admin/teachers` → 201
- [x] Click **"Huỷ"** hoặc click ngoài modal → Modal đóng, không tạo

---

## 5. Sửa giảng viên

- [x] Click icon **bút chì** trên dòng → Modal mở, form đã điền sẵn thông tin (tên, mô tả, kinh nghiệm)
- [x] Sửa tên → Click **"Lưu"** → Toast thành công, danh sách cập nhật đúng
- [x] Network: `PUT /api/v1/admin/teachers/{id}` → 200

---

## 6. Toggle trạng thái

- [x] Click nút **switch** trên dòng → Trạng thái đổi (Active ↔ Inactive)
- [x] Toast hiện thông báo thành công
- [x] Network: `PATCH /api/v1/admin/teachers/{id}/toggle-status` → 200
- [x] Switch disabled trong khi đang gọi API

---

## 7. Xoá giảng viên (Soft Delete)

- [x] Click icon **thùng rác** → Modal xác nhận hiện lên
- [x] Modal hiện tên giảng viên + ghi chú "chuyển vào thùng rác"
- [x] Click **"Xoá"** → Toast thành công, giảng viên biến mất khỏi danh sách
- [x] Số đếm thùng rác cập nhật (+1)
- [x] Click **"Huỷ"** → Không xoá

---

## 8. Thùng rác

- [x] Click tab **"Thùng rác"** → Hiện danh sách giảng viên đã xoá
- [x] Nút "Thêm giảng viên" ẩn đi
- [x] Bộ lọc trạng thái ẩn đi
- [x] Mỗi dòng có 2 nút: **Khôi phục** + **Xoá vĩnh viễn**

### 8.1 Khôi phục
- [x] Click icon **khôi phục** → Toast thành công, giảng viên quay lại tab "Tất cả"
- [x] Network: `POST /api/v1/admin/teachers/{id}/restore` → 200

### 8.2 Xoá vĩnh viễn
- [x] Click icon **thùng rác** → Modal xác nhận (cảnh báo đỏ "không thể hoàn tác")
- [x] Click **"Xoá vĩnh viễn"** → Giảng viên bị xoá hẳn, không thể khôi phục
- [x] Network: `DELETE /api/v1/admin/teachers/{id}/force-delete` → 200

### 8.3 Empty state thùng rác
- [x] Thùng rác trống → Hiện "Thùng rác trống."

---

## 9. Bulk Actions (Thao tác hàng loạt)

- [x] Tick checkbox ở header → Chọn tất cả trang hiện tại
- [x] Tick checkbox từng dòng → Thanh bulk action hiện ở dưới
- [x] Thanh hiện đúng số lượng đã chọn
- [x] Tab "Tất cả": Click **"Xoá"** → Xoá mềm tất cả đã chọn
- [x] Tab "Thùng rác": Click **"Khôi phục"** → Khôi phục tất cả đã chọn
- [x] Click **"Bỏ chọn"** → Bỏ chọn hết, thanh biến mất
- [x] Modal overlay phủ kín cả sidebar

---

## 10. Edge Cases

- [x] Student truy cập `/admin/teachers` → Redirect về login admin
- [x] Không có token → Redirect về login admin

---

## Checklist Tổng Hợp

| Test | Kết quả | Ghi chú |
|------|---------|---------|
| 1.1 Load trang | ✅ | |
| 1.2 Skeleton | ✅ | |
| 1.3 Empty state | ✅ | |
| 2.1 Tìm kiếm | ✅ | |
| 2.2 Lọc trạng thái | ✅ | |
| 3. Phân trang | ✅ | |
| 4. Thêm giảng viên | ✅ | |
| 5. Sửa giảng viên | ✅ | |
| 6. Toggle status | ✅ | |
| 7. Xoá (Soft Delete) | ✅ | |
| 8.1 Restore | ✅ | |
| 8.2 Force delete | ✅ | |
| 9. Bulk actions | ✅ | |
| 10. Edge cases | ✅ | |
