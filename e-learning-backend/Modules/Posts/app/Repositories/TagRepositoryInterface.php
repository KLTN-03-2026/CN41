<?php

namespace Modules\Posts\Repositories;

use App\Repositories\RepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TagRepositoryInterface extends RepositoryInterface
{
    /**
     * Get paginated tags with optional filters.
     */
    public function getFiltered(array $filters = [], int $perPage = 15): LengthAwarePaginator;
}
