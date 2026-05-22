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
