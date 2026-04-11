# 🗂️ Test Categories — Admin CRUD

> **Cần login Admin trước.** Xem [test-auth.md](test-auth.md) Test 1.6.
> Network tab: `GET/POST/PUT/DELETE /api/v1/admin/categories`

---

## Test 3.1: Danh sách — Load trang

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Mở `/admin/categories` | Bảng hiển thị dạng cây: indent `└` cho category con |
| 2 | Network | `GET /api/v1/admin/categories` → **200** |
| 3 | Cột bảng | Tên, Slug, Cấp (Gốc/Cấp 1/Cấp 2), Trạng thái, Hành động |
| 4 | Nếu đã seed | Hiện đủ categories (Lập trình, Ngoại ngữ, Web Development...) |

## Test 3.2: Tạo Category gốc — Form trống

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Click "Thêm danh mục" | Modal mở: Tên*, Slug*, Mô tả, Icon, Parent, Trạng thái |
| 2 | Submit không nhập gì | Lỗi: "Vui lòng nhập tên danh mục" |
| 3 | Network | Không có request |

## Test 3.3: Tạo Category gốc — Thành công ✅

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Tên: `Kỹ năng mềm` | Slug auto: `ky-nang-mem` |
| 2 | Parent: `-- Không có --` | — |
| 3 | Submit | Toast thành công, modal đóng, bảng refresh |
| 4 | Network | `POST /api/v1/admin/categories` → **201** |
| 5 | Bảng | Row mới: "Kỹ năng mềm", slug `ky-nang-mem`, Cấp: Gốc |

## Test 3.4: Tạo Category con

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Tên: `Thuyết trình`, Parent: `Kỹ năng mềm` | Slug: `thuyet-trinh` |
| 2 | Submit | Thành công |
| 3 | Bảng | Row "Thuyết trình" có indent `└`, Cấp: Cấp 1 |

## Test 3.5: Slug tự động từ tên tiếng Việt

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Tên: `Thiết kế đồ họa` | Slug auto: `thiet-ke-do-hoa` |
| 2 | Tên: `C++/C#` | Slug auto: `c-c` (normalize ký tự đặc biệt) |
| 3 | Tên: `AI & Machine Learning` | Slug auto: `ai-machine-learning` |

## Test 3.6: Slug trùng

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Tạo category tên `Kỹ năng mềm` lần 2 (slug `ky-nang-mem` đã tồn tại) | **422** |
| 2 | Response | `errors.slug: ["slug đã được sử dụng."]` |
| 3 | FE | Lỗi inline tại field slug |

## Test 3.7: Slug sai format

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Xóa slug auto → nhập `Slug Lớn` (có hoa, có space) | Lỗi: "Slug chỉ chứa chữ thường, số và dấu gạch ngang" |
| 2 | Nhập `slug-hop-le-123` | Không lỗi |

## Test 3.8: Sửa Category

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Click icon sửa (bút chì) trên row | Modal mở, form điền sẵn đúng data |
| 2 | Sửa tên → `Kỹ Năng Mềm (Updated)` | — |
| 3 | Submit | Toast thành công, bảng cập nhật |
| 4 | Network | `PUT /api/v1/admin/categories/{id}` → **200** |

## Test 3.9: Sửa slug trùng

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Sửa slug thành slug của category khác đã tồn tại | **422** lỗi slug trùng |

## Test 3.10: Xóa Category — Chưa có con

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Click icon xóa (thùng rác) | Confirm dialog: "Bạn có chắc muốn xóa?" |
| 2 | Click "Hủy" | Dialog đóng, không xóa |
| 3 | Click "Xóa" → xác nhận | Toast thành công, row biến mất |
| 4 | Network | `DELETE /api/v1/admin/categories/{id}` → **200** |

## Test 3.11: Xóa Category — Có category con

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Click icon xóa trên category cha đang có con | Confirm dialog hiện |
| 2 | Xác nhận xóa | Toast **lỗi**: "Không thể xóa danh mục đang có danh mục con." |
| 3 | Network | `DELETE /api/v1/admin/categories/{id}` → **400** |
| 4 | Bảng | Row vẫn còn, không bị xóa |
| 5 | Cách xử lý đúng | Xóa tất cả category con trước, rồi mới xóa category cha |

## Test 3.12: Xóa Category — Đang gắn với Course

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Click xóa trên category leaf đang được gán cho 1+ courses | Confirm dialog hiện |
| 2 | Xác nhận xóa | Toast **lỗi**: "Không thể xóa danh mục đang được dùng bởi N khóa học." |
| 3 | Network | `DELETE /api/v1/admin/categories/{id}` → **400** |
| 4 | Bảng | Row vẫn còn, không bị xóa |
| 5 | Cách xử lý đúng | Vào edit từng course → đổi sang danh mục khác → quay lại xóa category |

> **Lưu ý:** Bulk delete cũng áp dụng tương tự — các category đang có courses bị **skip** (không xóa), các category hợp lệ vẫn được xóa bình thường. Toast sẽ báo số lượng xóa được và message lỗi của item đầu tiên bị chặn.

## Test 3.13: Toggle Trạng thái

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Click badge "Hoạt động" / "Ẩn" trên row | Badge đổi ngược lại |
| 2 | Network | `PATCH /api/v1/admin/categories/{id}` hoặc tương tự → **200** |

## Test 3.14: Phân trang

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Khi có > 15 categories | Phân trang xuất hiện |
| 2 | Click trang 2 | Bảng load trang 2 |
| 3 | Network | `GET /api/v1/admin/categories?page=2` → **200** |

## Test 3.15: Search Categories

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Gõ "lập" vào ô search | Debounce → filter hiện "Lập trình", "Lập trình Web"... |
| 2 | Xóa search | Bảng trở về đầy đủ |
| 3 | Gõ text không tồn tại | Empty state: "Không tìm thấy danh mục" |

---

## Test 3.16: Khôi phục danh mục (Restore) — Strict Validation ✅

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Khôi phục danh mục con khi **cha đang trong thùng rác** (soft-deleted) | Toast lỗi: "Vui lòng khôi phục danh mục cha trước." |
| 2 | Khôi phục danh mục cha trước, sau đó khôi phục con | Toast thành công, danh mục hiện lại trong danh sách hoạt động |
| 3 | Network | `POST /api/v1/admin/categories/{id}/restore` → **200** (thành công) hoặc **400** (kèm message lỗi) |

---

## Checklist

| Test | Kết quả | Ghi chú |
|------|---------|---------|
| 3.1 Load trang | ✅ | Đã test list API returns 200 |
| 3.2 Form trống | ✅ | Validation schema ok |
| 3.3 Tạo gốc | ✅ | Tạo parent_id = null |
| 3.4 Tạo con | ✅ | Tạo parent_id có giá trị |
| 3.5 Slug auto | ⬜ | FE xử lý, Backend có DB save text |
| 3.6 Slug trùng | ✅ | Validation backend báo 422 chuẩn |
| 3.7 Slug sai format | ⬜ | Phụ thuộc validation rule |
| 3.8 Sửa | ✅ | PUT Request OK |
| 3.9 Sửa slug trùng | ✅ | Catch duplicate entry |
| 3.10 Xóa đơn | ✅ | SoftDelete verified |
| 3.11 Xóa có con | ✅ | Đã chặn xóa và trả lỗi 400 |
| 3.12 Xóa có course | ✅ | Bị chặn 400 "đang dùng bởi N khóa học", bulk delete skip item bị chặn |
| 3.13 Toggle status | ✅ | Toggle 0/1 thành công |
| 3.14 Phân trang | ✅ | Đã test per_page query |
| 3.15 Search | ✅ | Đã test search query (name/slug) |
| 3.16 Khôi phục (Strict) | ✅ | Chặn khôi phục nếu cha chưa active |
