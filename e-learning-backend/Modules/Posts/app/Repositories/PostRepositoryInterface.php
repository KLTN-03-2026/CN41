<?php

namespace Modules\Posts\Repositories;

use App\Repositories\RepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

interface PostRepositoryInterface extends RepositoryInterface
{
    /**
     * Get paginated posts with filters for admin.
     */
    public function getFiltered(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Get paginated published posts for client.
     */
    public function getPublished(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Find a published post by slug.
     */
    public function findBySlug(string $slug): ?Model;

    /**
     * Toggle published status.
     */
    public function togglePublish(int $id): Model;

    /**
     * Increment view count.
     */
    public function incrementViews(int $id): void;

    /**
     * Get paginated posts for a specific teacher (by author_id).
     */
    public function getFilteredForTeacher(int $authorId, array $filters, int $perPage): LengthAwarePaginator;
}
