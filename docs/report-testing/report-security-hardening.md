# Báo cáo Test — Bảo mật & Phân quyền nâng cao (Security Hardening)

> **Ngày test:** 2026-05-04
> **Tester:** Antigravity (AI Assistant)
> **Trạng thái:** Hoàn thành triển khai & Test

---

## 1. Mục tiêu Test
- [x] Xác thực cơ chế chặn API bằng Middleware `permission:xxx`.
- [x] Kiểm tra logic chống leo thang đặc quyền (Anti-Privilege Escalation).
- [x] Kiểm tra cơ chế lọc dữ liệu theo Role (Scoping) cho tài khoản non-super-admin.
- [x] Xác thực trải nghiệm người dùng (UX) khi bị chặn quyền.

---

## 2. Kết quả chi tiết

### 🛡️ Nhóm 1: API Middleware Hardening
Toàn bộ các action (view/create/edit/delete) của 6 modules chính đã được bảo vệ.

| Test | Mô tả | Kết quả | Ghi chú |
|------|-------|---------|---------|
| 1.1 | Truy cập API không có quyền | ✅ Pass | Server trả về 403 Forbidden |
| 1.2 | Hiển thị Toast cảnh báo | ✅ Pass | Axios interceptor bắt được lỗi 403, hiện Toast đỏ |
| 1.3 | Ẩn nút chức năng trên UI | ✅ Pass | Directive `v-permission` hoạt động đúng |

### 🔒 Nhóm 2: Anti-Privilege Escalation
Bảo vệ tài khoản `super-admin` khỏi các admin thường.

| Test | Mô tả | Kết quả | Ghi chú |
|------|-------|---------|---------|
| 2.1 | Admin thường cố xóa Super Admin | ✅ Pass | Backend chặn: "Bạn không có quyền thao tác trên tài khoản Super Admin." |
| 2.2 | Admin thường cố sửa Super Admin | ✅ Pass | Backend chặn 403 |
| 2.3 | Admin thường cố gán role "super-admin" | ✅ Pass | Cả Frontend (ẩn option) và Backend (chặn API) đều hoạt động |
| 2.4 | Admin thường tự xóa chính mình | ✅ Pass | Backend chặn: "Bạn không thể tự xoá tài khoản của chính mình." |

### 👥 Nhóm 3: Role Scoping & UI Reorganization
Người dùng chỉ thấy những gì họ được phép quản lý.

| Test | Mô tả | Kết quả | Ghi chú |
|------|-------|---------|---------|
| 3.1 | Lọc danh sách User (Scoping) | ✅ Pass | Admin thường chỉ thấy Student và Teacher. Accounts Admin/Super-Admin bị ẩn hoàn toàn. |
| 3.2 | Sidebar Reorganization | ✅ Pass | Group "Người dùng" mới (Quản trị viên, Giảng viên, Học viên) hoạt động mượt mà. |
| 3.3 | Role Dropdown Filter | ✅ Pass | Dropdown lọc role tự động ẩn các role hệ thống cho non-super-admin. |

---

## 3. Các lỗi phát hiện & đã sửa

| # | Lỗi | Nguyên nhân | Trạng thái |
|---|-----|-------------|-----------|
| 1 | Trang Học viên không hiển thị | Role chưa được cấp quyền `students.view` | ✅ Đã cấp quyền trong trang Roles |
| 2 | Lỗi syntax Vue (HTML Entities) | Arrow function trong computed bị encode sai | ✅ Đã fix syntax `isSuperAdmin` |
| 3 | API vẫn trả về admin accounts | Thiếu scoping trong Repository | ✅ Đã thêm `$allowedRoles` vào `paginateFiltered` |

---

## 4. Kết luận
Hệ thống hiện tại đã đạt độ bảo mật cao:
1. **Double-layer security**: Chặn cả ở UI (ẩn nút) và API (middleware).
2. **Data Isolation**: Admin cấp thấp không thể can thiệp hoặc thậm chí nhìn thấy thông tin của Admin cấp cao.
3. **Safe UI**: Người dùng không bị bối rối bởi các chức năng/dữ liệu không thuộc phạm vi quản lý của mình.
