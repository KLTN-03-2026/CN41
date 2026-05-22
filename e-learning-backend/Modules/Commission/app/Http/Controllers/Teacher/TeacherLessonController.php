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
        $this->courseRepo->findOrFail($course_id); // auto-scoped → 404 if not owner

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
        DB::transaction(function () use ($id) {
            $lesson = $this->repository->findOrFail($id); // auto-scoped, inside transaction
            $lesson->delete(); // soft-delete directly on model, avoids second findOrFail
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
        DB::transaction(function () use ($id) {
            $lesson = $this->repository->findTrashed($id); // auto-scoped, inside transaction
            $this->repository->restore($id);
            Course::withoutGlobalScope('teacher_scope')
                ->where('id', $lesson->course_id)->increment('total_lessons');
        });

        return $this->success(null, 'Khôi phục bài giảng thành công.');
    }

    public function forceDelete(int $id): JsonResponse
    {
        $this->repository->forceDeleteById($id); // auto-scoped via withTrashed inside BaseRepository

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
        $count = $this->repository->forceDeleteMany($request->ids); // auto-scoped via withTrashed

        return $this->success(null, "Xóa vĩnh viễn hàng loạt {$count} bài giảng thành công.");
    }
}
