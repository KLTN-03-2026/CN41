# 👩‍🎓 Test Checklist — Quản Lý Học Viên (Admin)

## Chuẩn bị
- [x] Chạy `php artisan migrate:fresh --seed`
- [x] Backend chạy: `php artisan serve`
- [x] Frontend chạy: `npm run dev`
- [x] Đăng nhập tài khoản Admin

---

## 1. Danh sách học viên

### 1.1 Hiển thị bảng
- [x] Truy cập `/admin/students` → Bảng danh sách hiển thị đúng
- [x] Các cột: Checkbox, Học viên (avatar + tên), Email, Ngày sinh, Xác minh, Ngày tạo, Thao tác
- [x] Avatar hiển thị chữ cái đầu tên nếu không có ảnh
- [x] Ngày sinh hiển thị định dạng `dd/mm/yyyy`, nếu trống hiện dấu `—`
- [x] Cột "Xác minh": icon ✅ xanh nếu đã verify, "Chưa" nếu chưa

### 1.2 Loading skeleton
- [x] Khi đang tải dữ liệu → Hiện skeleton animation (5 dòng)
- [x] Sau khi tải xong → Skeleton biến mất, hiện data thực

### 1.3 Empty state
- [x] Nếu chưa có học viên nào → Hiện "Chưa có học viên nào."

---

## 2. Tìm kiếm

- [x] Nhập từ khoá vào ô tìm kiếm → Kết quả lọc đúng theo tên/email
- [x] Tìm kiếm có debounce (không gọi API mỗi ký tự)
- [x] Xoá ô tìm kiếm → Hiện lại toàn bộ danh sách

---

## 3. Phân trang

- [x] Có > 15 học viên → PaginationBar hiển thị
- [x] Click sang trang 2 → Dữ liệu tải đúng trang
- [x] Có ≤ 15 → Không hiện thanh phân trang

---

## 4. Thêm học viên

- [x] Click **"Thêm học viên"** → Modal mở ra
- [x] Điền đầy đủ: Họ tên, Email, Mật khẩu → Click **"Lưu"** → Toast thành công, modal đóng, danh sách cập nhật
- [x] Bỏ trống trường bắt buộc → Trình duyệt chặn submit (HTML required)
- [x] Nhập email đã tồn tại → Hiện lỗi từ Backend (422)
- [x] Click **"Huỷ"** hoặc click ngoài modal → Modal đóng, không tạo

---

## 5. Sửa học viên

- [x] Click icon **bút chì** trên dòng → Modal mở, form đã điền sẵn thông tin
- [x] Trường mật khẩu **không hiển thị** (không bắt buộc khi sửa)
- [x] Sửa tên → Click **"Lưu"** → Toast thành công, danh sách cập nhật đúng
- [x] Sửa email sang email đã tồn tại → Hiện lỗi Backend

---

## 6. Xoá học viên (Soft Delete)

- [x] Click icon **thùng rác** → Modal xác nhận hiện lên
- [x] Modal hiện tên học viên + ghi chú "chuyển vào thùng rác"
- [x] Click **"Xoá"** → Toast thành công, học viên biến mất khỏi danh sách
- [x] Số đếm thùng rác cập nhật (+1)
- [x] Click **"Huỷ"** → Không xoá

---

## 7. Thùng rác

- [x] Click tab **"Thùng rác"** → Hiện danh sách học viên đã xoá
- [x] Nút "Thêm học viên" ẩn đi
- [x] Mỗi dòng có 2 nút: **Khôi phục** + **Xoá vĩnh viễn**

### 7.1 Khôi phục
- [x] Click icon **khôi phục** → Toast thành công, học viên quay lại tab "Tất cả"

### 7.2 Xoá vĩnh viễn
- [x] Click icon **thùng rác** → Modal xác nhận (cảnh báo đỏ)
- [x] Click **"Xoá"** → Học viên bị xoá hẳn, không thể khôi phục

### 7.3 Empty state thùng rác
- [x] Thùng rác trống → Hiện "Thùng rác trống."

---

## 8. Bulk Actions (Thao tác hàng loạt)

- [x] Tick checkbox ở header → Chọn tất cả
- [x] Tick checkbox từng dòng → Thanh bulk action hiện ở dưới
- [x] Thanh hiện đúng số lượng đã chọn
- [x] Tab "Tất cả": Click **"Xoá"** → Xoá mềm tất cả đã chọn
- [x] Tab "Thùng rác": Click **"Khôi phục"** → Khôi phục tất cả đã chọn
- [x] Click **"Bỏ chọn"** → Bỏ chọn hết, thanh biến mất

## 9. Xem chi tiết học viên

- [x] Click icon **con mắt** trên dòng → Modal chi tiết mở ra, nền phía sau tối đen + blur
- [x] Modal hiển thị đúng thông tin: tên, email, ngày sinh, xác minh email
- [x] Hiện đúng **Đơn hàng** (số lượng) và **Tổng chi tiêu** (format VNĐ)
- [x] Hiện danh sách **Khóa học đã đăng ký** kèm thumbnail, giá, ngày đăng ký
- [x] Thumbnail khóa học load đúng ảnh
- [x] Nếu chưa đăng ký khóa nào → Hiện "Chưa đăng ký khóa học nào."
- [x] Click nút **X** hoặc click ngoài modal → Modal đóng
- [x] Modal overlay phủ kín cả sidebar

---

## 10. Edge Cases

- [x] Student truy cập `/admin/students` → Redirect về login admin
- [x] Không có token → Redirect về login admin

