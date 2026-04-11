# Báo Cáo Kiểm Thử — Danh Mục (Categories)

**Trạng thái kiểm thử:** Hoàn thành 90% (Backend verified)
**Thời gian test + Fix:** 10/04/2026

Dựa trên Checklist trong `docs/testing/test-categories.md`.

---

## ✅ Phần 1: Các kịch bản thành công (Backend Verified)

| Test | Mô tả | Kết quả | Ghi chú |
|------|-------|---------|---------|
| 3.1 | Danh sách Categories | ✅ PASS | API trả về cấu trúc cây hoặc list kèm parent_id |
| 3.3 | Tạo Category gốc | ✅ PASS | parent_id = null |
| 3.4 | Tạo Category con | ✅ PASS | parent_id = {id_cha} |
| 3.6 | Slug trùng | ✅ PASS | Trả về lỗi 422 chuẩn |
| 3.8 | Cập nhật Category | ✅ PASS | — |
| 3.13 | Toggle Status | ✅ PASS | Đảo trạng thái 0/1 |
| 3.14 | Phân trang | ✅ PASS | key `pagination` trong response |
| 3.15 | Tìm kiếm | ✅ PASS | Tìm theo `name` hoặc `slug` |

---

## 🛠️ Phần 2: Logic Đặc Biệt & Ràng Buộc

### 3.11 Xóa Category đang có danh mục con
- **Behavior:** Hệ thống không cho phép xóa danh mục cha nếu vẫn còn tồn tại danh mục con trực thuộc.
- **Kết quả:** Trả về lỗi 400 kèm thông báo: "Không thể xóa danh mục này vì vẫn còn danh mục con."
- **Lý do:** Tránh hiện tượng Orphan Nodes (danh mục con mất gốc).

---

## 🐛 Bug & Fixes

### Bug: Phân trang sai key meta
- **Vấn đề:** Ban đầu test fail do assert tìm key `meta` nhưng API trả về `pagination`.
- **Fix:** Đồng bộ bộ test sử dụng key `pagination` theo đúng `ApiResponse` trait.

---

## 🏁 Tổng kết
Module Categories đã sẵn sàng về mặt API. Các tính năng cây danh mục phía Frontend cần kết hợp với API `GET /admin/categories` để hiển thị visual.
