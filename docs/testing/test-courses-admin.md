# 📚 Test Courses — Admin

> **Cần login Admin.** Seed data phải có: teachers, categories.
> Network tab: `/api/v1/admin/courses`

---

## Test 4.1: Danh sách Courses — Load trang

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Mở `/admin/courses` | Bảng: checkbox, thumbnail, tên + level badge, giảng viên, giá, học viên, status |
| 2 | Network | `GET /api/v1/admin/courses?page=1&per_page=15` → **200** |
| 3 | Tabs | "Đang hoạt động" + "Thùng rác" (badge số) |
| 4 | Nếu đã seed | Hiện 15 khóa học mẫu |

## Test 4.2: Search debounce

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Gõ "Laravel" | Đợi 400ms → filter tự động |
| 2 | Network | `?search=Laravel` |
| 3 | Xóa search | Hiện lại tất cả |
| 4 | Gõ text không có | Empty state: "Không tìm thấy khóa học" |

## Test 4.3: Filter Level

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Filter Level: "Cơ bản" | Chỉ hiện beginner |
| 2 | Filter Level: "Trung cấp" | Chỉ hiện intermediate |
| 3 | Filter Level: "Nâng cao" | Chỉ hiện advanced |
| 4 | Bỏ filter | Hiện tất cả |

## Test 4.4: Filter Status

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Filter Status: "Đã đăng" | Chỉ hiện status=1 |
| 2 | Filter Status: "Nháp" | Chỉ hiện status=0 |

## Test 4.5: Kết hợp Search + Filter

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Search "tiếng" + Level "Cơ bản" | Chỉ hiện course beginner chứa "tiếng" |
| 2 | Đổi level | Kết quả cập nhật giữ nguyên search |

## Test 4.6: Toggle Status (1 course)

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Click badge "Nháp" trên 1 row | Badge đổi → "Đã đăng" |
| 2 | Network | `PATCH /api/v1/admin/courses/{id}/toggle-status` → **200** |
| 3 | Toast | "Đã đăng khóa học" |
| 4 | Click lại | Đổi về "Nháp" |

## Test 4.7: Tạo khóa học — Thành công ✅

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Click "Thêm khóa học" | Navigate → `/admin/courses/create` |
| 2 | Form có | Tên*, Slug*, Mô tả, Teacher*, Category, Level*, Status, Giá, Sale Price, Thumbnail |
| 3 | Gõ tên: `Khóa học Test ABC` | Slug auto: `khoa-hoc-test-abc` |
| 4 | Chọn teacher, level, nhập giá `199000` | — |
| 5 | Submit | Toast "Tạo khóa học thành công" |
| 6 | Network | `POST /api/v1/admin/courses` → **201** |
| 7 | Redirect | → `/admin/courses` |
| 8 | Danh sách | Course mới xuất hiện |

## Test 4.8: Tạo — Thiếu field bắt buộc

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Submit form trống | Lỗi client: tên, slug, teacher bắt buộc |
| 2 | Network | Không có request |

## Test 4.9: Tạo — Slug trùng

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Tạo course slug trùng course đã có | **422**: "slug đã được sử dụng." |

## Test 4.10: Tạo — Sale price > Price

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Price: `100000`, Sale price: `200000` | Lỗi client: "Giá khuyến mãi phải nhỏ hơn giá gốc" |
| 2 | Network | Không có request |

## Test 4.11: Tạo — Price = 0 (miễn phí)

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Price: `0`, Sale price: để trống | Thành công |
| 2 | Danh sách | Hiện badge "Miễn phí" hoặc giá "0 ₫" |

## Test 4.12: Thumbnail Upload — Thành công ✅

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Click vùng dropzone | File picker mở |
| 2 | Chọn ảnh JPG/PNG/WebP < 5MB | Preview blob ngay, progress bar chạy |
| 3 | Network | `POST /api/v1/admin/upload/image` → **200** |
| 4 | Upload xong | Toast "Upload thành công", preview ảnh thật |
| 5 | Click X trên ảnh | Ảnh xóa, dropzone trở lại |

## Test 4.13: Thumbnail Upload — File quá lớn

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Chọn file > 5MB | Lỗi client: "File quá lớn. Tối đa 5MB." |
| 2 | Network | Không có request |

## Test 4.14: Thumbnail Upload — Sai định dạng

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Chọn file .gif | Lỗi: "Chỉ JPG, PNG, WebP." |
| 2 | Chọn file .pdf | Lỗi: "Chỉ JPG, PNG, WebP." |

## Test 4.15: Thumbnail Drag & Drop

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Kéo ảnh từ desktop vào dropzone | Border highlight xanh |
| 2 | Thả ảnh hợp lệ | Upload bắt đầu |
| 3 | Kéo thả file không phải ảnh | Lỗi format |

## Test 4.16: Edit khóa học — Load data

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Click icon sửa (bút chì) → `/admin/courses/{id}/edit` | Form điền sẵn đúng data |
| 2 | Thumbnail | Hiển thị ảnh đúng (không bị broken) |
| 3 | Category | Dropdown chọn đúng category đã gán |
| 4 | Tabs | "Thông tin" + "Nội dung" |
| 5 | Slug | Bị khóa, có nút "🔒 Mở khóa" |

## Test 4.17: Edit — Category giữ đúng giá trị

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Edit course có category "Laravel" | Dropdown hiện đúng "Laravel" |
| 2 | Đổi sang "Vue.js" → Submit → Mở lại edit | Category giữ "Vue.js" |

## Test 4.18: Edit — Mở khóa slug

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Click "🔒 Mở khóa" | Field slug enable, có thể sửa |
| 2 | Sửa slug hợp lệ → Submit | **200**, slug mới được lưu |
| 3 | Sửa slug trùng → Submit | **422** lỗi |

## Test 4.19: Tab "Nội dung" từ Edit

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Ở trang edit → Click tab "Nội dung" | Header đổi → "Nội dung khóa học", subtitle tên course |
| 2 | Component | SectionsLessonsManager load |

## Test 4.20: Shortcut icon "Nội dung" từ danh sách

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Click icon sách bên cạnh bút chì | Navigate → `/admin/courses/{id}/edit?tab=lessons` |
| 2 | Trang mở | Tab "Nội dung" tự động active |

## Test 4.21: Xóa (Soft Delete)

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Click icon thùng rác | Confirm: "Khóa học sẽ vào thùng rác" |
| 2 | Hủy | Không xóa |
| 3 | Xác nhận xóa | Toast, row biến mất, badge Thùng rác tăng |
| 4 | Network | `DELETE /api/v1/admin/courses/{id}` → **200** |

## Test 4.22: Tab Thùng rác

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Click tab "Thùng rác" | Bảng border đỏ, warning banner, thumbnail mờ |
| 2 | Network | `GET /api/v1/admin/courses/trashed` → **200** |
| 3 | Cột | Tên, giảng viên, giá, **thời gian xóa** |
| 4 | Search | Tìm kiếm trong thùng rác |

## Test 4.23: Khôi phục từ Thùng rác

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Click icon khôi phục (mũi tên xoay) | Toast "Đã khôi phục" |
| 2 | Network | `POST /api/v1/admin/courses/{id}/restore` → **200** |
| 3 | Thùng rác | Row biến mất, badge giảm |
| 4 | Tab active | Course xuất hiện lại |

## Test 4.24: Xóa vĩnh viễn

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Click icon xóa vĩnh viễn ở thùng rác | Confirm đỏ: "Hành động không thể hoàn tác!" |
| 2 | Xác nhận | Toast, row biến mất vĩnh viễn |
| 3 | Network | `DELETE /api/v1/admin/courses/{id}/force-delete` → **200** |
| 4 | Kiểm tra DB | Course không còn trong DB (kể cả soft deleted) |

## Test 4.25: Bulk Select — Chọn tất cả

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Click checkbox header | Tất cả rows highlight xanh |
| 2 | Floating bar | "Đã chọn N khóa học" + nút: Đăng, Nháp, Xóa, Bỏ chọn |
| 3 | Click header lần 2 | Bỏ chọn tất cả, floating bar ẩn |

## Test 4.26: Bulk Select — Chọn từng row

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Click checkbox row 1, 3 | 2 rows highlight, bar: "Đã chọn 2" |
| 2 | Header checkbox | Trạng thái indeterminate (—) |

## Test 4.27: Bulk Toggle → Đăng

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Chọn 2 courses nháp → Click "Đăng" | Toast: "Đã cập nhật 2 khóa học" |
| 2 | Badges | Cả 2 đổi "Đã đăng" |
| 3 | Floating bar | Biến mất |
| 4 | Network | `PATCH /api/v1/admin/courses/bulk-status` → **200** |

## Test 4.28: Bulk Toggle → Nháp

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Chọn 2 courses → "Nháp" | Cả 2 đổi "Nháp" |

## Test 4.29: Bulk Delete

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Chọn 3 courses → "Xóa" | Confirm: "Xóa 3 khóa học đã chọn?" |
| 2 | Xác nhận | Toast, 3 rows biến mất, thùng rác +3 |
| 3 | Network | `DELETE /api/v1/admin/courses/bulk-delete` body `{ ids: [...] }` → **200** |

## Test 4.30: Bulk Restore (Thùng rác)

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Tab thùng rác → chọn tất cả → "Khôi phục" | Toast thành công, thùng rác trống |
| 2 | Network | `POST /api/v1/admin/courses/bulk-restore` → **200** |

## Test 4.31: Bulk Force Delete (Thùng rác)

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Thùng rác → chọn 2 → "Xóa vĩnh viễn" | Confirm đỏ |
| 2 | Xác nhận | Toast, 2 rows biến mất vĩnh viễn |
| 3 | Network | `DELETE /api/v1/admin/courses/bulk-force-delete` → **200** |

## Test 4.32: Phân trang

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Khi có > 15 courses | Phân trang: "1–15 / N khóa học" |
| 2 | Click trang 2 | Load trang 2 |
| 3 | Network | `?page=2` |
| 4 | Bulk select qua trang | Chỉ select trang hiện tại |

---

## Checklist

| Test | Kết quả | Ghi chú |
|------|---------|---------|
| 4.1 Load trang | ✅ | Đã test index API |
| 4.2 Search | ✅ | API Search OK |
| 4.3 Filter Level | ✅ | API Filter Level OK |
| 4.4 Filter Status | ✅ | API Filter Status OK |
| 4.5 Kết hợp | ✅ | API Composite Filter OK |
| 4.6 Toggle Status | ✅ | API toggle status OK |
| 4.7 Tạo mới | ✅ | API create OK |
| 4.8 Thiếu field | ✅ | Validation schema backend OK |
| 4.9 Slug trùng | ✅ | Validation backend slug unique OK |
| 4.10 Sale > Price | ✅ | Đã chặn validation lte:price |
| 4.11 Miễn phí | ✅ | Price=0 support |
| 4.12 Upload ảnh | ⬜ | Upload service test |
| 4.13 File quá lớn | ⬜ | Backend/Frontend limit |
| 4.14 Sai định dạng | ⬜ | Mime type validation |
| 4.15 Drag & Drop | ⬜ | UI interaction |
| 4.16 Edit load data | ✅ | API show data OK |
| 4.17 Edit category | ✅ | API update category OK |
| 4.18 Mở khóa slug | ⬜ | UI implementation |
| 4.19-4.20 Tab nội dung | ⬜ | UI Layout |
| 4.21 Soft delete | ✅ | API delete OK |
| 4.22 Tab thùng rác | ✅ | API trashed OK |
| 4.23 Restore | ✅ | API restore OK |
| 4.24 Force delete | ✅ | API force delete OK |
| 4.25-4.26 Bulk select | ⬜ | UI Logic |
| 4.27-4.28 Bulk status | ✅ | API bulk status OK |
| 4.29 Bulk delete | ✅ | API bulk delete OK |
| 4.30 Bulk restore | ✅ | API bulk restore OK |
| 4.31 Bulk force delete | ✅ | API bulk force OK |
| 4.32 Phân trang | ✅ | API pagination OK |
