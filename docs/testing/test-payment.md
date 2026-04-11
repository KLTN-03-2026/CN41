# 💳 Test Checklist — VNPAY Payment Integration

## Chuẩn bị
- [ ] Chạy `php artisan migrate:fresh --seed`
- [ ] Đảm bảo `.env` có VNPAY credentials
- [ ] Backend chạy: `php artisan serve`
- [ ] Frontend chạy: `npm run dev`
- [ ] Đăng nhập tài khoản Student

---

## 1. Giỏ hàng (CartPage)

### 1.1 Thêm khóa học vào giỏ hàng
- [ ] Vào chi tiết khóa học có giá → Click **"Thêm vào giỏ hàng"** → Redirect sang `/cart`
- [ ] Khóa học hiện trong giỏ với đúng thumbnail, tên, giá
- [ ] Nếu có `sale_price` → hiện giá gốc gạch ngang + giá sale
- [ ] Thêm khóa học thứ 2 → Giỏ hàng hiện 2 item, tổng cộng đúng

### 1.2 Quản lý giỏ hàng
- [ ] Click nút **xoá** trên từng item → Item bị xoá, tổng cập nhật
- [ ] Click **"Xoá tất cả"** → Giỏ hàng trống, hiện empty state
- [ ] **Persist**: Reload trang → Giỏ hàng vẫn giữ items (localStorage)
- [ ] Empty state: Hiện nút **"Khám phá khóa học"** → link đúng `/courses`

### 1.3 Ngăn trùng lặp
- [ ] Thêm cùng 1 khóa học 2 lần → Chỉ hiện 1 lần (Pinia store đã handle)
- [ ] Khóa học đã mua (is_purchased) → Khi click "Thêm vào giỏ" → Toast info + redirect `/learn`

---

## 2. Thanh toán (CheckoutPage)

### 2.1 Giao diện
- [ ] Hiện danh sách khóa học từ giỏ hàng
- [ ] Hiện tạm tính, giảm giá (nếu có), tổng cộng
- [ ] **PaymentMethodSelector**: VNPAY radio active, MoMo disabled "Sắp ra mắt"

### 2.2 Tạo đơn hàng
- [ ] Click **"Thanh toán ngay"** → Loading spinner → Redirect sang VNPAY sandbox
- [ ] URL VNPAY chứa đúng `vnp_Amount`, `vnp_TxnRef`, `vnp_OrderInfo`

### 2.3 Validation errors
- [ ] Thêm khóa học đã sở hữu vào cart (qua URL trực tiếp) → Submit → Backend trả **422** "Bạn đã sở hữu các khóa học: ..."
- [ ] Error message hiện trên checkout page (ô đỏ)

### 2.4 Giỏ hàng trống
- [ ] Truy cập `/checkout` khi giỏ trống → Hiện message + link khám phá

---

## 3. VNPAY Sandbox

### 3.1 Thanh toán thành công
- [ ] Trên VNPAY sandbox, chọn ngân hàng NCB
- [ ] Số thẻ: `9704198526191432198` | Tên: `NGUYEN VAN A` | Ngày: `07/15` | OTP: `123456`
- [ ] Sau khi thanh toán → Redirect về `/payment/result?status=success&order_code=ORD-...`
- [ ] Trang kết quả hiện ✅ **"Thanh toán thành công!"** với mã đơn hàng
- [ ] Nút **"Vào học ngay"** và **"Xem đơn hàng"** hoạt động

### 3.2 Thanh toán thất bại
- [ ] Trên VNPAY sandbox → Click **"Huỷ"** hoặc nhập sai OTP
- [ ] Redirect về `/payment/result?status=failed`
- [ ] Trang kết quả hiện ❌ **"Thanh toán thất bại"**
- [ ] Nút **"Thử lại"** → Tạo payment URL mới → Redirect lại VNPAY

### 3.3 Giỏ hàng sau thanh toán
- [ ] Thanh toán thành công → Giỏ hàng tự clear (count = 0)
- [ ] Thanh toán thất bại → Giỏ hàng vẫn giữ items

---

## 4. Đơn hàng đã thanh toán → Trải nghiệm học

### 4.1 CourseDetailPage sau khi mua
- [ ] Vào chi tiết khóa học đã mua → Hiện nút **"Vào học ngay"** (xanh lá)
- [ ] **KHÔNG** hiện nút "Thêm vào giỏ hàng"
- [ ] Danh sách bài giảng: tất cả lessons unlock (không có icon 🔒)

### 4.2 LearnPage
- [ ] Click "Vào học ngay" → Vào trang learn → Video player hoạt động

---

## 5. Lịch sử đơn hàng (MyOrdersPage)

### 5.1 Danh sách đơn hàng
- [ ] Truy cập `/my-orders` → Hiện danh sách đơn hàng
- [ ] Mỗi đơn hiện: mã đơn, status badge, ngày tạo, items + thumbnails, tổng tiền
- [ ] Đơn **paid** → Badge xanh "Đã thanh toán" + nút **"Vào học"**
- [ ] Đơn **pending/failed** → Badge vàng/đỏ + nút **"Thanh toán lại"**

### 5.2 Thanh toán lại
- [ ] Click **"Thanh toán lại"** → Redirect sang VNPAY sandbox
- [ ] Thanh toán thành công → Status cập nhật sang "Đã thanh toán"

### 5.3 Pagination
- [ ] Tạo > 10 đơn → Pagination hiện đúng, click page khác hoạt động

### 5.4 Empty state
- [ ] Student mới chưa có đơn → Hiện "Bạn chưa có đơn hàng nào" + link khám phá

---

## 6. Admin — Quản lý đơn hàng

### 6.1 Danh sách đơn hàng
- [ ] Truy cập `/admin/orders` → Bảng danh sách đầy đủ
- [ ] Hiện: mã đơn, học viên (tên + email), khóa học, tổng tiền, status badge, ngày

### 6.2 Bộ lọc
- [ ] Tìm kiếm theo mã đơn → Kết quả đúng
- [ ] Tìm kiếm theo tên/email sinh viên → Kết quả đúng
- [ ] Filter theo status (paid/pending/failed) → Đúng
- [ ] Filter theo date range → Đúng
- [ ] **"Xoá bộ lọc"** → Reset tất cả

### 6.3 Chi tiết đơn hàng (Modal)
- [ ] Click icon **mắt** → Modal hiện chi tiết đơn hàng
- [ ] Hiện: thông tin order, student, items + giá, financial summary
- [ ] Hiện bảng **"Lịch sử giao dịch"** với transaction code, status
- [ ] **Cập nhật trạng thái**: chọn status mới → Click **"Lưu"** → Toast success

### 6.4 Xoá đơn hàng
- [ ] Click icon **thùng rác** → Confirm → Đơn bị soft delete
- [ ] Đơn biến mất khỏi danh sách

### 6.5 Dashboard
- [ ] `/admin/dashboard` → Widget doanh thu hiện dữ liệu thực (hoặc 0 nếu chưa có đơn paid)
- [ ] Bảng **"Đơn hàng gần đây"** hiện 5 đơn mới nhất
- [ ] Link **"Xem tất cả"** → Chuyển sang `/admin/orders`

---

## 7. Edge Cases & Bảo mật

- [ ] Chưa đăng nhập → Click "Thêm vào giỏ" → Redirect `/login?redirect=/cart`
- [ ] Chưa đăng nhập → Truy cập `/checkout` → Redirect login
- [ ] Chưa đăng nhập → Truy cập `/my-orders` → Redirect login
- [ ] Student A không thể xem đơn hàng của Student B (403)
- [ ] API `/admin/orders` không truy cập được với token Student (403 hoặc 401)

---

## 8. VNPAY Logging
- [ ] Sau khi thanh toán → Kiểm tra file `storage/logs/vnpay-*.log`
- [ ] File log chứa payload đầy đủ từ VNPAY (vnp_Amount, vnp_ResponseCode, etc.)
