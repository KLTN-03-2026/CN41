<?php

namespace Modules\Posts\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Modules\Posts\Http\Requests\Client\StoreCommentRequest;
use Modules\Posts\Http\Resources\PostCommentResource;
use Modules\Posts\Repositories\CommentRepositoryInterface;

class CommentController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected CommentRepositoryInterface $repository
    ) {}

    public function store(StoreCommentRequest $request, int $postId): JsonResponse
    {
        $data = $request->validated();
        $data['post_id'] = $postId;
        $data['user_id'] = auth()->id();
        $data['user_type'] = 'student';
        $data['is_approved'] = true; // Mặc định duyệt, có thể đổi lại nếu cần filter spam

        $comment = $this->repository->create($data);

        return $this->success(
            new PostCommentResource($comment->load('user')),
            'Đăng bình luận thành công.',
            201
        );
    }
}
