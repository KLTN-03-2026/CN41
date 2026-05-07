# Kiến trúc Frontend

## 1. Tổng quan

Frontend là **Vue 3 Single Page Application** viết TypeScript, build bằng Vite. Giao tiếp với backend qua REST API (Axios). Tách biệt hoàn toàn thành hai giao diện: Admin Panel và Student Portal.

**Nguyên tắc thiết kế:**
- **Views là thin orchestrator** — không chứa logic, chỉ import composables và wire props/events
- **Logic sống trong composables** — state, API calls, side effects
- **Stores chỉ quản lý auth state** — feature state không đặt trong Pinia
- **Services là HTTP layer thuần túy** — không catch error, chỉ throw

---

## 2. Cấu trúc thư mục

```
e-learning-frontend/src/
│
├── layouts/
│   ├── AdminLayout.vue          ← Sidebar + header, bọc tất cả /admin/**
│   └── ClientLayout.vue         ← Header + footer, bọc tất cả / (root)
│
├── views/                       ← Pages, lazy-loaded qua router
│   ├── admin/                   ← 16 trang
│   │   ├── DashboardPage.vue
│   │   ├── CoursesPage.vue
│   │   ├── CourseFormPage.vue   ← Dùng chung cho create + edit
│   │   ├── CategoriesPage.vue
│   │   ├── UsersPage.vue
│   │   ├── RolesPage.vue
│   │   ├── TeachersPage.vue
│   │   ├── StudentsPage.vue
│   │   ├── OrdersPage.vue
│   │   ├── PostsPage.vue
│   │   ├── PostFormPage.vue
│   │   ├── PostCategoriesPage.vue
│   │   ├── TagsPage.vue
│   │   ├── CommentsPage.vue
│   │   ├── CouponsPage.vue
│   │   └── ActivityLogsPage.vue
│   │
│   ├── client/                  ← 16 trang
│   │   ├── HomePage.vue
│   │   ├── CoursesPage.vue
│   │   ├── CourseDetailPage.vue
│   │   ├── LearnPage.vue        ← Fullscreen, ngoài ClientLayout
│   │   ├── MyCoursesPage.vue
│   │   ├── CartPage.vue
│   │   ├── CheckoutPage.vue
│   │   ├── PaymentResultPage.vue
│   │   ├── BlogPage.vue
│   │   ├── PostDetailPage.vue
│   │   ├── TeachersPage.vue
│   │   ├── TeacherProfilePage.vue
│   │   ├── ProfilePage.vue
│   │   ├── MyOrdersPage.vue
│   │   ├── QuizPage.vue         ← Fullscreen, ngoài ClientLayout
│   │   └── QuizHistoryPage.vue
│   │
│   └── auth/                    ← 7 trang
│       ├── AdminLoginPage.vue
│       ├── LoginPage.vue
│       ├── RegisterPage.vue
│       ├── VerifyEmailPage.vue
│       ├── VerifyEmailResultPage.vue
│       ├── ForgotPasswordPage.vue
│       └── ResetPasswordPage.vue
│
├── components/                  ← ~102 components
│   ├── admin/                   ← Per-resource: tables, filters, rows
│   │   ├── categories/          ← CategoryFilters, CategoryTable, ...
│   │   ├── courses/             ← CourseFilters, CourseTable, CourseTableRow
│   │   ├── lessons/             ← LessonList, LessonItem
│   │   └── sections/            ← SectionItem
│   ├── client/                  ← Course cards, hero, featured
│   ├── shared/
│   │   ├── admin/               ← SectionsLessonsManager, OrderDetailModal
│   │   └── client/              ← LearnSidebar, LearnVideoPlayer, LearnQuizPanel
│   ├── common/                  ← ConfirmModal, Pagination, BulkActions
│   ├── forms/                   ← CategoryForm, SectionFormModal, LessonFormModal
│   ├── icons/                   ← 40+ SVG icon components
│   ├── layout/                  ← AppSidebar, AppHeader
│   └── table/                   ← BulkActions, generic table utilities
│
├── composables/                 ← 18 composables (logic layer)
│   ├── useAsyncData.ts
│   ├── useBulkSelect.ts
│   ├── useCategories.ts
│   ├── useCategoryTree.ts
│   ├── useCourses.ts
│   ├── useDebounceSearch.ts
│   ├── useDeleteConfirm.ts
│   ├── useFormErrors.ts
│   ├── useHomePage.ts
│   ├── useLessonsManager.ts
│   ├── usePagination.ts
│   ├── usePostCategories.ts
│   ├── usePosts.ts
│   ├── useProfile.ts
│   ├── useSectionsManager.ts
│   ├── useSidebar.ts
│   ├── useTheme.ts
│   └── useTags.ts
│
├── stores/                      ← Pinia (auth state only)
│   ├── adminAuth.store.ts
│   ├── studentAuth.store.ts
│   └── cart.store.ts
│
├── services/                    ← HTTP layer (17 services)
│   ├── auth.service.ts
│   ├── category.service.ts
│   ├── coupon.service.ts
│   ├── course.service.ts
│   ├── dashboard.service.ts
│   ├── lesson.service.ts
│   ├── order.service.ts
│   ├── post.service.ts
│   ├── profile.service.ts
│   ├── quiz.service.ts
│   ├── role.service.ts
│   ├── section.service.ts
│   ├── student.service.ts
│   ├── system.service.ts
│   ├── teacher.service.ts
│   ├── upload.service.ts
│   └── user.service.ts
│
├── router/
│   └── index.js                 ← Route definitions + navigation guards
│
├── types/                       ← TypeScript type definitions
│   ├── admin-category.types.ts
│   ├── auth.types.ts
│   ├── common.types.ts
│   ├── course.types.ts
│   ├── order.types.ts
│   ├── post.types.ts
│   └── section-lesson.types.ts
│
├── directives/
│   └── permission.ts            ← v-permission directive
│
├── plugins/                     ← Vue plugins setup
├── constants/                   ← App constants
└── assets/                      ← CSS, images
```

---

## 3. Luồng dữ liệu

```
View (CoursesPage.vue)
  │
  │  const { courses, loading, filters, loadPage } = useCourses()
  │
  ▼
Composable (useCourses.ts)
  │  const courses = ref<Course[]>([])
  │  const loading = ref(false)
  │
  │  async function loadPage() {
  │    loading.value = true
  │    try {
  │      const res = await courseService.index(filters)
  │      courses.value = res.data.data
  │    } catch (err) {
  │      toast.error(err.response?.data?.message)
  │    } finally {
  │      loading.value = false
  │    }
  │  }
  │
  ▼
Service (course.service.ts)
  │  export const courseService = {
  │    index: (params) => http.get('/admin/courses', { params }),
  │    store: (data)   => http.post('/admin/courses', data),
  │    update: (id, d) => http.patch(`/admin/courses/${id}`, d),
  │  }
  │
  ▼
Axios instance (http)
  │  Interceptor: thêm Authorization: Bearer <token>
  │  Interceptor: 401 → clear token, redirect login
  │
  ▼
Backend API /api/v1/...
```

---

## 4. Composables

### Naming conventions

| Pattern | Ý nghĩa | Ví dụ |
|---------|---------|-------|
| `useXxx` | Composable tổng quát | `usePagination`, `useTheme` |
| `useXxxs` / `useXxx` | Full CRUD cho resource | `useCourses`, `useCategories` |
| `useXxxManager` | Quản lý resource trong một page | `useSectionsManager` |

### Anatomy của một CRUD composable

```ts
// src/composables/useCourses.ts
export function useCourses() {
  // State
  const courses     = ref<Course[]>([])
  const loading     = ref(false)
  const pagination  = ref<Pagination | null>(null)
  const filters     = reactive({ search: '', status: '', page: 1, per_page: 15 })

  // Load data
  async function loadPage() {
    loading.value = true
    try {
      const res = await courseService.index(filters)
      courses.value   = res.data.data
      pagination.value = res.data.pagination
    } finally {
      loading.value = false
    }
  }

  // Mutations
  async function deleteCourse(id: number) {
    await courseService.destroy(id)
    await loadPage()
    toast.success('Đã xóa khóa học.')
  }

  // Watchers
  watch(() => filters.search, useDebouncedSearch(() => loadPage()))

  onMounted(loadPage)

  return { courses, loading, pagination, filters, loadPage, deleteCourse }
}
```

### Composables đặc biệt

| Composable | Chức năng |
|-----------|---------|
| `useAsyncData` | Wrapper cho async operations với loading/error state |
| `useBulkSelect` | Checkbox select all + individual, trả `selectedIds` |
| `usePagination` | Page number, per_page, go-to-page |
| `useDebounceSearch` | Debounce search input (300ms) |
| `useDeleteConfirm` | Modal xác nhận trước khi xóa |
| `useFormErrors` | Map backend validation errors → field-level |
| `useCategoryTree` | Build cây phân cấp từ flat array (NestedSet) |
| `useSectionsManager` | Quản lý sections + lessons trong CourseFormPage |

---

## 5. Pinia Stores

Stores chỉ quản lý **auth state** — không chứa feature state.

### adminAuth.store.ts

```ts
state: {
  token: string | null    // từ localStorage 'adminToken'
  user: AdminUser | null  // { id, name, email, roles, permissions }
  loading: boolean
}

actions:
  login(email, password)  → POST /admin/auth/login → lưu token
  logout()                → POST /admin/auth/logout → xóa token
  fetchMe()               → GET /admin/auth/me
```

### studentAuth.store.ts

```ts
state: {
  token: string | null    // từ localStorage 'studentToken'
  student: Student | null // { id, name, email, avatar, email_verified_at }
  loading: boolean
}

actions:
  login(email, password)
  register(name, email, password, ...)
  logout()
  fetchMe()
```

### cart.store.ts

```ts
state: {
  items: CartItem[]   // { course_id, name, price, thumbnail }
}

actions:
  addItem(course)
  removeItem(courseId)
  clearCart()
  isInCart(courseId): boolean
```

**Pattern trả về:** Tất cả store actions return `{ success: boolean, message?, errors? }` — không throw lên component.

---

## 6. Service Layer

Mỗi service là plain object export với typed methods:

```ts
// src/services/course.service.ts
export const courseService = {
  // Admin
  index:        (params) => http.get('/admin/courses', { params }),
  store:        (data)   => http.post('/admin/courses', data),
  show:         (id)     => http.get(`/admin/courses/${id}`),
  update:       (id, d)  => http.patch(`/admin/courses/${id}`, d),
  destroy:      (id)     => http.delete(`/admin/courses/${id}`),
  toggleStatus: (id)     => http.patch(`/admin/courses/${id}/toggle-status`),
  trashed:      (params) => http.get('/admin/courses/trashed', { params }),
  restore:      (id)     => http.patch(`/admin/courses/${id}/restore`),
  bulkDelete:   (ids)    => http.delete('/admin/courses/bulk-delete', { data: { ids } }),

  // Public
  publicIndex:  (params) => http.get('/courses', { params }),
  publicShow:   (slug)   => http.get(`/courses/${slug}`),
  myCourses:    ()       => http.get('/my-courses'),
}
```

**Quy tắc:** Service không catch error — throw để composable/store xử lý.

---

## 7. Router và Navigation Guards

### Route meta conventions

```js
{ requiresAuth: true,  guard: 'admin'   }  // /admin/** cần login
{ requiresAuth: true,  guard: 'student' }  // student routes cần login
{ requiresGuest: true, guard: 'admin'   }  // /admin/login (chỉ guest)
{ requiresGuest: true, guard: 'student' }  // /login, /register (chỉ guest)
// Không có meta → public (/, /courses, /posts...)
```

### Navigation guard flow

```
router.beforeEach(to):
  │
  ├── [có studentToken]
  │     ├── Fetch student nếu chưa có (studentStore.fetchMe)
  │     └── [email_verified_at == null]
  │           └── Redirect /verify-email
  │               (ngoại trừ: /verify-email*, /login, /register)
  │
  ├── [có adminToken + đến trang admin]
  │     └── Fetch admin nếu chưa có
  │
  ├── [requiresAuth]
  │     ├── guard admin + !adminToken → /admin/login
  │     └── guard student + !studentToken → /login?redirect={to.fullPath}
  │
  └── [requiresGuest]
        ├── guard admin + adminToken → /admin/dashboard
        └── guard student + studentToken → /
```

Tất cả page imports đều lazy:
```js
component: () => import('@/views/admin/CoursesPage.vue')
```

NProgress (loading bar) start ở `beforeEach`, done ở `afterEach`.

---

## 8. Axios Instance

```ts
// src/plugins/http.ts (hoặc tương đương)
const http = axios.create({ baseURL: import.meta.env.VITE_API_URL })

// Request interceptor — thêm token
http.interceptors.request.use(config => {
  const token = localStorage.getItem('adminToken')
    ?? localStorage.getItem('studentToken')
  if (token) config.headers.Authorization = `Bearer ${token}`
  return config
})

// Response interceptor — xử lý 401
http.interceptors.response.use(
  res => res,
  err => {
    if (err.response?.status === 401) {
      localStorage.removeItem('adminToken')
      localStorage.removeItem('studentToken')
      window.location.href = '/login'
    }
    return Promise.reject(err)
  }
)
```

---

## 9. TypeScript Conventions

### Component script

```vue
<script setup lang="ts">
// Import order: Vue core → composables/stores → services → components
import { ref, reactive, onMounted, computed } from 'vue'
import { useCourses } from '@/composables/useCourses'
import { useAdminAuthStore } from '@/stores/adminAuth.store'
import CourseTable from '@/components/admin/courses/CourseTable.vue'

const { courses, loading, filters, loadPage } = useCourses()
onMounted(loadPage)
</script>
```

### defineEmits — dùng tuple syntax

```ts
// ✅ Correct
defineEmits<{
  'update:modelValue': [value: string]
  'submit': []
  'switch-tab': [trashed: boolean]
}>()
```

### v-permission directive

```html
<!-- Ẩn nút nếu user không có permission 'courses.delete' -->
<button v-permission="'courses.delete'">Xóa</button>
```

---

## 10. Build và Tooling

| Tool | Mục đích |
|------|---------|
| Vite 7 | Build tool, HMR |
| TypeScript 6 | Type checking |
| Tailwind CSS 3 + Flowbite | Styling |
| oxlint + ESLint | Linting (oxlint nhanh hơn, chạy trước) |
| Prettier | Code formatting |
| Vitest | Unit testing (quizResult.test.ts) |

```bash
npm run dev       # dev server localhost:5173
npm run build     # production build
npm run lint      # oxlint → eslint → prettier
npm run test      # vitest run
```
