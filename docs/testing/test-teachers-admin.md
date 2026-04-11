# Test Module: Quản lý Giảng viên (Admin)

> **Route BE:** `/api/v1/admin/teachers`
> **Page FE:** `/admin/teachers`
> **Auth:** Admin (guard: admin)

---

## QLCV — Việc cần làm

### Backend
- [ ] `index`: filter theo `search` (name/email), `status`, pagination
- [ ] `store`: validate unique email, upload ảnh đại diện (gọi upload API trước)
- [ ] `update`: không bắt buộc đổi password
- [ ] `toggleStatus`: active/inactive — inactive thì ẩn khỏi public listing
- [ ] `trashed`: danh sách soft-deleted
- [ ] `restore`, `forceDelete`, `bulkRestore`, `bulkDelete`, `bulkForceDelete`
- [ ] Public routes: `publicIndex`, `publicShow` — chỉ trả teacher active

### Frontend
- [ ] `TeachersPage.vue`: bảng danh sách với cột: avatar, tên, email, số khóa học, trạng thái, actions
- [ ] `TeachersPage.vue`: search, filter status, pagination
- [ ] `TeachersPage.vue`: modal tạo/sửa giảng viên (form inline hoặc drawer)
- [ ] `TeachersPage.vue`: upload ảnh đại diện với preview
- [ ] `TeachersPage.vue`: toggle status (switch)
- [ ] `TeachersPage.vue`: tab "Thùng rác" với restore/force-delete
- [ ] `TeachersPage.vue`: checkbox bulk actions

---

## MODULE 1 — Danh sách giảng viên

### Test 1.1: Load trang

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Vào `/admin/teachers` | Bảng giảng viên load, có avatar, tên, email, số khóa học, status |
| 2 | Network | `GET /api/v1/admin/teachers` → 200 |

### Test 1.2: Tìm kiếm

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Nhập tên giảng viên | Bảng lọc theo tên |
| 2 | Nhập email | Lọc theo email |
| 3 | Không có kết quả | Hiện "Không tìm thấy" |

### Test 1.3: Filter trạng thái

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Filter "Active" | Chỉ hiện giảng viên đang hoạt động |
| 2 | Filter "Inactive" | Chỉ hiện giảng viên bị ẩn |

---

## MODULE 2 — Thêm giảng viên

### Test 2.1: Form trống

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Click "Thêm giảng viên" → Submit trống | Lỗi: "Tên không được để trống", "Email không được để trống" |

### Test 2.2: Email đã tồn tại

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Nhập email đã có trong hệ thống | Lỗi: "Email đã được sử dụng" |

### Test 2.3: Tạo thành công ✅

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Điền đầy đủ: tên, email, bio, upload ảnh → Submit | Toast "Thêm giảng viên thành công" |
| 2 | Network | `POST /api/v1/admin/teachers` → 201 |
| 3 | Bảng | Giảng viên mới xuất hiện |
| 4 | Ảnh | Hiện đúng ảnh đã upload |

### Test 2.4: Upload ảnh đại diện

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Click upload → chọn file ảnh | Preview ảnh hiện trước khi submit |
| 2 | Upload file không phải ảnh (PDF) | Lỗi: "Chỉ chấp nhận file ảnh" |
| 3 | Upload ảnh quá lớn (>2MB) | Lỗi kích thước |

---

## MODULE 3 — Sửa giảng viên

### Test 3.1: Sửa thông tin cơ bản

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Click edit → sửa tên → Save | Toast thành công, bảng cập nhật |
| 2 | Network | `PUT /api/v1/admin/teachers/{id}` → 200 |

### Test 3.2: Đổi ảnh đại diện

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Upload ảnh mới → Save | Ảnh cũ bị xóa, ảnh mới hiện |

---

## MODULE 4 — Toggle trạng thái

### Test 4.1: Ẩn giảng viên

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Toggle status → Inactive | Badge đổi thành "Inactive" |
| 2 | Network | `PATCH /api/v1/admin/teachers/{id}/toggle-status` → 200 |
| 3 | Public `/teachers` | Giảng viên không còn hiện |

### Test 4.2: Kích hoạt lại

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Toggle lại → Active | Badge "Active", hiện lại trên public |

---

## MODULE 5 — Xóa và Thùng rác

### Test 5.1: Soft delete

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Click xóa → confirm | Giảng viên biến khỏi bảng chính |
| 2 | Network | `DELETE /api/v1/admin/teachers/{id}` → 200 |
| 3 | Tab "Thùng rác" | Giảng viên xuất hiện ở đây |

### Test 5.2: Restore

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Trong thùng rác → Restore | Giảng viên về lại bảng chính |
| 2 | Network | `POST /api/v1/admin/teachers/{id}/restore` → 200 |

### Test 5.3: Force delete

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Trong thùng rác → Xóa vĩnh viễn → confirm | Xóa hoàn toàn khỏi DB |
| 2 | Network | `DELETE /api/v1/admin/teachers/{id}/force-delete` → 200 |

### Test 5.4: Bulk actions

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Tick nhiều giảng viên → Xóa hàng loạt | Tất cả vào thùng rác |
| 2 | Trong thùng rác: tick nhiều → Restore hàng loạt | Tất cả về bảng chính |

---

## Checklist

| Test | Kết quả | Ghi chú |
|------|---------|---------|
| 1.1 Load trang | ⬜ | |
| 1.2 Tìm kiếm | ⬜ | |
| 1.3 Filter trạng thái | ⬜ | |
| 2.1 Form trống | ⬜ | |
| 2.2 Email trùng | ⬜ | |
| 2.3 Tạo thành công | ⬜ | |
| 2.4 Upload ảnh | ⬜ | |
| 3.1 Sửa thông tin | ⬜ | |
| 3.2 Đổi ảnh | ⬜ | |
| 4.1 Ẩn giảng viên | ⬜ | |
| 4.2 Kích hoạt lại | ⬜ | |
| 5.1 Soft delete | ⬜ | |
| 5.2 Restore | ⬜ | |
| 5.3 Force delete | ⬜ | |
| 5.4 Bulk actions | ⬜ | |
