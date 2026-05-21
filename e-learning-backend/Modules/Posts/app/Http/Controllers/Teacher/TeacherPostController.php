<?php

namespace Modules\Posts\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Posts\Http\Requests\Teacher\StoreTeacherPostRequest;
use Modules\Posts\Http\Requests\Teacher\UpdateTeacherPostRequest;
use Modules\Posts\Http\Resources\PostResource;
use Modules\Posts\Repositories\PostRepositoryInterface;

class TeacherPostController extends Controller
{
    use ApiResponse;

    public function __construct(private PostRepositoryInterface $repository) {}

    private function authorId(): int
    {
        return auth('admin')->id();
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);
        $posts = $this->repository->getFilteredForTeacher(
            $this->authorId(),
            $request->only(['approval_status']),
            $perPage
        );
        $posts->setCollection(PostResource::collection($posts->getCollection())->collection);

        return $this->paginated($posts, 'Lấy danh sách bài viết thành công.');
    }

    public function store(StoreTeacherPostRequest $request): JsonResponse
    {
        $data = array_merge($request->validated(), [
            'author_id' => $this->authorId(),
            'approval_status' => 'pending',
            'is_published' => false,
        ]);

        $post = DB::transaction(function () use ($data) {
            $tagIds = $data['tag_ids'] ?? [];
            unset($data['tag_ids']);
            $post = $this->repository->create($data);
            if (! empty($tagIds)) {
                $post->tags()->sync($tagIds);
            }

            return $post;
        });

        return $this->success(
            new PostResource($post->load(['category', 'tags'])),
            'Bài viết đã được gửi chờ duyệt.',
            201
        );
    }

    public function show(int $id): JsonResponse
    {
        $post = $this->repository->findOrFail($id);
        abort_if($post->author_id !== $this->authorId(), 403, 'Bạn không có quyền xem bài viết này.');

        return $this->success(new PostResource($post->load(['category', 'tags'])));
    }

    public function update(UpdateTeacherPostRequest $request, int $id): JsonResponse
    {
        $post = $this->repository->findOrFail($id);
        abort_if($post->author_id !== $this->authorId(), 403, 'Bạn không có quyền sửa bài viết này.');

        DB::transaction(function () use ($request, $post) {
            $data = $request->validated();
            $tagIds = $data['tag_ids'] ?? null;
            unset($data['tag_ids']);
            $this->repository->update($post->id, $data);
            if ($request->has('tag_ids')) {
                $post->tags()->sync($tagIds ?? []);
            }
        });

        return $this->success(
            new PostResource($post->fresh(['category', 'tags'])),
            'Cập nhật bài viết thành công.'
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $post = $this->repository->findOrFail($id);
        abort_if($post->author_id !== $this->authorId(), 403, 'Bạn không có quyền xóa bài viết này.');
        $this->repository->delete($id);

        return $this->success(null, 'Đã xóa bài viết.');
    }
}
