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
        private CommentRepositoryInterface $repository
    ) {}

    public function store(StoreCommentRequest $request, int $postId): JsonResponse
    {
        $data = $request->validated();
        $data['post_id'] = $postId;
        $data['user_id'] = auth('api')->id();
        $data['user_type'] = 'student';
        $data['is_approved'] = true;

        $comment = $this->repository->create($data);

        return $this->success(
            new PostCommentResource($comment->load('student')),
            'Đăng bình luận thành công.',
            201
        );
    }
}
