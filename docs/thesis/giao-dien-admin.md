# Mô tả Giao diện — Quản trị (Admin Panel)

> Tất cả giao diện Admin yêu cầu đăng nhập với tài khoản có vai trò admin/super-admin/teacher.
> Sidebar Admin chứa các mục điều hướng: Dashboard, Khóa học, Danh mục, Người dùng, Giảng viên, Học viên, Đơn hàng, Bài viết, Mã giảm giá, Nhật ký.

---

## 24. Giao diện "Dashboard Admin"

**Mô tả:** Tổng quan hệ thống với các số liệu thống kê: doanh thu, đơn hàng, học viên, khóa học và biểu đồ.

**Cách truy cập:** Đăng nhập admin thành công, hoặc nhấn "Dashboard" trong sidebar, hoặc truy cập `/admin/dashboard`.

### Nội dung giao diện

| Mục | Kiểu | Mô tả |
|-----|------|-------|
| Sidebar | MenuItem | Điều hướng tới các phần quản lý |
| Header Admin | Text | Logo, tên người dùng đang đăng nhập, nút đăng xuất |
| Thẻ Tổng doanh thu | Card | Tổng doanh thu đã nhận (VNĐ) |
| Thẻ Tổng đơn hàng | Card | Số đơn hàng đã thanh toán |
| Thẻ Tổng học viên | Card | Số học viên đã đăng ký tài khoản |
| Thẻ Tổng khóa học | Card | Số khóa học đang hoạt động |
| Biểu đồ doanh thu | Image | Biểu đồ đường doanh thu theo tháng |
| Biểu đồ học viên mới | Image | Biểu đồ cột học viên đăng ký theo tháng |
| Danh sách đơn hàng gần đây | Table | 5–10 đơn hàng mới nhất: mã, học viên, tiền, trạng thái |
| Nút đăng xuất | Button | Đăng xuất khỏi tài khoản admin |

### Hành động

| Tên hành động | Mô tả | Thành công | Thất bại |
|---------------|-------|------------|----------|
| Nhấn menu Sidebar | Điều hướng đến phần quản lý tương ứng | Chuyển đến trang tương ứng | — |
| Đăng xuất | Nhấn nút đăng xuất | Xóa adminToken, chuyển đến "/admin/login" | — |

*Bảng: Nội dung giao diện Dashboard Admin*

---

## 25. Giao diện "Quản lý khóa học" (Admin)

**Mô tả:** Danh sách toàn bộ khóa học với bộ lọc, tìm kiếm, tạo mới, chỉnh sửa và xóa mềm/khôi phục.

**Cách truy cập:** Nhấn "Khóa học" trong sidebar hoặc truy cập `/admin/courses`.

### Nội dung giao diện

| Mục | Kiểu | Mô tả |
|-----|------|-------|
| Sidebar | MenuItem | Điều hướng Admin |
| Tab "Đang hoạt động" | Tab | Hiển thị danh sách khóa học chưa xóa |
| Tab "Thùng rác" | Tab | Hiển thị khóa học đã xóa mềm |
| Ô tìm kiếm | Text Input | Tìm khóa học theo tên |
| Bộ lọc trạng thái | Dropdown | Tất cả / Bản nháp / Đã xuất bản |
| Bộ lọc cấp độ | Dropdown | Tất cả / Cơ bản / Trung cấp / Nâng cao |
| Nút "Tạo khóa học" | Button | Chuyển đến giao diện "Tạo khóa học" |
| Bảng khóa học | Table | Tên, slug, giảng viên, học viên, trạng thái, hành động |
| Nút "Sửa" | Button | Chuyển đến giao diện "Sửa khóa học" |
| Nút "Xóa" | Button | Xóa mềm khóa học (chuyển vào Thùng rác) |
| Nút "Khôi phục" (trong Thùng rác) | Button | Khôi phục khóa học đã xóa |
| Nút "Xóa vĩnh viễn" (trong Thùng rác) | Button | Xóa hoàn toàn khỏi hệ thống |
| Checkbox bulk-select | Checkbox | Chọn nhiều khóa học để thao tác hàng loạt |
| Nút "Xóa hàng loạt" | Button | Xóa nhiều khóa học đã chọn |
| Thanh phân trang | Pagination | Điều hướng qua các trang |

### Hành động

| Tên hành động | Mô tả | Thành công | Thất bại |
|---------------|-------|------------|----------|
| Tạo khóa học | Nhấn "Tạo khóa học" | Chuyển đến "/admin/courses/create" | — |
| Sửa khóa học | Nhấn "Sửa" | Chuyển đến "/admin/courses/:id/edit" | — |
| Xóa khóa học | Nhấn "Xóa" và xác nhận | Khóa học chuyển vào Thùng rác | Thông báo lỗi nếu không có quyền |
| Khôi phục | Nhấn "Khôi phục" trong tab Thùng rác | Khóa học khôi phục vào danh sách chính | — |
| Xóa vĩnh viễn | Nhấn "Xóa vĩnh viễn" và xác nhận | Xóa hoàn toàn khỏi database | — |
| Tìm kiếm | Nhập từ khóa | Danh sách lọc theo tên | — |
| Lọc | Chọn bộ lọc | Danh sách cập nhật theo bộ lọc | — |

*Bảng: Nội dung giao diện Quản lý khóa học*

---

## 26. Giao diện "Form khóa học" (Tạo/Sửa)

**Mô tả:** Form tạo mới hoặc chỉnh sửa khóa học gồm 2 tab: thông tin cơ bản và quản lý chương/bài học.

**Cách truy cập:** Nhấn "Tạo khóa học" hoặc "Sửa" từ danh sách, truy cập `/admin/courses/create` hoặc `/admin/courses/:id/edit`.

### Nội dung giao diện

| Mục | Kiểu | Mô tả |
|-----|------|-------|
| Tab "Thông tin" | Tab | Form nhập thông tin khóa học |
| Tab "Bài học" | Tab | Quản lý chương và bài học |
| **Tab Thông tin:** | | |
| Ô Tên khóa học | Text Input | Nhập tên khóa học |
| Ô Slug | Text Input | Định danh URL (tự động từ tên) |
| Ô Mô tả | Text Input | Soạn thảo mô tả rich-text |
| Ảnh thumbnail | Image | Upload ảnh đại diện khóa học |
| Nút upload ảnh | Button | Mở giao diện chọn/tải ảnh |
| Ô Giá gốc | Text Input | Nhập giá (VNĐ) |
| Ô Giá khuyến mãi | Text Input | Nhập giá sau giảm |
| Cấp độ | Select | Cơ bản / Trung cấp / Nâng cao |
| Danh mục | Select | Chọn một hoặc nhiều danh mục (multi-select) |
| Giảng viên | Select | Chọn giảng viên phụ trách |
| Trạng thái | Select | Bản nháp / Đã xuất bản |
| Nút "Lưu" | Button | Lưu thông tin khóa học |
| Nút "Hủy" | Button | Quay lại danh sách không lưu |
| **Tab Bài học:** | | |
| Danh sách chương | Table | Các chương (Section) với nút thêm/sửa/xóa |
| Nút "Thêm chương" | Button | Thêm chương mới |
| Nút "Sửa chương" | Button | Mở form sửa tiêu đề chương |
| Nút "Xóa chương" | Button | Xóa chương và toàn bộ bài học trong chương |
| Danh sách bài học | Table | Bài học trong mỗi chương với nút thêm/sửa/xóa |
| Nút "Thêm bài học" | Button | Mở modal tạo bài học mới |
| Nút "Sửa bài học" | Button | Mở modal chỉnh sửa bài học |
| Nút "Xóa bài học" | Button | Xóa bài học (xóa mềm) |

### Hành động

| Tên hành động | Mô tả | Thành công | Thất bại |
|---------------|-------|------------|----------|
| Lưu thông tin | Nhấn "Lưu" | Lưu khóa học, hiển thị thông báo thành công | Lỗi validation: tên trùng, slug trùng, giá không hợp lệ |
| Thêm chương | Nhấn "Thêm chương" | Chương mới xuất hiện trong danh sách | — |
| Thêm bài học | Nhấn "Thêm bài học" | Modal tạo bài học mở ra | — |
| Lưu bài học | Nhấn lưu trong modal bài học | Bài học được thêm vào chương | Lỗi nếu thiếu thông tin bắt buộc |
| Xóa chương | Nhấn "Xóa chương" và xác nhận | Chương và bài học trong đó bị xóa | — |

*Bảng: Nội dung giao diện Form khóa học*

---

## 27. Giao diện "Quản lý danh mục khóa học"

**Mô tả:** Quản lý cây danh mục phân cấp (cha–con), tạo/sửa/xóa danh mục, hỗ trợ tìm kiếm.

**Cách truy cập:** Nhấn "Danh mục" trong sidebar hoặc truy cập `/admin/categories`.

### Nội dung giao diện

| Mục | Kiểu | Mô tả |
|-----|------|-------|
| Sidebar | MenuItem | Điều hướng Admin |
| Tab "Đang hoạt động" | Tab | Danh sách danh mục chưa xóa |
| Tab "Thùng rác" | Tab | Danh sách danh mục đã xóa mềm |
| Ô tìm kiếm | Text Input | Tìm danh mục theo tên |
| Nút "Tạo danh mục" | Button | Mở form tạo danh mục mới |
| Bảng danh mục | Table | Tên, slug, danh mục cha, trạng thái, hành động |
| Nút "Sửa" | Button | Mở modal chỉnh sửa danh mục |
| Nút "Xóa" | Button | Xóa mềm danh mục |
| Nút "Khôi phục" | Button | Khôi phục danh mục (trong tab Thùng rác) |
| Nút "Xóa vĩnh viễn" | Button | Xóa hoàn toàn danh mục |
| Modal form danh mục | Modal | Form tạo/sửa: Tên, Slug, Mô tả, Icon, Danh mục cha, Trạng thái |

### Hành động

| Tên hành động | Mô tả | Thành công | Thất bại |
|---------------|-------|------------|----------|
| Tạo danh mục | Nhấn "Tạo danh mục" | Mở modal tạo mới | — |
| Lưu danh mục | Nhấn Lưu trong modal | Danh mục được thêm/cập nhật | Lỗi: tên trùng, slug trùng |
| Xóa danh mục | Nhấn "Xóa" và xác nhận | Chuyển vào Thùng rác | — |
| Khôi phục | Nhấn "Khôi phục" | Danh mục xuất hiện lại trong danh sách chính | — |

*Bảng: Nội dung giao diện Quản lý danh mục khóa học*

---

## 28. Giao diện "Quản lý người dùng Admin"

**Mô tả:** Quản lý danh sách tài khoản admin/nhân viên, gán vai trò, tạo/sửa/xóa.

**Cách truy cập:** Nhấn "Người dùng" trong sidebar hoặc truy cập `/admin/users`.

### Nội dung giao diện

| Mục | Kiểu | Mô tả |
|-----|------|-------|
| Tab "Đang hoạt động" | Tab | Danh sách user chưa xóa |
| Tab "Thùng rác" | Tab | Danh sách user đã xóa mềm |
| Ô tìm kiếm | Text Input | Tìm user theo tên, email |
| Bộ lọc vai trò | Dropdown | Lọc theo vai trò: Super Admin / Admin / Teacher |
| Bộ lọc trạng thái | Dropdown | Tất cả / Đang hoạt động / Đã vô hiệu |
| Nút "Tạo người dùng" | Button | Mở modal tạo tài khoản mới |
| Bảng người dùng | Table | Tên, email, vai trò, trạng thái, ngày tạo, hành động |
| Nút "Sửa" | Button | Mở modal chỉnh sửa thông tin |
| Nút "Xóa" | Button | Xóa mềm tài khoản |
| Nút "Khôi phục" | Button | Khôi phục tài khoản đã xóa |
| Modal form người dùng | Modal | Tên, Email, Mật khẩu, Vai trò, Trạng thái |

### Hành động

| Tên hành động | Mô tả | Thành công | Thất bại |
|---------------|-------|------------|----------|
| Tạo người dùng | Điền form và lưu | Tài khoản mới được tạo | Lỗi: email trùng, thiếu trường bắt buộc |
| Sửa người dùng | Chỉnh sửa thông tin và lưu | Cập nhật thành công | Lỗi validation |
| Xóa người dùng | Xác nhận xóa | Chuyển vào Thùng rác | Không thể xóa chính mình |
| Gán vai trò | Chọn vai trò trong form | Vai trò được cập nhật | — |

*Bảng: Nội dung giao diện Quản lý người dùng Admin*

---

## 29. Giao diện "Quản lý giảng viên"

**Mô tả:** Quản lý danh sách hồ sơ giảng viên, tạo/sửa/xóa, liên kết tài khoản admin.

**Cách truy cập:** Nhấn "Giảng viên" trong sidebar hoặc truy cập `/admin/teachers`.

### Nội dung giao diện

| Mục | Kiểu | Mô tả |
|-----|------|-------|
| Tab "Đang hoạt động" | Tab | Danh sách giảng viên chưa xóa |
| Tab "Thùng rác" | Tab | Danh sách giảng viên đã xóa mềm |
| Ô tìm kiếm | Text Input | Tìm giảng viên theo tên |
| Bộ lọc trạng thái | Dropdown | Lọc theo trạng thái active/inactive |
| Nút "Tạo giảng viên" | Button | Mở modal tạo hồ sơ giảng viên mới |
| Bảng giảng viên | Table | Ảnh, Tên, Slug, Kinh nghiệm, Số khóa học, Trạng thái, Hành động |
| Nút "Sửa" | Button | Mở modal chỉnh sửa hồ sơ |
| Nút "Xóa" | Button | Xóa mềm hồ sơ giảng viên |
| Modal form giảng viên | Modal | Tên, Slug, Ngày sinh, Mô tả, Kinh nghiệm, Ảnh, Liên kết tài khoản User, Trạng thái |

### Hành động

| Tên hành động | Mô tả | Thành công | Thất bại |
|---------------|-------|------------|----------|
| Tạo giảng viên | Điền form và lưu | Hồ sơ giảng viên được tạo | Lỗi: slug trùng, thiếu trường bắt buộc |
| Sửa giảng viên | Chỉnh sửa và lưu | Cập nhật thành công | Lỗi validation |
| Xóa giảng viên | Xác nhận xóa | Chuyển vào Thùng rác | — |
| Bật/tắt trạng thái | Nhấn nút toggle | Trạng thái cập nhật ngay | — |

*Bảng: Nội dung giao diện Quản lý giảng viên*

---

## 30. Giao diện "Quản lý học viên"

**Mô tả:** Xem và quản lý danh sách tài khoản học viên, xem thông tin đăng ký, khóa học đã tham gia.

**Cách truy cập:** Nhấn "Học viên" trong sidebar hoặc truy cập `/admin/students`.

### Nội dung giao diện

| Mục | Kiểu | Mô tả |
|-----|------|-------|
| Ô tìm kiếm | Text Input | Tìm học viên theo tên hoặc email |
| Bộ lọc trạng thái | Dropdown | Lọc theo trạng thái tài khoản |
| Bảng học viên | Table | Tên, Email, Ngày sinh, Số khóa học đã học, Ngày đăng ký, Hành động |
| Nút "Xóa" | Button | Xóa mềm tài khoản học viên |
| Thanh phân trang | Pagination | Điều hướng qua các trang |

### Hành động

| Tên hành động | Mô tả | Thành công | Thất bại |
|---------------|-------|------------|----------|
| Tìm kiếm | Nhập từ khóa | Danh sách lọc theo tên/email | — |
| Xóa học viên | Xác nhận xóa | Tài khoản học viên bị xóa mềm | — |

*Bảng: Nội dung giao diện Quản lý học viên*

---

## 31. Giao diện "Quản lý đơn hàng"

**Mô tả:** Xem toàn bộ lịch sử đơn hàng, lọc theo trạng thái, xem chi tiết từng đơn.

**Cách truy cập:** Nhấn "Đơn hàng" trong sidebar hoặc truy cập `/admin/orders`.

### Nội dung giao diện

| Mục | Kiểu | Mô tả |
|-----|------|-------|
| Ô tìm kiếm | Text Input | Tìm theo mã đơn hàng hoặc tên học viên |
| Bộ lọc trạng thái | Dropdown | Tất cả / Đang xử lý / Đã thanh toán / Thất bại / Đã hủy |
| Bảng đơn hàng | Table | Mã đơn, Học viên, Số khóa học, Tổng tiền, Trạng thái, Ngày tạo, Hành động |
| Badge trạng thái | Badge | Màu sắc phân biệt: xanh (đã thanh toán), vàng (đang xử lý), đỏ (thất bại) |
| Nút "Xem chi tiết" | Button | Mở modal chi tiết đơn hàng |
| Modal chi tiết đơn | Modal | Danh sách khóa học, giá, mã giảm giá, tổng tiền, thông tin giao dịch |
| Thanh phân trang | Pagination | Điều hướng qua các trang |

### Hành động

| Tên hành động | Mô tả | Thành công | Thất bại |
|---------------|-------|------------|----------|
| Lọc trạng thái | Chọn trạng thái từ dropdown | Danh sách cập nhật theo trạng thái | — |
| Xem chi tiết | Nhấn "Xem chi tiết" | Modal hiển thị thông tin đầy đủ đơn hàng | — |

*Bảng: Nội dung giao diện Quản lý đơn hàng*

---

## 32. Giao diện "Quản lý bài viết" (Admin)

**Mô tả:** Quản lý danh sách bài viết blog: tạo, sửa, xóa, xuất bản.

**Cách truy cập:** Nhấn "Bài viết" trong sidebar hoặc truy cập `/admin/posts`.

### Nội dung giao diện

| Mục | Kiểu | Mô tả |
|-----|------|-------|
| Tab "Đang hoạt động" | Tab | Bài viết chưa xóa |
| Tab "Thùng rác" | Tab | Bài viết đã xóa mềm |
| Ô tìm kiếm | Text Input | Tìm bài viết theo tiêu đề |
| Bộ lọc trạng thái | Dropdown | Tất cả / Bản nháp / Đã xuất bản |
| Bộ lọc danh mục | Dropdown | Lọc theo danh mục bài viết |
| Nút "Tạo bài viết" | Button | Chuyển đến giao diện "Form bài viết" |
| Bảng bài viết | Table | Tiêu đề, Danh mục, Tác giả, Trạng thái, Lượt xem, Ngày đăng, Hành động |
| Nút "Sửa" | Button | Chuyển đến form chỉnh sửa bài viết |
| Nút "Xóa" | Button | Xóa mềm bài viết |
| Nút "Khôi phục" | Button | Khôi phục bài viết đã xóa |

### Hành động

| Tên hành động | Mô tả | Thành công | Thất bại |
|---------------|-------|------------|----------|
| Tạo bài viết | Nhấn "Tạo bài viết" | Chuyển đến "/admin/posts/create" | — |
| Sửa bài viết | Nhấn "Sửa" | Chuyển đến "/admin/posts/:id/edit" | — |
| Xóa bài viết | Xác nhận xóa | Chuyển vào Thùng rác | — |
| Khôi phục | Nhấn "Khôi phục" | Bài viết xuất hiện lại trong danh sách chính | — |

*Bảng: Nội dung giao diện Quản lý bài viết*

---

## 33. Giao diện "Form bài viết" (Tạo/Sửa)

**Mô tả:** Form soạn thảo bài viết với rich-text editor, upload thumbnail, chọn danh mục, tags, và xuất bản.

**Cách truy cập:** Nhấn "Tạo bài viết" hoặc "Sửa" từ danh sách, truy cập `/admin/posts/create` hoặc `/admin/posts/:id/edit`.

### Nội dung giao diện

| Mục | Kiểu | Mô tả |
|-----|------|-------|
| Ô Tiêu đề | Text Input | Nhập tiêu đề bài viết |
| Ô Slug | Text Input | Định danh URL (tự động từ tiêu đề) |
| Ô Nội dung | Text Input | Soạn thảo rich-text (Quill editor) |
| Ảnh thumbnail | Image | Ảnh đại diện bài viết |
| Nút upload ảnh | Button | Mở giao diện tải ảnh |
| Danh mục bài viết | Select | Chọn danh mục từ danh sách |
| Tags | Select | Chọn hoặc tạo tags mới |
| Trạng thái xuất bản | Select | Bản nháp / Xuất bản |
| Ngày xuất bản | Text Input | Chọn ngày giờ xuất bản (nếu hẹn giờ) |
| Nút "Lưu" | Button | Lưu bài viết |
| Nút "Hủy" | Button | Quay lại danh sách không lưu |

### Hành động

| Tên hành động | Mô tả | Thành công | Thất bại |
|---------------|-------|------------|----------|
| Lưu bài viết | Nhấn "Lưu" | Bài viết được lưu, thông báo thành công | Lỗi: tiêu đề trống, slug trùng |
| Hủy | Nhấn "Hủy" | Quay lại "/admin/posts" không lưu | — |

*Bảng: Nội dung giao diện Form bài viết*

---

## 34. Giao diện "Quản lý danh mục bài viết"

**Mô tả:** Quản lý các danh mục phân loại bài viết blog.

**Cách truy cập:** Nhấn "Danh mục bài viết" trong sidebar hoặc truy cập `/admin/post-categories`.

### Nội dung giao diện

| Mục | Kiểu | Mô tả |
|-----|------|-------|
| Ô tìm kiếm | Text Input | Tìm danh mục theo tên |
| Nút "Tạo danh mục" | Button | Mở modal tạo mới |
| Bảng danh mục | Table | Tên, Slug, Mô tả, Số bài viết, Hành động |
| Nút "Sửa" | Button | Mở modal chỉnh sửa |
| Nút "Xóa" | Button | Xóa danh mục |
| Modal form | Modal | Tên, Slug, Mô tả |

### Hành động

| Tên hành động | Mô tả | Thành công | Thất bại |
|---------------|-------|------------|----------|
| Tạo danh mục | Điền form và lưu | Danh mục mới được tạo | Lỗi: tên trùng, slug trùng |
| Sửa danh mục | Chỉnh sửa và lưu | Cập nhật thành công | Lỗi validation |
| Xóa danh mục | Xác nhận xóa | Danh mục bị xóa | Lỗi nếu có bài viết đang dùng |

*Bảng: Nội dung giao diện Quản lý danh mục bài viết*

---

## 35. Giao diện "Quản lý Tags"

**Mô tả:** Quản lý các nhãn/từ khóa gắn với bài viết.

**Cách truy cập:** Nhấn "Tags" trong sidebar hoặc truy cập `/admin/tags`.

### Nội dung giao diện

| Mục | Kiểu | Mô tả |
|-----|------|-------|
| Ô tìm kiếm | Text Input | Tìm tag theo tên |
| Nút "Tạo tag" | Button | Mở modal tạo tag mới |
| Bảng tags | Table | Tên, Slug, Số bài viết dùng, Hành động |
| Nút "Sửa" | Button | Mở modal chỉnh sửa tag |
| Nút "Xóa" | Button | Xóa tag |
| Modal form | Modal | Tên, Slug |

### Hành động

| Tên hành động | Mô tả | Thành công | Thất bại |
|---------------|-------|------------|----------|
| Tạo tag | Điền form và lưu | Tag mới được tạo | Lỗi: tên trùng, slug trùng |
| Sửa tag | Chỉnh sửa và lưu | Cập nhật thành công | Lỗi validation |
| Xóa tag | Xác nhận xóa | Tag bị xóa | — |

*Bảng: Nội dung giao diện Quản lý Tags*

---

## 36. Giao diện "Quản lý bình luận bài viết"

**Mô tả:** Duyệt, xóa và quản lý bình luận của học viên và admin trên các bài viết.

**Cách truy cập:** Nhấn "Bình luận" trong sidebar hoặc truy cập `/admin/post-comments`.

### Nội dung giao diện

| Mục | Kiểu | Mô tả |
|-----|------|-------|
| Bộ lọc trạng thái | Dropdown | Tất cả / Đã duyệt / Chờ duyệt |
| Bộ lọc bài viết | Dropdown | Lọc bình luận theo bài viết cụ thể |
| Bảng bình luận | Table | Tác giả, Loại (Admin/Học viên), Bài viết, Nội dung trích dẫn, Trạng thái, Ngày tạo, Hành động |
| Badge loại tác giả | Badge | Admin / Học viên |
| Badge trạng thái | Badge | Đã duyệt / Chờ duyệt |
| Nút "Duyệt" | Button | Phê duyệt bình luận chờ duyệt |
| Nút "Xóa" | Button | Xóa bình luận |
| Thanh phân trang | Pagination | Điều hướng qua các trang |

### Hành động

| Tên hành động | Mô tả | Thành công | Thất bại |
|---------------|-------|------------|----------|
| Duyệt bình luận | Nhấn "Duyệt" | Bình luận chuyển sang trạng thái đã duyệt | — |
| Xóa bình luận | Xác nhận xóa | Bình luận bị xóa khỏi hệ thống | — |
| Lọc theo trạng thái | Chọn trạng thái | Danh sách cập nhật | — |

*Bảng: Nội dung giao diện Quản lý bình luận bài viết*

---

## 37. Giao diện "Quản lý mã giảm giá"

**Mô tả:** Tạo và quản lý các mã coupon giảm giá cho đơn hàng.

**Cách truy cập:** Nhấn "Mã giảm giá" trong sidebar hoặc truy cập `/admin/coupons`.

### Nội dung giao diện

| Mục | Kiểu | Mô tả |
|-----|------|-------|
| Tab "Đang hoạt động" | Tab | Danh sách coupon chưa xóa |
| Tab "Thùng rác" | Tab | Coupon đã xóa mềm |
| Ô tìm kiếm | Text Input | Tìm theo mã coupon |
| Nút "Tạo mã giảm giá" | Button | Mở modal tạo mã mới |
| Bảng coupon | Table | Mã, Loại (Cố định/Phần trăm), Giá trị, Đơn hàng tối thiểu, Lượt dùng, Ngày hiệu lực, Trạng thái, Hành động |
| Badge trạng thái | Badge | Đang hoạt động / Đã tắt / Hết hạn |
| Nút "Sửa" | Button | Mở modal chỉnh sửa |
| Nút "Bật/Tắt" | Button | Toggle trạng thái active/inactive |
| Nút "Xóa" | Button | Xóa mềm mã giảm giá |
| Modal form coupon | Modal | Mã, Loại, Giá trị, Đơn tối thiểu, Giảm tối đa, Giới hạn lượt, Ngày bắt đầu, Ngày kết thúc, Mô tả |

### Hành động

| Tên hành động | Mô tả | Thành công | Thất bại |
|---------------|-------|------------|----------|
| Tạo mã giảm giá | Điền form và lưu | Mã coupon được tạo | Lỗi: mã trùng, giá trị không hợp lệ |
| Sửa coupon | Chỉnh sửa và lưu | Cập nhật thành công | Lỗi validation |
| Bật/Tắt coupon | Nhấn toggle | Trạng thái thay đổi ngay | — |
| Xóa coupon | Xác nhận xóa | Chuyển vào Thùng rác | — |

*Bảng: Nội dung giao diện Quản lý mã giảm giá*

---

## 38. Giao diện "Quản lý vai trò & quyền"

**Mô tả:** Quản lý các vai trò (super-admin, admin, teacher) và phân quyền cho từng vai trò.

**Cách truy cập:** Nhấn "Vai trò & Quyền" trong sidebar hoặc truy cập `/admin/roles`.

### Nội dung giao diện

| Mục | Kiểu | Mô tả |
|-----|------|-------|
| Danh sách vai trò | Table | Tên vai trò, Guard, Số người dùng, Hành động |
| Nút "Tạo vai trò" | Button | Mở modal tạo vai trò mới |
| Nút "Sửa" | Button | Mở modal chỉnh sửa vai trò và phân quyền |
| Nút "Xóa" | Button | Xóa vai trò |
| Modal phân quyền | Modal | Danh sách checkbox tất cả permissions: users.view/create/edit/delete, courses.*, categories.*, lessons.*, orders.*, students.*, dashboard.view |

### Hành động

| Tên hành động | Mô tả | Thành công | Thất bại |
|---------------|-------|------------|----------|
| Tạo vai trò | Điền tên và chọn permissions | Vai trò mới được tạo | Lỗi: tên trùng |
| Sửa vai trò | Cập nhật permissions và lưu | Quyền hạn cập nhật | — |
| Xóa vai trò | Xác nhận xóa | Vai trò bị xóa | Lỗi nếu còn người dùng đang dùng vai trò này |

*Bảng: Nội dung giao diện Quản lý vai trò & quyền*

---

## 39. Giao diện "Nhật ký hoạt động"

**Mô tả:** Ghi lại và hiển thị lịch sử thao tác của tất cả admin trên hệ thống: tạo, cập nhật, xóa tài nguyên.

**Cách truy cập:** Nhấn "Nhật ký" trong sidebar hoặc truy cập `/admin/system-logs`.

### Nội dung giao diện

| Mục | Kiểu | Mô tả |
|-----|------|-------|
| Bộ lọc hành động | Dropdown | Tất cả / created / updated / deleted |
| Bộ lọc loại tài nguyên | Dropdown | Lọc theo model: Course, User, Post, ... |
| Bảng nhật ký | Table | Người dùng, Hành động, Loại tài nguyên, ID, Mô tả thay đổi, Thời gian |
| Nút "Xem chi tiết" | Button | Mở modal hiển thị chi tiết thay đổi (before/after) |
| Modal chi tiết | Modal | Các trường đã thay đổi và giá trị cũ/mới |
| Thanh phân trang | Pagination | Điều hướng qua các trang |

### Hành động

| Tên hành động | Mô tả | Thành công | Thất bại |
|---------------|-------|------------|----------|
| Lọc nhật ký | Chọn bộ lọc | Danh sách cập nhật theo bộ lọc | — |
| Xem chi tiết | Nhấn "Xem chi tiết" | Modal hiển thị thông tin thay đổi đầy đủ | — |

*Bảng: Nội dung giao diện Nhật ký hoạt động*
