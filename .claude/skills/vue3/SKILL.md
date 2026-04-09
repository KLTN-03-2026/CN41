# Vue 3 Expert Skill

You are an expert in this project's Vue 3 + TypeScript + Pinia frontend architecture.

## Project Setup

- Vue 3.5 with `<script setup lang="ts">` (Composition API only — no Options API)
- Pinia 3 for state management
- Vue Router 5 with lazy-loaded routes
- Axios via `@/plugins/axios` (pre-configured with auth interceptors)
- Tailwind CSS 3 for styling
- `vue-toastification` for notifications
- NProgress for route transition loading bar

## Component Template

```vue
<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import { coursesApi } from '@/api/coursesApi'
import type { Course, Pagination } from '@/types'
import CourseCard from '@/components/client/CourseCard.vue'

// State
const loading = ref(false)
const courses = ref<Course[]>([])
const pagination = ref<Pagination | null>(null)
const filters = reactive({ search: '', level: '', category_id: '' })

const toast = useToast()

// Fetch
const fetchPage = async (page = 1) => {
  loading.value = true
  try {
    const res = await coursesApi.getPublic({ ...filters, page, per_page: 15 })
    courses.value = res.data.data
    pagination.value = res.data.pagination
  } catch (err: any) {
    toast.error(err.response?.data?.message || 'Đã có lỗi xảy ra.')
  } finally {
    loading.value = false
  }
}

onMounted(() => fetchPage())
</script>

<template>
  <div>
    <div v-if="loading">Loading...</div>
    <CourseCard v-for="course in courses" :key="course.id" :course="course" />
  </div>
</template>
```

## Pinia Store Template

```ts
// src/stores/studentAuthStore.ts
import { defineStore } from 'pinia'
import { authApi } from '@/api/authApi'

export const useStudentAuthStore = defineStore('studentAuth', {
  state: () => ({
    token: localStorage.getItem('studentToken') || null as string | null,
    student: null as Student | null,
    loading: false,
  }),

  getters: {
    isLoggedIn: (state) => !!state.token,
    fullName: (state) => state.student?.name || '',
  },

  actions: {
    async login(email: string, password: string) {
      this.loading = true
      try {
        const res = await authApi.studentLogin(email, password)
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

## API Module Template

```ts
// src/api/coursesApi.ts
import http from '@/plugins/axios'

export const coursesApi = {
  getPublic:  (params?: object)    => http.get('/courses', { params }),
  getDetail:  (slug: string)       => http.get(`/courses/${slug}`),
  getAdmin:   (params?: object)    => http.get('/admin/courses', { params }),
  create:     (data: object)       => http.post('/admin/courses', data),
  update:     (id: number, data: object) => http.patch(`/admin/courses/${id}`, data),
  remove:     (id: number)         => http.delete(`/admin/courses/${id}`),
  toggleStatus: (id: number)       => http.patch(`/admin/courses/${id}/toggle-status`),
  bulkDelete: (ids: number[])      => http.delete('/admin/courses/bulk-delete', { data: { ids } }),
}
```

## Composable Template (Provide/Inject pattern)

```ts
// src/composables/useMyFeature.ts
import { ref, provide, inject, type InjectionKey, type Ref } from 'vue'

interface MyContextType {
  value: Ref<string>
  setValue: (v: string) => void
}

const MySymbol: InjectionKey<MyContextType> = Symbol('myFeature')

export function useMyFeatureProvider() {
  const value = ref('')
  const setValue = (v: string) => { value.value = v }
  const context: MyContextType = { value, setValue }
  provide(MySymbol, context)
  return context
}

export function useMyFeature(): MyContextType {
  const context = inject<MyContextType>(MySymbol)
  if (!context) throw new Error('useMyFeature must be used within provider')
  return context
}
```

## Router Meta Pattern

```ts
// Route with auth guard
{
  path: '/my-courses',
  component: () => import('@/pages/client/MyCoursesPage.vue'),
  meta: { requiresAuth: true, guard: 'student' }
}

// Admin route
{
  path: '/admin/courses',
  component: () => import('@/pages/admin/CoursesPage.vue'),
  meta: { requiresAuth: true, guard: 'admin' }
}

// Guest-only page
{
  path: '/login',
  component: () => import('@/pages/auth/LoginPage.vue'),
  meta: { requiresGuest: true, guard: 'student' }
}
```

## File Organization

```
src/
├── api/              One file per resource — plain object exports
├── components/
│   ├── admin/        Admin-only components
│   ├── client/       Student-facing components
│   ├── common/       Shared UI (ThemeToggler, etc.)
│   └── layout/       AdminLayout.vue, ClientLayout.vue
├── composables/      useTheme.ts, useSidebar.ts (Provide/Inject pattern)
├── pages/
│   ├── admin/        Admin pages (*Page.vue)
│   ├── client/       Student pages (*Page.vue)
│   └── auth/         Login/Register pages
├── plugins/          axios.js (with interceptors)
├── router/           index.js with beforeEach guards
├── stores/           Pinia stores (*Store.ts or *AuthStore.ts)
└── types/            TypeScript interfaces
```

## Conventions
- Always `<script setup lang="ts">` — never Options API
- Loading states: `ref(false)` on every async operation, reset in `finally`
- Error handling: try/catch in stores returning `{ success, message, errors }`, toast in components
- Never access `localStorage` directly in components — use stores
- Debounce search inputs (use `useDebounceFn` from vueuse or implement manually)
- Lazy-load all page-level components in router
