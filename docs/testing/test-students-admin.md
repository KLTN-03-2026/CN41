# 👩‍🎓 Test Checklist — Quản Lý Học Viên (Admin)

## Chuẩn bị
- [ ] Chạy `php artisan migrate:fresh --seed`
- [ ] Backend chạy: `php artisan serve`
- [ ] Frontend chạy: `npm run dev`
- [ ] Đăng nhập tài khoản Admin

---

## 1. Danh sách học viên

### 1.1 Hiển thị bảng
- [ ] Truy cập `/admin/students` → Bảng danh sách hiển thị đúng
- [ ] Các cột: Checkbox, Học viên (avatar + tên), Email, Ngày sinh, Xác minh, Ngày tạo, Thao tác
- [ ] Avatar hiển thị chữ cái đầu tên nếu không có ảnh
- [ ] Ngày sinh hiển thị định dạng `dd/mm/yyyy`, nếu trống hiện dấu `—`
- [ ] Cột "Xác minh": icon ✅ xanh nếu đã verify, "Chưa" nếu chưa

### 1.2 Loading skeleton
- [ ] Khi đang tải dữ liệu → Hiện skeleton animation (5 dòng)
- [ ] Sau khi tải xong → Skeleton biến mất, hiện data thực

### 1.3 Empty state
- [ ] Nếu chưa có học viên nào → Hiện "Chưa có học viên nào."

---

## 2. Tìm kiếm

- [ ] Nhập từ khoá vào ô tìm kiếm → Kết quả lọc đúng theo tên/email
- [ ] Tìm kiếm có debounce (không gọi API mỗi ký tự)
- [ ] Xoá ô tìm kiếm → Hiện lại toàn bộ danh sách

---

## 3. Phân trang

- [ ] Có > 15 học viên → PaginationBar hiển thị
- [ ] Click sang trang 2 → Dữ liệu tải đúng trang
- [ ] Có ≤ 15 → Không hiện thanh phân trang

---

## 4. Thêm học viên

- [ ] Click **"Thêm học viên"** → Modal mở ra
- [ ] Điền đầy đủ: Họ tên, Email, Mật khẩu → Click **"Lưu"** → Toast thành công, modal đóng, danh sách cập nhật
- [ ] Bỏ trống trường bắt buộc → Trình duyệt chặn submit (HTML required)
- [ ] Nhập email đã tồn tại → Hiện lỗi từ Backend (422)
- [ ] Click **"Huỷ"** hoặc click ngoài modal → Modal đóng, không tạo

---

## 5. Sửa học viên

- [ ] Click icon **bút chì** trên dòng → Modal mở, form đã điền sẵn thông tin
- [ ] Trường mật khẩu **không hiển thị** (không bắt buộc khi sửa)
- [ ] Sửa tên → Click **"Lưu"** → Toast thành công, danh sách cập nhật đúng
- [ ] Sửa email sang email đã tồn tại → Hiện lỗi Backend

---

## 6. Xoá học viên (Soft Delete)

- [ ] Click icon **thùng rác** → Modal xác nhận hiện lên
- [ ] Modal hiện tên học viên + ghi chú "chuyển vào thùng rác"
- [ ] Click **"Xoá"** → Toast thành công, học viên biến mất khỏi danh sách
- [ ] Số đếm thùng rác cập nhật (+1)
- [ ] Click **"Huỷ"** → Không xoá

---

## 7. Thùng rác

- [ ] Click tab **"Thùng rác"** → Hiện danh sách học viên đã xoá
- [ ] Nút "Thêm học viên" ẩn đi
- [ ] Mỗi dòng có 2 nút: **Khôi phục** + **Xoá vĩnh viễn**

### 7.1 Khôi phục
- [ ] Click icon **khôi phục** → Toast thành công, học viên quay lại tab "Tất cả"

### 7.2 Xoá vĩnh viễn
- [ ] Click icon **thùng rác** → Modal xác nhận (cảnh báo đỏ)
- [ ] Click **"Xoá"** → Học viên bị xoá hẳn, không thể khôi phục

### 7.3 Empty state thùng rác
- [ ] Thùng rác trống → Hiện "Thùng rác trống."

---

## 8. Bulk Actions (Thao tác hàng loạt)

- [ ] Tick checkbox ở header → Chọn tất cả
- [ ] Tick checkbox từng dòng → Thanh bulk action hiện ở dưới
- [ ] Thanh hiện đúng số lượng đã chọn
- [ ] Tab "Tất cả": Click **"Xoá"** → Xoá mềm tất cả đã chọn
- [ ] Tab "Thùng rác": Click **"Khôi phục"** → Khôi phục tất cả đã chọn
- [ ] Click **"Bỏ chọn"** → Bỏ chọn hết, thanh biến mất

## 9. Xem chi tiết học viên

- [ ] Click icon **con mắt** trên dòng → Modal chi tiết mở ra, nền phía sau tối đen + blur
- [ ] Modal hiển thị đúng thông tin: tên, email, ngày sinh, xác minh email
- [ ] Hiện đúng **Đơn hàng** (số lượng) và **Tổng chi tiêu** (format VNĐ)
- [ ] Hiện danh sách **Khóa học đã đăng ký** kèm thumbnail, giá, ngày đăng ký
- [ ] Thumbnail khóa học load đúng ảnh
- [ ] Nếu chưa đăng ký khóa nào → Hiện "Chưa đăng ký khóa học nào."
- [ ] Click nút **X** hoặc click ngoài modal → Modal đóng
- [ ] Modal overlay phủ kín cả sidebar

---

## 10. Edge Cases

- [ ] Student truy cập `/admin/students` → Redirect về login admin
- [ ] Không có token → Redirect về login admin

