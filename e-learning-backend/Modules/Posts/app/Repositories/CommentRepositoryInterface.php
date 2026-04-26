<?php

namespace Modules\Posts\Repositories;

use App\Repositories\RepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

interface CommentRepositoryInterface extends RepositoryInterface
{
    /**
     * Get paginated comments with filters for admin.
     */
    public function getFiltered(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Toggle approval status.
     */
    public function toggleApproval(int $id): Model;
}
