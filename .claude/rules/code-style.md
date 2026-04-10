# Code Style

## Backend (Laravel / PHP)

### File & Class Naming
- Controllers: `PascalCase` + `Controller` suffix — `CourseController`, `AdminCourseController`
- Form Requests: `StoreCourseRequest`, `UpdateCourseRequest`
- Resources: `CourseResource`, `CourseCollection`
- Models: Singular PascalCase — `Course`, `Student`, `Teacher`
- Repositories: `CourseRepository`, `CourseRepositoryInterface`
- Migrations: `YYYY_MM_DD_HHMMSS_create_<table>_table.php`

### Module Structure (Nwidart)
Every feature lives in `Modules/<Name>/`:
```
Modules/Course/
├── app/
│   ├── Http/
│   │   ├── Controllers/    PascalCase, extend base Controller
│   │   ├── Requests/       FormRequest per action (Store/Update)
│   │   ├── Resources/      API transformers (never return raw models)
│   │   └── Middleware/
│   ├── Models/
│   └── Repositories/       Interface + Implementation pair
├── database/migrations/
├── routes/api.php
└── module.json
```

### Controller Style
- Constructor injects repository interface (not concrete class)
- DB::transaction() wraps multi-step writes
- Return `$this->success(...)` / `$this->error(...)` / `$this->paginated(...)` from ApiResponse trait
- No business logic in controllers — delegate to repository

```php
public function __construct(private CourseRepositoryInterface $repository) {}

public function store(StoreCourseRequest $request): JsonResponse
{
    $course = DB::transaction(fn() => $this->repository->create($request->validated()));
    return $this->success(new CourseResource($course), 'Tạo thành công', 201);
}
```

### Repository Style
- Extends `BaseRepository`, implements module `RepositoryInterface`
- Base methods: `getAll`, `find`, `findOrFail`, `create`, `update`, `delete`, `paginate`
- Module adds custom methods: `findBySlug`, `getFiltered`, `toggleStatus`, etc.
- Clamp perPage: `$perPage = max(1, min($perPage, self::MAX_PER_PAGE))`

### Validation (Form Requests)
- All rules in `rules()` method
- Vietnamese messages in `messages()` method
- Always override `failedValidation()` to return standardized JSON (not Laravel's default redirect)

---

## Frontend (Vue 3 + TypeScript)

### File Naming
- Components: `PascalCase.vue` — `CourseCard.vue`, `SectionsLessonsManager.vue`
- Views (pages): `PascalCasePage.vue` — `CoursesPage.vue`, `AdminLoginPage.vue`
- Composables: `camelCase.ts` — `useTheme.ts`, `useCourses.ts`
- Stores: `camelCase.ts` — `studentAuthStore.ts`, `adminAuthStore.ts`
- Service files: `camelCase.service.ts` — `course.service.ts`, `category.service.ts`

### Component Style
- Always use `<script setup lang="ts">` (Composition API)
- Import order: Vue core → composables/stores → services → child components
- Reactive state: `ref()` for primitives, `reactive()` for filter objects
- **Never use `as` cast inside `<template>` expressions** — extract to a function in `<script setup>`

```vue
<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { useCourses } from '@/composables/useCourses'
import CourseCard from '@/components/client/CourseCard.vue'

const { courses, loading, filters, loadActivePage } = useCourses()
onMounted(() => loadActivePage())
</script>
```

### Composables Pattern
Logic (state + API calls + side effects) lives in `src/composables/`, **not** in views.
Views are thin orchestrators — they import composables and wire props/events to components.

```ts
// src/composables/useCourses.ts
export function useCourses() {
  const courses = ref<AdminCourse[]>([])
  const loading = ref(true)
  // ... state, methods
  return { courses, loading, ... }
}
```

Composable naming conventions:
- `useXxx` — generic feature composable (usePagination, useBulkSelect)
- `useXxxManager` — manages a resource within a page (useSectionsManager)
- `useXxxs` / `useXxx` — full CRUD for a resource (useCourses, useCategories)

### Service Layer (`src/services/`)
- One file per resource, named `<resource>.service.ts`
- Plain object export with typed return (AxiosResponse)
- No error handling — throw to composable/store

```ts
// src/services/course.service.ts
export const courseService = {
  index: (params: Record<string, unknown>) => http.get('/admin/courses', { params }),
  store: (data: Record<string, unknown>) => http.post('/admin/courses', data),
  update: (id: number, data: Record<string, unknown>) => http.patch(`/admin/courses/${id}`, data),
}
```

### Pinia Stores
- Options API syntax (`defineStore` with `state/getters/actions`)
- Token persisted to `localStorage` on login, cleared on logout
- Actions always return `{ success: boolean, message?: string, errors?: object }`
- Stores handle **auth state only** — feature state lives in composables

### `defineEmits` Syntax
Always use **tuple syntax** (not call-signature):

```ts
// ✅ Correct
defineEmits<{
  'update:modelValue': [value: string]
  'submit': []
  'switch-tab': [trashed: boolean]
}>()

// ❌ Old syntax — Volar flags as error
defineEmits<{
  (e: 'update:modelValue', value: string): void
}>()
```

### Router
- Admin routes: `meta: { requiresAuth: true, guard: 'admin' }`
- Student routes: `meta: { requiresAuth: true, guard: 'student' }`
- Guest-only pages: `meta: { requiresGuest: true, guard: 'admin|student' }`
- All page imports are lazy: `() => import('@/views/...')`
- NProgress start/stop in `beforeEach`/`afterEach`

### Component Organization
```
components/
├── admin/          Per-resource UI tables, filters, rows (admin panel only)
│   ├── categories/ CategoryFilters, CategoryTable, CategoryTrashedTable
│   ├── courses/    CourseFilters, CourseTable, CourseTableRow
│   ├── lessons/    LessonList, LessonItem
│   └── sections/   SectionItem
├── common/         Shared UI: ConfirmModal, BulkActions, Pagination
├── forms/          Form modals: CategoryForm, SectionFormModal, LessonFormModal
├── icons/          SVG icon components
├── layout/         AppSidebar, AppHeader, ThemeProvider, SidebarProvider
├── shared/
│   ├── admin/      Complex shared admin components (SectionsLessonsManager)
│   └── client/     Complex shared client components
└── table/          Generic table utilities (BulkActions)
```
