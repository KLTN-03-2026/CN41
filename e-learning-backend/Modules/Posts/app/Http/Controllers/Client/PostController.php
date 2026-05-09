<?php

namespace Modules\Posts\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
        $posts = $this->repository->getPublished(
            $request->all(),
            $request->get('per_page', 12)
        );

        $posts->setCollection(PostResource::collection($posts->getCollection())->collection);

        return $this->paginated($posts, 'Lấy danh sách bài viết thành công.');
    }

    public function show(string $slug): JsonResponse
    {
        $post = $this->repository->findBySlug($slug);

        if (! $post) {
            return $this->error('Bài viết không tồn tại hoặc đã bị ẩn.', 404);
        }

        return $this->success(
            new PostResource($post),
            'Lấy chi tiết bài viết thành công.'
        );
    }

    public function incrementViews(int $id): JsonResponse
    {
        $this->repository->incrementViews($id);

        return $this->success(null, 'Tăng lượt xem thành công.');
    }
}
