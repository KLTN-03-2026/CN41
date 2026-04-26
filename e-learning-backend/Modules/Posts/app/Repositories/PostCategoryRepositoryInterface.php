<?php

namespace Modules\Posts\Repositories;

use App\Repositories\RepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PostCategoryRepositoryInterface extends RepositoryInterface
{
    /**
     * Get paginated post categories with optional filters.
     */
    public function getFiltered(array $filters = [], int $perPage = 15): LengthAwarePaginator;
}
