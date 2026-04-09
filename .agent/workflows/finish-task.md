---
description: Hoàn thành task hiện tại — verify với handbook.md, tạo báo cáo, cập nhật tiến độ trong QLCV-FE.md, và gợi ý task tiếp theo.
---

# ✅ Workflow: Hoàn Thành Task

> Sử dụng: `/finish-task` hoặc nói "hoàn thành task [tên task]"

## Các bước thực hiện

### Bước 1: Xác nhận task vừa hoàn thành
- Hỏi USER hoặc dựa vào context chat hiện tại để biết task nào vừa xong
- Xác nhận danh sách file đã tạo / chỉnh sửa

### Bước 2: Tạo báo cáo hoàn thành (Summary Report)
- Tạo file `Promt-set-up/Phase{X}-Task{Y}.md` dựa theo cấu trúc:

```markdown
# 📝 PHASE {X} — TASK {Y}: Summary Report
> Ngày hoàn thành: {DD/MM/YYYY}

---

## Mục tiêu
{Mô tả ngắn gọn mục tiêu của task UI/Chức năng}

---

## Các file đã tạo / chỉnh sửa

### ✅ File tạo mới / chỉnh sửa
| File | Thay đổi / Chức năng |
|------|----------------------|
| `src/pages/...` | ... |

---

## Kiến trúc Frontend đã thiết lập
{Components dùng chung, Store state mới, format UI, v.v.}

---

## Review & Cải thiện đã thực hiện
| Vấn đề phát hiện | Cách fix |
|-------------------|----------|
| ... | ... |

---

## Lưu ý cho task tiếp theo
- {Lưu ý API bind, router props...}
```

### Bước 3: Cập nhật QLCV-FE.md
// turbo
- Đọc file `Promt-set-up/QLCV-FE.md`
- Cập nhật các thay đổi sau:
  - Đổi trạng thái task từ `⬜ Todo` thành `✅ Done`
  - Ghi ngày hoàn thành vào cột
  - Tăng số "Hoàn thành" của Phase và % tổng thể

### Bước 4: Verify với handbook.md
// turbo
- Đọc `Promt-set-up/handbook.md` và kiểm tra chéo code frontend vừa viết:
  1. **Cấu trúc file**: Component có đặt đúng thư mục common/admin/client không?
  2. **API Layer**: File Axios Call đã gom vào `src/api` chưa?
  3. **State**: Vuex/Pinia Store có gọi đúng pattern không?
  4. **UI Pattern**: Có dùng đúng Class Tailwind hoặc Flowbite components?
- Ghi kết quả verify vào báo cáo.

### Bước 5: Review nhanh
- Kiểm tra code UI Vue:
  1. Vue directives: `v-if`, `v-for` có `:key` không?
  2. Form Validation: Đã validate đầy đủ dữ liệu trước khi call API?
  3. UX/UI: Đã có trạng thái Loading/Error hiển thị cho user chưa?
- Ghi kết quả vào báo cáo.

### Bước 6: Gợi ý task tiếp theo
- Đọc lại QLCV-FE.md
- Tóm tắt task xong, tiến độ và task kế tiếp
