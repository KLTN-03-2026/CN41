---
description: "FE 2.1–2.3 — Admin CRUD: Categories, Teachers, Courses (DataTable + Modal)"
---

# FE 2.1–2.3 — Admin CRUD Pages

> **Phase F2 (phần Admin)** — Tạo các trang quản lý Categories, Teachers, Courses.
> Prerequisite: Admin Layout đã hoạt động + Backend API tương ứng đã có.

---

## Context chung

```
Stack: Vue 3, Tailwind, Pinia (adminAuth), Lucide icons, Vue Toastification.
Pattern: DataTable + CRUD Modal + AppPagination.
API: gọi qua src/api/*.js → Axios instance tự gắn adminToken.
```

---

## Task F2.1 — Admin CategoriesPage.vue

Backend: GET/POST/PUT/DELETE `/api/v1/admin/categories`

### Tạo `src/api/categoryApi.js`:
```js
adminGetList(params), adminCreate(data), adminUpdate(id, data), adminDelete(id)
```

### Tạo `src/pages/admin/CategoriesPage.vue`:
- **Toolbar:** h2 "Danh mục" + nút "+ Thêm mới" (btn-primary)
- **DataTable:** Tên | Slug | Parent | Thao tác (Sửa/Xóa)
- **Search input** + **AppPagination**
- **AppModal CRUD:** form tên + chọn parent category (nested set)
- **Xóa:** confirm trước → toast.success hoặc toast.error
- Loading state + Empty state

---

## Task F2.2 — Admin TeachersPage.vue

Backend: GET/POST/PUT/DELETE `/api/v1/admin/teachers`

### Tạo `src/api/teacherApi.js`:
```js
adminGetList(params), adminCreate(data), adminUpdate(id, data), adminDelete(id)
```

### Tạo `src/pages/admin/TeachersPage.vue`:
- **DataTable:** Avatar | Tên | Email | Bio | Thao tác
- **CRUD Modal:** form tên, email, bio, upload avatar
- **Search + Pagination**

---

## Task F2.3 — Admin CoursesPage.vue + CourseFormPage.vue

Backend: GET/POST/PUT/DELETE `/api/v1/admin/courses`

### Tạo `src/api/courseApi.js`:
```js
adminGetList(params), adminCreate(data), adminUpdate(id, data), adminDelete(id), adminToggleStatus(id)
// Public:
getList(params), getBySlug(slug), getLessons(slug)
```

### Tạo `src/pages/admin/CoursesPage.vue`:
- **DataTable:** Thumbnail | Tên | Giảng viên | Giá | Trạng thái (badge) | Thao tác
- **Toggle status:** nút bật/tắt active
- **Search + Filter category + Pagination**

### Tạo `src/pages/admin/CourseFormPage.vue`:
- Route: `/admin/courses/create` và `/admin/courses/:id/edit`
- Form: tên, slug (auto-generate), mô tả, chọn teacher, chọn categories, price, sale_price
- Upload thumbnail (Cloudinary)
- VeeValidate + Zod validation
- Submit → toast.success → redirect `/admin/courses`

---

## ✅ Kiểm tra hoàn thành

- [ ] Các trang CRUD hiện DataTable đúng
- [ ] Thêm/Sửa/Xóa hoạt động + toast notification
- [ ] Search + Pagination
- [ ] Validate form đúng
- [ ] Upload ảnh (nếu applicable)
- [ ] Loading state + Empty state

Sau khi xong, tick F2.1 → F2.3 trong `QLCV/QLCV-FE.md`.
