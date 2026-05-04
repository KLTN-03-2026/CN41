# 👨‍🏫 Test Checklist — Quản Lý Giảng Viên (Admin)

> **Route BE:** `/api/v1/admin/teachers`
> **Page FE:** `/admin/teachers`
> **Auth:** Admin (guard: admin)

---

## Chuẩn bị
- [ ] Chạy `php artisan migrate:fresh --seed`
- [ ] Backend chạy: `php artisan serve`
- [ ] Frontend chạy: `npm run dev`
- [ ] Đăng nhập tài khoản Admin

---

## 1. Danh sách giảng viên

### 1.1 Hiển thị bảng
- [ ] Truy cập `/admin/teachers` → Bảng danh sách hiển thị đúng
- [ ] Các cột: Checkbox, Giảng viên (avatar + tên + slug), Kinh nghiệm, Trạng thái (switch), Ngày tạo, Thao tác
- [ ] Avatar hiển thị chữ cái đầu tên nếu không có ảnh
- [ ] Kinh nghiệm hiện dấu `—` nếu trống
- [ ] Network: `GET /api/v1/admin/teachers` → 200

### 1.2 Loading skeleton
- [ ] Khi đang tải dữ liệu → Hiện skeleton animation (5 dòng)
- [ ] Sau khi tải xong → Skeleton biến mất, hiện data thực

### 1.3 Empty state
- [ ] Nếu chưa có giảng viên nào → Hiện "Chưa có giảng viên nào."

---

## 2. Tìm kiếm & Lọc

### 2.1 Tìm kiếm
- [ ] Nhập từ khoá vào ô tìm kiếm → Kết quả lọc đúng theo tên
- [ ] Tìm kiếm có debounce (không gọi API mỗi ký tự)
- [ ] Xoá ô tìm kiếm → Hiện lại toàn bộ danh sách

### 2.2 Lọc trạng thái
- [ ] Chọn "Đang hoạt động" → Chỉ hiện giảng viên status = 1
- [ ] Chọn "Vô hiệu hoá" → Chỉ hiện giảng viên status = 0
- [ ] Chọn "Tất cả trạng thái" → Hiện tất cả

---

## 3. Phân trang

- [ ] Có > 15 giảng viên → PaginationBar hiển thị
- [ ] Click sang trang 2 → Dữ liệu tải đúng trang
- [ ] Có ≤ 15 → Không hiện thanh phân trang

---

## 4. Thêm giảng viên

- [ ] Click **"Thêm giảng viên"** → Modal mở ra
- [ ] Điền đầy đủ: Tên, Mô tả, Kinh nghiệm → Click **"Lưu"** → Toast thành công, modal đóng, danh sách cập nhật
- [ ] Bỏ trống tên → Trình duyệt chặn submit (HTML required)
- [ ] Network: `POST /api/v1/admin/teachers` → 201
- [ ] Click **"Huỷ"** hoặc click ngoài modal → Modal đóng, không tạo

---

## 5. Sửa giảng viên

- [ ] Click icon **bút chì** trên dòng → Modal mở, form đã điền sẵn thông tin (tên, mô tả, kinh nghiệm)
- [ ] Sửa tên → Click **"Lưu"** → Toast thành công, danh sách cập nhật đúng
- [ ] Network: `PUT /api/v1/admin/teachers/{id}` → 200

---

## 6. Toggle trạng thái

- [ ] Click nút **switch** trên dòng → Trạng thái đổi (Active ↔ Inactive)
- [ ] Toast hiện thông báo thành công
- [ ] Network: `PATCH /api/v1/admin/teachers/{id}/toggle-status` → 200
- [ ] Switch disabled trong khi đang gọi API

---

## 7. Xoá giảng viên (Soft Delete)

- [ ] Click icon **thùng rác** → Modal xác nhận hiện lên
- [ ] Modal hiện tên giảng viên + ghi chú "chuyển vào thùng rác"
- [ ] Click **"Xoá"** → Toast thành công, giảng viên biến mất khỏi danh sách
- [ ] Số đếm thùng rác cập nhật (+1)
- [ ] Click **"Huỷ"** → Không xoá

---

## 8. Thùng rác

- [ ] Click tab **"Thùng rác"** → Hiện danh sách giảng viên đã xoá
- [ ] Nút "Thêm giảng viên" ẩn đi
- [ ] Bộ lọc trạng thái ẩn đi
- [ ] Mỗi dòng có 2 nút: **Khôi phục** + **Xoá vĩnh viễn**

### 8.1 Khôi phục
- [ ] Click icon **khôi phục** → Toast thành công, giảng viên quay lại tab "Tất cả"
- [ ] Network: `POST /api/v1/admin/teachers/{id}/restore` → 200

### 8.2 Xoá vĩnh viễn
- [ ] Click icon **thùng rác** → Modal xác nhận (cảnh báo đỏ "không thể hoàn tác")
- [ ] Click **"Xoá vĩnh viễn"** → Giảng viên bị xoá hẳn, không thể khôi phục
- [ ] Network: `DELETE /api/v1/admin/teachers/{id}/force-delete` → 200

### 8.3 Empty state thùng rác
- [ ] Thùng rác trống → Hiện "Thùng rác trống."

---

## 9. Bulk Actions (Thao tác hàng loạt)

- [ ] Tick checkbox ở header → Chọn tất cả trang hiện tại
- [ ] Tick checkbox từng dòng → Thanh bulk action hiện ở dưới
- [ ] Thanh hiện đúng số lượng đã chọn
- [ ] Tab "Tất cả": Click **"Xoá"** → Xoá mềm tất cả đã chọn
- [ ] Tab "Thùng rác": Click **"Khôi phục"** → Khôi phục tất cả đã chọn
- [ ] Click **"Bỏ chọn"** → Bỏ chọn hết, thanh biến mất
- [ ] Modal overlay phủ kín cả sidebar

---

## 10. Edge Cases

- [ ] Student truy cập `/admin/teachers` → Redirect về login admin
- [ ] Không có token → Redirect về login admin

---

## Checklist Tổng Hợp

| Test | Kết quả | Ghi chú |
|------|---------|---------|
| 1.1 Load trang | ⬜ | |
| 1.2 Skeleton | ⬜ | |
| 1.3 Empty state | ⬜ | |
| 2.1 Tìm kiếm | ⬜ | |
| 2.2 Lọc trạng thái | ⬜ | |
| 3. Phân trang | ⬜ | |
| 4. Thêm giảng viên | ⬜ | |
| 5. Sửa giảng viên | ⬜ | |
| 6. Toggle status | ⬜ | |
| 7. Xoá (Soft Delete) | ⬜ | |
| 8.1 Restore | ⬜ | |
| 8.2 Force delete | ⬜ | |
| 9. Bulk actions | ⬜ | |
| 10. Edge cases | ⬜ | |
