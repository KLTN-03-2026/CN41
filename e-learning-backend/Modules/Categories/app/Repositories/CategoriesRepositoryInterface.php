<?php

namespace Modules\Categories\Repositories;

use App\Repositories\RepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface CategoriesRepositoryInterface extends RepositoryInterface
{
    public function paginate(int $perPage = 15, array $columns = ['*'], array $relations = [], ?string $search = null): LengthAwarePaginator;

    public function getTree(bool $activeOnly = false): Collection;

    public function getFlatTree(bool $activeOnly = false): Collection;

    public function getAncestors(int $id): Collection;

    public function getDescendants(int $id): Collection;

    public function moveToParent(int $id, ?int $parentId): Model;

    public function findBySlug(string $slug, bool $activeOnly = false): ?Model;

    public function toggleStatus(int $id): Model;

    public function getIdsHavingPublishedCourses(): array;
}
