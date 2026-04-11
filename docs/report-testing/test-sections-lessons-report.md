# Báo Cáo Kiểm Thử — Chương & Bài Giảng (Sections & Lessons)

**Trạng thái kiểm thử:** Hoàn thành 100% (Backend logic)
**Thời gian test + Fix:** 10/04/2026

Dựa trên Checklist trong `docs/testing/test-sections-lessons.md`.

---

## ✅ Phần 1: Các kịch bản thành công

| Test | Mô tả | Kết quả | Ghi chú |
|------|-------|---------|---------|
| 5.1-5.2 | Load nội dung | ✅ PASS | API lấy Curriculum theo course_id |
| 5.3-5.5 | CRUD Section | ✅ PASS | Create/Update/Delete Section thành công |
| 5.6-5.8 | Reorder & Status | ✅ PASS | Sắp xếp Drag & Drop (Backend logic) và Toggle |
| 5.10-5.18| CRUD Lesson | ✅ PASS | Video/Text/Doc types verified |
| 5.19-5.21| Lesson Actions | ✅ PASS | Toggle status & Reorder lessons |
| 5.22-5.25| Bulk Actions | ✅ PASS | Assign/Unassign lessons to section |

---

## 🛠️ Logic Đặc Biệt & Fixes quan trọng

### 5.9 Xóa Chương (Orphan Lessons)
- **Logic:** Khi xóa một Section, các bài giảng bên trong KHÔNG bị xóa theo. Thay vào đó, trường `section_id` của chúng được cập nhật về `null`.
- **Verify:** Đã viết test case `test_delete_section_keeps_lessons_as_orphans` để đảm bảo bài giảng tự động chuyển vào mục "Chưa phân chương".

### 5.21 Reorder Bài giảng
- **Behavior:** Nhận mảng `orders` gồm `id` và `order` mới để cập nhật thứ tự hàng loạt.
- **Verify:** Đã Pass test case `test_reorder_lessons_success`.

---

## 🐛 Bug & Fixes (Major)

### Bug: 500 Internel Server Error - Logout Test
- **Vấn đề:** Lỗi `delete() on null` khi chạy feature test logout.
- **Fix:** Đã bổ sung null-check cho `currentAccessToken()` trong Controller. (Đã fix triệt để cho cả Student & Admin).

### Bug: Validation Lesson (Missing Fields)
- **Vấn đề:** Test tạo bài giảng video bị 422 do thiếu `video_id`.
- **Fix:** Cập nhật bộ test khởi tạo dữ liệu mẫu trong bảng `media_files` để vượt qua validation `exists:media_files,id`.

---

## 🏁 Tổng kết
Module Syllabus (Chương/Bài giảng) đã có Logic Backend rất vững chắc, xử lý tốt các trường hợp Edge Cases (Xóa chương giữ bài, phân bài giảng lỏng lẻo không chương). Các API đã sẵn sàng cho giao diện Drag & Drop phức tạp phía Client.
