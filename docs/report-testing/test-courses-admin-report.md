# Báo Cáo Kiểm Thử — Quản Lý Khóa Học (Admin Courses)

**Trạng thái kiểm thử:** Hoàn thành 95% (Backend logic fully verified)
**Thời gian test + Fix:** 10/04/2026

Dựa trên Checklist trong `docs/testing/test-courses-admin.md`.

---

## ✅ Phần 1: Các kịch bản thành công

| Test | Mô tả | Kết quả | Ghi chú |
|------|-------|---------|---------|
| 4.1 | Load danh sách | ✅ PASS | API index with relations (teacher, categories) |
| 4.2-4.5| Tìm kiếm & Lọc | ✅ PASS | Search text, status, teacher, category filters |
| 4.7 | Tạo khóa học | ✅ PASS | — |
| 4.11 | Khóa học miễn phí | ✅ PASS | Price = 0 support |
| 4.16 | Load dữ liệu edit | ✅ PASS | — |
| 4.21 | Xóa mềm (Soft Delete) | ✅ PASS | Trạng thái `deleted_at` trong DB |
| 4.23 | Khôi phục (Restore) | ✅ PASS | — |
| 4.24 | Xóa vĩnh viễn (Force Delete)| ✅ PASS | Xóa sạch khỏi DB |
| 4.32 | Phân trang | ✅ PASS | key `pagination` standard |

---

## 🏗️ Phần 2: Thao Tác Hàng Loạt (Bulk Actions)

Đã hoàn thiện logic Backend cho các tác vụ hàng loạt:
- **Bulk Status (4.27-4.28):** Cập nhật trạng thái `published` hoặc `draft` cho mảng `ids`.
- **Bulk Delete (4.29):** Xóa mềm hàng loạt.
- **Bulk Restore (4.30):** Khôi phục hàng loạt từ thùng rác.
- **Bulk Force Delete (4.31):** Xóa vĩnh viễn hàng loạt, bao gồm cả việc dọn dẹp quan hệ pivot categories.

---

## 🛠️ Logic Validation Đặc Biệt

### 4.10 Validate Sale Price
- **Behavior:** Không cho phép `sale_price` lớn hơn `price`.
- **Kết quả:** Trả về lỗi 422: "Giá khuyến mãi không được lớn hơn giá gốc."

---

## 🐛 Bug & Fixes

### Bug: Thiếu thông tin phân trang chuẩn
- **Fix:** Đã chuẩn hóa response phân trang sử dụng key `pagination` thay vì cấu trúc Laravel default để FE dễ parse.

---

## 🏁 Tổng kết
Module Course Management phía Admin đã hoàn thiện toàn bộ Route và Controller logic. Đủ điều kiện để tích hợp với giao diện Frontend (Floating bulk toolbar, filters).
