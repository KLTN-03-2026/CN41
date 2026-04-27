# 📰 Test Checklist — Quản lý Tin Tức & Bài Viết (Posts Module)

> **Route BE Admin:** `/api/v1/admin/posts` (cùng các route categories, tags, comments)
> **Route BE Client:** `/api/v1/posts`
> **Page FE Admin:** `/admin/posts`, `/admin/post-categories`, `/admin/tags`, `/admin/post-comments`
> **Page FE Client:** `/posts`, `/posts/:slug`

---

## Chuẩn bị
- [x] Chạy `php artisan migrate:fresh --seed`
- [x] Backend chạy: `php artisan serve`
- [x] Frontend chạy: `npm run dev`
- [x] Đăng nhập tài khoản Admin (để test admin)
- [x] Đăng nhập tài khoản Student (để test chức năng bình luận client)

---

## 1. Quản lý Danh mục Bài viết (Admin)

### 1.1 Hiển thị danh sách
- [x] Truy cập `/admin/post-categories` → Hiển thị bảng danh mục (Tên, Slug, Mô tả, Số bài viết, Ngày tạo, Thao tác)
- [x] Network: `GET /api/v1/admin/post-categories` → 200

### 1.2 Thêm & Sửa danh mục
- [x] Click **"Thêm danh mục"** → Modal mở ra
- [x] Điền tên danh mục (Slug tự động render), mô tả → Nhấn Lưu → Toast thành công.
- [x] Network: `POST /api/v1/admin/post-categories` → 201
- [x] Click icon **Bút chì** → Sửa thông tin → Lưu → Cập nhật thành công.
- [x] Network: `PUT /api/v1/admin/post-categories/{id}` → 200

### 1.3 Xóa & Bulk Actions
- [x] Xóa từng danh mục → Cảnh báo → Xóa thành công.
- [x] Chọn nhiều danh mục → Xóa hàng loạt → Xóa thành công.

---

## 2. Quản lý Thẻ - Tags (Admin)

### 2.1 Hiển thị & Thao tác
- [x] Truy cập `/admin/tags` → Hiển thị bảng thẻ (Tên, Slug, Số bài viết, Ngày tạo)
- [x] Thêm Thẻ mới → Tạo thành công.
- [x] Sửa Thẻ → Cập nhật thành công.
- [x] Xóa & Bulk Delete → Xóa thành công.

---

## 3. Quản lý Bài viết (Admin)

### 3.1 Hiển thị danh sách
- [x] Truy cập `/admin/posts` → Hiển thị danh sách bài viết (Ảnh bìa, Tiêu đề, Tác giả, Lượt xem, Trạng thái xuất bản)
- [x] Phân trang và tìm kiếm hoạt động bình thường.
- [x] Bộ lọc theo Danh mục và Trạng thái xuất bản.

### 3.2 Thêm Bài viết mới
- [x] Click **"Thêm bài viết"** → Chuyển sang trang form thêm mới.
- [x] Điền tiêu đề, chọn danh mục, gán thẻ (Tags multi-select).
- [x] Trình soạn thảo Quill Editor hoạt động (in đậm, nghiêng, list, thêm ảnh).
- [x] Upload thumbnail thành công.
- [x] Chọn "Lưu nháp" hoặc "Công khai ngay bài viết này".
- [x] Network: `POST /api/v1/admin/posts` (có chứa multipart form data) → 201

### 3.3 Sửa Bài viết
- [x] Click icon **Bút chì** trên bài viết → Load data vào form thành công.
- [x] Chỉnh sửa nội dung Quill Editor, thay đổi ảnh bìa, tags → Nhấn Lưu.
- [x] Network: `POST /api/v1/admin/posts/{id}` (với `_method=PUT`) → 200

### 3.4 Toggle Xuất bản & Xóa
- [x] Bấm nút **Switch** tại cột trạng thái → Trạng thái đổi từ Đã xuất bản ↔ Bản nháp.
- [x] Xóa từng bài viết thành công.
- [x] Chọn nhiều bài viết → Bulk Delete thành công.

---

## 4. Quản lý Bình luận (Admin)

### 4.1 Hiển thị danh sách bình luận
- [x] Truy cập `/admin/post-comments` → Bảng hiển thị (Người dùng + Avatar, Nội dung, Bài viết, Trạng thái, Ngày gửi).
- [x] Network: `GET /api/v1/admin/comments` → 200 (không gặp lỗi 500 do sai relationship).

### 4.2 Duyệt & Xóa bình luận
- [x] Click icon **Check** để duyệt bình luận (nếu đang ở trạng thái chờ duyệt).
- [x] Click icon **Thùng rác** để xóa bình luận.
- [x] Chọn nhiều bình luận → **Bulk Delete** hoạt động thành công.

---

## 5. Trang Tin Tức - Blog (Client)

### 5.1 Giao diện danh sách
- [x] Truy cập `/posts` (không cần đăng nhập) → Hiển thị lưới bài viết đẹp mắt.
- [x] Chỉ hiển thị những bài viết có `is_published = true`.
- [x] Phân trang (Nút Load more hoặc Phân trang số) hoạt động.

### 5.2 Tìm kiếm & Bộ lọc
- [x] Tìm kiếm bài viết theo từ khóa.
- [x] Click vào một Danh mục ở Sidebar → Lọc bài viết theo danh mục.
- [x] Click vào một Tag ở Sidebar → Lọc bài viết theo tag.

---

## 6. Chi tiết Bài viết & Bình luận (Client)

### 6.1 Hiển thị Nội dung
- [x] Click vào một bài viết → Chuyển đến `/posts/:slug`
- [x] Render chính xác nội dung HTML từ Quill Editor.
- [x] Hiển thị đúng số lượt xem, ngày đăng, tên tác giả, tags.
- [x] Lượt xem tự động tăng (`POST /api/v1/posts/{id}/increment-views`).

### 6.2 Tính năng Bình luận
- [x] **Trạng thái chưa đăng nhập:** Ẩn form bình luận, hiện thông báo yêu cầu đăng nhập.
- [x] **Trạng thái đã đăng nhập:** Hiện ô text-area nhập bình luận.
- [x] Gửi bình luận thành công → Toast hiển thị báo thành công.
- [x] Danh sách bình luận tự động refresh và hiển thị nội dung bình luận vừa gửi.
- [x] Hiển thị Avatar và Tên (commenter.name) người bình luận một cách chính xác.

---

## 7. Checklist Tổng Hợp

| Test | Kết quả | Ghi chú |
|------|---------|---------|
| 1.1 Danh mục Bài viết | ✅ | |
| 2.1 Quản lý Thẻ (Tags) | ✅ | |
| 3.1 Danh sách Bài viết | ✅ | |
| 3.2 Thêm Bài viết mới (Quill) | ✅ | |
| 3.3 Sửa bài viết (Multipart PUT) | ✅ | Lỗi Spoofing đã fix |
| 3.4 Bật/Tắt xuất bản bài viết | ✅ | |
| 4.1 Danh sách Bình luận | ✅ | Lỗi Polymorphic relation đã fix |
| 4.2 Duyệt & Xóa Bình luận hàng loạt | ✅ | |
| 5.1 Giao diện Client Blog | ✅ | |
| 5.2 Bộ lọc Client (Search/Cat/Tag) | ✅ | |
| 6.1 Hiển thị chi tiết bài viết HTML | ✅ | Lỗi 500 do API đã fix |
| 6.2 Bình luận bài viết phía Client | ✅ | Tự động tải lại sau khi gửi |
