<?php

namespace Modules\Posts\Repositories;

use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Modules\Posts\Models\Post;

class PostRepository extends BaseRepository implements PostRepositoryInterface
{
    public function __construct(Post $model)
    {
        parent::__construct($model);
    }

    public function getFiltered(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $perPage = max(1, min($perPage, static::MAX_PER_PAGE));

        $query = $this->model->newQuery()
            ->with(['author', 'category', 'tags'])
            ->latest();

        if (! empty($filters['search'])) {
            $query->where('title', 'like', '%'.$filters['search'].'%');
        }

        if (isset($filters['is_published']) && $filters['is_published'] !== '') {
            $query->where('is_published', (bool) $filters['is_published']);
        }

        if (! empty($filters['post_category_id'])) {
            $query->where('post_category_id', $filters['post_category_id']);
        }

        if (! empty($filters['approval_status'])) {
            $query->where('approval_status', $filters['approval_status']);
        }

        return $query->paginate($perPage);
    }

    public function getPublished(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $perPage = max(1, min($perPage, static::MAX_PER_PAGE));

        $query = $this->model->newQuery()
            ->where('is_published', true)
            ->where('approval_status', 'approved')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->with(['author', 'category', 'tags'])
            ->latest();

        if (! empty($filters['search'])) {
            $query->where('title', 'like', '%'.$filters['search'].'%');
        }

        if (! empty($filters['category_id'])) {
            $query->where('post_category_id', $filters['category_id']);
        }

        if (! empty($filters['category_slug'])) {
            $query->whereHas('category', function ($q) use ($filters) {
                $q->where('slug', $filters['category_slug']);
            });
        }

        if (! empty($filters['tag_slug'])) {
            $query->whereHas('tags', function ($q) use ($filters) {
                $q->where('slug', $filters['tag_slug']);
            });
        }

        if (! empty($filters['author_id'])) {
            $query->where('author_id', $filters['author_id']);
        }

        return $query->paginate($perPage);
    }

    public function findBySlug(string $slug): ?Model
    {
        return $this->model->newQuery()
            ->where('slug', $slug)
            ->where('is_published', true)
            ->where('approval_status', 'approved')
            ->with(['author', 'category', 'tags', 'comments' => function ($q) {
                $q->where('is_approved', true)->whereNull('parent_id')->with('replies.adminUser', 'replies.student', 'adminUser', 'student');
            }])
            ->first();
    }

    public function togglePublish(int $id): Model
    {
        $post = $this->findOrFail($id);
        $post->is_published = ! $post->is_published;

        if ($post->is_published && ! $post->published_at) {
            $post->published_at = now();
        }

        $post->save();

        return $post;
    }

    public function incrementViews(int $id): void
    {
        $this->model->newQuery()->where('id', $id)->increment('views');
    }

    public function getFilteredForTeacher(int $authorId, array $filters, int $perPage): LengthAwarePaginator
    {
        $perPage = max(1, min($perPage, static::MAX_PER_PAGE));

        $query = $this->model->newQuery()
            ->where('author_id', $authorId)
            ->with(['category', 'tags'])
            ->latest();

        if (! empty($filters['approval_status'])) {
            $query->where('approval_status', $filters['approval_status']);
        }

        return $query->paginate($perPage);
    }
}
