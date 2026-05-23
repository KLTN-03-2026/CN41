# Mô tả Giao diện — Phía Học viên (Client)

---

## 1. Giao diện "Trang chủ"

**Mô tả:** Trang landing page giới thiệu nền tảng, hiển thị banner, thống kê, danh mục nổi bật, khóa học nổi bật, giảng viên tiêu biểu và tin tức mới nhất.

**Cách truy cập:** Truy cập đường dẫn `/` hoặc tên miền gốc của trang web.

### Nội dung giao diện

| Mục | Kiểu | Mô tả |
|-----|------|-------|
| Trang chủ | MenuItem | Item menu điều hướng đến giao diện "Trang chủ" |
| Khóa học | MenuItem | Item menu điều hướng đến giao diện "Danh sách khóa học" |
| Giảng viên | MenuItem | Item menu điều hướng đến giao diện "Danh sách giảng viên" |
| Tin tức | MenuItem | Item menu điều hướng đến giao diện "Danh sách bài viết" |
| Đăng nhập | MenuItem | Item menu điều hướng đến giao diện "Đăng nhập" (hiển thị khi chưa đăng nhập) |
| Đăng ký | MenuItem | Item menu điều hướng đến giao diện "Đăng ký" (hiển thị khi chưa đăng nhập) |
| Khóa học của tôi | MenuItem | Item menu điều hướng đến giao diện "Khóa học của tôi" (hiển thị khi đã đăng nhập) |
| Giỏ hàng | MenuItem | Icon giỏ hàng kèm số lượng, điều hướng đến giao diện "Giỏ hàng" |
| Avatar / Tên học viên | Dropdown | Menu dropdown: Khóa học của tôi, Đơn hàng, Hồ sơ, Đăng xuất |
| Banner chính (Hero) | Image | Ảnh banner lớn với tiêu đề và nút CTA "Khám phá khóa học" |
| Thống kê nền tảng | Text | Hiển thị tổng số học viên, khóa học, giảng viên, chứng chỉ |
| Danh mục nổi bật | Card | Danh sách các danh mục khóa học dạng card với icon và tên |
| Tên danh mục | LinkString | Link điều hướng đến giao diện "Danh sách khóa học" với filter danh mục |
| Khóa học nổi bật | Card | Danh sách 4–8 khóa học nổi bật dạng card |
| Tên khóa học | LinkString | Link điều hướng đến giao diện "Chi tiết khóa học" |
| Giảng viên tiêu biểu | Card | Danh sách giảng viên dạng card với ảnh, tên, số khóa học |
| Tên giảng viên | LinkString | Link điều hướng đến giao diện "Hồ sơ giảng viên" |
| Tin tức mới nhất | Card | Danh sách 3 bài viết mới nhất dạng card thumbnail |
| Tên bài viết | LinkString | Link điều hướng đến giao diện "Chi tiết bài viết" |
| Nút "Xem tất cả khóa học" | Button | Điều hướng đến giao diện "Danh sách khóa học" |
| Nút "Xem tất cả giảng viên" | Button | Điều hướng đến giao diện "Danh sách giảng viên" |
| Nút "Xem tất cả bài viết" | Button | Điều hướng đến giao diện "Danh sách bài viết" |

### Hành động

| Tên hành động | Mô tả | Thành công | Thất bại |
|---------------|-------|------------|----------|
| Nhấn "Trang chủ" | MenuItem điều hướng đến "Trang chủ" | Ở lại trang chủ | — |
| Nhấn "Khóa học" | MenuItem điều hướng đến "Danh sách khóa học" | Chuyển đến "/courses" | — |
| Nhấn "Giảng viên" | MenuItem điều hướng đến "Danh sách giảng viên" | Chuyển đến "/teachers" | — |
| Nhấn "Tin tức" | MenuItem điều hướng đến "Danh sách bài viết" | Chuyển đến "/posts" | — |
| Nhấn "Đăng nhập" | MenuItem điều hướng đến trang đăng nhập | Chuyển đến "/login" | — |
| Nhấn "Đăng ký" | MenuItem điều hướng đến trang đăng ký | Chuyển đến "/register" | — |
| Nhấn tên danh mục | LinkString điều hướng đến danh sách khóa học lọc theo danh mục | Chuyển đến "/courses?category=..." | — |
| Nhấn tên khóa học | LinkString điều hướng đến chi tiết khóa học | Chuyển đến "/courses/:slug" | — |
| Nhấn tên giảng viên | LinkString điều hướng đến hồ sơ giảng viên | Chuyển đến "/teachers/:slug" | — |
| Nhấn tên bài viết | LinkString điều hướng đến chi tiết bài viết | Chuyển đến "/posts/:slug" | — |
| Nhấn "Đăng xuất" | Xóa token, điều hướng về trang chủ | Chuyển đến "/" và xóa token | — |

*Bảng: Nội dung giao diện Trang chủ*

---

## 2. Giao diện "Danh sách khóa học"

**Mô tả:** Hiển thị toàn bộ khóa học đang hoạt động, hỗ trợ tìm kiếm theo tên, lọc theo danh mục và cấp độ, phân trang.

**Cách truy cập:** Nhấn "Khóa học" trên thanh điều hướng hoặc truy cập đường dẫn `/courses`.

### Nội dung giao diện

| Mục | Kiểu | Mô tả |
|-----|------|-------|
| Thanh điều hướng | MenuItem | Giống Trang chủ (Trang chủ, Khóa học, Giảng viên, Tin tức, ...) |
| Ô tìm kiếm | Text Input | Nhập từ khóa để tìm khóa học theo tên |
| Nút tìm kiếm | Button | Kích hoạt tìm kiếm theo từ khóa đã nhập |
| Bộ lọc cấp độ | Dropdown | Chọn: Tất cả / Cơ bản / Trung cấp / Nâng cao |
| Bộ lọc danh mục | Dropdown | Chọn danh mục (phân cấp cha–con) |
| Card khóa học | Card | Hiển thị thumbnail, tên, giảng viên, số học viên, rating, giá |
| Tên khóa học | LinkString | Link điều hướng đến giao diện "Chi tiết khóa học" |
| Thanh phân trang | Pagination | Điều hướng qua các trang, 12 khóa học mỗi trang |

### Hành động

| Tên hành động | Mô tả | Thành công | Thất bại |
|---------------|-------|------------|----------|
| Tìm kiếm | Nhập từ khóa và nhấn nút tìm kiếm | Danh sách khóa học khớp từ khóa được hiển thị | Hiển thị thông báo "Không tìm thấy khóa học" |
| Lọc cấp độ | Chọn cấp độ từ dropdown | Danh sách lọc theo cấp độ đã chọn | — |
| Lọc danh mục | Chọn danh mục từ dropdown | Danh sách lọc theo danh mục đã chọn | — |
| Xem chi tiết | Nhấn tên hoặc card khóa học | Chuyển đến "/courses/:slug" | — |
| Chuyển trang | Nhấn số trang trong thanh phân trang | Danh sách cập nhật theo trang được chọn | — |

*Bảng: Nội dung giao diện Danh sách khóa học*

---

## 3. Giao diện "Chi tiết khóa học"

**Mô tả:** Hiển thị đầy đủ thông tin khóa học: mô tả, giảng viên, chương trình học, giá, nút đăng ký/mua hoặc tiếp tục học.

**Cách truy cập:** Nhấn vào tên/card của khóa học bất kỳ hoặc truy cập đường dẫn `/courses/:slug`.

### Nội dung giao diện

| Mục | Kiểu | Mô tả |
|-----|------|-------|
| Thanh điều hướng | MenuItem | Giống Trang chủ |
| Breadcrumb | LinkString | Trang chủ > Khóa học > Tên khóa học |
| Tên khóa học | Text | Tiêu đề của khóa học |
| Mô tả khóa học | Text | Mô tả chi tiết nội dung và mục tiêu |
| Badge cấp độ | Badge | Hiển thị cấp độ: Cơ bản / Trung cấp / Nâng cao |
| Số học viên | Text | Tổng số học viên đã đăng ký |
| Đánh giá (Rating) | Text | Điểm đánh giá trung bình |
| Ảnh thumbnail | Image | Ảnh đại diện khóa học |
| Tên giảng viên | LinkString | Link điều hướng đến giao diện "Hồ sơ giảng viên" |
| Giá gốc | Text | Giá gốc (gạch ngang nếu có giá khuyến mãi) |
| Giá khuyến mãi | Text | Giá sau giảm |
| Nút "Vào học ngay" | Button | Hiển thị khi học viên đã mua khóa học |
| Nút "Thêm vào giỏ hàng" | Button | Hiển thị khi khóa học có phí và chưa mua |
| Nút "Đăng ký học miễn phí" | Button | Hiển thị khi khóa học miễn phí |
| Nút "Học thử miễn phí" | Button | Hiển thị khi có bài học xem trước |
| Chương trình học | Table | Danh sách chương (Section) và bài học (Lesson) |
| Tính năng khóa học | Text | Lưới 6 tính năng nổi bật của khóa học |
| Khóa học liên quan | Card | Các khóa học cùng danh mục |

### Hành động

| Tên hành động | Mô tả | Thành công | Thất bại |
|---------------|-------|------------|----------|
| Nhấn "Vào học ngay" | Điều hướng đến trang học | Chuyển đến "/courses/:slug/learn" | Chuyển đến "/login" nếu chưa đăng nhập |
| Nhấn "Thêm vào giỏ hàng" | Thêm khóa học vào giỏ hàng | Hiển thị thông báo thành công, cập nhật số lượng giỏ hàng | Thông báo lỗi nếu khóa học đã trong giỏ |
| Nhấn "Đăng ký học miễn phí" | Đăng ký học khóa học miễn phí | Đăng ký thành công, chuyển đến trang học | Chuyển đến "/login" nếu chưa đăng nhập |
| Nhấn "Học thử miễn phí" | Mở bài học xem trước | Chuyển đến "/courses/:slug/learn" | — |
| Nhấn tên giảng viên | Xem hồ sơ giảng viên | Chuyển đến "/teachers/:slug" | — |
| Nhấn khóa học liên quan | Xem chi tiết khóa học khác | Chuyển đến "/courses/:slug" tương ứng | — |

*Bảng: Nội dung giao diện Chi tiết khóa học*

---

## 4. Giao diện "Danh sách giảng viên"

**Mô tả:** Hiển thị danh sách tất cả giảng viên đang hoạt động, hỗ trợ tìm kiếm theo tên, xem thông tin cơ bản.

**Cách truy cập:** Nhấn "Giảng viên" trên thanh điều hướng hoặc truy cập `/teachers`.

### Nội dung giao diện

| Mục | Kiểu | Mô tả |
|-----|------|-------|
| Thanh điều hướng | MenuItem | Giống Trang chủ |
| Ô tìm kiếm | Text Input | Nhập tên để tìm giảng viên (debounced 300ms) |
| Card giảng viên | Card | Hiển thị ảnh đại diện, tên, số khóa học, mô tả ngắn |
| Tên giảng viên | LinkString | Link điều hướng đến giao diện "Hồ sơ giảng viên" |

### Hành động

| Tên hành động | Mô tả | Thành công | Thất bại |
|---------------|-------|------------|----------|
| Tìm kiếm giảng viên | Nhập tên giảng viên vào ô tìm kiếm | Danh sách lọc theo tên nhập vào | Hiển thị thông báo "Không tìm thấy giảng viên" |
| Xem hồ sơ | Nhấn vào card hoặc tên giảng viên | Chuyển đến "/teachers/:slug" | — |

*Bảng: Nội dung giao diện Danh sách giảng viên*

---

## 5. Giao diện "Hồ sơ giảng viên"

**Mô tả:** Hiển thị thông tin chi tiết giảng viên: ảnh đại diện, tiểu sử, kinh nghiệm, và danh sách khóa học của giảng viên đó.

**Cách truy cập:** Nhấn vào tên giảng viên bất kỳ hoặc truy cập `/teachers/:slug`.

### Nội dung giao diện

| Mục | Kiểu | Mô tả |
|-----|------|-------|
| Thanh điều hướng | MenuItem | Giống Trang chủ |
| Ảnh đại diện | Image | Ảnh profile của giảng viên |
| Tên giảng viên | Text | Tên đầy đủ |
| Tiểu sử | Text | Mô tả kinh nghiệm và chuyên môn |
| Số năm kinh nghiệm | Text | Số năm kinh nghiệm trong nghề |
| Số khóa học | Text | Tổng số khóa học giảng viên đã tạo |
| Danh sách khóa học | Card | Các khóa học của giảng viên này |
| Tên khóa học | LinkString | Link điều hướng đến "Chi tiết khóa học" |

### Hành động

| Tên hành động | Mô tả | Thành công | Thất bại |
|---------------|-------|------------|----------|
| Xem chi tiết khóa học | Nhấn vào card khóa học của giảng viên | Chuyển đến "/courses/:slug" | — |

*Bảng: Nội dung giao diện Hồ sơ giảng viên*

---

## 6. Giao diện "Danh sách bài viết" (Blog)

**Mô tả:** Trang blog hiển thị tất cả bài viết đã xuất bản, hỗ trợ tìm kiếm và lọc theo danh mục bài viết.

**Cách truy cập:** Nhấn "Tin tức" trên thanh điều hướng hoặc truy cập `/posts`.

### Nội dung giao diện

| Mục | Kiểu | Mô tả |
|-----|------|-------|
| Thanh điều hướng | MenuItem | Giống Trang chủ |
| Banner tiêu đề trang | Text | Tiêu đề "Tin tức & Bài viết" với mô tả |
| Ô tìm kiếm | Text Input | Nhập từ khóa tìm bài viết |
| Nút tìm kiếm | Button | Kích hoạt tìm kiếm |
| Lọc danh mục | Select | Chọn danh mục bài viết để lọc |
| Card bài viết | Card | Thumbnail, tiêu đề, danh mục, ngày đăng, lượt xem |
| Tên bài viết | LinkString | Link điều hướng đến giao diện "Chi tiết bài viết" |
| Sidebar danh mục | LinkString | Danh sách danh mục bài viết, click để lọc |
| Bài viết nổi bật | LinkString | Danh sách bài viết nhiều lượt xem nhất |
| Thanh phân trang | Pagination | Điều hướng qua các trang kết quả |

### Hành động

| Tên hành động | Mô tả | Thành công | Thất bại |
|---------------|-------|------------|----------|
| Tìm kiếm bài viết | Nhập từ khóa và nhấn tìm kiếm | Danh sách bài viết khớp từ khóa | Hiển thị thông báo "Không tìm thấy bài viết" |
| Lọc theo danh mục | Chọn danh mục từ sidebar hoặc dropdown | Danh sách lọc theo danh mục | — |
| Xem chi tiết bài viết | Nhấn tiêu đề hoặc thumbnail bài viết | Chuyển đến "/posts/:slug" | — |
| Chuyển trang | Nhấn số trang phân trang | Cập nhật danh sách theo trang | — |

*Bảng: Nội dung giao diện Danh sách bài viết*

---

## 7. Giao diện "Chi tiết bài viết"

**Mô tả:** Hiển thị toàn bộ nội dung bài viết, thông tin tác giả, ngày đăng, và các bài viết liên quan.

**Cách truy cập:** Nhấn vào tiêu đề bài viết bất kỳ hoặc truy cập `/posts/:slug`.

### Nội dung giao diện

| Mục | Kiểu | Mô tả |
|-----|------|-------|
| Thanh điều hướng | MenuItem | Giống Trang chủ |
| Breadcrumb | LinkString | Trang chủ > Tin tức > Tên bài viết |
| Tiêu đề bài viết | Text | Tiêu đề lớn đầu trang |
| Ảnh đại diện (thumbnail) | Image | Ảnh bìa của bài viết |
| Tên tác giả | Text | Tên người đăng bài |
| Ngày đăng | Text | Ngày xuất bản bài viết |
| Số lượt xem | Text | Tổng lượt xem của bài viết |
| Danh mục | Badge | Danh mục bài viết |
| Nội dung bài viết | Text | Toàn bộ nội dung rich-text |
| Tags | Badge | Danh sách tag gắn với bài viết |
| Bài viết liên quan | Card | Gợi ý bài viết cùng danh mục |
| Tên bài viết liên quan | LinkString | Link điều hướng đến bài viết liên quan |

### Hành động

| Tên hành động | Mô tả | Thành công | Thất bại |
|---------------|-------|------------|----------|
| Nhấn breadcrumb | Điều hướng theo breadcrumb | Chuyển đến trang tương ứng | — |
| Nhấn bài viết liên quan | Xem bài viết khác | Chuyển đến "/posts/:slug" tương ứng | — |
| Nhấn tag | Lọc bài viết theo tag | Chuyển đến "/posts?tag=..." | — |

*Bảng: Nội dung giao diện Chi tiết bài viết*

---

## 8. Giao diện "Giỏ hàng"

**Mô tả:** Hiển thị danh sách khóa học đã thêm vào giỏ, cho phép xóa từng khóa, xóa toàn bộ và tiến hành thanh toán.

**Cách truy cập:** Nhấn icon giỏ hàng trên thanh điều hướng hoặc truy cập `/cart`. Yêu cầu đăng nhập.

### Nội dung giao diện

| Mục | Kiểu | Mô tả |
|-----|------|-------|
| Thanh điều hướng | MenuItem | Giống Trang chủ |
| Danh sách khóa học trong giỏ | Card | Mỗi item: thumbnail, tên, giá, nút xóa |
| Nút xóa từng khóa học | Button | Xóa một khóa học cụ thể khỏi giỏ |
| Nút "Xóa toàn bộ" | Button | Xóa hết tất cả khóa học trong giỏ |
| Tên khóa học | LinkString | Link điều hướng đến "Chi tiết khóa học" |
| Tổng tiền | Text | Tổng giá trị các khóa học trong giỏ |
| Nút "Tiến hành thanh toán" | Button | Điều hướng đến trang thanh toán |
| Thông báo giỏ trống | Text | Hiển thị khi giỏ hàng không có sản phẩm |
| Nút "Khám phá khóa học" | Button | Điều hướng đến "/courses" khi giỏ trống |

### Hành động

| Tên hành động | Mô tả | Thành công | Thất bại |
|---------------|-------|------------|----------|
| Xóa khóa học | Nhấn nút xóa trên từng item | Khóa học bị xóa khỏi giỏ, tổng tiền cập nhật | — |
| Xóa toàn bộ | Nhấn "Xóa toàn bộ" | Giỏ hàng trống, hiển thị trạng thái empty | — |
| Tiến hành thanh toán | Nhấn nút "Tiến hành thanh toán" | Chuyển đến "/checkout" | Chuyển đến "/login" nếu chưa đăng nhập |
| Khám phá khóa học | Nhấn "Khám phá khóa học" khi giỏ trống | Chuyển đến "/courses" | — |

*Bảng: Nội dung giao diện Giỏ hàng*

---

## 9. Giao diện "Thanh toán"

**Mô tả:** Xem lại đơn hàng, nhập mã giảm giá, xem tổng tiền và thực hiện thanh toán qua VNPAY.

**Cách truy cập:** Nhấn "Tiến hành thanh toán" từ Giỏ hàng hoặc truy cập `/checkout`. Yêu cầu đăng nhập.

### Nội dung giao diện

| Mục | Kiểu | Mô tả |
|-----|------|-------|
| Thanh điều hướng | MenuItem | Giống Trang chủ |
| Danh sách khóa học đặt mua | Card | Tên, giá từng khóa học trong đơn |
| Thông tin học viên | Text | Tên, email của học viên đang đăng nhập |
| Ô nhập mã giảm giá | Text Input | Nhập mã coupon để áp dụng giảm giá |
| Nút "Áp dụng" | Button | Kiểm tra và áp dụng mã giảm giá |
| Tiền giảm | Text | Hiển thị số tiền được giảm (sau khi áp mã) |
| Tổng tiền | Text | Tổng số tiền phải thanh toán |
| Chọn phương thức thanh toán | Radio Button | VNPAY (phương thức duy nhất hiện tại) |
| Nút "Thanh toán ngay" | Button | Tạo đơn hàng và chuyển sang cổng VNPAY |

### Hành động

| Tên hành động | Mô tả | Thành công | Thất bại |
|---------------|-------|------------|----------|
| Áp dụng mã giảm giá | Nhập mã và nhấn "Áp dụng" | Hiển thị số tiền giảm, cập nhật tổng tiền | Hiển thị lỗi: mã không hợp lệ, hết hạn, hoặc chưa đến ngày sử dụng |
| Thanh toán | Nhấn "Thanh toán ngay" | Tạo đơn hàng, chuyển hướng đến cổng thanh toán VNPAY | Hiển thị thông báo lỗi nếu hệ thống lỗi |

*Bảng: Nội dung giao diện Thanh toán*

---

## 10. Giao diện "Kết quả thanh toán"

**Mô tả:** Hiển thị kết quả sau khi hoàn tất giao dịch VNPAY: thành công, thất bại hoặc đang chờ xử lý.

**Cách truy cập:** Được VNPAY chuyển hướng về sau khi thanh toán, đường dẫn `/payment/result`.

### Nội dung giao diện

| Mục | Kiểu | Mô tả |
|-----|------|-------|
| Thanh điều hướng | MenuItem | Giống Trang chủ |
| Icon kết quả | Image | Icon tick xanh (thành công) / X đỏ (thất bại) / đồng hồ (đang xử lý) |
| Tiêu đề kết quả | Text | "Thanh toán thành công" / "Thanh toán thất bại" / "Đang xử lý" |
| Mã đơn hàng | Text | Mã order_code của đơn hàng |
| Số tiền đã thanh toán | Text | Tổng số tiền giao dịch |
| Ngày giờ giao dịch | Text | Thời điểm thực hiện giao dịch |
| Nút "Vào học ngay" | Button | Điều hướng đến "/my-courses" (khi thành công) |
| Nút "Về trang chủ" | Button | Điều hướng về "/" |
| Nút "Thử lại" | Button | Quay lại giỏ hàng để thử thanh toán lại (khi thất bại) |

### Hành động

| Tên hành động | Mô tả | Thành công | Thất bại |
|---------------|-------|------------|----------|
| Nhấn "Vào học ngay" | Điều hướng đến khóa học đã mua | Chuyển đến "/my-courses" | — |
| Nhấn "Về trang chủ" | Điều hướng về trang chủ | Chuyển đến "/" | — |
| Nhấn "Thử lại" | Quay lại giỏ hàng | Chuyển đến "/cart" | — |

*Bảng: Nội dung giao diện Kết quả thanh toán*

---

## 11. Giao diện "Khóa học của tôi"

**Mô tả:** Hiển thị danh sách tất cả khóa học mà học viên đã đăng ký hoặc mua, kèm tiến độ học.

**Cách truy cập:** Nhấn "Khóa học của tôi" trong menu hoặc truy cập `/my-courses`. Yêu cầu đăng nhập.

### Nội dung giao diện

| Mục | Kiểu | Mô tả |
|-----|------|-------|
| Thanh điều hướng | MenuItem | Giống Trang chủ |
| Card khóa học | Card | Thumbnail, tên khóa học, tiến độ học (%) |
| Thanh tiến độ | Text | Phần trăm bài học đã hoàn thành |
| Nút "Tiếp tục học" | Button | Chuyển đến bài học tiếp theo chưa hoàn thành |
| Tên khóa học | LinkString | Link điều hướng đến "/courses/:slug/learn" |
| Thông báo trống | Text | Hiển thị khi chưa đăng ký khóa học nào |
| Nút "Khám phá khóa học" | Button | Điều hướng đến "/courses" khi chưa có khóa học |

### Hành động

| Tên hành động | Mô tả | Thành công | Thất bại |
|---------------|-------|------------|----------|
| Tiếp tục học | Nhấn card hoặc nút "Tiếp tục học" | Chuyển đến "/courses/:slug/learn" | — |
| Khám phá khóa học | Nhấn nút khi danh sách trống | Chuyển đến "/courses" | — |

*Bảng: Nội dung giao diện Khóa học của tôi*

---

## 12. Giao diện "Đơn hàng của tôi"

**Mô tả:** Lịch sử mua hàng của học viên, hiển thị danh sách đơn hàng với trạng thái và chi tiết.

**Cách truy cập:** Nhấn "Đơn hàng" trong menu dropdown tài khoản hoặc truy cập `/my-orders`. Yêu cầu đăng nhập.

### Nội dung giao diện

| Mục | Kiểu | Mô tả |
|-----|------|-------|
| Thanh điều hướng | MenuItem | Giống Trang chủ |
| Bảng đơn hàng | Table | Mã đơn hàng, ngày tạo, tổng tiền, trạng thái, hành động |
| Mã đơn hàng | Text | order_code của đơn |
| Trạng thái đơn | Badge | Đang xử lý / Đã thanh toán / Thất bại / Đã hủy |
| Nút "Xem chi tiết" | Button | Mở modal chi tiết đơn hàng |
| Modal chi tiết đơn | Modal | Danh sách khóa học, số tiền giảm, tổng tiền, phương thức |

### Hành động

| Tên hành động | Mô tả | Thành công | Thất bại |
|---------------|-------|------------|----------|
| Xem chi tiết đơn | Nhấn "Xem chi tiết" | Mở modal hiển thị thông tin đầy đủ đơn hàng | — |
| Đóng modal | Nhấn nút đóng | Modal ẩn đi | — |

*Bảng: Nội dung giao diện Đơn hàng của tôi*

---

## 13. Giao diện "Hồ sơ cá nhân"

**Mô tả:** Học viên xem và chỉnh sửa thông tin tài khoản: ảnh đại diện, tên, ngày sinh, đổi mật khẩu và đăng xuất.

**Cách truy cập:** Nhấn Avatar > "Hồ sơ" hoặc truy cập `/profile`. Yêu cầu đăng nhập.

### Nội dung giao diện

| Mục | Kiểu | Mô tả |
|-----|------|-------|
| Thanh điều hướng | MenuItem | Giống Trang chủ |
| Ảnh đại diện | Image | Ảnh profile hiện tại |
| Nút thay đổi ảnh | Button | Mở file picker để tải ảnh mới lên |
| Ô tên | Text Input | Nhập tên hiển thị mới |
| Email | Text | Hiển thị email (không chỉnh sửa được) |
| Ngày sinh | Text Input | Chọn ngày sinh |
| Nút "Lưu thay đổi" | Button | Gửi cập nhật thông tin profile |
| Ô mật khẩu hiện tại | Text Input | Nhập mật khẩu cũ khi đổi mật khẩu |
| Ô mật khẩu mới | Text Input | Nhập mật khẩu mới |
| Ô xác nhận mật khẩu | Text Input | Nhập lại mật khẩu mới |
| Nút "Đổi mật khẩu" | Button | Thực hiện đổi mật khẩu |
| Nút "Đăng xuất" | Button | Đăng xuất khỏi tài khoản |

### Hành động

| Tên hành động | Mô tả | Thành công | Thất bại |
|---------------|-------|------------|----------|
| Cập nhật hồ sơ | Nhấn "Lưu thay đổi" | Hiển thị thông báo "Cập nhật thành công" | Hiển thị lỗi validation (tên bỏ trống, ...) |
| Đổi mật khẩu | Nhấn "Đổi mật khẩu" | Thông báo "Đổi mật khẩu thành công" | Lỗi nếu mật khẩu cũ sai hoặc mật khẩu mới không khớp |
| Đăng xuất | Nhấn "Đăng xuất" | Xóa token, chuyển về "/" | — |
| Tải ảnh đại diện | Chọn file ảnh | Ảnh cập nhật trên giao diện và server | Lỗi nếu file không đúng định dạng hoặc quá lớn |

*Bảng: Nội dung giao diện Hồ sơ cá nhân*

---

## 14. Giao diện "Trang học" (Learn)

**Mô tả:** Giao diện học tập toàn màn hình, hiển thị nội dung bài học (video/tài liệu/bài kiểm tra) kèm sidebar danh sách bài học và điều hướng tiếp/trước.

**Cách truy cập:** Nhấn "Vào học" từ trang chi tiết khóa học, truy cập `/courses/:slug/learn`. Yêu cầu đăng nhập và đã đăng ký khóa học.

### Nội dung giao diện

| Mục | Kiểu | Mô tả |
|-----|------|-------|
| Header trang học | Text | Logo và nút quay lại trang chi tiết khóa học |
| Sidebar danh sách bài học | Table | Cây cấu trúc: Chương > Bài học, kèm trạng thái hoàn thành |
| Tên chương (Section) | Text | Tiêu đề chương, có thể mở/thu gọn |
| Tên bài học (Lesson) | LinkString | Click để chuyển sang bài học đó |
| Icon trạng thái bài học | Badge | Tick xanh (đã học), icon khóa (chưa mua), mặc định |
| Khu vực nội dung chính | — | Hiển thị video / tài liệu / quiz tùy loại bài học |
| Video player | — | Phát video bài học, theo dõi thời gian xem |
| Document viewer | — | Hiển thị PDF/tài liệu bài học |
| Quiz panel | — | Giao diện làm bài trắc nghiệm nội tuyến |
| Tiêu đề bài học | Text | Tên bài học đang hiển thị |
| Badge loại bài học | Badge | Video / Tài liệu / Văn bản / Kiểm tra |
| Thời lượng | Text | Độ dài bài học (phút/giây) |
| Nút "Đánh dấu hoàn thành" | Button | Đánh dấu bài học đã hoàn thành |
| Nút "Bài trước" | Button | Chuyển sang bài học trước |
| Nút "Bài tiếp theo" | Button | Chuyển sang bài học kế tiếp |
| Nút toggle Sidebar | Button | Ẩn/hiện sidebar (trên màn hình nhỏ) |
| Overlay khóa | — | Hiển thị khi bài học yêu cầu mua mà chưa mua |

### Hành động

| Tên hành động | Mô tả | Thành công | Thất bại |
|---------------|-------|------------|----------|
| Chọn bài học | Nhấn tên bài học trong sidebar | Tải nội dung bài học tương ứng | Hiển thị overlay khóa nếu chưa mua |
| Đánh dấu hoàn thành | Nhấn nút "Đánh dấu hoàn thành" | Tiến độ cập nhật, icon tick xuất hiện | — |
| Bài tiếp theo | Nhấn nút "Bài tiếp theo" | Chuyển sang bài học kế tiếp | Ẩn nút nếu đang ở bài cuối |
| Bài trước | Nhấn nút "Bài trước" | Chuyển sang bài học trước | Ẩn nút nếu đang ở bài đầu |
| Làm bài quiz nội tuyến | Nhấn bài học loại Quiz | Mở giao diện quiz trong trang học | — |

*Bảng: Nội dung giao diện Trang học*

---

## 15. Giao diện "Làm bài trắc nghiệm"

**Mô tả:** Giao diện làm bài kiểm tra trắc nghiệm toàn màn hình, hiển thị câu hỏi, đáp án và kết quả sau khi nộp bài.

**Cách truy cập:** Nhấn vào bài học loại Quiz trong trang học, truy cập `/lessons/:lessonId/quiz`. Yêu cầu đăng nhập.

### Nội dung giao diện

| Mục | Kiểu | Mô tả |
|-----|------|-------|
| Tiêu đề bài kiểm tra | Text | Tên quiz |
| Số câu hỏi | Text | Tổng số câu và câu đang làm (VD: 3/10) |
| Đồng hồ đếm ngược | Text | Hiển thị thời gian còn lại (nếu có giới hạn thời gian) |
| Nội dung câu hỏi | Text | Câu hỏi dạng văn bản |
| Đáp án A | Radio Button | Lựa chọn đáp án A |
| Đáp án B | Radio Button | Lựa chọn đáp án B |
| Đáp án C | Radio Button | Lựa chọn đáp án C |
| Đáp án D | Radio Button | Lựa chọn đáp án D |
| Nút "Nộp bài" | Button | Nộp toàn bộ bài trắc nghiệm |
| Nút "Câu tiếp theo" | Button | Chuyển sang câu hỏi tiếp theo |
| **Phần kết quả (sau nộp bài)** | | |
| Điểm số | Text | Số câu đúng / tổng số câu |
| Phần trăm đúng | Text | Tỷ lệ đúng dạng % |
| Emoji phản hồi | Image | Biểu cảm theo điểm: ≥80% vui, 50–79% bình thường, <50% buồn |
| Phân tích từng câu | Table | Câu hỏi, đáp án đã chọn, đáp án đúng, trạng thái đúng/sai |
| Nút "Làm lại" | Button | Làm bài lần nữa (nếu còn lượt) |
| Nút "Xem lịch sử" | Button | Điều hướng đến giao diện "Lịch sử làm bài" |
| Nút "Quay lại trang học" | Button | Trở về trang học |

### Hành động

| Tên hành động | Mô tả | Thành công | Thất bại |
|---------------|-------|------------|----------|
| Chọn đáp án | Nhấn radio button đáp án | Đáp án được chọn (highlight) | — |
| Nộp bài | Nhấn "Nộp bài" | Hiển thị kết quả điểm số và phân tích | Thông báo nếu chưa trả lời hết câu |
| Làm lại | Nhấn "Làm lại" | Bài quiz reset, bắt đầu lại từ câu 1 | Hiển thị thông báo "Đã hết lượt làm bài" nếu max_attempts đạt giới hạn |
| Xem lịch sử | Nhấn "Xem lịch sử" | Chuyển đến "/quizzes/:id/history" | — |
| Quay lại trang học | Nhấn "Quay lại trang học" | Chuyển về trang học | — |

*Bảng: Nội dung giao diện Làm bài trắc nghiệm*

---

## 16. Giao diện "Lịch sử làm bài trắc nghiệm"

**Mô tả:** Hiển thị tất cả các lần làm bài của học viên cho một bài quiz, bao gồm điểm số và thời gian mỗi lần.

**Cách truy cập:** Nhấn "Xem lịch sử" từ trang làm bài, truy cập `/quizzes/:id/history`. Yêu cầu đăng nhập.

### Nội dung giao diện

| Mục | Kiểu | Mô tả |
|-----|------|-------|
| Tiêu đề | Text | "Lịch sử làm bài: [Tên quiz]" |
| Bảng lịch sử | Table | STT, ngày giờ làm bài, số câu đúng, tổng câu, điểm % |
| Điểm số | Text | Hiển thị điểm mỗi lần nộp |
| Ngày giờ | Text | Thời điểm completed_at |
| Nút "Quay lại" | Button | Quay về trang trước |

### Hành động

| Tên hành động | Mô tả | Thành công | Thất bại |
|---------------|-------|------------|----------|
| Quay lại | Nhấn nút "Quay lại" | Về trang làm bài hoặc trang học | — |

*Bảng: Nội dung giao diện Lịch sử làm bài trắc nghiệm*
