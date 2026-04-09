---
description: Review code frontend — kiểm tra Component, Axios, Vue Router, Tailwind CSS chuẩn handbook.
---

# 🔍 Workflow: Review Code Frontend

> Sử dụng: `/review-task` hoặc "review UI", "review code"

## Các bước thực hiện

### Bước 1: Xác định scope
// turbo
- Biết task nào vừa xong, xem các tệp `.vue` / `.js` đã sửa/tạo.

### Bước 2: Đọc tham chiếu handbook.md
// turbo
- Đối chiếu **Section 8 (Quy ước components)**, **Section 9 (Tailwind CSS)**.

### Bước 3: Check-list review cho Vue 3
#### Component Design
- [ ] Logic có ôm đồm dồn quá nhiều vào 1 file `.vue` không? Có cần tách sub-component?
- [ ] Props và Emits định nghĩa cẩn thận chưa?
#### State & API
- [ ] Gọi Axios trực tiếp trong component hay đã gói vào `src/api/`?
- [ ] Đã handle Exception/Toast khi call API lỗi (`try-catch`, Toast) chưa?
- [ ] Trạng thái Loading (isSubmitting...) đã có để disable các Button chưa?
#### Markup & Styling
- [ ] Dùng thẻ Semantic HTML?
- [ ] Code styles Tailwind CSS có đúng chuẩn thiết kế không? Tránh inline styles.

### Bước 4: Đề xuất Fix (Critical / Warning / Info)
- Báo cáo cho USER nếu có phần nào vi phạm Best Practice.
