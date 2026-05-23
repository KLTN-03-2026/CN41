# Mô tả Giao diện — Xác thực (Auth)

---

## 17. Giao diện "Đăng nhập học viên"

**Mô tả:** Màn hình đăng nhập dành cho học viên với form email/mật khẩu, hỗ trợ cảnh báo email chưa xác minh.

**Cách truy cập:** Nhấn "Đăng nhập" trên thanh điều hướng hoặc truy cập `/login`. Chỉ hiển thị khi chưa đăng nhập.

### Nội dung giao diện

| Mục | Kiểu | Mô tả |
|-----|------|-------|
| Tiêu đề | Text | "Đăng nhập" |
| Ô Email | Text Input | Nhập địa chỉ email |
| Ô Mật khẩu | Text Input | Nhập mật khẩu (ẩn ký tự) |
| Nút "Đăng nhập" | Button | Gửi yêu cầu đăng nhập |
| Link "Quên mật khẩu?" | LinkString | Điều hướng đến giao diện "Quên mật khẩu" |
| Link "Đăng ký" | LinkString | Điều hướng đến giao diện "Đăng ký" |
| Cảnh báo email chưa xác minh | Text | Hiển thị khi email chưa được xác minh |
| Nút "Gửi lại email xác minh" | Button | Gửi lại email xác thực (hiển thị khi có cảnh báo) |

### Hành động

| Tên hành động | Mô tả | Thành công | Thất bại |
|---------------|-------|------------|----------|
| Đăng nhập | Nhập email, mật khẩu và nhấn "Đăng nhập" | Lưu token, chuyển về trang trước đó hoặc "/" | Hiển thị thông báo lỗi: sai thông tin, hoặc cảnh báo email chưa xác minh |
| Quên mật khẩu | Nhấn link "Quên mật khẩu?" | Chuyển đến "/forgot-password" | — |
| Đăng ký | Nhấn link "Đăng ký" | Chuyển đến "/register" | — |
| Gửi lại email xác minh | Nhấn nút gửi lại | Thông báo "Đã gửi email xác minh" | Thông báo lỗi nếu quá giới hạn gửi |

*Bảng: Nội dung giao diện Đăng nhập học viên*

---

## 18. Giao diện "Đăng ký học viên"

**Mô tả:** Màn hình tạo tài khoản học viên mới với form họ tên, email và mật khẩu.

**Cách truy cập:** Nhấn "Đăng ký" trên thanh điều hướng hoặc truy cập `/register`. Chỉ hiển thị khi chưa đăng nhập.

### Nội dung giao diện

| Mục | Kiểu | Mô tả |
|-----|------|-------|
| Tiêu đề | Text | "Đăng ký tài khoản" |
| Ô Họ và tên | Text Input | Nhập tên đầy đủ |
| Ô Email | Text Input | Nhập địa chỉ email |
| Ô Mật khẩu | Text Input | Nhập mật khẩu (ít nhất 8 ký tự) |
| Ô Xác nhận mật khẩu | Text Input | Nhập lại mật khẩu |
| Nút "Đăng ký" | Button | Gửi yêu cầu tạo tài khoản |
| Link "Đăng nhập" | LinkString | Điều hướng đến giao diện "Đăng nhập" |

### Hành động

| Tên hành động | Mô tả | Thành công | Thất bại |
|---------------|-------|------------|----------|
| Đăng ký | Điền đầy đủ thông tin và nhấn "Đăng ký" | Tạo tài khoản, chuyển đến trang xác minh email | Hiển thị lỗi validation: email đã tồn tại, mật khẩu không khớp, ... |
| Chuyển đăng nhập | Nhấn link "Đăng nhập" | Chuyển đến "/login" | — |

*Bảng: Nội dung giao diện Đăng ký học viên*

---

## 19. Giao diện "Quên mật khẩu"

**Mô tả:** Học viên nhập email để nhận liên kết đặt lại mật khẩu.

**Cách truy cập:** Nhấn "Quên mật khẩu?" trên trang đăng nhập hoặc truy cập `/forgot-password`.

### Nội dung giao diện

| Mục | Kiểu | Mô tả |
|-----|------|-------|
| Tiêu đề | Text | "Quên mật khẩu" |
| Mô tả | Text | Hướng dẫn nhập email để nhận link đặt lại |
| Ô Email | Text Input | Nhập địa chỉ email đã đăng ký |
| Nút "Gửi yêu cầu" | Button | Gửi email chứa link đặt lại mật khẩu |
| Link "Quay lại đăng nhập" | LinkString | Điều hướng về giao diện "Đăng nhập" |

### Hành động

| Tên hành động | Mô tả | Thành công | Thất bại |
|---------------|-------|------------|----------|
| Gửi yêu cầu | Nhập email và nhấn "Gửi yêu cầu" | Hiển thị thông báo "Đã gửi email" (dù email có tồn tại hay không để tránh dò email) | Hiển thị lỗi nếu email không đúng định dạng |
| Quay lại đăng nhập | Nhấn link | Chuyển đến "/login" | — |

*Bảng: Nội dung giao diện Quên mật khẩu*

---

## 20. Giao diện "Đặt lại mật khẩu"

**Mô tả:** Học viên nhập mật khẩu mới sau khi nhấn link trong email đặt lại mật khẩu.

**Cách truy cập:** Nhấn link trong email đặt lại mật khẩu, truy cập `/reset-password?token=...&email=...`.

### Nội dung giao diện

| Mục | Kiểu | Mô tả |
|-----|------|-------|
| Tiêu đề | Text | "Đặt lại mật khẩu" |
| Ô Mật khẩu mới | Text Input | Nhập mật khẩu mới (ít nhất 8 ký tự) |
| Ô Xác nhận mật khẩu | Text Input | Nhập lại mật khẩu mới |
| Nút "Đặt lại mật khẩu" | Button | Xác nhận đặt mật khẩu mới |

### Hành động

| Tên hành động | Mô tả | Thành công | Thất bại |
|---------------|-------|------------|----------|
| Đặt lại mật khẩu | Nhập mật khẩu mới và nhấn xác nhận | Đặt lại thành công, chuyển đến "/login" kèm thông báo | Lỗi nếu token hết hạn, mật khẩu không khớp |

*Bảng: Nội dung giao diện Đặt lại mật khẩu*

---

## 21. Giao diện "Xác minh email"

**Mô tả:** Thông báo cho học viên vừa đăng ký kiểm tra hộp thư để xác minh tài khoản.

**Cách truy cập:** Tự động chuyển hướng sau khi đăng ký thành công, hoặc truy cập `/verify-email`.

### Nội dung giao diện

| Mục | Kiểu | Mô tả |
|-----|------|-------|
| Tiêu đề | Text | "Xác minh địa chỉ email" |
| Mô tả | Text | Hướng dẫn kiểm tra hộp thư và nhấn link xác minh |
| Email | Text | Địa chỉ email đã đăng ký |
| Nút "Gửi lại email" | Button | Gửi lại email xác minh |
| Link "Đăng nhập" | LinkString | Điều hướng về trang đăng nhập |

### Hành động

| Tên hành động | Mô tả | Thành công | Thất bại |
|---------------|-------|------------|----------|
| Gửi lại email xác minh | Nhấn "Gửi lại email" | Thông báo "Đã gửi lại email xác minh" | Thông báo lỗi nếu quá giới hạn gửi (3 lần/phút) |

*Bảng: Nội dung giao diện Xác minh email*

---

## 22. Giao diện "Kết quả xác minh email"

**Mô tả:** Hiển thị trạng thái sau khi học viên nhấn link xác minh trong email: thành công, đã xác minh, hết hạn hoặc không hợp lệ.

**Cách truy cập:** Nhấn link trong email xác minh, truy cập `/verify-email/result?token=...`.

### Nội dung giao diện

| Mục | Kiểu | Mô tả |
|-----|------|-------|
| Icon kết quả | Image | Icon tick xanh / cảnh báo / lỗi tùy trạng thái |
| Tiêu đề | Text | "Xác minh thành công" / "Đã xác minh" / "Link hết hạn" / "Link không hợp lệ" |
| Mô tả | Text | Giải thích chi tiết trạng thái |
| Nút "Đăng nhập ngay" | Button | Điều hướng đến "/login" (khi thành công) |
| Nút "Gửi lại email" | Button | Gửi link xác minh mới (khi link hết hạn) |

### Hành động

| Tên hành động | Mô tả | Thành công | Thất bại |
|---------------|-------|------------|----------|
| Đăng nhập ngay | Nhấn "Đăng nhập ngay" | Chuyển đến "/login" | — |
| Gửi lại email | Nhấn "Gửi lại email" khi link hết hạn | Thông báo "Đã gửi email mới" | Lỗi giới hạn gửi |

*Bảng: Nội dung giao diện Kết quả xác minh email*

---

## 23. Giao diện "Đăng nhập Admin"

**Mô tả:** Màn hình đăng nhập dành riêng cho quản trị viên và nhân viên, tách biệt hoàn toàn với đăng nhập học viên.

**Cách truy cập:** Truy cập trực tiếp đường dẫn `/admin/login`. Chỉ hiển thị khi admin chưa đăng nhập.

### Nội dung giao diện

| Mục | Kiểu | Mô tả |
|-----|------|-------|
| Logo hệ thống | Image | Logo e-learning |
| Tiêu đề | Text | "Đăng nhập Quản trị viên" |
| Ô Email | Text Input | Nhập email tài khoản admin |
| Ô Mật khẩu | Text Input | Nhập mật khẩu (ẩn ký tự) |
| Nút "Đăng nhập" | Button | Xác thực và đăng nhập vào hệ thống admin |

### Hành động

| Tên hành động | Mô tả | Thành công | Thất bại |
|---------------|-------|------------|----------|
| Đăng nhập Admin | Nhập email, mật khẩu và nhấn "Đăng nhập" | Lưu adminToken, chuyển đến "/admin/dashboard" | Hiển thị lỗi: sai thông tin xác thực, tài khoản bị vô hiệu hóa |

*Bảng: Nội dung giao diện Đăng nhập Admin*
