<?php

namespace Modules\Posts\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Posts\Http\Requests\Admin\StorePostRequest;
use Modules\Posts\Http\Requests\Admin\UpdatePostRequest;
use Modules\Posts\Http\Resources\PostResource;
use Modules\Posts\Repositories\PostRepositoryInterface;

class PostController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected PostRepositoryInterface $repository
    ) {}

    public function index(Request $request): JsonResponse
    {
        $posts = $this->repository->getFiltered(
            $request->all(),
            $request->get('per_page', 15)
        );

        $posts->setCollection(PostResource::collection($posts->getCollection())->collection);

        return $this->paginated($posts, 'Lấy danh sách bài viết thành công.');
    }

    public function store(StorePostRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['author_id'] = auth()->id();
        
        if (!empty($data['is_published'])) {
            $data['published_at'] = now();
        }

        $post = $this->repository->create($data);

        if (!empty($data['tag_ids'])) {
            $post->tags()->sync($data['tag_ids']);
        }

        return $this->success(
            new PostResource($post->load(['author', 'category', 'tags'])),
            'Tạo bài viết thành công.',
            201
        );
    }

    public function show(int $id): JsonResponse
    {
        $post = $this->repository->findOrFail($id);
        $post->load(['author', 'category', 'tags']);

        return $this->success(
            new PostResource($post),
            'Lấy thông tin bài viết thành công.'
        );
    }

    public function update(UpdatePostRequest $request, int $id): JsonResponse
    {
        $data = $request->validated();
        $post = $this->repository->findOrFail($id);

        if (isset($data['is_published']) && $data['is_published'] && !$post->is_published && !$post->published_at) {
            $data['published_at'] = now();
        }

        $post = $this->repository->update($id, $data);

        if (isset($data['tag_ids'])) {
            $post->tags()->sync($data['tag_ids']);
        }

        return $this->success(
            new PostResource($post->load(['author', 'category', 'tags'])),
            'Cập nhật bài viết thành công.'
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $this->repository->delete($id);

        return $this->success(null, 'Xóa bài viết thành công.');
    }

    public function togglePublish(int $id): JsonResponse
    {
        $post = $this->repository->togglePublish($id);

        return $this->success(
            new PostResource($post->load(['author', 'category', 'tags'])),
            'Thay đổi trạng thái xuất bản thành công.'
        );
    }
}
