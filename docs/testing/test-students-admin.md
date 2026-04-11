# Test Module: Quản lý Học viên (Admin)

> **Route BE:** `/api/v1/admin/students`
> **Page FE:** `/admin/students`
> **Auth:** Admin (guard: admin)

---

## QLCV — Việc cần làm

### Backend
- [ ] `index`: filter theo `search` (name/email), `status`, pagination
- [ ] `show`: chi tiết student kèm danh sách khóa học đã enroll + progress
- [ ] `store`: tạo student thủ công (không qua register flow) — hash password
- [ ] `update`: sửa thông tin student, không bắt buộc đổi password
- [ ] `destroy`: soft delete
- [ ] `restore`, `forceDelete`, `bulkRestore`, `bulkDelete`, `bulkForceDelete`
- [ ] Chưa có: API xem lịch sử đơn hàng của student

### Frontend
- [ ] `StudentsPage.vue`: bảng danh sách: avatar, tên, email, ngày đăng ký, số khóa đã enroll, trạng thái, actions
- [ ] `StudentsPage.vue`: search, filter status, pagination
- [ ] `StudentsPage.vue`: xem chi tiết student (drawer/modal) — khóa học đã enroll, progress
- [ ] `StudentsPage.vue`: toggle trạng thái (active/banned)
- [ ] `StudentsPage.vue`: tab "Thùng rác"
- [ ] `StudentsPage.vue`: bulk actions

---

## MODULE 1 — Danh sách học viên

### Test 1.1: Load trang

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Vào `/admin/students` | Bảng học viên load với các cột đầy đủ |
| 2 | Network | `GET /api/v1/admin/students` → 200 |

### Test 1.2: Tìm kiếm

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Nhập tên hoặc email | Bảng lọc kết quả |
| 2 | Không có kết quả | Hiện "Không tìm thấy học viên nào" |

### Test 1.3: Filter trạng thái

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Filter "Active" | Chỉ hiện học viên đang hoạt động |
| 2 | Filter "Banned" | Chỉ hiện học viên bị khóa |

### Test 1.4: Xem chi tiết học viên

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Click vào tên/icon detail | Drawer/Modal hiện: avatar, tên, email, ngày đăng ký |
| 2 | Tab "Khóa học" | Danh sách khóa học đã enroll kèm progress |
| 3 | Network | `GET /api/v1/admin/students/{id}` → 200 |

---

## MODULE 2 — Thêm học viên (Admin tạo thủ công)

### Test 2.1: Validation

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Submit trống | Lỗi required cho tên, email, password |
| 2 | Email không đúng format | Lỗi format |
| 3 | Email đã tồn tại | Lỗi "Email đã được sử dụng" |
| 4 | Password < 8 ký tự | Lỗi độ dài |

### Test 2.2: Tạo thành công ✅

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Điền đủ thông tin → Submit | Toast "Thêm học viên thành công" |
| 2 | Network | `POST /api/v1/admin/students` → 201 |
| 3 | Học viên có thể login | Dùng email/password vừa tạo → login thành công |

---

## MODULE 3 — Sửa học viên

### Test 3.1: Sửa thông tin

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Sửa tên → Save | Toast thành công, bảng cập nhật |
| 2 | Network | `PUT /api/v1/admin/students/{id}` → 200 |

### Test 3.2: Đổi password

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Để trống password khi sửa | Password giữ nguyên không đổi |
| 2 | Nhập password mới → Save | Password cập nhật, học viên login bằng password mới |

---

## MODULE 4 — Khóa / Mở khóa tài khoản

### Test 4.1: Khóa học viên (Ban)

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Toggle → Banned | Badge "Banned", học viên không thể login |
| 2 | Học viên thử login | BE trả về 403 "Tài khoản đã bị khóa" |

### Test 4.2: Mở khóa

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Toggle lại → Active | Học viên login được bình thường |

---

## MODULE 5 — Xóa và Thùng rác

### Test 5.1: Soft delete

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Xóa học viên → confirm | Biến khỏi bảng chính |
| 2 | Network | `DELETE /api/v1/admin/students/{id}` → 200 |
| 3 | Học viên thử login | Không thể login (account bị xóa mềm) |

### Test 5.2: Restore

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Thùng rác → Restore | Học viên về bảng chính, login được lại |

### Test 5.3: Force delete

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Thùng rác → Xóa vĩnh viễn | Xóa hoàn toàn, dữ liệu enroll/progress cũng mất |

### Test 5.4: Bulk actions

| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Tick nhiều → Xóa hàng loạt | Tất cả vào thùng rác |
| 2 | Tick nhiều ở thùng rác → Restore hàng loạt | Tất cả về bảng chính |

---

## Checklist

| Test | Kết quả | Ghi chú |
|------|---------|---------|
| 1.1 Load trang | ⬜ | |
| 1.2 Tìm kiếm | ⬜ | |
| 1.3 Filter trạng thái | ⬜ | |
| 1.4 Xem chi tiết | ⬜ | |
| 2.1 Validation | ⬜ | |
| 2.2 Tạo thành công | ⬜ | |
| 3.1 Sửa thông tin | ⬜ | |
| 3.2 Đổi password | ⬜ | |
| 4.1 Khóa học viên | ⬜ | |
| 4.2 Mở khóa | ⬜ | |
| 5.1 Soft delete | ⬜ | |
| 5.2 Restore | ⬜ | |
| 5.3 Force delete | ⬜ | |
| 5.4 Bulk actions | ⬜ | |
