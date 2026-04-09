---
description: "FE 1.4 — Client Layout (Flowbite + Lucide) + HomePage landing page"
---

# FE 1.4 — Client Layout + HomePage

> Tạo ClientLayout theo phong cách Udemy + HomePage landing page sơ bộ.
> Prerequisite: Student Register/Login đã hoạt động.

---

## Context

```
Stack: Vue 3, Tailwind, Vue Router, Pinia, lucide-vue-next, flowbite-vue.
```

---

## Bước 1 — Tạo ClientLayout.vue

Tạo `src/layouts/ClientLayout.vue`:

### Header (sticky top, bg-white, shadow-sm):
- Logo "E-Learning" + `BookOpen` icon (lucide)
- Nav links: Khóa học (`/courses`), Tin tức (`/posts`)
- Cart icon với badge số lượng (`ShoppingCart` từ lucide + `cartStore.count`)
- **Guest:** nút Đăng nhập + Đăng ký
- **Logged in:** avatar initials + dropdown (CSS `group-hover`)
  - Dropdown items: Khóa học của tôi, Tài khoản, Lịch sử đơn hàng, Đăng xuất

### Footer (bg-gray-800, text-gray-400):
- Tên project + năm
- Đơn giản, không cần nhiều cột

### Icons dùng cho navbar:
```js
import { BookOpen, ShoppingCart, ChevronDown, Menu, X, LogOut, User } from 'lucide-vue-next'
```

---

## Bước 2 — Tạo HomePage.vue

Tạo `src/pages/client/HomePage.vue`:

- **Hero section:** tiêu đề lớn + subtitle + nút CTA → `/courses`
- **Section "Danh mục nổi bật":** grid icon + tên (data tĩnh trước, gọi API sau)
- **Section "Khóa học nổi bật":** placeholder 3 CourseCard (sẽ gọi API ở Phase 2)
- Dùng `FwbCarousel` từ flowbite-vue nếu muốn carousel

---

## ✅ Kiểm tra hoàn thành

- [ ] Navbar hiển thị đúng theo trạng thái login/guest
- [ ] Cart badge hiện số lượng
- [ ] Dropdown user menu hoạt động
- [ ] Đăng xuất hoạt động
- [ ] Responsive: hamburger menu trên mobile
- [ ] HomePage hiện hero + sections
- [ ] Footer hiện đúng
