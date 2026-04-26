<?php

namespace Modules\Posts\Repositories;

use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Modules\Posts\Models\PostComment;

class CommentRepository extends BaseRepository implements CommentRepositoryInterface
{
    public function __construct(PostComment $model)
    {
        parent::__construct($model);
    }

    public function getFiltered(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $perPage = max(1, min($perPage, static::MAX_PER_PAGE));

        $query = $this->model->newQuery()
            ->with(['post', 'commenter', 'parent'])
            ->latest();

        if (!empty($filters['search'])) {
            $query->where('content', 'like', '%' . $filters['search'] . '%');
        }

        if (isset($filters['is_approved']) && $filters['is_approved'] !== '') {
            $query->where('is_approved', (bool)$filters['is_approved']);
        }

        if (!empty($filters['post_id'])) {
            $query->where('post_id', $filters['post_id']);
        }

        return $query->paginate($perPage);
    }

    public function toggleApproval(int $id): Model
    {
        $comment = $this->findOrFail($id);
        $comment->is_approved = !$comment->is_approved;
        $comment->save();
        return $comment;
    }
}
