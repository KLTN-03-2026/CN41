---
description: "FE 1.2 — Admin Layout (TailAdmin style) + Dashboard placeholder"
---

# FE 1.2 — Admin Layout

> Tạo AdminLayout theo phong cách TailAdmin Vue.
> Prerequisite: Phase F0 đã hoàn thành.

---

## Context

```
Stack: Vue 3, Tailwind, Vue Router, Pinia, Lucide icons (lucide-vue-next).
Nguồn tham khảo: github.com/TailAdmin/vue-tailwind-admin-dashboard
→ KHÔNG install package — copy layout structure, tự điều chỉnh.
```

---

## Bước 1 — Tạo AdminLayout.vue

Tạo `src/layouts/AdminLayout.vue`:

### Sidebar items (dùng icon từ lucide-vue-next):

| Label | Route | Icon |
|-------|-------|------|
| Dashboard | /admin/dashboard | LayoutDashboard |
| Khóa học | /admin/courses | BookOpen |
| Bài giảng | /admin/lessons | PlayCircle |
| Danh mục | /admin/categories | Tag |
| Giảng viên | /admin/teachers | UserCheck |
| Học viên | /admin/students | Users |
| Đơn hàng | /admin/orders | ShoppingBag |
| Mã giảm giá | /admin/coupons | Ticket |
| Người dùng | /admin/users | Shield |
| Tin tức | /admin/posts | Newspaper |

### Header:
- Nút hamburger (`Menu` icon) để toggle sidebar trên mobile
- Tên trang hiện tại
- Avatar + tên admin + nút đăng xuất (dùng `LogOut` icon)

### Đăng xuất:
```
adminAuthStore.logout() → redirect /admin/login + toast.success
```

### Design (giống TailAdmin):
- Sidebar: width 250px, `bg-slate-800`, `text-slate-300`, active: `bg-slate-700 text-white`
- Header: `bg-white border-b h-14`
- Content: `padding p-6, bg-gray-50`
- Responsive: sidebar ẩn trên mobile, toggle bằng hamburger

---

## Bước 2 — Tạo DashboardPage.vue (placeholder)

Tạo `src/pages/admin/DashboardPage.vue`:

- 4 StatCard: Tổng học viên, Tổng khóa học, Doanh thu tháng này, Đơn hàng mới
- Dữ liệu placeholder (sẽ gọi API sau khi làm BE 4.3)
- Dùng icons: `Users`, `BookOpen`, `DollarSign`, `ShoppingBag` từ lucide-vue-next

---

## ✅ Kiểm tra hoàn thành

- [ ] Sidebar hiện đúng menu items với icons
- [ ] Active route highlight đúng
- [ ] Responsive: sidebar ẩn trên mobile, hamburger toggle
- [ ] Đăng xuất hoạt động
- [ ] Dashboard placeholder hiện 4 StatCard
