# 📊 Test Checklist — Dashboard & Thống Kê

## Chuẩn bị
- [ ] Chạy `php artisan migrate:fresh --seed`
- [ ] Đảm bảo có ít nhất 1 khóa học (published), vài student, và vài đơn hàng (status: paid).
- [ ] Backend chạy: `php artisan serve`
- [ ] Frontend chạy: `npm run dev`
- [ ] Đăng nhập tài khoản Admin

---

## 1. Backend API (`/api/v1/admin/dashboard/stats`)

### 1.1 Phân quyền (Authorization)
- [ ] Truy cập API với token của Student → Báo lỗi `403 Forbidden` hoặc `401 Unauthorized`.
- [ ] Truy cập API không có token → Báo lỗi `401 Unauthorized`.
- [ ] Truy cập API với token của Admin → Trả về dữ liệu thành công (`200 OK`).

### 1.2 Dữ liệu trả về (Data Integrity)
- [ ] Trường `summary.total_students`: Số lượng đếm chính xác (user có role student).
- [ ] Trường `summary.total_courses`: Bằng số lượng khóa học có status = 1 (published).
- [ ] Trường `summary.total_orders`: Bằng số đơn hàng có status = 'paid'.
- [ ] Trường `summary.total_revenue`: Bằng tổng cột `total_price` của đơn 'paid'.
- [ ] Trường `monthly_revenue`: Mảng 12 phần tử, tháng hiện tại có doanh thu khớp với Database.
- [ ] Trường `top_courses`: Tối đa 5 khóa học bán chạy nhất, sắp xếp theo doanh thu giảm dần.
- [ ] Trường `recent_orders`: Tối đa 5 đơn hàng mới nhất, sắp xếp theo ngày tạo (desc).

---

## 2. Frontend — Giao diện Dashboard (Admin)

### 2.1 Trạng thái Loading
- [ ] Khi load trang (`/admin/dashboard`), hiển thị Skeleton loading cho các chỉ số, biểu đồ và danh sách đơn hàng.

### 2.2 Hiển thị tổng quan (Summary Cards)
- [ ] Hiển thị đủ 4 thẻ: Học viên, Khóa học, Đơn hàng, Doanh thu.
- [ ] Con số hiển thị định dạng chuẩn (vd: doanh thu có định dạng VNĐ, formatCurrency).
- [ ] Số liệu khớp hoàn toàn với API trả về.

### 2.3 Biểu đồ doanh thu (Monthly Revenue Chart)
- [ ] Hiển thị đủ 12 cột (Từ T1 đến T12).
- [ ] Cột nào có doanh thu > 0 thì thanh màu xanh cao lên tương ứng so với tổng max.
- [ ] Hover vào thanh bar → Hiển thị tooltips doanh thu cụ thể của tháng đó.

### 2.4 Top khóa học bán chạy
- [ ] Hiển thị danh sách khóa học (tối đa 5).
- [ ] Cột xếp hạng 1, 2, 3...
- [ ] Tên khóa học cắt bớt nếu quá dài (truncate).
- [ ] Hiển thị lượt bán (`sales_count`) và doanh thu của khóa học đó.

### 2.5 Danh sách đơn hàng gần đây
- [ ] Hiển thị danh sách đơn hàng (tối đa 5).
- [ ] Tên học viên, Tên khóa học, Số tiền hiển thị đúng.
- [ ] Trạng thái đơn hàng (Đã thanh toán, Chờ thanh toán, Thất bại) render đúng màu sắc (xanh, vàng, đỏ).
- [ ] Link "Xem tất cả" → Chuyển hướng sang trang Quản lý đơn hàng (`/admin/orders`).

---

## 3. Edge Cases (Trường hợp ngoại lệ)

### 3.1 Không có dữ liệu (Empty State)
- [ ] DB rỗng (chưa có học viên, khóa học, đơn hàng nào).
- [ ] Các con số tổng quan đều là 0.
- [ ] Biểu đồ nằm ở mức 0% (hoặc mức tối thiểu để thấy được vạch).
- [ ] Top khóa học: Hiển thị dòng chữ "Chưa có dữ liệu".
- [ ] Đơn hàng gần đây: Hiển thị dòng chữ "Chưa có đơn hàng nào".

### 3.2 Lỗi API hoặc Network
- [ ] Tắt Backend server.
- [ ] Load lại Dashboard.
- [ ] FE không crash, catch lỗi thành công và fallback hiển thị 0 hoặc empty state mượt mà.
