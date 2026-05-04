# Báo cáo Test — Categories Admin

> **Ngày test:** 2026-04-11
> **Tester:** Ahryxx
> **Môi trường:** localhost (Backend :8000 · Frontend :5173)
> **Seed data:** 20 categories (2 gốc, đầy đủ cây con)

---

## Tổng quan

| Hạng mục | Số lượng |
|----------|---------|
| Tổng test case | 16 |
| ✅ Pass | 14 |
| ⬜ Chưa test | 2 |
| ❌ Fail | 0 |

---

## Kết quả chi tiết

### Nhóm 1 — Danh sách & Search

| Test | Mô tả | Kết quả | Ghi chú |
|------|-------|---------|---------|
| 3.1 | Load trang danh sách | ✅ Pass | `GET /api/v1/admin/categories` → 200, hiển thị dạng cây với indent |
| 3.15 | Search categories | ✅ Pass | Tìm theo name/slug, debounce hoạt động đúng |
| 3.14 | Phân trang | ✅ Pass | Phân trang theo root categories, `PaginationBar` Prev/Next + ellipsis |

### Nhóm 2 — Tạo mới

| Test | Mô tả | Kết quả | Ghi chú |
|------|-------|---------|---------|
| 3.2 | Form trống — validation | ✅ Pass | Client chặn, không có request |
| 3.3 | Tạo category gốc | ✅ Pass | `POST` → 201, `parent_id = null` |
| 3.4 | Tạo category con | ✅ Pass | `parent_id` có giá trị, hiển thị indent đúng cấp |
| 3.5 | Slug auto từ tên tiếng Việt | ⬜ Chưa test | FE xử lý transliterate, cần test thủ công |
| 3.6 | Slug trùng | ✅ Pass | Backend → 422 `errors.slug` |
| 3.7 | Slug sai format | ⬜ Chưa test | Cần test input có chữ hoa/space |

### Nhóm 3 — Sửa

| Test | Mô tả | Kết quả | Ghi chú |
|------|-------|---------|---------|
| 3.8 | Sửa category | ✅ Pass | `PUT` → 200, modal điền sẵn đúng data |
| 3.9 | Sửa slug trùng | ✅ Pass | Backend → 422 |

### Nhóm 4 — Xóa

| Test | Mô tả | Kết quả | Ghi chú |
|------|-------|---------|---------|
| 3.10 | Xóa category leaf không có courses | ✅ Pass | Soft delete thành công, row biến mất |
| 3.11 | Xóa category có con | ✅ Pass | Backend → 400 "Không thể xóa danh mục đang có danh mục con." |
| 3.12 | Xóa category đang gắn với course | ✅ Pass | Backend → 400 "Không thể xóa danh mục đang được dùng bởi N khóa học." |

### Nhóm 5 — Trạng thái & Khôi phục

| Test | Mô tả | Kết quả | Ghi chú |
|------|-------|---------|---------|
| 3.13 | Toggle status | ✅ Pass | Badge đổi ngay, `PATCH toggle-status` → 200 |
| 3.16 | Khôi phục — strict validation | ✅ Pass | Chặn restore con khi cha chưa được restore |

---

## Các lỗi phát hiện & đã sửa

| # | Lỗi | Nguyên nhân | Trạng thái |
|---|-----|-------------|-----------|
| 1 | Xóa category đang có courses không bị chặn | Chưa có validation trong `CategoriesRepository::delete()` | ✅ Thêm check `Course::whereHas('categories')` trước khi delete |
| 2 | Bulk delete: 1 item lỗi làm fail toàn bộ | `Promise.all` ném exception khi có 1 request 400 | ✅ Đổi sang `Promise.allSettled`, toast báo số xóa được + item lỗi đầu tiên |
| 3 | `category.service.ts` dùng `Category` sai type cho admin endpoints | `Category` từ `course.types.ts` thiếu `depth`, `status`, `is_root` | ✅ Đổi sang `AdminCategory` cho tất cả admin endpoints |

---

## Việc cần làm tiếp

- [ ] Test 3.5: Slug auto — test các ký tự đặc biệt (C++, &, /, tiếng Việt nâng cao)
- [ ] Test 3.7: Slug sai format — nhập chữ hoa, có space, ký tự đặc biệt
