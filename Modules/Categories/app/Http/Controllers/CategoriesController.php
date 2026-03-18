<?php

namespace Modules\Categories\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Categories\Http\Requests\StoreCategoriesRequest;
use Modules\Categories\Http\Requests\UpdateCategoriesRequest;
use Modules\Categories\Repositories\CategoriesRepositoryInterface;

class CategoriesController extends Controller
{
    use ApiResponse;

    protected CategoriesRepositoryInterface $repository;

    public function __construct(CategoriesRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Danh sách Categories (có phân trang).
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);
        $data = $this->repository->paginate($perPage);

        return $this->paginated($data);
    }

    /**
     * Tạo mới Categories.
     */
    public function store(StoreCategoriesRequest $request): JsonResponse
    {
        $data = $this->repository->create($request->validated());

        return $this->success($data, 'Categories đã được tạo thành công.', 201);
    }

    /**
     * Chi tiết Categories.
     */
    public function show(int $id): JsonResponse
    {
        $data = $this->repository->findOrFail($id);

        return $this->success($data);
    }

    /**
     * Cập nhật Categories.
     */
    public function update(UpdateCategoriesRequest $request, int $id): JsonResponse
    {
        $data = $this->repository->update($id, $request->validated());

        return $this->success($data, 'Categories đã được cập nhật thành công.');
    }

    /**
     * Xoá Categories.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->repository->delete($id);

        return $this->success(null, 'Categories đã được xoá thành công.');
    }
}
