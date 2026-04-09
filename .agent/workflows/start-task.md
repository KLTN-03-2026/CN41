---
description: Bắt đầu làm một task mới trong dự án E-Learning Frontend. Đọc context từ handbook.md + QLCV-FE.md, lấy chỉ dẫn từ promt-fe-setup.md, và thực thi task.
---

# 🚀 Workflow: Bắt Đầu Task Mới

> Sử dụng: `/start-task` hoặc nói "bắt đầu task [tên task]"

## Các bước thực hiện

### Bước 0: Đọc handbook — Xương sống kiến trúc Frontend
// turbo
- Đọc file `Promt-set-up/handbook.md` để nắm vững:
  - **Kiến trúc tổng quan**: Vue 3 + Vite, SPA, Pinia State Management, Vue Router.
  - **Cấu trúc Thư mục chuẩn**: layouts, pages, components (common, admin, client), stores, api, utils.
  - **API Layer**: Cấu trúc Axios interceptors, cách gọi API chuẩn.
  - **Quy tắc UI/UX**: Tailwind CSS, Flowbite, TailAdmin components.
- **ĐÂY LÀ BẮT BUỘC** — Mọi code sinh ra PHẢI tuân thủ handbook.

### Bước 1: Đọc tiến độ hiện tại
// turbo
- Đọc file `Promt-set-up/QLCV-FE.md` để biết:
  - Những task nào đã ✅ Done
  - Task tiếp theo cần làm (⬜ Todo đầu tiên)
  - Phase hiện tại đang ở đâu

### Bước 2: Xác định task cần làm
- Nếu USER chỉ định task cụ thể → dùng task đó
- Nếu USER không chỉ định → lấy task ⬜ Todo ĐẦU TIÊN theo thứ tự trong QLCV-FE.md
- Hiển thị cho USER biết task sắp thực hiện và hỏi xác nhận

### Bước 3: Lấy prompt chi tiết từ promt-fe-setup.md
// turbo
- Đọc file `Promt-set-up/promt-fe-setup.md`
- Tìm section tương ứng với task cần làm
- Extract toàn bộ prompt chi tiết trong block để áp dụng xây dựng UI/Logic

### Bước 4: Đọc báo cáo task trước đó (nếu có)
// turbo
- Kiểm tra thư mục `Promt-set-up/` xem có file báo cáo Phase trước không (VD: `PhaseF1-Task1.md`)
- Áp dụng các lưu ý quan trọng vào task hiện tại

### Bước 5: Thiết lập context và thực thi
- Kết hợp tất cả thông tin đã thu thập:
  - **Kiến trúc & Quy tắc**: từ `handbook.md` (NGUỒN CHÍNH)
  - **Context dự án**: SPA Vue 3, LocalStorage Tokens (admin/student).
  - **Tiến độ hiện tại**: từ QLCV-FE.md
  - **Yêu cầu chi tiết**: từ promt-fe-setup.md
- **Kiểm tra chéo**: Đảm bảo các views, components xây dựng khớp với router và api call trong handbook.
- Bắt đầu thực thi task theo yêu cầu chi tiết.

### Bước 6: Kiểm tra kết quả
- Sau khi hoàn thành các file, chạy kiểm tra cơ bản:
  - Đảm bảo `npm run format` / script format không có lỗi syntax.
  - Hướng dẫn USER xem thử UI tại `http://localhost:5173`.
- Báo cáo kết quả cho USER.

## Lưu ý
- **handbook.md là nguồn sự thật duy nhất**
- Mỗi lần chỉ làm 1 task
- Sau khi xong task, dùng workflow `/finish-task` để cập nhật tiến độ
