# Báo Cáo Kết Quả Kiểm Thử (Test Report)

**Thời gian cập nhật:** 09/04/2026
**Dự án:** E-Learning Marketplace

---

## 1. Module 4: Quản lý Khóa học (Courses Admin)

Quá trình kiểm thử cho module quản lý khóa học đã cơ bản được hoàn thành với các tính năng chính đã chạy ổn định:

### 1.1 Khởi tạo và Danh sách
- **Hiển thị danh sách**: Load thành công dữ liệu từ API với các trường thông tin cơ bản.
- **Bộ lọc (Filters)**:
  - Tìm kiếm theo từ khóa (Keyword) hoạt động chính xác.
  - Lọc theo trình độ (Cơ bản, Trung cấp, Nâng cao) hoạt động chính xác.
  - Lọc theo trạng thái (Đã đăng, Nháp) hoạt động chính xác.
- **Toggle Trạng thái**: Tính năng nhấn vào switch box để chuyển đổi giữa "Đã đăng" và "Nháp" trực tiếp trên bảng được xác nhận hoạt động ổn định, gọi API cập nhật trạng thái mượt mà.

### 1.2 Thêm mới và Chỉnh sửa (CRUD)
- **Thêm mới khóa học**:
  - Tự động sinh `slug` từ tiêu đề thành công.
  - Validation phía Frontend (bắt buộc nhập) và Backend hoạt động tốt. 
  - Đã fix triệt để lỗi validation regex cho slug (hiện tại chỉ cho phép chữ thường, số và dấu gạch ngang).
- **Chỉnh sửa khóa học**:
  - Khi mở form chỉnh sửa, toàn bộ dữ liệu (bao gồm cả ảnh thumbnail và categories) được tự động điền lại (pre-fill) đầy đủ.
  - **Bug Fixed**: Đã khắc phục thành công vấn đề Frontend lưu sai định dạng Category, giúp việc cập nhật và thay đổi danh mục khóa học diễn ra chính xác.

### 1.3 Soft Delete & Thùng rác
- **Chức năng Xóa tạm (Soft Delete)**: Đưa khóa học rác vào thùng rác hoạt động thành công.
- **Bug Fixed (Quan trọng)**: Đã phát hiện và sửa triệt để lỗi 500 Internal Server Error khi truy cập tab "Thùng rác". Nguyên nhân là do Controller trong Backend thiếu dòng `use Modules\Course\Models\Course;`.
- **Khôi phục (Restore)**: Đã test khôi phục khóa học từ tab "Thùng rác" trở về danh sách "Đang hoạt động" thành công. Giao diện thay đổi mượt mà.

### 1.4 Thao tác hàng loạt (Bulk Actions)
- Đã sửa các API gây lỗi `405 Method Not Allowed`, chuyển các khai báo Bulk API lên trước Restful API resource routes ở trong backend.
- Đã refactor lại trên Frontend bằng việc tạo base component `<BulkActions />` tái sử dụng, giúp khắc phục tình trạng code lộn xộn.
- Việc thao tác `Bulk Delete` (Chuyển nhiều khóa học vào thùng rác) và `Bulk Restore` (Khôi phục số lượng lớn) tích hợp thành công. 

---

## 2. Hệ thống Router & Lỗi Diễn Hướng (404 Error)

### 2.1 Các lỗi đã sửa
- **Tình trạng**: Bị lỗi 404 trang Page Not Found khi truy cập vào url `/admin/users` và `/admin/teachers`.
- **Nguyên nhân**: Module ở backend đã được active và thiết lập API đầy đủ, nhưng trong Vue Router (`src/router/index.js`) lại cấu hình sót và chưa đăng ký các đường dẫn con cho layout Admin.
- **Khắc phục**: Đã bổ sung khai báo cho các trang: `users`, `teachers`, `students`, `orders`, `posts`, `coupons` vào đúng child route, giải quyết dứt điểm lỗi 404 cho Frontend.

---

## 3. Các đầu việc tiếp theo (Next Steps)
Thông qua kết quả test và rà soát hiện tại, các hạng mục sau cần được xử lý ở session tiếp theo:
1. **Triển khai giao diện Users/Teachers**: Hiện tại sau khi xử lý lỗi 404, file `UsersPage.vue` và `TeachersPage.vue` chỉ hiển thị template tĩnh (`<h1>...`), thao tác CRUD trên frontend thực sự chưa có mặt.
2. **Kiểm tra Module Bài giảng (Lessons & Sections)**: Đảm bảo giao diện quản lý cây chương trình (Section -> Lesson) khi lồng vào form khóa học hoạt động chuẩn xác và không lỗi.
3. **Clean-up dữ liệu test**: Thực hiện dùng Bulk Delete để xóa toàn bộ các Khóa học mang dữ liệu nháp ảo trong Database, chuẩn bị cho việc test Flow ở End-user.
