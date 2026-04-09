---
description: "Review component trước khi sang task mới"
---

# Review Component

> Dùng sau khi xong một component/task, trước khi chuyển sang task mới.

---

## Checklist review

### 1. UX/UI
- [ ] Layout hợp lý, responsive
- [ ] Màu sắc theo design system (primary, gray, red, green, amber)
- [ ] Icons nhất quán (lucide-vue-next, đúng size)
- [ ] Spacing/padding hợp lý

### 2. Form Validation
- [ ] VeeValidate + Zod schema đầy đủ
- [ ] Error message hiện đúng field
- [ ] class `input-error` khi có lỗi
- [ ] class `error-msg` cho message

### 3. Loading / Error / Empty State
- [ ] Loading skeleton hoặc spinner khi fetch
- [ ] Error state khi API lỗi
- [ ] Empty state khi không có data
- [ ] Button loading khi submit

### 4. Code Quality
- [ ] Không gọi Axios trực tiếp — dùng `src/api/*.js`
- [ ] Không format thủ công — dùng `formatCurrency`, `formatDate`
- [ ] Dùng `try/catch/finally` cho API calls
- [ ] Component có thể tách nhỏ thêm không?

### 5. Convention
- [ ] File name đúng convention: `{Name}Page.vue`, `App{Name}.vue`
- [ ] Dùng `btn-primary`, `input-field`, `card` từ `@layer components`
- [ ] Icons import tree-shakable từ `lucide-vue-next`
