# Teacher Course Management — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Cho phép giảng viên tạo, sửa, xóa khóa học và quản lý sections/lessons đầy đủ tại `/teacher/courses`.

**Architecture:** 3 Laravel controllers mới (Commission module) dùng lại existing repositories — `ScopesToTeacher` global scope trên Course/Section/Lesson model tự động giới hạn data về của từng teacher, không cần ownership check thủ công. Frontend: teacher-specific services + composables clone từ admin versions (đổi service import), `TeacherSectionsLessonsManager.vue` reuse toàn bộ UI components.

**Tech Stack:** Laravel 12 (Commission module), Course/Lessons repositories, Vue 3 + TypeScript, Tailwind CSS.

---

## File Structure

### Backend (mới tạo)
- `Modules/Commission/app/Http/Controllers/Teacher/TeacherCourseController.php`
- `Modules/Commission/app/Http/Controllers/Teacher/TeacherSectionController.php`
- `Modules/Commission/app/Http/Controllers/Teacher/TeacherLessonController.php`

### Backend (sửa)
- `Modules/Commission/routes/api.php` — thêm course/section/lesson routes

### Backend (tests)
- `tests/Feature/Admin/TeacherCoursePortalTest.php`

### Frontend (mới tạo)
- `e-learning-frontend/src/services/teacher-section.service.ts`
- `e-learning-frontend/src/services/teacher-lesson.service.ts`
- `e-learning-frontend/src/composables/useTeacherSectionsManager.ts`
- `e-learning-frontend/src/composables/useTeacherLessonsManager.ts`
- `e-learning-frontend/src/components/shared/teacher/TeacherSectionsLessonsManager.vue`
- `e-learning-frontend/src/views/teacher/TeacherCourseFormPage.vue`

### Frontend (sửa)
- `e-learning-frontend/src/services/commission.service.ts` — thêm course CRUD methods
- `e-learning-frontend/src/views/teacher/TeacherCoursesPage.vue` — thêm nút + edit links
- `e-learning-frontend/src/router/index.js` — thêm 2 routes

---

## Task 1: Backend — TeacherCourseController

> **Context:** `ScopesToTeacher` trên `Course` model tự động filter data về của teacher đang login khi dùng admin guard. `StoreCourseRequest::prepareForValidation()` đã auto-set `teacher_id`. Repositories của admin có thể dùng lại.

**Files:**
- Create: `e-learning-backend/Modules/Commission/app/Http/Controllers/Teacher/TeacherCourseController.php`

- [ ] **Step 1: Tạo TeacherCourseController**

```php
<?php

namespace Modules\Commission\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Modules\Course\Http\Requests\StoreCourseRequest;
use Modules\Course\Http\Requests\UpdateCourseRequest;
use Modules\Course\Http\Resources\CourseResource;
use Modules\Course\Repositories\CourseRepositoryInterface;

class TeacherCourseController extends Controller
{
    use ApiResponse;

    public function __construct(private CourseRepositoryInterface $repository) {}

    public function store(StoreCourseRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $categoryIds = $validated['category_ids'] ?? [];
        unset($validated['category_ids']);

        $course = DB::transaction(function () use ($validated, $categoryIds) {
            $course = $this->repository->create($validated);
            if (! empty($categoryIds)) {
                $this->repository->syncCategories($course->id, $categoryIds);
            }
            return $course;
        });

        $course->refresh()->load(['teacher', 'categories']);

        return $this->success(new CourseResource($course), 'Khóa học đã được tạo thành công.', 201);
    }

    public function show(int $id): JsonResponse
    {
        // ScopesToTeacher auto-filters: 404 if not teacher's course
        $course = $this->repository->findOrFail($id, ['*'], ['teacher', 'categories']);

        return $this->success(new CourseResource($course));
    }

    public function update(UpdateCourseRequest $request, int $id): JsonResponse
    {
        $validated = $request->validated();
        $categoryIds = $validated['category_ids'] ?? null;
        unset($validated['category_ids'], $validated['teacher_id']); // prevent hijacking

        $course = DB::transaction(function () use ($id, $validated, $categoryIds) {
            $course = $this->repository->update($id, $validated); // auto-scoped → 404 if not owner
            if ($categoryIds !== null) {
                $this->repository->syncCategories($course->id, $categoryIds);
            }
            return $course;
        });

        $course->load(['teacher', 'categories']);

        return $this->success(new CourseResource($course), 'Khóa học đã được cập nhật thành công.');
    }

    public function destroy(int $id): JsonResponse
    {
        $this->repository->delete($id); // auto-scoped

        return $this->success(null, 'Khóa học đã được xóa thành công.');
    }

    public function toggleStatus(int $id): JsonResponse
    {
        $course = $this->repository->toggleStatus($id); // auto-scoped

        $statusText = $course->status === 1 ? 'xuất bản' : 'chuyển về nháp';

        return $this->success(new CourseResource($course), "Khóa học đã được {$statusText}.");
    }
}
```

- [ ] **Step 2: Verify file exists**

```bash
wsl.exe -d Ubuntu -- bash -c "ls Modules/Commission/app/Http/Controllers/Teacher/" | cat
# Expected: TeacherCourseController.php TeacherPortalController.php
```

---

## Task 2: Backend — TeacherSectionController

**Files:**
- Create: `e-learning-backend/Modules/Commission/app/Http/Controllers/Teacher/TeacherSectionController.php`

- [ ] **Step 1: Tạo TeacherSectionController**

```php
<?php

namespace Modules\Commission\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Course\Repositories\CourseRepositoryInterface;
use Modules\Lessons\Http\Requests\ReorderSectionRequest;
use Modules\Lessons\Http\Requests\StoreSectionRequest;
use Modules\Lessons\Http\Requests\UpdateSectionRequest;
use Modules\Lessons\Http\Resources\SectionResource;
use Modules\Lessons\Repositories\SectionRepositoryInterface;

class TeacherSectionController extends Controller
{
    use ApiResponse;

    public function __construct(
        private SectionRepositoryInterface $repository,
        private CourseRepositoryInterface $courseRepo,
    ) {}

    public function index(Request $request, int $course_id): JsonResponse
    {
        // courseRepo::findOrFail auto-scoped → 404 if not teacher's course
        $this->courseRepo->findOrFail($course_id);

        $perPage = (int) $request->query('per_page', 100);
        $data = $this->repository->getByCourse($course_id, [], $perPage);

        return $this->paginated($data, 'Lấy danh sách chương thành công.');
    }

    public function store(StoreSectionRequest $request, int $course_id): JsonResponse
    {
        $this->courseRepo->findOrFail($course_id);

        $validated = $request->validated();
        $validated['course_id'] = $course_id;

        if (! isset($validated['order'])) {
            $validated['order'] = $this->repository->countByCourse($course_id);
        }

        $section = $this->repository->create($validated);

        return $this->success(new SectionResource($section), 'Tạo chương thành công.', 201);
    }

    public function update(UpdateSectionRequest $request, int $id): JsonResponse
    {
        // ScopesToTeacher auto-scoped → 404 if not teacher's section
        $section = $this->repository->update($id, $request->validated());

        return $this->success(new SectionResource($section), 'Cập nhật chương thành công.');
    }

    public function destroy(int $id): JsonResponse
    {
        $this->repository->delete($id); // auto-scoped

        return $this->success(null, 'Xóa chương thành công.');
    }

    public function toggleStatus(int $id): JsonResponse
    {
        $section = $this->repository->toggleStatus($id); // auto-scoped

        return $this->success(
            ['id' => $section->id, 'status' => $section->status],
            'Cập nhật trạng thái chương thành công.'
        );
    }

    public function reorder(ReorderSectionRequest $request): JsonResponse
    {
        // SectionRepository::reorder uses Section::where() which is auto-scoped
        $this->repository->reorder($request->orders);

        return $this->success(null, 'Sắp xếp chương thành công.');
    }
}
```

- [ ] **Step 2: Verify**

```bash
wsl.exe -d Ubuntu -- bash -c "ls Modules/Commission/app/Http/Controllers/Teacher/" | cat
# Expected: TeacherCourseController.php TeacherPortalController.php TeacherSectionController.php
```

---

## Task 3: Backend — TeacherLessonController

**Files:**
- Create: `e-learning-backend/Modules/Commission/app/Http/Controllers/Teacher/TeacherLessonController.php`

- [ ] **Step 1: Tạo TeacherLessonController**

```php
<?php

namespace Modules\Commission\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Course\Models\Course;
use Modules\Course\Repositories\CourseRepositoryInterface;
use Modules\Lessons\Http\Requests\BulkActionLessonRequest;
use Modules\Lessons\Http\Requests\BulkDeleteLessonRequest;
use Modules\Lessons\Http\Requests\BulkForceDeleteLessonRequest;
use Modules\Lessons\Http\Requests\BulkRestoreLessonRequest;
use Modules\Lessons\Http\Requests\ReorderLessonRequest;
use Modules\Lessons\Http\Requests\StoreLessonRequest;
use Modules\Lessons\Http\Requests\UpdateLessonRequest;
use Modules\Lessons\Http\Resources\LessonResource;
use Modules\Lessons\Repositories\LessonRepositoryInterface;
use Modules\Lessons\Repositories\SectionRepositoryInterface;

class TeacherLessonController extends Controller
{
    use ApiResponse;

    public function __construct(
        private LessonRepositoryInterface $repository,
        private CourseRepositoryInterface $courseRepo,
        private SectionRepositoryInterface $sectionRepo,
    ) {}

    public function index(Request $request, int $course_id): JsonResponse
    {
        $this->courseRepo->findOrFail($course_id); // auto-scoped → 404 if not owner

        $perPage = (int) $request->query('per_page', 100);
        $filters = $request->only(['status', 'type']);
        $data = $this->repository->getByCourse($course_id, $filters, $perPage);

        return $this->paginated($data, 'Lấy danh sách bài giảng thành công.');
    }

    public function store(StoreLessonRequest $request, int $course_id): JsonResponse
    {
        $this->courseRepo->findOrFail($course_id); // auto-scoped

        $validated = $request->validated();
        $validated['course_id'] = $course_id;
        $validated['slug'] = Str::slug($validated['title']).'-'.uniqid();

        if (! empty($validated['section_id'])) {
            if (! $this->sectionRepo->belongsToCourse($validated['section_id'], $course_id)) {
                return $this->error('Chương không thuộc khóa học này.', 422);
            }
        }

        if (! isset($validated['order'])) {
            $validated['order'] = $this->repository->countInScope(
                $course_id,
                ! empty($validated['section_id']) ? $validated['section_id'] : null
            );
        }

        $lesson = DB::transaction(function () use ($validated, $course_id) {
            $lesson = $this->repository->create($validated);
            Course::withoutGlobalScope('teacher_scope')
                ->where('id', $course_id)->increment('total_lessons');
            return $lesson;
        });

        return $this->success(new LessonResource($lesson), 'Tạo bài giảng thành công.', 201);
    }

    public function show(int $id): JsonResponse
    {
        $lesson = $this->repository->findOrFail($id, ['*'], ['video', 'document']); // auto-scoped

        return $this->success(new LessonResource($lesson), 'Lấy chi tiết bài giảng thành công.');
    }

    public function update(UpdateLessonRequest $request, int $id): JsonResponse
    {
        $lesson = $this->repository->update($id, $request->validated()); // auto-scoped

        return $this->success(new LessonResource($lesson), 'Cập nhật bài giảng thành công.');
    }

    public function destroy(int $id): JsonResponse
    {
        $lesson = $this->repository->findOrFail($id); // auto-scoped

        DB::transaction(function () use ($lesson, $id) {
            $this->repository->delete($id);
            Course::withoutGlobalScope('teacher_scope')
                ->where('id', $lesson->course_id)->decrement('total_lessons');
        });

        return $this->success(null, 'Xóa bài giảng thành công.');
    }

    public function toggleStatus(int $id): JsonResponse
    {
        $lesson = $this->repository->toggleStatus($id); // auto-scoped

        return $this->success(
            ['id' => $lesson->id, 'status' => $lesson->status],
            'Cập nhật trạng thái thành công.'
        );
    }

    public function trashed(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);
        $data = $this->repository->paginateTrashed($perPage); // auto-scoped to teacher's lessons

        return $this->paginated($data, 'Lấy danh sách bài giảng đã xóa thành công.');
    }

    public function restore(int $id): JsonResponse
    {
        $lesson = $this->repository->findTrashed($id); // auto-scoped

        DB::transaction(function () use ($lesson, $id) {
            $this->repository->restore($id);
            Course::withoutGlobalScope('teacher_scope')
                ->where('id', $lesson->course_id)->increment('total_lessons');
        });

        return $this->success(null, 'Khôi phục bài giảng thành công.');
    }

    public function forceDelete(int $id): JsonResponse
    {
        $this->repository->forceDeleteById($id); // auto-scoped via findTrashed inside

        return $this->success(null, 'Xóa vĩnh viễn bài giảng thành công.');
    }

    public function reorder(ReorderLessonRequest $request): JsonResponse
    {
        $ids = collect($request->orders)->pluck('id')->toArray();
        $courseIds = $this->repository->getDistinctCourseIds($ids);

        if ($courseIds->count() > 1) {
            return $this->error('Không thể sắp xếp bài giảng của nhiều khóa học cùng lúc.', 422);
        }

        $this->repository->reorder($request->orders);

        return $this->success(null, 'Sắp xếp bài giảng thành công.');
    }

    public function bulkDelete(BulkDeleteLessonRequest $request): JsonResponse
    {
        $lessons = $this->repository->getByIds($request->ids); // auto-scoped

        $count = DB::transaction(function () use ($request, $lessons) {
            $count = $this->repository->deleteMany($request->ids);
            foreach ($lessons->groupBy('course_id') as $courseId => $group) {
                Course::withoutGlobalScope('teacher_scope')
                    ->where('id', $courseId)->decrement('total_lessons', $group->count());
            }
            return $count;
        });

        return $this->success(null, "Xóa hàng loạt {$count} bài giảng thành công.");
    }

    public function bulkAction(BulkActionLessonRequest $request): JsonResponse
    {
        if ($request->action === 'assign-section') {
            $sectionId = $request->section_id;
            $courseIds = $this->repository->getDistinctCourseIds($request->ids);

            if ($courseIds->count() > 1) {
                return $this->error('Các bài giảng phải thuộc cùng một khóa học.', 422);
            }

            if ($sectionId) {
                $courseId = $courseIds->first();
                if (! $this->sectionRepo->belongsToCourse($sectionId, $courseId)) {
                    return $this->error('Chương không thuộc khóa học này.', 422);
                }
            }

            $count = $this->repository->assignSection($request->ids, $sectionId);
            $message = $sectionId
                ? "Đã gán {$count} bài giảng vào chương thành công."
                : "Đã bỏ phân chương {$count} bài giảng thành công.";

            return $this->success(null, $message);
        }

        $count = $this->repository->actionMany($request->ids, $request->action);

        return $this->success(null, "Cập nhật trạng thái hàng loạt {$count} bài giảng thành công.");
    }

    public function bulkRestore(BulkRestoreLessonRequest $request): JsonResponse
    {
        $lessons = $this->repository->getManyTrashed($request->ids); // auto-scoped

        $count = DB::transaction(function () use ($request, $lessons) {
            $count = $this->repository->restoreMany($request->ids);
            foreach ($lessons->groupBy('course_id') as $courseId => $group) {
                Course::withoutGlobalScope('teacher_scope')
                    ->where('id', $courseId)->increment('total_lessons', $group->count());
            }
            return $count;
        });

        return $this->success(null, "Khôi phục hàng loạt {$count} bài giảng thành công.");
    }

    public function bulkForceDelete(BulkForceDeleteLessonRequest $request): JsonResponse
    {
        $count = $this->repository->forceDeleteMany($request->ids); // auto-scoped

        return $this->success(null, "Xóa vĩnh viễn hàng loạt {$count} bài giảng thành công.");
    }
}
```

- [ ] **Step 2: Verify**

```bash
wsl.exe -d Ubuntu -- bash -c "ls Modules/Commission/app/Http/Controllers/Teacher/" | cat
# Expected 3 files: TeacherCourseController.php TeacherLessonController.php TeacherPortalController.php TeacherSectionController.php
```

---

## Task 4: Backend — Routes + Tests

**Files:**
- Modify: `e-learning-backend/Modules/Commission/routes/api.php`
- Create: `e-learning-backend/tests/Feature/Admin/TeacherCoursePortalTest.php`

- [ ] **Step 1: Thêm routes vào Commission api.php**

Thêm vào sau dòng `Route::post('change-email/confirm', ...)` bên trong group `['auth:admin', 'role:teacher']`:

```php
use Modules\Commission\Http\Controllers\Teacher\TeacherCourseController;
use Modules\Commission\Http\Controllers\Teacher\TeacherSectionController;
use Modules\Commission\Http\Controllers\Teacher\TeacherLessonController;
```

Thêm vào đầu file (cùng chỗ với các `use` imports khác), rồi thêm routes:

```php
// ── Teacher Course CRUD ──────────────────────────────────────────
Route::post('courses', [TeacherCourseController::class, 'store']);
Route::get('courses/{id}', [TeacherCourseController::class, 'show']);
Route::patch('courses/{id}', [TeacherCourseController::class, 'update']);
Route::delete('courses/{id}', [TeacherCourseController::class, 'destroy']);
Route::patch('courses/{id}/toggle-status', [TeacherCourseController::class, 'toggleStatus']);

// ── Teacher Section CRUD ─────────────────────────────────────────
Route::post('sections/reorder', [TeacherSectionController::class, 'reorder']);
Route::get('courses/{course_id}/sections', [TeacherSectionController::class, 'index']);
Route::post('courses/{course_id}/sections', [TeacherSectionController::class, 'store']);
Route::patch('sections/{id}', [TeacherSectionController::class, 'update']);
Route::delete('sections/{id}', [TeacherSectionController::class, 'destroy']);
Route::patch('sections/{id}/toggle-status', [TeacherSectionController::class, 'toggleStatus']);

// ── Teacher Lesson CRUD ──────────────────────────────────────────
// Static routes trước parameterized (QUAN TRỌNG: tránh Laravel match sai)
Route::get('lessons/trashed', [TeacherLessonController::class, 'trashed']);
Route::post('lessons/reorder', [TeacherLessonController::class, 'reorder']);
Route::delete('lessons/bulk-delete', [TeacherLessonController::class, 'bulkDelete']);
Route::post('lessons/bulk-action', [TeacherLessonController::class, 'bulkAction']);
Route::patch('lessons/bulk-restore', [TeacherLessonController::class, 'bulkRestore']);
Route::delete('lessons/bulk-force-delete', [TeacherLessonController::class, 'bulkForceDelete']);
Route::get('courses/{course_id}/lessons', [TeacherLessonController::class, 'index']);
Route::post('courses/{course_id}/lessons', [TeacherLessonController::class, 'store']);
Route::get('lessons/{id}', [TeacherLessonController::class, 'show']);
Route::patch('lessons/{id}', [TeacherLessonController::class, 'update']);
Route::delete('lessons/{id}', [TeacherLessonController::class, 'destroy']);
Route::patch('lessons/{id}/toggle-status', [TeacherLessonController::class, 'toggleStatus']);
Route::patch('lessons/{id}/restore', [TeacherLessonController::class, 'restore']);
Route::delete('lessons/{id}/force-delete', [TeacherLessonController::class, 'forceDelete']);
```

Sau khi sửa, `Modules/Commission/routes/api.php` section teacher trông như sau:

```php
<?php

use Illuminate\Support\Facades\Route;
use Modules\Commission\Http\Controllers\Admin\CommissionSettingsController;
use Modules\Commission\Http\Controllers\Admin\PayoutController;
use Modules\Commission\Http\Controllers\Admin\TeacherEarningsController;
use Modules\Commission\Http\Controllers\Teacher\EarningsController;
use Modules\Commission\Http\Controllers\Teacher\TeacherCourseController;
use Modules\Commission\Http\Controllers\Teacher\TeacherLessonController;
use Modules\Commission\Http\Controllers\Teacher\TeacherPortalController;
use Modules\Commission\Http\Controllers\Teacher\TeacherSectionController;

Route::middleware(['auth:admin'])->prefix('admin')->group(function () {
    Route::get('commission-settings', [CommissionSettingsController::class, 'show']);
    Route::patch('commission-settings', [CommissionSettingsController::class, 'update']);
    Route::get('payouts', [PayoutController::class, 'index']);
    Route::patch('payouts/{id}/approve', [PayoutController::class, 'approve']);
    Route::patch('payouts/{id}/reject', [PayoutController::class, 'reject']);
    Route::patch('payouts/{id}/mark-paid', [PayoutController::class, 'markPaid']);
    Route::get('teacher-earnings', [TeacherEarningsController::class, 'index']);
});

Route::middleware(['auth:admin', 'role:teacher'])->prefix('teacher')->group(function () {
    Route::get('earnings', [EarningsController::class, 'index']);
    Route::get('payouts', [EarningsController::class, 'myPayouts']);
    Route::post('payouts', [EarningsController::class, 'requestPayout']);

    Route::get('dashboard', [TeacherPortalController::class, 'dashboard']);
    Route::get('profile', [TeacherPortalController::class, 'profile']);
    Route::patch('profile', [TeacherPortalController::class, 'updateProfile']);
    Route::post('change-password/send-otp', [TeacherPortalController::class, 'sendPasswordOtp']);
    Route::post('change-password/confirm', [TeacherPortalController::class, 'confirmPasswordChange']);
    Route::post('change-email/send-otp', [TeacherPortalController::class, 'sendEmailChangeOtp']);
    Route::post('change-email/confirm', [TeacherPortalController::class, 'confirmEmailChange']);

    // Course CRUD
    Route::post('courses', [TeacherCourseController::class, 'store']);
    Route::get('courses/{id}', [TeacherCourseController::class, 'show']);
    Route::patch('courses/{id}', [TeacherCourseController::class, 'update']);
    Route::delete('courses/{id}', [TeacherCourseController::class, 'destroy']);
    Route::patch('courses/{id}/toggle-status', [TeacherCourseController::class, 'toggleStatus']);

    // Section CRUD
    Route::post('sections/reorder', [TeacherSectionController::class, 'reorder']);
    Route::get('courses/{course_id}/sections', [TeacherSectionController::class, 'index']);
    Route::post('courses/{course_id}/sections', [TeacherSectionController::class, 'store']);
    Route::patch('sections/{id}', [TeacherSectionController::class, 'update']);
    Route::delete('sections/{id}', [TeacherSectionController::class, 'destroy']);
    Route::patch('sections/{id}/toggle-status', [TeacherSectionController::class, 'toggleStatus']);

    // Lesson CRUD (static routes TRƯỚC parameterized)
    Route::get('lessons/trashed', [TeacherLessonController::class, 'trashed']);
    Route::post('lessons/reorder', [TeacherLessonController::class, 'reorder']);
    Route::delete('lessons/bulk-delete', [TeacherLessonController::class, 'bulkDelete']);
    Route::post('lessons/bulk-action', [TeacherLessonController::class, 'bulkAction']);
    Route::patch('lessons/bulk-restore', [TeacherLessonController::class, 'bulkRestore']);
    Route::delete('lessons/bulk-force-delete', [TeacherLessonController::class, 'bulkForceDelete']);
    Route::get('courses/{course_id}/lessons', [TeacherLessonController::class, 'index']);
    Route::post('courses/{course_id}/lessons', [TeacherLessonController::class, 'store']);
    Route::get('lessons/{id}', [TeacherLessonController::class, 'show']);
    Route::patch('lessons/{id}', [TeacherLessonController::class, 'update']);
    Route::delete('lessons/{id}', [TeacherLessonController::class, 'destroy']);
    Route::patch('lessons/{id}/toggle-status', [TeacherLessonController::class, 'toggleStatus']);
    Route::patch('lessons/{id}/restore', [TeacherLessonController::class, 'restore']);
    Route::delete('lessons/{id}/force-delete', [TeacherLessonController::class, 'forceDelete']);
});
```

Note: Xóa route `Route::get('courses', ...)` cũ nếu còn tồn tại vì nay TeacherCoursesPage dùng endpoint hiện có (`GET /teacher/courses` trong TeacherPortalController).

- [ ] **Step 2: Chạy syntax check**

```bash
wsl.exe -d Ubuntu -- bash -c "php artisan route:list --path=teacher 2>&1" | cat
# Expected: danh sách routes /teacher/... không có lỗi
```

- [ ] **Step 3: Viết test**

Tạo `tests/Feature/Admin/TeacherCoursePortalTest.php`:

```php
<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Course\Models\Course;
use Modules\Teachers\Models\Teachers;
use Modules\Users\Models\User;
use Tests\TestCase;
use Tests\Traits\HasAdminUser;

class TeacherCoursePortalTest extends TestCase
{
    use RefreshDatabase, HasAdminUser;

    private User $teacher;
    private Teachers $teacherProfile;

    protected function setUp(): void
    {
        parent::setUp();

        // Tạo teacher user
        $this->teacher = User::forceCreate([
            'name'              => 'Teacher Test',
            'email'             => 'teacher_test@test.com',
            'password'          => bcrypt('password'),
            'email_verified_at' => now(),
        ]);

        $this->teacher->assignRole('teacher');
        $this->actingAs($this->teacher, 'admin');

        // Tạo teacher profile
        $this->teacherProfile = Teachers::create([
            'user_id' => $this->teacher->id,
            'name'    => 'Teacher Test',
            'email'   => 'teacher_test@test.com',
            'slug'    => 'teacher-test-' . $this->teacher->id,
        ]);
    }

    public function test_teacher_can_create_course(): void
    {
        $response = $this->postJson('/api/v1/teacher/courses', [
            'name'  => 'Laravel 12 Course',
            'slug'  => 'laravel-12-course-test',
            'price' => 0,
            'level' => 'beginner',
        ]);

        $response->assertStatus(201)->assertJsonPath('success', true);

        $this->assertDatabaseHas('courses', [
            'slug'       => 'laravel-12-course-test',
            'teacher_id' => $this->teacherProfile->id,
        ]);
    }

    public function test_teacher_cannot_see_other_teachers_course(): void
    {
        // Tạo teacher khác và course của họ
        $otherTeacherProfile = Teachers::create([
            'user_id' => null,
            'name'    => 'Other Teacher',
            'email'   => 'other@test.com',
            'slug'    => 'other-teacher',
        ]);

        $course = Course::create([
            'name'       => 'Other Course',
            'slug'       => 'other-course',
            'teacher_id' => $otherTeacherProfile->id,
            'price'      => 0,
            'level'      => 'beginner',
        ]);

        // Teacher hiện tại không thể xem course của người khác
        $response = $this->getJson("/api/v1/teacher/courses/{$course->id}");
        $response->assertStatus(404);
    }

    public function test_teacher_can_create_section_in_own_course(): void
    {
        $course = Course::create([
            'name'       => 'My Course',
            'slug'       => 'my-course-section-test',
            'teacher_id' => $this->teacherProfile->id,
            'price'      => 0,
            'level'      => 'beginner',
        ]);

        $response = $this->postJson("/api/v1/teacher/courses/{$course->id}/sections", [
            'title' => 'Chapter 1',
        ]);

        $response->assertStatus(201)->assertJsonPath('success', true);
        $this->assertDatabaseHas('sections', [
            'title'     => 'Chapter 1',
            'course_id' => $course->id,
        ]);
    }

    public function test_teacher_can_create_lesson_in_own_course(): void
    {
        $course = Course::create([
            'name'       => 'My Course Lesson',
            'slug'       => 'my-course-lesson-test',
            'teacher_id' => $this->teacherProfile->id,
            'price'      => 0,
            'level'      => 'beginner',
        ]);

        $response = $this->postJson("/api/v1/teacher/courses/{$course->id}/lessons", [
            'title' => 'Lesson 1',
            'type'  => 'text',
        ]);

        $response->assertStatus(201)->assertJsonPath('success', true);
        $this->assertDatabaseHas('lessons', [
            'course_id' => $course->id,
            'title'     => 'Lesson 1',
        ]);
    }
}
```

- [ ] **Step 4: Chạy tests**

```bash
wsl.exe -d Ubuntu -- bash -c "php artisan test tests/Feature/Admin/TeacherCoursePortalTest.php 2>&1" | cat
# Expected: 4 tests, 4 passed
```

- [ ] **Step 5: Chạy pint**

```bash
wsl.exe -d Ubuntu -- bash -c "./vendor/bin/pint 2>&1" | cat
# Expected: All files pass
```

- [ ] **Step 6: Commit**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning && git add e-learning-backend/Modules/Commission/app/Http/Controllers/Teacher/TeacherCourseController.php e-learning-backend/Modules/Commission/app/Http/Controllers/Teacher/TeacherSectionController.php e-learning-backend/Modules/Commission/app/Http/Controllers/Teacher/TeacherLessonController.php e-learning-backend/Modules/Commission/routes/api.php e-learning-backend/tests/Feature/Admin/TeacherCoursePortalTest.php && git commit -m 'feat(teacher): add teacher course/section/lesson CRUD endpoints with auto-ownership scope'" | cat
```

---

## Task 5: Frontend — Services

**Files:**
- Modify: `e-learning-frontend/src/services/commission.service.ts`
- Create: `e-learning-frontend/src/services/teacher-section.service.ts`
- Create: `e-learning-frontend/src/services/teacher-lesson.service.ts`

- [ ] **Step 1: Thêm teacher course methods vào commission.service.ts**

Thêm vào cuối object `commissionService` (trước dấu `}`):

```ts
  // Teacher course CRUD
  createCourse: (data: Record<string, unknown>) =>
    http.post('/teacher/courses', data),
  showCourse: (id: number) =>
    http.get(`/teacher/courses/${id}`),
  updateCourse: (id: number, data: Record<string, unknown>) =>
    http.patch(`/teacher/courses/${id}`, data),
  deleteCourse: (id: number) =>
    http.delete(`/teacher/courses/${id}`),
  toggleCourseStatus: (id: number) =>
    http.patch(`/teacher/courses/${id}/toggle-status`),
```

- [ ] **Step 2: Tạo teacher-section.service.ts**

```ts
import http from '@/plugins/axios'

export const teacherSectionService = {
  index: (courseId: number, params: Record<string, unknown> = {}) =>
    http.get(`/teacher/courses/${courseId}/sections`, { params }),

  store: (courseId: number, data: Record<string, unknown>) =>
    http.post(`/teacher/courses/${courseId}/sections`, data),

  update: (id: number, data: Record<string, unknown>) =>
    http.patch(`/teacher/sections/${id}`, data),

  destroy: (id: number) =>
    http.delete(`/teacher/sections/${id}`),

  toggleStatus: (id: number) =>
    http.patch(`/teacher/sections/${id}/toggle-status`),

  reorder: (orders: unknown[]) =>
    http.post('/teacher/sections/reorder', { orders }),
}
```

- [ ] **Step 3: Tạo teacher-lesson.service.ts**

```ts
import http from '@/plugins/axios'

export const teacherLessonService = {
  index: (courseId: number, params: Record<string, unknown> = {}) =>
    http.get(`/teacher/courses/${courseId}/lessons`, { params }),

  store: (courseId: number, data: Record<string, unknown>) =>
    http.post(`/teacher/courses/${courseId}/lessons`, data),

  show: (id: number) =>
    http.get(`/teacher/lessons/${id}`),

  update: (id: number, data: Record<string, unknown>) =>
    http.patch(`/teacher/lessons/${id}`, data),

  destroy: (id: number) =>
    http.delete(`/teacher/lessons/${id}`),

  toggleStatus: (id: number) =>
    http.patch(`/teacher/lessons/${id}/toggle-status`),

  trashed: (params: Record<string, unknown> = {}) =>
    http.get('/teacher/lessons/trashed', { params }),

  restore: (id: number) =>
    http.patch(`/teacher/lessons/${id}/restore`),

  forceDelete: (id: number) =>
    http.delete(`/teacher/lessons/${id}/force-delete`),

  reorder: (orders: unknown[]) =>
    http.post('/teacher/lessons/reorder', { orders }),

  bulkDelete: (ids: number[]) =>
    http.delete('/teacher/lessons/bulk-delete', { data: { ids } }),

  bulkAction: (data: Record<string, unknown>) =>
    http.post('/teacher/lessons/bulk-action', data),

  bulkRestore: (ids: number[]) =>
    http.patch('/teacher/lessons/bulk-restore', { ids }),

  bulkForceDelete: (ids: number[]) =>
    http.delete('/teacher/lessons/bulk-force-delete', { data: { ids } }),
}
```

- [ ] **Step 4: Lint check**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning/e-learning-frontend && npm run lint 2>&1" | cat
# Expected: 0 errors (warnings OK)
```

---

## Task 6: Frontend — useTeacherSectionsManager.ts

> **Context:** Clone của `useSectionsManager.ts` nhưng dùng `teacherSectionService` và `teacherLessonService`. Logic giữ nguyên hoàn toàn.

**Files:**
- Create: `e-learning-frontend/src/composables/useTeacherSectionsManager.ts`

- [ ] **Step 1: Tạo useTeacherSectionsManager.ts**

```ts
import { ref, reactive, computed } from 'vue'
import { useToast } from 'vue-toastification'
import { teacherSectionService } from '@/services/teacher-section.service'
import { teacherLessonService } from '@/services/teacher-lesson.service'
import { useDeleteConfirm } from '@/composables/useDeleteConfirm'
import { useFormErrors } from '@/composables/useFormErrors'
import type { AdminSection, AdminLesson, SectionForm } from '@/types/section-lesson.types'

export function useTeacherSectionsManager(courseId: number) {
  const toast = useToast()

  const sectionsList = ref<AdminSection[]>([])
  const orphanLessons = ref<AdminLesson[]>([])
  const loading = ref(true)
  const expandedSections = reactive(new Set<number | string>())

  const totalLessons = computed(() => {
    let total = orphanLessons.value.length
    for (const s of sectionsList.value) {
      total += (s.lessons || []).length
    }
    return total
  })

  async function fetchAll() {
    loading.value = true
    try {
      const [sectionsRes, lessonsRes] = await Promise.all([
        teacherSectionService.index(courseId, { per_page: 100 }),
        teacherLessonService.index(courseId, { per_page: 100 }),
      ])

      const allSections: AdminSection[] = ((sectionsRes.data as { data: AdminSection[] }).data || []).map((s) => ({
        ...s,
        lessons: [],
      }))

      const allLessons: AdminLesson[] = (lessonsRes.data as { data: AdminLesson[] }).data || []

      const sectionMap = new Map<number, AdminSection>()
      for (const s of allSections) {
        sectionMap.set(s.id, s)
      }

      const orphans: AdminLesson[] = []
      for (const lesson of allLessons) {
        if (lesson.section_id && sectionMap.has(lesson.section_id)) {
          sectionMap.get(lesson.section_id)!.lessons.push(lesson)
        } else {
          orphans.push(lesson)
        }
      }

      allSections.sort((a, b) => a.order - b.order)
      for (const s of allSections) {
        s.lessons.sort((a, b) => a.order - b.order)
      }

      sectionsList.value = allSections
      orphanLessons.value = orphans
    } catch (err) {
      console.error('Failed to fetch course content', err)
      toast.error('Không thể tải nội dung khóa học')
    } finally {
      loading.value = false
    }
  }

  function toggleExpand(id: number | string) {
    if (expandedSections.has(id)) expandedSections.delete(id)
    else expandedSections.add(id)
  }

  async function reorderSection(fromIdx: number, toIdx: number) {
    const arr = [...sectionsList.value]
    const [item] = arr.splice(fromIdx, 1)
    arr.splice(toIdx, 0, item)
    sectionsList.value = arr

    const orders = arr.map((s, i) => ({ id: s.id, order: i }))
    try {
      await teacherSectionService.reorder(orders)
    } catch {
      toast.error('Sắp xếp chương thất bại')
      fetchAll()
    }
  }

  const togglingSection = ref<number | null>(null)

  async function toggleSectionStatus(section: AdminSection) {
    togglingSection.value = section.id
    try {
      await teacherSectionService.toggleStatus(section.id)
      section.status = section.status === 1 ? 0 : 1
    } catch {
      toast.error('Không thể cập nhật trạng thái chương')
    } finally {
      togglingSection.value = null
    }
  }

  const showSectionModal = ref(false)
  const editingSectionId = ref<number | null>(null)
  const sSubmitting = ref(false)
  const {
    errors: sErrors,
    submitError: sSubmitError,
    handleApiError: handleSectionError,
    clearErrors: clearSectionErrors,
  } = useFormErrors()

  const defaultSForm = (): SectionForm => ({
    title: '',
    description: '',
    order: 0,
    status: 0,
  })
  const sForm = ref(defaultSForm())

  function openCreateSection() {
    editingSectionId.value = null
    sForm.value = defaultSForm()
    sForm.value.order = sectionsList.value.length
    clearSectionErrors()
    showSectionModal.value = true
  }

  function openEditSection(section: AdminSection) {
    editingSectionId.value = section.id
    sForm.value = {
      title: section.title,
      description: section.description || '',
      order: section.order,
      status: section.status,
    }
    clearSectionErrors()
    showSectionModal.value = true
  }

  async function submitSection() {
    clearSectionErrors()
    if (!sForm.value.title) {
      sErrors.value.title = 'Vui lòng nhập tiêu đề'
      return
    }

    sSubmitting.value = true
    const payload = {
      title: sForm.value.title,
      description: sForm.value.description || null,
      order: sForm.value.order,
      status: sForm.value.status,
    }

    try {
      if (editingSectionId.value) {
        await teacherSectionService.update(editingSectionId.value, payload)
        toast.success('Cập nhật chương thành công')
      } else {
        await teacherSectionService.store(courseId, payload)
        toast.success('Tạo chương thành công')
      }
      showSectionModal.value = false
      fetchAll()
    } catch (err: unknown) {
      handleSectionError(err)
    } finally {
      sSubmitting.value = false
    }
  }

  const deleteSection = useDeleteConfirm({
    async onConfirm(section: AdminSection) {
      await teacherSectionService.destroy(section.id)
      toast.success('Xóa chương thành công')
      fetchAll()
    },
  })

  function confirmDeleteSection(section: AdminSection) {
    deleteSection.confirm(section)
  }

  function isSectionAllSelected(section: AdminSection, selectedLessons?: number[]): boolean {
    const sel = selectedLessons ?? []
    if (!section.lessons || section.lessons.length === 0) return false
    return section.lessons.every((l: AdminLesson) => sel.includes(l.id))
  }

  function getSectionLessonIds(section: AdminSection): number[] {
    return (section.lessons || []).map((l: AdminLesson) => l.id)
  }

  return {
    sectionsList,
    orphanLessons,
    loading,
    expandedSections,
    totalLessons,
    togglingSection,
    showSectionModal,
    editingSectionId,
    sSubmitting,
    sErrors,
    sSubmitError,
    sForm,
    deleteSection,
    fetchAll,
    toggleExpand,
    reorderSection,
    toggleSectionStatus,
    openCreateSection,
    openEditSection,
    submitSection,
    confirmDeleteSection,
    isSectionAllSelected,
    getSectionLessonIds,
  }
}
```

---

## Task 7: Frontend — useTeacherLessonsManager.ts

> **Context:** Clone của `useLessonsManager.ts` dùng `teacherLessonService`. Logic giữ nguyên hoàn toàn, chỉ đổi service import.

**Files:**
- Create: `e-learning-frontend/src/composables/useTeacherLessonsManager.ts`

- [ ] **Step 1: Đọc toàn bộ nội dung useLessonsManager.ts**

```bash
cat e-learning-frontend/src/composables/useLessonsManager.ts
```

- [ ] **Step 2: Tạo useTeacherLessonsManager.ts**

Tạo file `e-learning-frontend/src/composables/useTeacherLessonsManager.ts` với nội dung giống hệt `useLessonsManager.ts` nhưng thay đổi:

**Dòng import (thay thế):**
```ts
// Xóa:
import { lessonService } from '@/services/lesson.service'

// Thêm:
import { teacherLessonService as lessonService } from '@/services/teacher-lesson.service'
```

**Tên export function (thay thế):**
```ts
// Xóa:
export function useLessonsManager(

// Thành:
export function useTeacherLessonsManager(
```

Tất cả các lời gọi `lessonService.xxx()` bên trong giữ nguyên vì đã alias `teacherLessonService as lessonService`.

- [ ] **Step 3: Lint check**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning/e-learning-frontend && npm run lint 2>&1" | cat
# Expected: 0 errors
```

---

## Task 8: Frontend — TeacherSectionsLessonsManager.vue

> **Context:** Clone của `SectionsLessonsManager.vue`. Template giữ nguyên 100%. Script chỉ thay 2 import composable.

**Files:**
- Create: `e-learning-frontend/src/components/shared/teacher/TeacherSectionsLessonsManager.vue`

- [ ] **Step 1: Đọc toàn bộ SectionsLessonsManager.vue**

```bash
cat e-learning-frontend/src/components/shared/admin/SectionsLessonsManager.vue
```

- [ ] **Step 2: Tạo TeacherSectionsLessonsManager.vue**

Tạo thư mục và file:
```bash
mkdir -p e-learning-frontend/src/components/shared/teacher
```

Tạo `TeacherSectionsLessonsManager.vue` với nội dung giống hệt `SectionsLessonsManager.vue`, chỉ thay 2 dòng trong `<script setup>`:

```ts
// Xóa:
import { useSectionsManager } from '@/composables/useSectionsManager'
import { useLessonsManager } from '@/composables/useLessonsManager'

// Thêm:
import { useTeacherSectionsManager } from '@/composables/useTeacherSectionsManager'
import { useTeacherLessonsManager } from '@/composables/useTeacherLessonsManager'
```

Và thay 2 lời gọi trong script:
```ts
// Xóa:
} = useSectionsManager(props.courseId)
// ...
} = useLessonsManager(

// Thành:
} = useTeacherSectionsManager(props.courseId)
// ...
} = useTeacherLessonsManager(
```

Template `<template>` và tất cả imports khác (SectionItem, LessonList, BulkActions, modals...) giữ nguyên.

- [ ] **Step 3: Lint + Build check**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning/e-learning-frontend && npm run lint 2>&1" | cat
# Expected: 0 errors
```

---

## Task 9: Frontend — TeacherCourseFormPage.vue

> **Context:** Tương tự `CourseFormPage.vue` admin nhưng: không load danh sách teachers (teacher_id auto-set backend), back về `/teacher/courses`, gọi `commissionService.createCourse()`/`showCourse()`/`updateCourse()`, tab Nội dung dùng `TeacherSectionsLessonsManager`.
>
> `CourseInfoForm.vue` đã có `isTeacherOnly` computed tự ẩn dropdown chọn giảng viên khi user là teacher. Không cần sửa component này.

**Files:**
- Create: `e-learning-frontend/src/views/teacher/TeacherCourseFormPage.vue`

- [ ] **Step 1: Tạo TeacherCourseFormPage.vue**

```vue
<template>
  <div class="p-6 max-w-4xl mx-auto">
    <!-- Header -->
    <div class="flex items-center gap-3 mb-6">
      <router-link
        to="/teacher/courses"
        class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg dark:hover:bg-white/10 transition-colors"
      >
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
        </svg>
      </router-link>
      <div>
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">
          {{ isEdit ? (activeTab === 'lessons' ? 'Nội dung khóa học' : 'Thông tin khóa học') : 'Thêm khóa học mới' }}
        </h2>
        <p v-if="isEdit" class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
          {{ form.name || `ID: ${courseId}` }}
        </p>
      </div>
    </div>

    <!-- Tabs (chỉ hiện khi edit) -->
    <div v-if="isEdit" class="flex gap-1 mb-6 bg-gray-100 dark:bg-gray-800 p-1 rounded-xl w-fit">
      <button
        v-for="tab in tabs"
        :key="tab.key"
        @click="activeTab = tab.key"
        :class="
          activeTab === tab.key
            ? 'bg-white dark:bg-gray-700 text-gray-800 dark:text-white/90 shadow-sm'
            : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'
        "
        class="px-4 py-1.5 text-sm rounded-lg transition-all"
      >
        {{ tab.label }}
      </button>
    </div>

    <!-- Tab: Thông tin -->
    <div v-if="activeTab === 'info'">
      <div v-if="pageLoading" class="flex justify-center py-10">
        <svg class="animate-spin w-6 h-6 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
        </svg>
      </div>
      <CourseInfoForm
        v-else
        :form="form"
        @update:form="form = $event"
        :errors="formErrors"
        :teachers="[]"
        :flat-categories="flatCategories"
        :is-edit="isEdit"
        :slug-unlocked="slugUnlocked"
        @update:slug-unlocked="slugUnlocked = $event"
        :submit-error="submitError"
        :submitting="submitting"
        @submit="submitForm"
        @auto-slug="autoSlug"
      />
    </div>

    <!-- Tab: Nội dung (Sections + Lessons) -->
    <div v-if="activeTab === 'lessons' && isEdit">
      <TeacherSectionsLessonsManager :course-id="courseId ?? 0" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import { commissionService } from '@/services/commission.service'
import { categoryService } from '@/services/category.service'
import CourseInfoForm from '@/components/forms/CourseInfoForm.vue'
import TeacherSectionsLessonsManager from '@/components/shared/teacher/TeacherSectionsLessonsManager.vue'
import { useFormErrors } from '@/composables/useFormErrors'

const route = useRoute()
const router = useRouter()
const toast = useToast()

const courseId = computed(() => (route.params.id ? Number(route.params.id) : null))
const isEdit = computed(() => !!courseId.value)
const initialTab = route.query.tab === 'lessons' ? 'lessons' : 'info'
const activeTab = ref<'info' | 'lessons'>(initialTab as 'info' | 'lessons')
const tabs = [
  { key: 'info' as const, label: 'Thông tin' },
  { key: 'lessons' as const, label: 'Nội dung' },
]

const pageLoading = ref(false)
const submitting = ref(false)
const slugUnlocked = ref(false)
const { errors: formErrors, submitError, clearErrors, handleApiError } = useFormErrors()

const flatCategories = ref<{ id: number; name: string; depth: number }[]>([])

const defaultForm = () => ({
  name: '',
  slug: '',
  description: '',
  teacher_id: null as number | null,
  category_id: null as number | null,
  level: 'beginner' as string,
  status: 0 as number,
  price: 0 as number,
  sale_price: null as number | null,
  thumbnail: '' as string,
})
const form = ref(defaultForm())

watch(courseId, (newId, oldId) => {
  if (newId !== oldId) {
    form.value = defaultForm()
    clearErrors()
    activeTab.value = 'info'
  }
})

function autoSlug() {
  const slug = form.value.name
    .toLowerCase()
    .normalize('NFD')
    .replace(/[̀-ͯ]/g, '')
    .replace(/đ/g, 'd')
    .replace(/[^a-z0-9\s-]/g, '')
    .trim()
    .replace(/\s+/g, '-')
  form.value = { ...form.value, slug }
}

onMounted(async () => {
  const catRes = await categoryService.flatTree().catch(() => null)
  if (catRes) flatCategories.value = catRes.data.data

  if (isEdit.value) {
    pageLoading.value = true
    try {
      const res = await commissionService.showCourse(courseId.value!)
      const c = res.data.data
      form.value = {
        name: c.name,
        slug: c.slug,
        description: c.description || '',
        teacher_id: c.teacher?.id ?? null,
        category_id: c.categories?.[0]?.id ?? null,
        level: c.level,
        status: c.status,
        price: Number(c.price),
        sale_price: c.sale_price ? Number(c.sale_price) : null,
        thumbnail: c.thumbnail || '',
      }
    } catch {
      toast.error('Không thể tải thông tin khóa học.')
      router.push('/teacher/courses')
    } finally {
      pageLoading.value = false
    }
  }
})

async function submitForm() {
  clearErrors()
  submitting.value = true

  const payload: Record<string, unknown> = {
    name: form.value.name,
    slug: form.value.slug,
    description: form.value.description || null,
    price: form.value.price,
    sale_price: form.value.sale_price ?? null,
    level: form.value.level,
    status: form.value.status,
    thumbnail: form.value.thumbnail || null,
  }
  if (form.value.category_id) {
    payload.category_ids = [form.value.category_id]
  }

  try {
    if (isEdit.value) {
      await commissionService.updateCourse(courseId.value!, payload)
      toast.success('Cập nhật khóa học thành công!')
    } else {
      const res = await commissionService.createCourse(payload)
      const newId = res.data.data.id
      toast.success('Tạo khóa học thành công!')
      router.push(`/teacher/courses/${newId}/edit?tab=lessons`)
    }
  } catch (err: unknown) {
    handleApiError(err)
  } finally {
    submitting.value = false
  }
}
</script>
```

- [ ] **Step 2: Lint check**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning/e-learning-frontend && npm run lint 2>&1" | cat
# Expected: 0 errors
```

---

## Task 10: Frontend — TeacherCoursesPage.vue + Router

**Files:**
- Modify: `e-learning-frontend/src/views/teacher/TeacherCoursesPage.vue`
- Modify: `e-learning-frontend/src/router/index.js`

- [ ] **Step 1: Cập nhật TeacherCoursesPage.vue**

Thay toàn bộ nội dung file:

```vue
<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Khóa học của tôi</h1>
      <router-link
        to="/teacher/courses/create"
        class="flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition-colors"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
        </svg>
        Thêm khóa học
      </router-link>
    </div>

    <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-400">
          <tr>
            <th class="px-5 py-3 text-left font-semibold">Tên khóa học</th>
            <th class="px-5 py-3 text-right font-semibold">Học viên</th>
            <th class="px-5 py-3 text-right font-semibold">Giá</th>
            <th class="px-5 py-3 text-center font-semibold">Trạng thái</th>
            <th class="px-5 py-3 text-center font-semibold">Hành động</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading">
            <td colspan="5" class="px-5 py-10 text-center text-gray-400">Đang tải...</td>
          </tr>
          <tr v-else-if="!courses.length">
            <td colspan="5" class="px-5 py-10 text-center text-gray-400">Chưa có khóa học nào.</td>
          </tr>
          <tr
            v-for="course in courses"
            :key="course.id"
            class="border-t border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/50"
          >
            <td class="px-5 py-3">
              <p class="font-medium text-gray-900 dark:text-white">{{ course.name }}</p>
              <p class="text-xs text-gray-400">{{ course.slug }}</p>
            </td>
            <td class="px-5 py-3 text-right text-gray-700 dark:text-gray-300">
              {{ course.total_students.toLocaleString('vi-VN') }}
            </td>
            <td class="px-5 py-3 text-right">
              <span v-if="course.sale_price" class="text-green-700 dark:text-green-400 font-medium">
                {{ Number(course.sale_price).toLocaleString('vi-VN') }} ₫
              </span>
              <span v-else class="text-gray-700 dark:text-gray-300 font-medium">
                {{ Number(course.price).toLocaleString('vi-VN') }} ₫
              </span>
            </td>
            <td class="px-5 py-3 text-center">
              <span
                :class="[
                  'inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium',
                  course.status === 1
                    ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                    : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
                ]"
              >
                {{ course.status === 1 ? 'Đã xuất bản' : 'Bản nháp' }}
              </span>
            </td>
            <td class="px-5 py-3 text-center">
              <router-link
                :to="`/teacher/courses/${course.id}/edit`"
                class="inline-flex items-center gap-1 px-3 py-1 text-xs text-blue-600 dark:text-blue-400 border border-blue-200 dark:border-blue-700 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors"
              >
                Sửa
              </router-link>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div
        v-if="pagination.last_page > 1"
        class="px-5 py-3 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between text-sm text-gray-500"
      >
        <span>Tổng {{ pagination.total }} khóa học</span>
        <div class="flex gap-1">
          <button
            v-for="page in pagination.last_page"
            :key="page"
            @click="changePage(page)"
            :class="[
              'px-3 py-1 rounded text-sm',
              page === pagination.current_page
                ? 'bg-blue-600 text-white'
                : 'hover:bg-gray-100 dark:hover:bg-gray-700',
            ]"
          >
            {{ page }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import { useTeacherCourses } from '@/composables/useTeacherCourses'

const { courses, pagination, loading, loadCourses, changePage } = useTeacherCourses()
onMounted(() => loadCourses())
</script>
```

- [ ] **Step 2: Thêm routes vào router/index.js**

Thêm 2 routes sau `{ path: 'courses', name: 'teacher.courses', ... }`:

```js
{
  path: 'courses/create',
  name: 'teacher.courses.create',
  component: () => import('@/views/teacher/TeacherCourseFormPage.vue'),
},
{
  path: 'courses/:id/edit',
  name: 'teacher.courses.edit',
  component: () => import('@/views/teacher/TeacherCourseFormPage.vue'),
},
```

**Lưu ý quan trọng:** `courses/create` phải khai báo TRƯỚC `courses/:id/edit` để Vue Router không match `create` như là `:id`.

- [ ] **Step 3: Lint + Build**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning/e-learning-frontend && npm run lint 2>&1" | cat
# Expected: 0 errors

wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning/e-learning-frontend && npm run build 2>&1" | cat
# Expected: Build succeeded, no errors
```

- [ ] **Step 4: Commit toàn bộ frontend**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning && git add e-learning-frontend/src/services/commission.service.ts e-learning-frontend/src/services/teacher-section.service.ts e-learning-frontend/src/services/teacher-lesson.service.ts e-learning-frontend/src/composables/useTeacherSectionsManager.ts e-learning-frontend/src/composables/useTeacherLessonsManager.ts e-learning-frontend/src/components/shared/teacher/TeacherSectionsLessonsManager.vue e-learning-frontend/src/views/teacher/TeacherCourseFormPage.vue e-learning-frontend/src/views/teacher/TeacherCoursesPage.vue e-learning-frontend/src/router/index.js && git commit -m 'feat(teacher): add course create/edit with sections and lessons management in teacher portal'" | cat
```

---

## Checklist tổng kết

- [ ] Backend: TeacherCourseController (5 methods)
- [ ] Backend: TeacherSectionController (6 methods)
- [ ] Backend: TeacherLessonController (14 methods)
- [ ] Backend: Commission routes cập nhật
- [ ] Backend: 4 tests pass
- [ ] Frontend: commission.service.ts (5 methods mới)
- [ ] Frontend: teacher-section.service.ts (6 methods)
- [ ] Frontend: teacher-lesson.service.ts (14 methods)
- [ ] Frontend: useTeacherSectionsManager.ts
- [ ] Frontend: useTeacherLessonsManager.ts
- [ ] Frontend: TeacherSectionsLessonsManager.vue
- [ ] Frontend: TeacherCourseFormPage.vue
- [ ] Frontend: TeacherCoursesPage.vue (nút + edit links)
- [ ] Frontend: Router (2 routes mới)
- [ ] Build pass, 0 lint errors
