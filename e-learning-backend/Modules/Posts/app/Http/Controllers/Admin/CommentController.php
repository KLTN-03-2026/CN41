<?php

namespace Modules\Posts\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Posts\Http\Resources\PostCommentResource;
use Modules\Posts\Repositories\CommentRepositoryInterface;

class CommentController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected CommentRepositoryInterface $repository
    ) {}

    public function index(Request $request): JsonResponse
    {
        $comments = $this->repository->getFiltered(
            $request->all(),
            $request->get('per_page', 15)
        );

        $comments->setCollection(PostCommentResource::collection($comments->load(['commenter', 'post']))->collection);

        return $this->paginated($comments, 'Lấy danh sách bình luận thành công.');
    }

    public function toggleApproval(int $id): JsonResponse
    {
        $comment = $this->repository->toggleApproval($id);

        return $this->success(
            new PostCommentResource($comment->load(['post', 'commenter'])),
            'Thay đổi trạng thái duyệt bình luận thành công.'
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $this->repository->delete($id);

        return $this->success(null, 'Xóa bình luận thành công.');
    }
}
