<?php

namespace Modules\Lessons\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Modules\Course\Repositories\CourseRepositoryInterface;
use Modules\Lessons\Http\Requests\BulkActionSectionRequest;
use Modules\Lessons\Http\Requests\BulkDeleteSectionRequest;
use Modules\Lessons\Http\Requests\BulkForceDeleteSectionRequest;
use Modules\Lessons\Http\Requests\BulkRestoreSectionRequest;
use Modules\Lessons\Http\Requests\IndexSectionRequest;
use Modules\Lessons\Http\Requests\ReorderSectionRequest;
use Modules\Lessons\Http\Requests\StoreSectionRequest;
use Modules\Lessons\Http\Requests\UpdateSectionRequest;
use Modules\Lessons\Http\Resources\SectionResource;
use Modules\Lessons\Repositories\SectionRepositoryInterface;

class SectionController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected SectionRepositoryInterface $repository,
        protected CourseRepositoryInterface $courseRepo,
    ) {}

    public function index(IndexSectionRequest $request, int $course_id): JsonResponse
    {
        $this->courseRepo->findOrFail($course_id);

        $perPage = (int) $request->query('per_page', 15);
        $filters = $request->only(['status']);

        $data = $this->repository->getByCourse($course_id, $filters, $perPage);

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

    public function show(int $id): JsonResponse
    {
        $section = $this->repository->findOrFail($id);
        $section->load('lessons');

        return $this->success(new SectionResource($section), 'Lấy chi tiết chương thành công.');
    }

    public function update(UpdateSectionRequest $request, int $id): JsonResponse
    {
        $section = $this->repository->update($id, $request->validated());

        return $this->success(new SectionResource($section), 'Cập nhật chương thành công.');
    }

    public function destroy(int $id): JsonResponse
    {
        $this->repository->delete($id);

        return $this->success(null, 'Xóa chương thành công.');
    }

    public function toggleStatus(int $id): JsonResponse
    {
        $section = $this->repository->toggleStatus($id);

        return $this->success(
            ['id' => $section->id, 'status' => $section->status],
            'Cập nhật trạng thái chương thành công.'
        );
    }

    public function trashed(IndexSectionRequest $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);
        $data = $this->repository->paginateTrashed($perPage);

        return $this->paginated($data, 'Lấy danh sách chương đã xóa thành công.');
    }

    public function restore(int $id): JsonResponse
    {
        $this->repository->findTrashed($id);
        $this->repository->restore($id);

        return $this->success(null, 'Khôi phục chương thành công.');
    }

    public function forceDelete(int $id): JsonResponse
    {
        $this->repository->forceDeleteById($id);

        return $this->success(null, 'Xóa vĩnh viễn chương thành công.');
    }

    public function reorder(ReorderSectionRequest $request): JsonResponse
    {
        $ids = collect($request->orders)->pluck('id')->toArray();
        $courseIds = $this->repository->getDistinctCourseIds($ids);

        if ($courseIds->count() > 1) {
            return $this->error('Không thể sắp xếp chương của nhiều khóa học cùng lúc.', 422);
        }

        $this->repository->reorder($request->orders);

        return $this->success(null, 'Sắp xếp chương thành công.');
    }

    public function bulkAction(BulkActionSectionRequest $request): JsonResponse
    {
        $count = $this->repository->actionMany($request->ids, $request->action);

        return $this->success(null, "Cập nhật trạng thái hàng loạt {$count} chương thành công.");
    }

    public function bulkDelete(BulkDeleteSectionRequest $request): JsonResponse
    {
        $count = $this->repository->deleteMany($request->ids);

        return $this->success(null, "Xóa hàng loạt {$count} chương thành công.");
    }

    public function bulkRestore(BulkRestoreSectionRequest $request): JsonResponse
    {
        $count = $this->repository->restoreMany($request->ids);

        return $this->success(null, "Khôi phục hàng loạt {$count} chương thành công.");
    }

    public function bulkForceDelete(BulkForceDeleteSectionRequest $request): JsonResponse
    {
        $count = $this->repository->forceDeleteMany($request->ids);

        return $this->success(null, "Xóa vĩnh viễn hàng loạt {$count} chương thành công.");
    }

    public function curriculum(string $slug): JsonResponse
    {
        $course = $this->courseRepo->findBySlug($slug, true);

        if (! $course) {
            return $this->error('Khóa học không tồn tại.', 404);
        }

        $sections = $this->repository->getPublishedWithLessons($course->id)
            ->map(fn ($section) => [
                'id' => $section->id,
                'title' => $section->title,
                'description' => $section->description,
                'order' => $section->order,
                'lessons' => $section->lessons->map(fn ($lesson) => [
                    'id' => $lesson->id,
                    'section_id' => $lesson->section_id,
                    'title' => $lesson->title,
                    'slug' => $lesson->slug,
                    'type' => $lesson->type,
                    'order' => $lesson->order,
                    'is_preview' => $lesson->is_preview,
                    'duration' => $lesson->duration,
                ])->values(),
            ]);

        return $this->success([
            'course_id' => $course->id,
            'sections' => $sections,
        ], 'Lấy curriculum thành công.');
    }
}
