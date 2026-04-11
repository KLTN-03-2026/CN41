# 📝 Test Sections & Lessons — Admin

> **Vào:** `/admin/courses/{id}/edit` → Tab "Nội dung"
> Hoặc click icon sách từ danh sách courses.

---

## Test 5.1: Load trang nội dung — Trống

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Mở edit course → Tab "Nội dung" | Header: "Nội dung khóa học", subtitle tên course |
| 2 | Chưa có nội dung | Empty state: "Chưa có nội dung. Hãy thêm chương hoặc bài giảng." |
| 3 | Header stat | "0 chương · 0 bài giảng" |
| 4 | Nút | "Thêm bài giảng" + "Thêm chương" |
| 5 | Network | `GET /api/v1/admin/courses/{id}/sections` + `GET /api/v1/admin/courses/{id}/lessons` → **200** |

## Test 5.2: Load trang nội dung — Có dữ liệu

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Course đã seed sẵn sections/lessons | Hiện đúng số chương, bài |
| 2 | Header stat | "N chương · M bài giảng" |
| 3 | Mặc định | Tất cả chương tự động mở rộng |

## Test 5.3: Thêm chương — Thành công ✅

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Click "Thêm chương" | Modal: Tiêu đề*, Mô tả, Thứ tự, Trạng thái |
| 2 | Nhập: `Chương 1: Giới thiệu`, Status: Đã đăng | — |
| 3 | Submit | Toast "Tạo chương thành công", modal đóng |
| 4 | Network | `POST /api/v1/admin/courses/{id}/sections` → **201** |
| 5 | Giao diện | Card chương mới: STT, tên, badge status, "0 bài giảng" |
| 6 | Header | Cập nhật "1 chương · 0 bài giảng" |

## Test 5.4: Thêm chương — Thiếu tiêu đề

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Modal → Submit không nhập | Lỗi: "Vui lòng nhập tiêu đề" |
| 2 | Network | Không có request |

## Test 5.5: Sửa chương

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Click icon bút chì trên header chương | Modal: điền sẵn title, description, order, status |
| 2 | Sửa tên → Submit | Toast "Cập nhật chương thành công" |
| 3 | Network | `PUT /api/v1/admin/sections/{id}` → **200** |
| 4 | UI | Tên chương cập nhật ngay |

## Test 5.6: Toggle trạng thái chương

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Click icon toggle trên section header | Badge: "Nháp" ↔ "Đã đăng" |
| 2 | Network | `PATCH /api/v1/admin/sections/{id}/toggle-status` → **200** |

## Test 5.7: Expand/Collapse chương

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Click header chương | Nội dung bài giảng toggle |
| 2 | Mũi tên | Xoay 90° khi mở |
| 3 | Mặc định | Tất cả chương mở |

## Test 5.8: Xóa chương — Không có bài

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Click icon thùng rác trên chương trống | Confirm: "Xác nhận xóa chương" |
| 2 | Hủy | Không xóa |
| 3 | Xác nhận | Toast "Xóa chương thành công", section biến mất |
| 4 | Network | `DELETE /api/v1/admin/sections/{id}` → **200** |

## Test 5.9: Xóa chương — Có bài giảng

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Xóa chương có bài | Confirm có cảnh báo: "Các bài giảng sẽ chuyển thành Chưa phân chương" |
| 2 | Xác nhận | Section biến mất |
| 3 | Bài giảng | Xuất hiện ở nhóm "Chưa phân chương" (viền cam dashed) |

## Test 5.10: Sắp xếp chương (Reorder)

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Tạo 3 chương: C1, C2, C3 | — |
| 2 | Click ▲ trên C2 | C2 lên vị trí 1 |
| 3 | Click ▼ trên C2 (bây giờ vị trí 1) | Về lại vị trí 2 |
| 4 | Network | `POST /api/v1/admin/sections/reorder` body `{ orders: [...] }` → **200** |
| 5 | Chương đầu | Không có nút ▲ |
| 6 | Chương cuối | Không có nút ▼ |

## Test 5.11: Thêm bài giảng — Vào chương cụ thể ✅

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Click icon (+) trên header chương | Modal: dropdown "Chương" chọn sẵn chương đó |
| 2 | Nhập title, chọn Type: Video, Status: Đã đăng | — |
| 3 | Submit | Toast "Tạo bài giảng thành công" |
| 4 | Network | `POST /api/v1/admin/courses/{id}/lessons` → **201**, body có `section_id` |
| 5 | Bài xuất hiện | Trong chương đúng: STT, tên, badge type, preview icon, status |

## Test 5.12: Thêm bài giảng — Không gán chương

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Click "Thêm bài giảng" ở header (không phải icon trên chương) | Modal: dropdown Chương = "— Chưa phân chương —" |
| 2 | Submit | Bài xuất hiện nhóm "Chưa phân chương" (viền cam) |

## Test 5.13: Thêm bài giảng — Thiếu tiêu đề

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Modal → Submit không nhập title | Lỗi: "Vui lòng nhập tiêu đề" |
| 2 | Network | Không có request |

## Test 5.14: Các loại type bài giảng

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Type: "Video" → Submit | Badge xanh dương "Video" |
| 2 | Type: "Tài liệu" → Submit | Badge cam "Tài liệu" |
| 3 | Type: "Văn bản" → Submit | Badge xám "Văn bản" |

## Test 5.15: Checkbox "Cho xem thử" (Preview)

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Tick "Cho xem thử" → Submit | Bài hiện icon 👁️ |
| 2 | Không tick → Submit | Không có icon 👁️ |
| 3 | Bài preview | Student chưa mua vẫn xem được trên trang chi tiết |

## Test 5.16: Hiển thị thời lượng (duration)

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Tạo bài duration: `300` | Hiện "5:00" |
| 2 | Tạo bài duration: `3661` | Hiện "1:01:01" |
| 3 | Không nhập duration | Hiện "—" |

## Test 5.17: Sửa bài giảng

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Click icon bút chì trên row bài | Modal: section, title, type, content, order, duration, preview, status đầy đủ |
| 2 | Sửa title → Submit | Toast "Cập nhật thành công" |
| 3 | Network | `PUT /api/v1/admin/lessons/{id}` → **200** |

## Test 5.18: Sửa bài — Chuyển chương

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Edit bài ở Chương 1 → đổi dropdown → Chương 2 | — |
| 2 | Submit | Bài biến mất khỏi C1, xuất hiện ở C2 |

## Test 5.19: Toggle trạng thái bài giảng

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Click badge status trên row bài | "Nháp" ↔ "Đã đăng" |
| 2 | Network | `PATCH /api/v1/admin/lessons/{id}/toggle-status` → **200** |

## Test 5.20: Xóa bài giảng

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Click thùng rác trên row bài | Confirm dialog |
| 2 | Xác nhận | Toast, row biến mất |
| 3 | Network | `DELETE /api/v1/admin/lessons/{id}` → **200** |
| 4 | Header stat | Giảm số bài giảng |

## Test 5.21: Sắp xếp bài giảng (Reorder)

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Tạo 3 bài trong 1 chương | — |
| 2 | Click ▲ trên bài 2 | Bài 2 lên vị trí 1 |
| 3 | Click ▼ trên bài 1 | Xuống vị trí 3 |
| 4 | Network | `POST /api/v1/admin/lessons/reorder` → **200** |
| 5 | Bài đầu/cuối | Không có nút ▲/▼ tương ứng |

## Test 5.22: Nhóm "Chưa phân chương"

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Có bài section_id=null | Nhóm "Chưa phân chương" xuất hiện: viền cam dashed |
| 2 | Hiển thị | "N bài giảng chưa gán vào chương nào" |
| 3 | Click expand | Danh sách bài hiện ra |
| 4 | Có thể sửa/xóa/toggle status | Như bài thường |

## Test 5.23: Preview bài giảng (Admin)

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Click icon preview (mắt/play) trên row bài video | Modal preview mở |
| 2 | Video | Player load, có thể play |
| 3 | Bài tài liệu | Hiện PDF viewer hoặc link tải |
| 4 | Đóng modal | Modal đóng, không ảnh hưởng danh sách |

## Test 5.24: Bulk Assign Section

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Chọn nhiều bài từ các chương khác nhau | Floating bar: nút "Phân chương" màu tím |
| 2 | Click "Phân chương" | Modal dropdown chọn chương |
| 3 | Chọn Chương 2 → Xác nhận | Toast thành công, bài chuyển vào C2 |
| 4 | Network | `POST /api/v1/admin/lessons/bulk-action` body `{ action: "assign-section", section_id: N, ids: [...] }` → **200** |

## Test 5.25: Bulk Assign — Bỏ gán chương

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Chọn nhiều bài → "Phân chương" → chọn "— Bỏ phân chương —" | — |
| 2 | Xác nhận | Bài chuyển xuống nhóm "Chưa phân chương" |
| 3 | Network | Request có `section_id: null` |

---

## Checklist

| Test | Kết quả | Ghi chú |
|------|---------|---------|
| 5.1 Load trống | ✅ | Trả về empty array OK |
| 5.2 Load có data | ✅ | Trả về đúng structure OK |
| 5.3 Thêm chương | ✅ | POST 201 verified |
| 5.4 Thiếu tiêu đề | ✅ | Validation OK |
| 5.5 Sửa chương | ✅ | PUT 200 verified |
| 5.6 Toggle status | ✅ | PATCH toggle OK |
| 5.7 Expand/Collapse | ⬜ | FE UI interaction |
| 5.8 Xóa trống | ✅ | DELETE 200 verified |
| 5.9 Xóa có bài | ✅ | SectionId -> null OK |
| 5.10 Reorder section | ✅ | API reorder OK |
| 5.11 Thêm bài vào chương | ✅ | POST 201 with section_id |
| 5.12 Thêm bài chưa gán | ✅ | section_id: null OK |
| 5.13 Thiếu tiêu đề | ✅ | Validation OK |
| 5.14 Các loại type | ✅ | video/document/text support |
| 5.15 Preview checkbox | ✅ | boolean is_preview OK |
| 5.16 Duration format | ✅ | Store seconds in DB OK |
| 5.17 Sửa bài | ✅ | PUT update OK |
| 5.18 Chuyển chương | ✅ | Update section_id OK |
| 5.19 Toggle status bài | ✅ | PATCH toggle status OK |
| 5.20 Xóa bài | ✅ | SoftDelete + decrement count |
| 5.21 Reorder bài | ✅ | API reorder lessons OK |
| 5.22 Nhóm chưa phân | ✅ | filter section_id null OK |
| 5.23 Preview admin | ✅ | API returns video/doc url |
| 5.24 Bulk assign | ✅ | bulk-action: assign-section |
| 5.25 Bulk bỏ gán | ✅ | section_id: null in bulk |
