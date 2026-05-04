# Báo cáo Test — Courses Admin

> **Ngày test:** 2026-04-11 (cập nhật 2026-04-11)
> **Tester:** Ahryxx
> **Môi trường:** localhost (Backend :8000 · Frontend :5173)
> **Seed data:** 25 khóa học, teachers + categories đầy đủ

---

## Tổng quan

| Hạng mục | Số lượng |
|----------|---------|
| Tổng test case | 32 |
| ✅ Pass | 26 |
| ⬜ Chưa test | 6 |
| ❌ Fail | 0 |

---

## Kết quả chi tiết

### Nhóm 1 — Danh sách & Filter

| Test | Mô tả | Kết quả | Ghi chú |
|------|-------|---------|---------|
| 4.1 | Load trang danh sách | ✅ Pass | `GET /api/v1/admin/courses?page=1&per_page=15` → 200 |
| 4.2 | Search debounce | ✅ Pass | `?search=Laravel` hoạt động đúng, debounce 400ms |
| 4.3 | Filter Level | ✅ Pass | beginner / intermediate / advanced filter đúng |
| 4.4 | Filter Status | ✅ Pass | Đã đăng / Nháp filter đúng |
| 4.5 | Kết hợp Search + Filter | ✅ Pass | Composite filter hoạt động, giữ nguyên search khi đổi level |

### Nhóm 2 — Toggle Status

| Test | Mô tả | Kết quả | Ghi chú |
|------|-------|---------|---------|
| 4.6 | Toggle status 1 course | ✅ Pass | `PATCH .../toggle-status` → 200, badge đổi ngay, toast đúng |

### Nhóm 3 — Tạo mới khóa học

| Test | Mô tả | Kết quả | Ghi chú |
|------|-------|---------|---------|
| 4.7  | Tạo thành công | ✅ Pass | `POST /api/v1/admin/courses` → 201, redirect về danh sách |
| 4.8  | Thiếu field bắt buộc | ✅ Pass | Client validation chặn, không có request |
| 4.9  | Slug trùng | ✅ Pass | Backend → 422 "slug đã được sử dụng" |
| 4.10 | Sale price > Price | ✅ Pass | Client validation chặn |
| 4.11 | Price = 0 (miễn phí) | ✅ Pass | Tạo thành công, hiển thị "0 ₫" |

### Nhóm 4 — Thumbnail Upload

| Test | Mô tả | Kết quả | Ghi chú |
|------|-------|---------|---------|
| 4.12 | Upload ảnh thành công | ⬜ Chưa test | Đã fix lỗi 403 (`FILESYSTEM_DISK=public`), cần test lại UI |
| 4.13 | File quá lớn (> 5MB) | ⬜ Chưa test | |
| 4.14 | Sai định dạng (.gif, .pdf) | ⬜ Chưa test | |
| 4.15 | Drag & Drop | ⬜ Chưa test | |

### Nhóm 5 — Edit khóa học

| Test | Mô tả | Kết quả | Ghi chú |
|------|-------|---------|---------|
| 4.16 | Edit load data | ✅ Pass | Form điền sẵn đúng, thumbnail hiển thị |
| 4.17 | Edit category giữ đúng giá trị | ✅ Pass | Dropdown select đúng, lưu → mở lại vẫn đúng |
| 4.18 | Mở khóa slug | ⬜ Chưa test | UI có nút "🔒 Mở khóa" nhưng chưa test flow |
| 4.19 | Tab "Nội dung" từ Edit | ⬜ Chưa test | |
| 4.20 | Shortcut icon "Nội dung" từ danh sách | ⬜ Chưa test | |

### Nhóm 6 — Soft Delete / Thùng rác

| Test | Mô tả | Kết quả | Ghi chú |
|------|-------|---------|---------|
| 4.21 | Soft delete | ✅ Pass | `DELETE .../courses/{id}` → 200, row biến mất, badge tăng. Cascade: sections + lessons bị soft delete theo |
| 4.22 | Tab Thùng rác | ✅ Pass | `GET .../courses/trashed` → 200, bảng border đỏ |
| 4.23 | Khôi phục | ✅ Pass | `PATCH .../restore` → 200, course + sections + lessons được restore theo |
| 4.24 | Xóa vĩnh viễn | ✅ Pass | `DELETE .../force-delete` → 200, course + sections + lessons bị xóa vĩnh viễn khỏi DB |

### Nhóm 7 — Bulk Actions

| Test | Mô tả | Kết quả | Ghi chú |
|------|-------|---------|---------|
| 4.25 | Bulk select — chọn tất cả | ⬜ Chưa test | |
| 4.26 | Bulk select — chọn từng row | ⬜ Chưa test | |
| 4.27 | Bulk toggle → Đăng | ✅ Pass | Toast đúng, badges cập nhật |
| 4.28 | Bulk toggle → Nháp | ✅ Pass | |
| 4.29 | Bulk delete | ✅ Pass | `DELETE .../bulk-delete` → 200, thùng rác tăng đúng số |
| 4.30 | Bulk restore | ✅ Pass | `POST .../bulk-restore` → 200, cascade restore sections + lessons |
| 4.31 | Bulk force delete | ✅ Pass | `DELETE .../bulk-force-delete` → 200 |

### Nhóm 8 — Phân trang

| Test | Mô tả | Kết quả | Ghi chú |
|------|-------|---------|---------|
| 4.32 | Phân trang | ✅ Pass | 25 courses / 15 per_page = 2 trang, `?page=2` đúng |

---

## Các lỗi phát hiện & đã sửa trong quá trình test

| # | Lỗi | Nguyên nhân | Trạng thái |
|---|-----|-------------|-----------|
| 1 | Phân trang không hiển thị | Seed data chỉ có 15 courses = 1 trang | ✅ Đã seed thêm lên 25 courses |
| 2 | Thumbnail trả về 403 | `FILESYSTEM_DISK=local` lưu vào `storage/app/` thay vì `storage/app/public/` | ✅ Đã đổi sang `FILESYSTEM_DISK=public` |
| 3 | Danh mục không có validation bắt buộc | Thiếu validate `category_id` ở client | ✅ Đã thêm validate + hiển thị error |
| 4 | `PaginationBar` render tất cả số trang | Dùng `v-for="p in last_page"` trực tiếp | ✅ Tạo component `PaginationBar.vue` với Prev/Next + ellipsis |
| 5 | Nút "Xóa filter" chưa có | Thiếu UI để reset filter nhanh | ✅ Đã thêm nút "Xóa filter" cho cả active tab và trashed tab |
| 6 | `Course` interface dùng `title` thay vì `name` | Type mismatch với API response | ✅ Đã sửa trong `course.types.ts` |
| 7 | Soft delete course không cascade xuống sections/lessons | Không có model event | ✅ Thêm `deleting` event trong `Course::booted()` |
| 8 | Force delete course không cascade xuống sections/lessons | Không có model event | ✅ Thêm `forceDeleting` event trong `Course::booted()` |
| 9 | Restore course không cascade sections/lessons | Eloquent mass `.restore()` không trigger events | ✅ Thêm `restoring` event + override `restoreMany()` trong `CourseRepository` |

---

## Việc cần làm tiếp

- [ ] Test 4.12–4.15: Thumbnail upload (UI flow sau khi fix 403)
- [ ] Test 4.18: Flow mở khóa slug và sửa
- [ ] Test 4.19–4.20: Tab nội dung và shortcut icon
- [ ] Test 4.25–4.26: Bulk select UI (checkbox indeterminate, floating bar)

## Cải tiến hậu test (không có trong test plan gốc)

| # | Thay đổi | File |
|---|----------|------|
| 1 | Cascade soft delete / force delete / restore xuống sections + lessons | `Course.php`, `CourseRepository.php` |
| 2 | `PaginationBar.vue` component dùng chung (Prev/Next + ellipsis) | `components/common/PaginationBar.vue` |
| 3 | Nút "Xóa filter" trên CourseFilters | `CourseFilters.vue` |
| 4 | Validate bắt buộc chọn danh mục khi tạo/sửa khóa học | `CourseFormPage.vue`, `CourseInfoForm.vue` |
