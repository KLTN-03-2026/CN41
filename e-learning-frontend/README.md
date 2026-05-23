# E-Learning Marketplace — Frontend

> **Đồ án tốt nghiệp** — Khoa Khoa học Máy tính, Đại học Duy Tân  
> Sinh viên: **Phan Văn Thành** | GVHD: Trịnh Sử Trường Thi | 2026

---

## Thông tin dự án

| | |
|---|---|
| **Tên đề tài** | Xây dựng hệ thống nền tảng học tập trực tuyến (E-Learning Marketplace) tích hợp thanh toán trực tuyến |
| **Sinh viên** | Phan Văn Thành — MSSV: 28211102974 |
| **GVHD** | Trịnh Sử Trường Thi |
| **Thời gian** | 12/03/2026 – 15/05/2026 |

---

## Công nghệ sử dụng

| Công nghệ | Mô tả |
|-----------|-------|
| **Vue.js 3 + Vite + TypeScript** | SPA framework chính, Composition API (`<script setup>`) |
| **Vue Router 4** | Client-side routing + navigation guards (admin/student/teacher) |
| **Pinia** | State management — auth state (adminAuth, studentAuth, cart) |
| **Tailwind CSS v3** | Styling (`darkMode: 'class'`) |
| **TailAdmin Vue** | Admin panel UI template (sidebar, header, dashboard, dark mode) |
| **Flowbite Vue** | Client-side UI components |
| **VeeValidate + Zod** | Schema-based form validation |
| **Axios** | HTTP client — kết nối Backend API tại `http://localhost:8000` |
| **Laravel Echo + pusher-js** | WebSocket client — thông báo real-time qua Laravel Reverb |
| **Video.js + @videojs-player/vue** | Video player cho bài giảng có bảo vệ nội dung |
| **@vueup/vue-quill** | Rich text editor cho bài viết (Giảng viên) |
| **vue3-apexcharts** | Biểu đồ thống kê Dashboard |
| **vue-toastification** | Toast notification |
| **NProgress** | Loading bar khi navigate route |
| **@vueuse/core** | Composable utilities (useDebounce, useLocalStorage…) |
| **lucide-vue-next** | Icon system |

---

## Cài đặt

```bash
# 1. Cài dependencies
npm install

# 2. Cấu hình môi trường
cp .env.example .env

# 3. Chạy dev server
npm run dev
```

Truy cập: `http://localhost:5173`

> **Yêu cầu:** Node.js >= 18.x | Backend API phải đang chạy tại `http://localhost:8000`

### Biến môi trường cần thiết (`.env`)

```env
VITE_API_URL=/api/v1
VITE_REVERB_APP_KEY=<your_reverb_app_key>
VITE_REVERB_HOST=localhost
VITE_REVERB_PORT=8080
VITE_REVERB_SCHEME=http
```

---

## Cấu trúc thư mục

```
src/
├── assets/             # Static assets (CSS, images, fonts)
│
├── components/
│   ├── admin/          # UI components — Admin panel
│   │   ├── categories/ # CategoryFilters, CategoryTable, CategoryTrashedTable
│   │   ├── courses/    # CourseFilters, CourseTable, CourseTableRow
│   │   ├── lessons/    # LessonList, LessonItem
│   │   └── sections/   # SectionItem
│   ├── common/         # Shared UI: ConfirmModal, BulkActions, ThemeToggler…
│   ├── forms/          # Form modals: CategoryForm, SectionFormModal, LessonFormModal…
│   ├── icons/          # SVG icon components
│   ├── layout/         # AppSidebar, AppHeader, NotificationBell, header/*
│   ├── shared/
│   │   ├── admin/      # SectionsLessonsManager, LessonPreviewModal, OrderDetailModal…
│   │   └── client/     # Components dùng chung phía student
│   └── table/          # BulkActions, Pagination
│
├── composables/        # Logic tái sử dụng — tất cả state + API calls
│   ├── useCategories.ts          # CRUD + bulk + form cho danh mục
│   ├── useCategoryTree.ts        # Cây danh mục: expand/collapse, search
│   ├── useCourses.ts             # CRUD + bulk + trashed cho khóa học (Admin)
│   ├── useSectionsManager.ts     # Quản lý chương (Admin)
│   ├── useLessonsManager.ts      # Quản lý bài giảng + bulk (Admin)
│   ├── useTeacherCourses.ts      # CRUD khóa học (Teacher portal)
│   ├── useTeacherSectionsManager.ts  # Quản lý chương (Teacher portal)
│   ├── useTeacherLessonsManager.ts   # Quản lý bài giảng (Teacher portal)
│   ├── useTeacherPosts.ts        # Quản lý bài viết (Teacher portal)
│   ├── useTeacherPostForm.ts     # Form tạo/sửa bài viết
│   ├── useTeacherProfile.ts      # Hồ sơ giảng viên + đổi mật khẩu/email
│   ├── useTeacherSecurity.ts     # Bảo mật tài khoản giảng viên
│   ├── useTeacherDashboard.ts    # Thống kê Dashboard giảng viên
│   ├── useTeacherEarnings.ts     # Thu nhập + yêu cầu rút tiền
│   ├── useNotifications.ts       # Thông báo real-time (Reverb WebSocket)
│   ├── useEarnings.ts            # Thu nhập (Admin view)
│   ├── usePayouts.ts             # Quản lý thanh toán
│   ├── usePosts.ts               # Bài viết (Admin)
│   ├── useBulkSelect.ts          # Generic multi-select cho bảng
│   ├── useDeleteConfirm.ts       # Confirm dialog pattern
│   ├── useFormErrors.ts          # Xử lý lỗi form từ API
│   ├── usePagination.ts          # Phân trang
│   ├── useDebounceSearch.ts      # Debounce search input
│   ├── useCheckout.ts            # Quy trình thanh toán
│   ├── useSidebar.ts             # Trạng thái sidebar
│   └── useTheme.ts               # Dark/light mode
│
├── constants/          # Hằng số toàn cục
│
├── layouts/            # AdminLayout.vue | TeacherLayout.vue | ClientLayout.vue
│
├── plugins/
│   ├── axios.ts        # Axios instance + interceptors (auth header, 401 redirect)
│   ├── echo.ts         # Laravel Echo client (Reverb WebSocket)
│   └── nprogress.ts    # Route loading bar
│
├── router/             # Routes + navigation guards
│   └── index.js        # Guards: requiresAuth, guard (admin/student/teacher)
│
├── services/           # API service layer — 1 file per resource
│   ├── auth.service.ts
│   ├── category.service.ts
│   ├── commission.service.ts
│   ├── coupon.service.ts
│   ├── course.service.ts
│   ├── dashboard.service.ts
│   ├── lesson.service.ts
│   ├── notification.service.ts   # Admin + Teacher notifications API
│   ├── order.service.ts
│   ├── post.service.ts
│   ├── profile.service.ts
│   ├── quiz.service.ts
│   ├── role.service.ts
│   ├── section.service.ts
│   ├── student.service.ts
│   ├── system.service.ts
│   ├── teacher-lesson.service.ts # Lesson CRUD (Teacher portal)
│   ├── teacher-section.service.ts # Section CRUD (Teacher portal)
│   ├── teacher.service.ts
│   ├── upload.service.ts
│   └── user.service.ts
│
├── stores/             # Pinia stores
│   ├── adminAuthStore.ts    # Admin/Teacher auth state + token
│   ├── studentAuthStore.ts  # Student auth state + token
│   └── cartStore.ts         # Shopping cart
│
├── types/              # TypeScript interfaces
│   ├── admin-category.types.ts
│   ├── auth.types.ts           # AuthAdminUser (bao gồm teacher_id)
│   ├── common.types.ts
│   ├── course.types.ts
│   ├── order.types.ts
│   ├── section-lesson.types.ts
│   └── index.ts        # Re-export tất cả types
│
├── utils/              # formatCurrency, formatDate, formatDuration
│
└── views/              # Pages (lazy-loaded)
    ├── admin/
    │   ├── DashboardPage.vue
    │   ├── CoursesPage.vue / CourseFormPage.vue
    │   ├── CategoriesPage.vue
    │   ├── StudentsPage.vue / TeachersPage.vue / UsersPage.vue / RolesPage.vue
    │   ├── OrdersPage.vue / PayoutsPage.vue
    │   ├── PostsPage.vue / PostFormPage.vue / PostCategoriesPage.vue
    │   ├── CouponsPage.vue / TagsPage.vue
    │   ├── CommissionSettingsPage.vue / TeacherEarningsPage.vue
    │   ├── CommentsPage.vue
    │   └── ActivityLogsPage.vue
    ├── teacher/                # Teacher portal (Giảng viên)
    │   ├── TeacherDashboardPage.vue  # Thống kê doanh thu
    │   ├── TeacherCoursesPage.vue    # Danh sách khóa học
    │   ├── TeacherCourseFormPage.vue # Tạo/sửa khóa học + sections + lessons
    │   ├── TeacherPostsPage.vue      # Quản lý bài viết
    │   ├── TeacherPostFormPage.vue   # Tạo/sửa bài viết (Quill editor)
    │   ├── TeacherProfilePage.vue    # Hồ sơ + bảo mật tài khoản
    │   └── EarningsPage.vue          # Thu nhập + yêu cầu rút tiền
    ├── client/
    │   ├── HomePage.vue / CoursesPage.vue / CourseDetailPage.vue
    │   ├── LearnPage.vue       # Video player + ghi chú bài học
    │   ├── QuizPage.vue / QuizHistoryPage.vue
    │   ├── CartPage.vue / CheckoutPage.vue / PaymentResultPage.vue
    │   ├── MyCoursesPage.vue / MyOrdersPage.vue / ProfilePage.vue
    │   ├── BlogPage.vue / PostDetailPage.vue
    │   └── TeachersPage.vue / TeacherProfilePage.vue
    └── auth/
        ├── AdminLoginPage.vue / LoginPage.vue / RegisterPage.vue
        ├── ForgotPasswordPage.vue / ResetPasswordPage.vue
        └── VerifyEmailPage.vue / VerifyEmailResultPage.vue
```

---

## Scripts

```bash
npm run dev       # Dev server (http://localhost:5173)
npm run build     # Build production
npm run lint      # Kiểm tra code style (oxlint + eslint)
npm run format    # Format code (Prettier)
npm run test      # Chạy unit tests (Vitest)
```

---

## Kiến trúc

- **Views** là thin orchestrators — chỉ wire props/events, không chứa logic
- **Composables** chứa toàn bộ state + API calls + side effects
- **Services** là plain axios calls — không xử lý error, không dispatch
- **Stores** chỉ quản lý auth state — feature state ở composables
- **Guards** phân tách 3 luồng: `admin`, `teacher`, `student`

---

## Tác giả

**Phan Văn Thành** — phvanthanh06@gmail.com  
Sinh viên năm 4, Khoa Khoa học Máy tính, Đại học Duy Tân

## License

Dự án phát hành theo giấy phép [MIT](LICENSE).

Được thực hiện với mục đích học thuật — Đồ án tốt nghiệp Đại học Duy Tân, 2026.
