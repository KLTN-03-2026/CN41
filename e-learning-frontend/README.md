# 🎨 E-Learning Marketplace — Frontend

> **Đồ án tốt nghiệp** — Khoa Khoa học Máy tính, Đại học Duy Tân  
> Sinh viên: **Phan Văn Thành** | GVHD: Trịnh Sử Trường Thi | 2026

---

## 📋 Thông tin dự án

| | |
|---|---|
| **Tên đề tài** | Xây dựng hệ thống nền tảng học tập trực tuyến (E-Learning Marketplace) tích hợp thanh toán trực tuyến |
| **Sinh viên** | Phan Văn Thành — MSSV: 28211102974 |
| **GVHD** | Trịnh Sử Trường Thi |
| **Thời gian** | 12/03/2026 – 15/05/2026 |

---

## 🛠️ Công nghệ sử dụng

- **Vue.js 3** + **Vite** + **TypeScript** (composables)
- **Vue Router 4** + **Pinia**
- **Tailwind CSS v3** (`darkMode: 'class'`) + **Flowbite Vue** (Client UI)
- **TailAdmin Vue** (Admin UI — đã tích hợp template: sidebar, header, dashboard, dark mode, icons)
- **Axios** — kết nối Backend API Laravel 12 tại `http://localhost:8000`
- **lucide-vue-next** + **TailAdmin SVG icons** — icon system
- **VeeValidate + Zod** — form validation
- **Video.js** — video player
- **vue3-apexcharts** — biểu đồ Dashboard
- **vue-toastification** — toast notification
- **NProgress** — loading bar khi navigate route
- **@vueuse/core** — composables (useDebounce, useLocalStorage...)

---

## ⚙️ Cài đặt

```bash
# 1. Clone repository
git clone https://github.com/<your-username>/elearning-frontend.git
cd elearning-frontend

# 2. Cài dependencies
npm install

# 3. Cấu hình môi trường
cp .env.example .env

# 4. Chạy dev server
npm run dev
```

Truy cập: `http://localhost:5173`

> **Yêu cầu:** Node.js >= 18.x | Backend API phải đang chạy tại `http://localhost:8000`

---

## 📁 Cấu trúc thư mục

```
src/
├── assets/             # Static assets (images, fonts)
├── components/
│   ├── admin/          # UI components dùng riêng cho admin panel
│   │   ├── categories/ # CategoryFilters, CategoryTable, CategoryTrashedTable
│   │   ├── courses/    # CourseFilters, CourseTable, CourseTableRow
│   │   ├── lessons/    # LessonList, LessonItem
│   │   └── sections/   # SectionItem
│   ├── common/         # Shared UI: ConfirmModal, BulkActions, ThemeToggler...
│   ├── forms/          # Form modals: CategoryForm, CourseForm, SectionFormModal, LessonFormModal...
│   ├── icons/          # SVG icon components
│   ├── layout/         # AppSidebar, AppHeader, ThemeProvider, SidebarProvider, header/*
│   ├── shared/
│   │   ├── admin/      # SectionsLessonsManager, LessonPreviewModal, OrderDetailModal...
│   │   └── client/     # Components dùng chung phía client
│   └── table/          # BulkActions, Pagination
├── composables/        # Logic tái sử dụng (Composition API)
│   ├── useCategories.ts       # CRUD + bulk + form + delete cho danh mục
│   ├── useCategoryTree.ts     # Cây danh mục: expand/collapse, search, filter
│   ├── useCourses.ts          # CRUD + bulk + trashed cho khóa học
│   ├── useSectionsManager.ts  # Quản lý chương (section) theo khóa học
│   ├── useLessonsManager.ts   # Quản lý bài giảng (lesson) + drag-drop + bulk
│   ├── useBulkSelect.ts       # Generic multi-select cho bảng
│   ├── useDeleteConfirm.ts    # Confirm dialog pattern
│   ├── useFormErrors.ts       # Xử lý lỗi form từ API
│   ├── usePagination.ts       # Phân trang
│   ├── useDebounceSearch.ts   # Debounce search input
│   ├── useSidebar.ts          # Trạng thái sidebar
│   └── useTheme.ts            # Dark/light mode
├── constants/          # Hằng số toàn cục
├── layouts/            # AdminLayout.vue | ClientLayout.vue
├── plugins/            # axios (instance + interceptors) | nprogress
├── router/             # Routes + navigation guards
├── services/           # API service layer (1 file/resource)
│   ├── category.service.ts
│   ├── course.service.ts
│   └── ...
├── stores/             # Pinia stores: adminAuthStore, studentAuthStore, cart
├── types/              # TypeScript interfaces
│   ├── admin-category.types.ts  # AdminCategory, AdminCourse
│   ├── section-lesson.types.ts  # AdminSection, AdminLesson, SectionForm, LessonForm
│   ├── course.types.ts
│   ├── auth.types.ts
│   ├── common.types.ts
│   ├── order.types.ts
│   └── index.ts        # Re-export tất cả types
├── utils/              # formatCurrency, formatDate, formatDuration
└── views/              # Pages (lazy-loaded)
    ├── admin/          # CoursesPage, CategoriesPage, UsersPage...
    ├── auth/           # LoginPage, RegisterPage...
    └── client/         # HomePage, CourseDetailPage, MyCoursesPage...
```

---

## 📦 Scripts

```bash
npm run dev      # Dev server
npm run build    # Build production
npm run lint     # Kiểm tra code
```

---

## 👤 Tác giả

**Phan Văn Thành** — 📧 phvanthanh06@gmail.com  
Sinh viên năm 4, Khoa Khoa học Máy tính, Đại học Duy Tân

## License

Dự án phát hành theo giấy phép [MIT](LICENSE).

Được thực hiện với mục đích học thuật — Đồ án tốt nghiệp Đại học Duy Tân, 2026.