# Teacher Course Management — Design Spec

**Date:** 2026-05-22  
**Goal:** Cho phép giảng viên tạo, sửa, xóa khóa học và quản lý sections/lessons ngay trong teacher portal (`/teacher/courses`).

**Architecture:** Teacher-specific endpoints với ownership enforcement. Backend: 3 controller mới trong Commission module. Frontend: service + composable + component riêng, tái sử dụng UI components từ admin.

**Tech Stack:** Laravel 12 (Commission module), Vue 3 + TypeScript, existing Course/Lessons repositories.

---

## 1. Backend — API Routes

Tất cả routes dưới prefix `/teacher`, middleware `auth:admin` + `role:teacher` (đã có sẵn trong Commission routes).

### 1.1 Course CRUD

```
POST   /teacher/courses                       → TeacherCourseController@store
GET    /teacher/courses/{id}                  → TeacherCourseController@show
PATCH  /teacher/courses/{id}                  → TeacherCourseController@update
DELETE /teacher/courses/{id}                  → TeacherCourseController@destroy
PATCH  /teacher/courses/{id}/toggle-status    → TeacherCourseController@toggleStatus
```

### 1.2 Section CRUD (teacher-scoped)

```
GET    /teacher/courses/{course_id}/sections  → TeacherSectionController@index
POST   /teacher/courses/{course_id}/sections  → TeacherSectionController@store
POST   /teacher/sections/reorder              → TeacherSectionController@reorder
PATCH  /teacher/sections/{id}                 → TeacherSectionController@update
DELETE /teacher/sections/{id}                 → TeacherSectionController@destroy
PATCH  /teacher/sections/{id}/toggle-status   → TeacherSectionController@toggleStatus
```

### 1.3 Lesson CRUD (teacher-scoped, đầy đủ như admin)

```
GET    /teacher/courses/{course_id}/lessons   → TeacherLessonController@index
POST   /teacher/courses/{course_id}/lessons   → TeacherLessonController@store
GET    /teacher/lessons/trashed               → TeacherLessonController@trashed
POST   /teacher/lessons/reorder               → TeacherLessonController@reorder
DELETE /teacher/lessons/bulk-delete           → TeacherLessonController@bulkDelete
POST   /teacher/lessons/bulk-action           → TeacherLessonController@bulkAction
PATCH  /teacher/lessons/bulk-restore          → TeacherLessonController@bulkRestore
DELETE /teacher/lessons/bulk-force-delete     → TeacherLessonController@bulkForceDelete
GET    /teacher/lessons/{id}                  → TeacherLessonController@show
PATCH  /teacher/lessons/{id}                  → TeacherLessonController@update
DELETE /teacher/lessons/{id}                  → TeacherLessonController@destroy
PATCH  /teacher/lessons/{id}/toggle-status    → TeacherLessonController@toggleStatus
PATCH  /teacher/lessons/{id}/restore          → TeacherLessonController@restore
DELETE /teacher/lessons/{id}/force-delete     → TeacherLessonController@forceDelete
```

---

## 2. Backend — Controllers & Ownership

### 2.1 Ownership Pattern

Mọi controller đều extend `TeacherPortalController` để dùng `getTeacher()`. Ownership check bằng cách scope query:

```php
// Course: chỉ trả về course thuộc teacher đang login
private function ownedCourse(int $id): Course
{
    return Course::where('id', $id)
        ->where('teacher_id', $this->getTeacher()->id)
        ->firstOrFail(); // 404 nếu không phải của mình
}

// Section: verify qua course.teacher_id
private function ownedSection(int $id): Section
{
    $teacher = $this->getTeacher();
    return Section::whereHas('course', fn($q) => $q->where('teacher_id', $teacher->id))
        ->findOrFail($id);
}

// Lesson: verify qua section.course.teacher_id
private function ownedLesson(int $id): Lesson
{
    $teacher = $this->getTeacher();
    return Lesson::whereHas('section.course', fn($q) => $q->where('teacher_id', $teacher->id))
        ->findOrFail($id);
}
```

### 2.2 TeacherCourseController

File: `Modules/Commission/app/Http/Controllers/Teacher/TeacherCourseController.php`

- `store()` — delegate đến `CourseRepository::create()`, tự set `teacher_id = $teacher->id`
- `show()` — `ownedCourse($id)` + load relations
- `update()` — `ownedCourse($id)` + `CourseRepository::update()`  
- `destroy()` — `ownedCourse($id)` + soft delete
- `toggleStatus()` — `ownedCourse($id)` + toggle status field

Reuse `StoreCourseRequest` và `UpdateCourseRequest` đã có (chúng đã handle teacher_id auto-assign).

### 2.3 TeacherSectionController

File: `Modules/Commission/app/Http/Controllers/Teacher/TeacherSectionController.php`

- `index($course_id)` — verify course ownership + delegate `SectionRepository`
- `store($course_id)` — verify course ownership + `SectionRepository::create()`
- `update($id)` — `ownedSection($id)` + `SectionRepository::update()`
- `destroy($id)` — `ownedSection($id)` + soft delete
- `toggleStatus($id)` — `ownedSection($id)` + toggle
- `reorder()` — verify all section IDs belong to teacher's courses + reorder

Reuse `StoreSectionRequest`, `UpdateSectionRequest`, `ReorderSectionRequest`.

### 2.4 TeacherLessonController

File: `Modules/Commission/app/Http/Controllers/Teacher/TeacherLessonController.php`

- `index($course_id)` — verify course ownership + lessons
- `store($course_id)` — verify course ownership + create
- `show($id)` — `ownedLesson($id)`
- `update($id)` — `ownedLesson($id)` + update
- `destroy($id)` — `ownedLesson($id)` + soft delete
- `toggleStatus($id)` — `ownedLesson($id)` + toggle
- `restore($id)` — verify lesson ownership + restore
- `forceDelete($id)` — verify lesson ownership + force delete
- `trashed()` — paginate trashed lessons scoped to teacher's courses
- `reorder()`, `bulkDelete()`, `bulkAction()`, `bulkRestore()`, `bulkForceDelete()` — scoped to teacher's lessons

Reuse `StoreLessonRequest`, `UpdateLessonRequest`, `ReorderLessonRequest`, `BulkAction*` requests.

---

## 3. Frontend — File Structure

### 3.1 Services

```
src/services/teacher-section.service.ts
  — Cùng methods với sectionService nhưng gọi /teacher/... thay vì /admin/...

src/services/teacher-lesson.service.ts  
  — Cùng methods với lessonService nhưng gọi /teacher/...
```

Thêm vào `commission.service.ts`:
```ts
createCourse: (data) => http.post('/teacher/courses', data)
showCourse: (id) => http.get(`/teacher/courses/${id}`)
updateCourse: (id, data) => http.patch(`/teacher/courses/${id}`, data)
deleteCourse: (id) => http.delete(`/teacher/courses/${id}`)
toggleCourseStatus: (id) => http.patch(`/teacher/courses/${id}/toggle-status`)
```

### 3.2 Composables

```
src/composables/useTeacherSectionsManager.ts
  — Clone useSectionsManager, dùng teacherSectionService + teacherLessonService

src/composables/useTeacherLessonsManager.ts
  — Clone useLessonsManager, dùng teacherLessonService
```

### 3.3 Components

```
src/components/shared/teacher/TeacherSectionsLessonsManager.vue
  — Template giống hệt SectionsLessonsManager.vue
  — Script dùng useTeacherSectionsManager + useTeacherLessonsManager
  — Tái sử dụng: SectionItem, LessonList, modals, BulkActions (không đổi)
```

Sửa nhỏ `CourseInfoForm.vue`:
```ts
// Thêm 1 prop:
const props = defineProps<{
  ...,
  hideTeacher?: boolean  // ẩn dropdown chọn giảng viên trong teacher portal
}>()
```

### 3.4 Views

```
src/views/teacher/TeacherCoursesPage.vue (sửa)
  — Thêm nút "+ Thêm khóa học" → /teacher/courses/create
  — Thêm nút "Sửa" trên mỗi dòng → /teacher/courses/:id/edit

src/views/teacher/TeacherCourseFormPage.vue (mới)
  — Hai tab: "Thông tin" + "Nội dung"
  — Create mode: chỉ tab Thông tin, sau khi tạo redirect sang edit
  — Edit mode: cả 2 tab, tab Nội dung dùng TeacherSectionsLessonsManager
```

### 3.5 Router

```js
// Thêm vào /teacher children:
{ path: 'courses/create',   name: 'teacher.courses.create', component: TeacherCourseFormPage }
{ path: 'courses/:id/edit', name: 'teacher.courses.edit',   component: TeacherCourseFormPage }
```

---

## 4. Data Flow

```
Teacher clicks "+ Thêm khóa học"
  → /teacher/courses/create
  → TeacherCourseFormPage (create mode)
  → form submit → POST /teacher/courses
  → backend: auto-set teacher_id, create Course
  → redirect → /teacher/courses/:id/edit?tab=lessons

Teacher on edit page, tab "Nội dung"
  → TeacherSectionsLessonsManager
  → useTeacherSectionsManager(courseId)
  → GET /teacher/courses/:id/sections + GET /teacher/courses/:id/lessons
  → render SectionItem + LessonList (reused UI)
  → Teacher adds section → POST /teacher/courses/:id/sections (verify ownership)
  → Teacher adds lesson → POST /teacher/courses/:id/lessons (verify ownership)
```

---

## 5. Ownership Security

- Mọi write operation đều verify `course.teacher_id === $teacher->id` trước khi thực hiện
- `firstOrFail()` trả về 404 tự động nếu không tìm thấy hoặc không phải owner
- `trashed()` và bulk operations đều scope theo `teacher_id` — teacher không thể thao tác data của teacher khác
- Thumbnail upload vẫn dùng `/admin/upload/thumbnail` (teacher đã có quyền `courses.create`)

---

## 6. Không implement (ngoài scope)

- Soft delete/restore cho courses trong teacher portal (chỉ admin xử lý)
- Bulk delete courses
- Course approval workflow (nếu cần thêm sau)
