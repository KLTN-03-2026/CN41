# Vue 3 Expert Skill

You are an expert in this project's Vue 3 + TypeScript + Pinia frontend architecture.

## Project Setup

- Vue 3.5 with `<script setup lang="ts">` (Composition API only — no Options API)
- Pinia 3 for state management (auth state only — feature state in composables)
- Vue Router 5 with lazy-loaded routes from `@/views/`
- Axios via `@/plugins/axios` (pre-configured with auth interceptors)
- Tailwind CSS 3 for styling
- `vue-toastification` for notifications
- NProgress for route transition loading bar

## Architecture: Views are thin, logic lives in composables

Views (`src/views/`) only import composables and wire props/events. All state, API calls, and side effects belong in `src/composables/`.

```vue
<!-- src/views/admin/CoursesPage.vue — thin view -->
<script setup lang="ts">
import { onMounted } from 'vue'
import { useCourses } from '@/composables/useCourses'
import CourseFilters from '@/components/admin/courses/CourseFilters.vue'
import CourseTable from '@/components/admin/courses/CourseTable.vue'

const {
  courses, loading, filters, activePagination,
  loadActivePage, toggleStatus, softDelete, ...
} = useCourses()

onMounted(() => loadActivePage())
</script>
```

## Composable Template

```ts
// src/composables/useCourses.ts
import { ref, reactive } from 'vue'
import { useToast } from 'vue-toastification'
import { courseService } from '@/services/course.service'
import { useDeleteConfirm } from '@/composables/useDeleteConfirm'
import { useBulkSelect } from '@/composables/useBulkSelect'
import { usePagination } from '@/composables/usePagination'
import type { AdminCourse } from '@/types/admin-category.types'

export function useCourses() {
  const toast = useToast()
  const courses = ref<AdminCourse[]>([])
  const loading = ref(true)
  const filters = reactive({ search: '', status: '', level: '' })

  async function loadActivePage(page = 1) {
    loading.value = true
    try {
      const res = await courseService.index({ page, per_page: 15, ...filters })
      courses.value = res.data.data
      activeUpdatePagination(res.data.pagination)
    } catch {
      toast.error('Không thể tải khóa học')
    } finally {
      loading.value = false
    }
  }

  const { pagination: activePagination, updatePagination: activeUpdatePagination } =
    usePagination(loadActivePage, 15)

  const softDelete = useDeleteConfirm({
    async onConfirm(course: AdminCourse) {
      await courseService.destroy(course.id)
      toast.success('Xóa thành công')
      loadActivePage()
    },
  })

  return { courses, loading, filters, activePagination, loadActivePage, softDelete }
}
```

## Service Layer Template

```ts
// src/services/course.service.ts
import http from '@/plugins/axios'
import type { AxiosResponse } from 'axios'
import type { ApiResponse, PaginatedResponse } from '@/types'
import type { AdminCourse } from '@/types/admin-category.types'

export const courseService = {
  index:        (params: Record<string, unknown>) =>
    http.get<PaginatedResponse<AdminCourse>>('/admin/courses', { params }),
  store:        (data: Record<string, unknown>) =>
    http.post<ApiResponse<AdminCourse>>('/admin/courses', data),
  update:       (id: number, data: Record<string, unknown>) =>
    http.patch<ApiResponse<AdminCourse>>(`/admin/courses/${id}`, data),
  destroy:      (id: number) => http.delete(`/admin/courses/${id}`),
  toggleStatus: (id: number) => http.patch(`/admin/courses/${id}/toggle-status`),
  trashed:      (params: Record<string, unknown>) =>
    http.get<PaginatedResponse<AdminCourse>>('/admin/courses/trashed', { params }),
  restore:      (id: number) => http.post(`/admin/courses/${id}/restore`),
  forceDelete:  (id: number) => http.delete(`/admin/courses/${id}/force-delete`),
  bulkDelete:   (ids: number[]) => http.delete('/admin/courses/bulk-delete', { data: { ids } }),
}
```

## Pinia Store Template (auth only)

```ts
// src/stores/studentAuthStore.ts
import { defineStore } from 'pinia'
import { authService } from '@/services/auth.service'

export const useStudentAuthStore = defineStore('studentAuth', {
  state: () => ({
    token: localStorage.getItem('studentToken') || null as string | null,
    student: null as Student | null,
    loading: false,
  }),
  getters: {
    isLoggedIn: (state) => !!state.token,
  },
  actions: {
    async login(email: string, password: string) {
      this.loading = true
      try {
        const res = await authService.studentLogin(email, password)
        const { token, student } = res.data.data
        this.token = token
        this.student = student
        localStorage.setItem('studentToken', token)
        return { success: true }
      } catch (err: any) {
        return {
          success: false,
          message: err.response?.data?.message || 'Đăng nhập thất bại.',
          errors: err.response?.data?.errors,
        }
      } finally {
        this.loading = false
      }
    },
    logout() {
      this.token = null
      this.student = null
      localStorage.removeItem('studentToken')
    },
  },
})
```

## defineEmits — Always tuple syntax

```ts
// ✅ Correct
defineEmits<{
  'update:modelValue': [value: string]
  'submit': []
  'switch-tab': [trashed: boolean]
}>()

// ❌ Old call-signature syntax — Volar flags as error
defineEmits<{
  (e: 'update:modelValue', value: string): void
}>()
```

## Template: No `as` cast

Never cast inside template expressions — extract to a function in `<script setup>`:

```vue
<!-- ❌ Volar parse error -->
@input="emit('update:value', ($event.target as HTMLInputElement).value)"

<!-- ✅ Extract to function -->
@input="onInput"

// in script:
function onInput(e: Event) {
  emit('update:value', (e.target as HTMLInputElement).value)
}
```

## Template ref for composable refs

When a template `ref` points to a variable from a composable, use `:ref` binding:

```vue
<!-- ❌ String ref doesn't bind to composable ref -->
<BulkActions ref="bulkActionsRef" />

<!-- ✅ Use setter function -->
<BulkActions :ref="setBulkActionsRef" />

// in script:
function setBulkActionsRef(el: unknown) { bulkActionsRef.value = el }
```

## Router Meta Pattern

```ts
{
  path: '/admin/courses',
  component: () => import('@/views/admin/CoursesPage.vue'),  // views/, not pages/
  meta: { requiresAuth: true, guard: 'admin' }
}
{
  path: '/my-courses',
  component: () => import('@/views/client/MyCoursesPage.vue'),
  meta: { requiresAuth: true, guard: 'student' }
}
{
  path: '/login',
  component: () => import('@/views/auth/LoginPage.vue'),
  meta: { requiresGuest: true, guard: 'student' }
}
```

## File Organization

```
src/
├── components/
│   ├── admin/        Per-resource tables/filters/rows (admin panel)
│   │   ├── categories/  CategoryFilters, CategoryTable, CategoryTrashedTable
│   │   ├── courses/     CourseFilters, CourseTable, CourseTableRow
│   │   ├── lessons/     LessonList, LessonItem
│   │   └── sections/    SectionItem
│   ├── common/       Shared UI: ConfirmModal, BulkActions
│   ├── forms/        Form modals
│   ├── layout/       AppSidebar, AppHeader, ThemeProvider, SidebarProvider
│   └── shared/admin/ Complex multi-feature components (SectionsLessonsManager)
├── composables/      All feature logic — thin views import from here
├── services/         API calls — one file per resource (*.service.ts)
├── stores/           Pinia — auth state only
├── types/            TypeScript interfaces (admin-category.types, section-lesson.types, ...)
├── views/            Thin page components — admin/ | client/ | auth/
├── plugins/          axios.ts (with interceptors)
└── router/           index.ts with beforeEach guards
```

## Conventions
- Always `<script setup lang="ts">` — never Options API
- Import order: Vue core → composables → services → components
- Loading states: `ref(false)` reset in `finally`
- Error handling: try/catch in composables/stores, toast for user feedback
- Never access `localStorage` directly in components — use stores
- Debounce search with `useDebounceSearch` composable
- Lazy-load all page-level components: `() => import('@/views/...')`
