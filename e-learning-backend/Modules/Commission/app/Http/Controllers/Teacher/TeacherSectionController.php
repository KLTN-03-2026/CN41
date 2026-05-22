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
use Modules\Lessons\Models\Section;
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
        $ids = collect($request->orders)->pluck('id')->toArray();
        $ownedCount = Section::whereIn('id', $ids)->count(); // ScopesToTeacher filters to teacher's own sections

        if ($ownedCount !== count($ids)) {
            return $this->error('Một số chương không thuộc khóa học của bạn.', 403);
        }

        $this->repository->reorder($request->orders);

        return $this->success(null, 'Sắp xếp chương thành công.');
    }
}
