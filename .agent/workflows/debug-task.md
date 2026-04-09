---
description: Debug lỗi trong task hiện tại của Vue Frontend — đọc context từ handbook, console log, analyze Vue component lỗi.
---

# 🐛 Workflow: Debug Lỗi FE

> Sử dụng: `/debug-task` hoặc "gặp lỗi task", "lỗi giao diện"

## Các bước thực hiện

### Bước 1: Thu thập thông tin lỗi
- Hỏi USER cung cấp log trên Console DevTools hoặc Terminal Vite.
- Nếu lỗi liên quan giao diện Tailwind: yêu cầu mô tả UI vỡ ở màn hình kích thước nào.
- Nếu lỗi API (`AxiosError`): xác định HTTP Status.

### Bước 2: Đọc context dự án
// turbo
- Đọc `Promt-set-up/handbook.md`:
  - Lỗi Axios Interceptors? Xem **Section 4**.
  - Lỗi Vue Router/Nav Guards? Xem **Section 5**.
  - Lỗi Pinia Auth token? Xem **Section 6**.
- Đọc prompt task gần nhất ở `promt-fe-setup.md`.

### Bước 3: Phân tích lỗi Vue
- Kiểm tra lại Component import có đúng đường dẫn không (ví dụ `@/components/...`).
- Kiểm tra Object Reactivity (`ref`, `reactive` dùng có đúng hay bị gán mất tracking `.value`).
- Đề xuất file cần thay đổi.

### Bước 4: Thực hiện fix
- Fix lỗi trong SFC (`.vue`), js/ts files.

### Bước 5: Ghi nhận sửa lỗi
- Nhớ ghi nhận vào "Lưu ý cho task kế" nếu là lỗi hệ thống phổ biến để tránh lặp lại.
