<?php

namespace Modules\Posts\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Posts\Http\Requests\Admin\StoreTagRequest;
use Modules\Posts\Http\Requests\Admin\UpdateTagRequest;
use Modules\Posts\Http\Resources\TagResource;
use Modules\Posts\Repositories\TagRepositoryInterface;

class TagController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected TagRepositoryInterface $repository
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tags = $this->repository->getFiltered(
            $request->all(),
            $request->get('per_page', 15)
        );

        $tags->setCollection(TagResource::collection($tags->getCollection())->collection);

        return $this->paginated($tags, 'Lấy danh sách tags thành công.');
    }

    public function store(StoreTagRequest $request): JsonResponse
    {
        $tag = $this->repository->create($request->validated());

        return $this->success(
            new TagResource($tag),
            'Tạo tag thành công.',
            201
        );
    }

    public function show(int $id): JsonResponse
    {
        $tag = $this->repository->findOrFail($id);

        return $this->success(
            new TagResource($tag),
            'Lấy thông tin tag thành công.'
        );
    }

    public function update(UpdateTagRequest $request, int $id): JsonResponse
    {
        $tag = $this->repository->update($id, $request->validated());

        return $this->success(
            new TagResource($tag),
            'Cập nhật tag thành công.'
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $this->repository->delete($id);

        return $this->success(null, 'Xóa tag thành công.');
    }
}
