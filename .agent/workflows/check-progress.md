---
description: Kiểm tra tiến độ tổng thể Frontend — so sánh với QLCV-FE.md và đưa ra nhận xét.
---

# 📊 Workflow: Kiểm Tra Tiến Độ

> Sử dụng: `/check-progress` hoặc nói "kiểm tra tiến độ"

## Các bước thực hiện

### Bước 1: Đọc QLCV-FE.md + handbook.md
// turbo
- Đọc file `Promt-set-up/QLCV-FE.md`
- Thu thập dữ liệu trạng thái task của tất cả các Phases.
- Đọc `Promt-set-up/handbook.md` để đối chiếu xem kiến trúc UI/Logic các trang có thiếu trang nào chưa mô tả trong hệ thống không.

### Bước 2: Tính toán tiến độ
- Đếm số task ✅ Done / tổng task mỗi Phase
- Tính % hoàn thành từng Phase và tổng thể dự án FE

### Bước 3: Phân tích và nhận xét
- Đánh giá tổng quan (Ví dụ: UX admin đã xong, UX client đang làm đến giỏ hàng...)

### Bước 4: Đưa ra đề xuất
- Gợi ý task FE ưu tiên cao nhất nên làm tiếp. Báo cáo định dạng thống kê ra markdown.

## Output mẫu

```
📊 TIẾN ĐỘ DỰ ÁN E-LEARNING FRONTEND
Ngày: {DD/MM/YYYY}

Phase F0: ████████▒ 100% (4/4 tasks)
Phase F1: ░░░░░░░░░ 0% (0/4 tasks)
...

Tổng: ...%
📌 Task tiếp theo: Phase F1 — Task 1: UI Authentication
```
