# 📰 Test Checklist — Quản lý Tin Tức & Bài Viết (Posts Module)

> **Route BE Admin:** `/api/v1/admin/posts` (cùng các route categories, tags, comments)
> **Route BE Client:** `/api/v1/posts`
> **Page FE Admin:** `/admin/posts`, `/admin/post-categories`, `/admin/tags`, `/admin/post-comments`
> **Page FE Client:** `/posts`, `/posts/:slug`

---

## Chuẩn bị
- [ ] Chạy `php artisan migrate:fresh --seed`
- [ ] Backend chạy: `php artisan serve`
- [ ] Frontend chạy: `npm run dev`
- [ ] Đăng nhập tài khoản Admin (để test admin)
- [ ] Đăng nhập tài khoản Student (để test chức năng bình luận client)

---

## 1. Quản lý Danh mục Bài viết (Admin)

### 1.1 Hiển thị danh sách
- [ ] Truy cập `/admin/post-categories` → Hiển thị bảng danh mục (Tên, Slug, Mô tả, Số bài viết, Ngày tạo, Thao tác)
- [ ] Network: `GET /api/v1/admin/post-categories` → 200

### 1.2 Thêm & Sửa danh mục
- [ ] Click **"Thêm danh mục"** → Modal mở ra
- [ ] Điền tên danh mục (Slug tự động render), mô tả → Nhấn Lưu → Toast thành công.
- [ ] Network: `POST /api/v1/admin/post-categories` → 201
- [ ] Click icon **Bút chì** → Sửa thông tin → Lưu → Cập nhật thành công.
- [ ] Network: `PUT /api/v1/admin/post-categories/{id}` → 200

### 1.3 Xóa & Bulk Actions
- [ ] Xóa từng danh mục → Cảnh báo → Xóa thành công.
- [ ] Chọn nhiều danh mục → Xóa hàng loạt → Xóa thành công.

---

## 2. Quản lý Thẻ - Tags (Admin)

### 2.1 Hiển thị & Thao tác
- [ ] Truy cập `/admin/tags` → Hiển thị bảng thẻ (Tên, Slug, Số bài viết, Ngày tạo)
- [ ] Thêm Thẻ mới → Tạo thành công.
- [ ] Sửa Thẻ → Cập nhật thành công.
- [ ] Xóa & Bulk Delete → Xóa thành công.

---

## 3. Quản lý Bài viết (Admin)

### 3.1 Hiển thị danh sách
- [ ] Truy cập `/admin/posts` → Hiển thị danh sách bài viết (Ảnh bìa, Tiêu đề, Tác giả, Lượt xem, Trạng thái xuất bản)
- [ ] Phân trang và tìm kiếm hoạt động bình thường.
- [ ] Bộ lọc theo Danh mục và Trạng thái xuất bản.

### 3.2 Thêm Bài viết mới
- [ ] Click **"Thêm bài viết"** → Chuyển sang trang form thêm mới.
- [ ] Điền tiêu đề, chọn danh mục, gán thẻ (Tags multi-select).
- [ ] Trình soạn thảo Quill Editor hoạt động (in đậm, nghiêng, list, thêm ảnh).
- [ ] Upload thumbnail thành công.
- [ ] Chọn "Lưu nháp" hoặc "Công khai ngay bài viết này".
- [ ] Network: `POST /api/v1/admin/posts` (có chứa multipart form data) → 201

### 3.3 Sửa Bài viết
- [ ] Click icon **Bút chì** trên bài viết → Load data vào form thành công.
- [ ] Chỉnh sửa nội dung Quill Editor, thay đổi ảnh bìa, tags → Nhấn Lưu.
- [ ] Network: `POST /api/v1/admin/posts/{id}` (với `_method=PUT`) → 200

### 3.4 Toggle Xuất bản & Xóa
- [ ] Bấm nút **Switch** tại cột trạng thái → Trạng thái đổi từ Đã xuất bản ↔ Bản nháp.
- [ ] Xóa từng bài viết thành công.
- [ ] Chọn nhiều bài viết → Bulk Delete thành công.

---

## 4. Quản lý Bình luận (Admin)

### 4.1 Hiển thị danh sách bình luận
- [ ] Truy cập `/admin/post-comments` → Bảng hiển thị (Người dùng + Avatar, Nội dung, Bài viết, Trạng thái, Ngày gửi).
- [ ] Network: `GET /api/v1/admin/comments` → 200 (không gặp lỗi 500 do sai relationship).

### 4.2 Duyệt & Xóa bình luận
- [ ] Click icon **Check** để duyệt bình luận (nếu đang ở trạng thái chờ duyệt).
- [ ] Click icon **Thùng rác** để xóa bình luận.
- [ ] Chọn nhiều bình luận → **Bulk Delete** hoạt động thành công.

---

## 5. Trang Tin Tức - Blog (Client)

### 5.1 Giao diện danh sách
- [ ] Truy cập `/posts` (không cần đăng nhập) → Hiển thị lưới bài viết đẹp mắt.
- [ ] Chỉ hiển thị những bài viết có `is_published = true`.
- [ ] Phân trang (Nút Load more hoặc Phân trang số) hoạt động.

### 5.2 Tìm kiếm & Bộ lọc
- [ ] Tìm kiếm bài viết theo từ khóa.
- [ ] Click vào một Danh mục ở Sidebar → Lọc bài viết theo danh mục.
- [ ] Click vào một Tag ở Sidebar → Lọc bài viết theo tag.

---

## 6. Chi tiết Bài viết & Bình luận (Client)

### 6.1 Hiển thị Nội dung
- [ ] Click vào một bài viết → Chuyển đến `/posts/:slug`
- [ ] Render chính xác nội dung HTML từ Quill Editor.
- [ ] Hiển thị đúng số lượt xem, ngày đăng, tên tác giả, tags.
- [ ] Lượt xem tự động tăng (`POST /api/v1/posts/{id}/increment-views`).

### 6.2 Tính năng Bình luận
- [ ] **Trạng thái chưa đăng nhập:** Ẩn form bình luận, hiện thông báo yêu cầu đăng nhập.
- [ ] **Trạng thái đã đăng nhập:** Hiện ô text-area nhập bình luận.
- [ ] Gửi bình luận thành công → Toast hiển thị báo thành công.
- [ ] Danh sách bình luận tự động refresh và hiển thị nội dung bình luận vừa gửi.
- [ ] Hiển thị Avatar và Tên (commenter.name) người bình luận một cách chính xác.

---

## 7. Checklist Tổng Hợp

| Test | Kết quả | Ghi chú |
|------|---------|---------|
| 1.1 Danh mục Bài viết | ⬜ | |
| 2.1 Quản lý Thẻ (Tags) | ⬜ | |
| 3.1 Danh sách Bài viết | ⬜ | |
| 3.2 Thêm Bài viết mới (Quill) | ⬜ | |
| 3.3 Sửa bài viết (Multipart PUT) | ⬜ | Lỗi Spoofing đã fix |
| 3.4 Bật/Tắt xuất bản bài viết | ⬜ | |
| 4.1 Danh sách Bình luận | ⬜ | Lỗi Polymorphic relation đã fix |
| 4.2 Duyệt & Xóa Bình luận hàng loạt | ⬜ | |
| 5.1 Giao diện Client Blog | ⬜ | |
| 5.2 Bộ lọc Client (Search/Cat/Tag) | ⬜ | |
| 6.1 Hiển thị chi tiết bài viết HTML | ⬜ | Lỗi 500 do API đã fix |
| 6.2 Bình luận bài viết phía Client | ⬜ | Tự động tải lại sau khi gửi |
