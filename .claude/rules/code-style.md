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
- Pages: `PascalCasePage.vue` — `CoursesPage.vue`, `AdminLoginPage.vue`
- Composables: `camelCase.ts` — `useTheme.ts`, `useSidebar.ts`
- Stores: `camelCase.ts` — `studentAuthStore.ts`, `adminAuthStore.ts`
- API files: `camelCaseApi.js` — `coursesApi.js`, `authApi.js`

### Component Style
- Always use `<script setup lang="ts">` (Composition API)
- Import order: Vue core → composables/stores → API → child components
- Reactive state: `ref()` for primitives, `reactive()` for filter objects

```vue
<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import { coursesApi } from '@/api/coursesApi'
import CourseCard from '@/components/client/CourseCard.vue'

const loading = ref(false)
const courses = ref([])
const filters = reactive({ search: '', level: '', category_id: '' })
</script>
```

### Pinia Stores
- Options API syntax (`defineStore` with `state/getters/actions`)
- Token persisted to `localStorage` on login, cleared on logout
- Actions always return `{ success: boolean, message?: string, errors?: object }`

### API Layer (`src/api/`)
- One file per resource, plain object export (not class-based)
- All functions return the raw axios promise
- No error handling in api files — catch in stores/components

```js
export const coursesApi = {
  getPublic: (params) => http.get('/courses', { params }),
  getById: (id) => http.get(`/admin/courses/${id}`),
  create: (data) => http.post('/admin/courses', data),
}
```

### Router
- Admin routes: `meta: { requiresAuth: true, guard: 'admin' }`
- Student routes: `meta: { requiresAuth: true, guard: 'student' }`
- Guest-only pages: `meta: { requiresGuest: true, guard: 'admin|student' }`
- All page imports are lazy: `() => import('@/pages/...')`
- NProgress start/stop in `beforeEach`/`afterEach`

### Component Organization
```
components/
├── admin/    Components used only in admin panel
├── client/   Components used only in student-facing UI
├── common/   Shared UI (theme toggler, shapes, etc.)
└── layout/   AdminLayout.vue, ClientLayout.vue
```
