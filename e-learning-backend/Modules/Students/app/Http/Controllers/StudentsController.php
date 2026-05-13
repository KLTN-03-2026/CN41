<?php

namespace Modules\Students\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Modules\Students\Http\Requests\BulkDeleteStudentsRequest;
use Modules\Students\Http\Requests\BulkForceDeleteStudentsRequest;
use Modules\Students\Http\Requests\BulkRestoreStudentsRequest;
use Modules\Students\Http\Requests\IndexStudentsRequest;
use Modules\Students\Http\Requests\StoreStudentsRequest;
use Modules\Students\Http\Requests\UpdateStudentsRequest;
use Modules\Students\Http\Resources\StudentResource;
use Modules\Students\Repositories\StudentsRepositoryInterface;

class StudentsController extends Controller
{
    use ApiResponse;

    protected StudentsRepositoryInterface $repository;

    public function __construct(StudentsRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Danh sách Students (có phân trang).
     */
    public function index(IndexStudentsRequest $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);
        $filters = $request->only(['search']);

        $data = $this->repository->getFiltered($filters, $perPage);
        $data->setCollection(StudentResource::collection($data->getCollection())->collection);

        return $this->paginated($data);
    }

    /**
     * Tạo mới Student (Admin tạo thủ công).
     */
    public function store(StoreStudentsRequest $request): JsonResponse
    {
        $student = $this->repository->create($request->validated());

        return $this->success(new StudentResource($student), 'Student đã được tạo thành công.', 201);
    }

    /**
     * Chi tiết Student (kèm khóa học đã đăng ký).
     */
    public function show(int $id): JsonResponse
    {
        $student = $this->repository->findOrFail($id);
        $student->load(['enrolledCourses:id,name,slug,thumbnail,price,sale_price', 'orders']);

        return $this->success(new StudentResource($student));
    }

    /**
     * Cập nhật Student.
     */
    public function update(UpdateStudentsRequest $request, int $id): JsonResponse
    {
        $student = $this->repository->update($id, $request->validated());

        return $this->success(new StudentResource($student), 'Student đã được cập nhật thành công.');
    }

    /**
     * Xoá Student (soft delete).
     */
    public function destroy(int $id): JsonResponse
    {
        $this->repository->delete($id);

        return $this->success(null, 'Student đã được xoá thành công.');
    }

    /**
     * Danh sách Students đã bị soft-delete (thùng rác).
     */
    public function trashed(IndexStudentsRequest $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);
        $data = $this->repository->paginateTrashed($perPage);
        $data->setCollection(StudentResource::collection($data->getCollection())->collection);

        return $this->paginated($data);
    }

    /**
     * Khôi phục một Student đã soft-delete.
     */
    public function restore(int $id): JsonResponse
    {
        $this->repository->restore($id);

        return $this->success(null, 'Student đã được khôi phục thành công.');
    }

    /**
     * Xoá vĩnh viễn một Student.
     */
    public function forceDelete(int $id): JsonResponse
    {
        $this->repository->forceDeleteById($id);

        return $this->success(null, 'Student đã bị xoá vĩnh viễn.');
    }

    /**
     * Xoá nhiều Students cùng lúc (soft delete).
     */
    public function bulkDelete(BulkDeleteStudentsRequest $request): JsonResponse
    {
        $deleted = DB::transaction(fn () => $this->repository->deleteMany($request->ids));

        return $this->success(
            ['deleted_count' => $deleted, 'deleted_ids' => $request->ids],
            "Đã xoá {$deleted} student thành công."
        );
    }

    /**
     * Khôi phục nhiều Students đã soft-delete.
     */
    public function bulkRestore(BulkRestoreStudentsRequest $request): JsonResponse
    {
        $restored = DB::transaction(fn () => $this->repository->restoreMany($request->ids));

        return $this->success(
            ['restored_count' => $restored, 'restored_ids' => $request->ids],
            "Đã khôi phục {$restored} student thành công."
        );
    }

    /**
     * Xoá vĩnh viễn nhiều Students.
     */
    public function bulkForceDelete(BulkForceDeleteStudentsRequest $request): JsonResponse
    {
        $deleted = DB::transaction(fn () => $this->repository->forceDeleteMany($request->ids));

        return $this->success(
            ['deleted_count' => $deleted, 'deleted_ids' => $request->ids],
            "Đã xoá vĩnh viễn {$deleted} student."
        );
    }
}
