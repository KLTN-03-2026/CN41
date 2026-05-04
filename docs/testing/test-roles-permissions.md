# 🔐 Test Roles & Permissions (ACL)

> **Chuẩn bị:** Backend chạy tại `http://localhost:8000`, Frontend tại `http://localhost:5173`.
> Đảm bảo đã chạy seeder: `php artisan module:seed Users`.

---

## Tài khoản test

| Role | Email | Password |
|------|-------|----------|
| Super Admin | superadmin@elearning.com | password |
| Admin | admin@elearning.com | password |

---

## MODULE 1 — Quản lý Vai trò (Roles List)

### Test 1.1: Hiển thị danh sách
| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Mở `/admin/roles` | Hiển thị bảng danh sách Roles: super-admin, admin, teacher... |
| 2 | Cột "Số quyền hạn" | Hiển thị đúng số lượng quyền (super-admin: Toàn quyền) |
| 3 | Cột "Số người dùng" | Hiển thị đúng số lượng user đang giữ role đó |

---

## MODULE 2 — Thêm mới Vai trò (Create Role)

### Test 2.1: Validation Form
| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Nhấn "Thêm vai trò" | Mở Modal với form trống |
| 2 | Để trống tên và nhấn "Lưu" | Input báo đỏ "Vui lòng nhập tên vai trò" |
| 3 | Nhập tên đã tồn tại (VD: `admin`) | Alert lỗi: "Tên vai trò đã tồn tại" |

### Test 2.2: Chọn quyền (Permissions)
| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Kiểm tra phân nhóm | Các quyền phải được chia theo nhóm (Khóa học, Người dùng, Đơn hàng...) |
| 2 | Nhấn "Chọn tất cả" | Tất cả checkbox đều được tick |
| 3 | Nhấn "Bỏ chọn tất cả" | Tất cả checkbox đều bị bỏ tick |
| 4 | Chọn vài quyền lẻ và Lưu | Role mới được tạo với đúng các quyền đã chọn |

---

## MODULE 3 — Chỉnh sửa Vai trò (Edit Role)

### Test 3.1: Load dữ liệu cũ
| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Nhấn "Sửa" một role (VD: `teacher`) | Modal hiện lên với tên và các checkbox đã tick sẵn đúng với role đó |

### Test 3.2: Cập nhật quyền
| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Tick thêm/bớt vài quyền và Lưu | Thông báo thành công, danh sách cập nhật số lượng quyền mới |

---

## MODULE 4 — Bảo mật & Ràng buộc (Protection)

### Test 4.1: Bảo vệ Super Admin
| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Tìm role `super-admin` | Không thấy nút Sửa và Xóa (hoặc bị disable) |
| 2 | Cố tình gọi API xóa super-admin | Server trả về lỗi 403: "Không thể xóa vai trò mặc định" |

### Test 4.2: Chặn xóa Role đang sử dụng
| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Tạo Role "Test Role", gán cho 1 User | — |
| 2 | Nhấn Xóa "Test Role" | Toast lỗi: "Không thể xóa vai trò đang được gán cho người dùng" |

---

## MODULE 5 — Gán Role cho Người dùng (Integration)

### Test 5.1: Dropdown gán quyền
| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Vào `/admin/users`, Sửa một User | Dropdown "Vai trò" phải hiển thị đầy đủ các Role vừa tạo ở trên |
| 2 | Chọn Role mới và Lưu | User đó phải được cập nhật Badge Role mới trên bảng danh sách |

---

## Checklist Báo cáo

| STT | Tính năng | Trạng thái | Ghi chú |
|-----|-----------|------------|---------|
| 1 | Hiển thị danh sách Roles | ⬜ | |
| 2 | Thêm mới Role | ⬜ | |
| 3 | Phân nhóm Permissions UI | ⬜ | |
| 4 | Sửa Role & Sync quyền | ⬜ | |
| 5 | Xóa Role (không có user) | ⬜ | |
| 6 | Chặn xóa Role (có user) | ⬜ | |
| 7 | Bảo vệ Super Admin | ⬜ | |
| 8 | Gán Role cho User thành công | ⬜ | |
| 9 | Chặn non-super-admin gán role super-admin | ⬜ | NEW |
| 10 | Chặn non-super-admin sửa/xóa tài khoản super-admin | ⬜ | NEW |
| 11 | Lọc danh sách User theo Role (Scoping) | ⬜ | NEW |
| 12 | Chặn API trái phép (Toast 403) | ⬜ | NEW |

---

## MODULE 6 — Chống Leo thang Đặc quyền (Security Hardening)

### Test 6.1: Thao tác trên tài khoản Super Admin
| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Đăng nhập Admin thường (không có role super-admin) | — |
| 2 | Vào `/admin/users`, cố tìm tài khoản superadmin | **Kết quả:** Không thấy (đã bị lọc bởi API scoping) |
| 3 | Thử gọi API `GET /api/v1/admin/users/{super_admin_id}` | **403 Forbidden**: "Bạn không có quyền xem tài khoản này." |
| 4 | Thử gọi API `DELETE` hoặc `PATCH` tới superadmin | **403 Forbidden**: "Bạn không có quyền thao tác trên tài khoản Super Admin." |

### Test 6.2: Gán quyền trái phép
| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Admin thường sửa 1 User, cố chọn role "super-admin" | **Kết quả:** Role "super-admin" không xuất hiện trong dropdown (Frontend filter) |
| 2 | Cố tình gọi API `POST .../assign-role` với `role=super-admin` | **403 Forbidden**: "Bạn không có quyền gán role super-admin." |

---

## MODULE 7 — Phân vùng dữ liệu (Role Scoping)

### Test 7.1: Hiển thị theo quyền hạn
| # | Hành động | Kết quả mong đợi |
|---|-----------|------------------|
| 1 | Đăng nhập tài khoản có quyền `users.view` (không phải super-admin) | — |
| 2 | Vào trang Người dùng (`/admin/users`) | Chỉ thấy danh sách **Giảng viên** và **Học viên**. Không thấy Admin khác. |
| 3 | Kiểm tra Dropdown lọc Role | Chỉ hiện "Giảng viên" và "Học viên". |
| 4 | Kiểm tra Sidebar | Menu "Người dùng" hiện 3 mục: Quản trị viên, Giảng viên, Học viên (nhưng Quản trị viên sẽ trống nếu không phải super-admin). |

