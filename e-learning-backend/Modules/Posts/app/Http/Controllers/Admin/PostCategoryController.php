<?php

namespace Modules\Posts\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Posts\Http\Requests\Admin\StorePostCategoryRequest;
use Modules\Posts\Http\Requests\Admin\UpdatePostCategoryRequest;
use Modules\Posts\Http\Resources\PostCategoryResource;
use Modules\Posts\Repositories\PostCategoryRepositoryInterface;

class PostCategoryController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected PostCategoryRepositoryInterface $repository
    ) {}

    public function index(Request $request): JsonResponse
    {
        $categories = $this->repository->getFiltered(
            $request->all(),
            $request->get('per_page', 15)
        );

        $categories->setCollection(PostCategoryResource::collection($categories->getCollection())->collection);

        return $this->paginated($categories, 'Lấy danh sách danh mục bài viết thành công.');
    }

    public function store(StorePostCategoryRequest $request): JsonResponse
    {
        $category = $this->repository->create($request->validated());

        return $this->success(
            new PostCategoryResource($category),
            'Tạo danh mục bài viết thành công.',
            201
        );
    }

    public function show(int $id): JsonResponse
    {
        $category = $this->repository->findOrFail($id);

        return $this->success(
            new PostCategoryResource($category),
            'Lấy thông tin danh mục bài viết thành công.'
        );
    }

    public function update(UpdatePostCategoryRequest $request, int $id): JsonResponse
    {
        $category = $this->repository->update($id, $request->validated());

        return $this->success(
            new PostCategoryResource($category),
            'Cập nhật danh mục bài viết thành công.'
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $this->repository->delete($id);

        return $this->success(null, 'Xóa danh mục bài viết thành công.');
    }
}
