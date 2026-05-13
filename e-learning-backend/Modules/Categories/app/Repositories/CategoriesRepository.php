<?php

namespace Modules\Categories\Repositories;

use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\Categories\Models\Category;
use Modules\Course\Models\Course;

class CategoriesRepository extends BaseRepository implements CategoriesRepositoryInterface
{
    public function __construct(Category $model)
    {
        parent::__construct($model);
    }

    public function paginate(int $perPage = 15, array $columns = ['*'], array $relations = [], ?string $search = null): LengthAwarePaginator
    {
        $perPage = max(1, min($perPage, static::MAX_PER_PAGE));

        if ($search) {
            return $this->model->newQuery()
                ->with($relations)
                ->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%");
                })
                ->paginate($perPage, $columns);
        }

        // Paginate by root nodes so the tree is never split across pages
        $paginator = $this->model->newQuery()
            ->whereIsRoot()
            ->defaultOrder()
            ->paginate($perPage, ['*']);

        $roots = $paginator->getCollection();

        if ($roots->isEmpty()) {
            return $paginator;
        }

        $ranges = $roots->map(fn ($r) => ['lft' => $r->_lft, 'rgt' => $r->_rgt]);

        $items = $this->model->newQuery()
            ->with($relations)
            ->withDepth()
            ->defaultOrder()
            ->where(function ($q) use ($ranges) {
                foreach ($ranges as $range) {
                    $q->orWhere(function ($sub) use ($range) {
                        $sub->where('_lft', '>=', $range['lft'])
                            ->where('_rgt', '<=', $range['rgt']);
                    });
                }
            })
            ->get();

        return $paginator->setCollection($items);
    }

    public function getTree(bool $activeOnly = false): Collection
    {
        $query = $this->model->newQuery()->defaultOrder();

        if ($activeOnly) {
            $query->active();
        }

        return $query->get()->toTree();
    }

    public function getFlatTree(bool $activeOnly = false): Collection
    {
        $query = $this->model->newQuery()->defaultOrder()->withDepth();

        if ($activeOnly) {
            $query->active();
        }

        return $query->get();
    }

    public function getAncestors(int $id): Collection
    {
        $category = $this->model->newQuery()->findOrFail($id);

        return $category->ancestors()->defaultOrder()->get();
    }

    public function getDescendants(int $id): Collection
    {
        $category = $this->model->newQuery()->findOrFail($id);

        return $category->descendants()->defaultOrder()->withDepth()->get();
    }

    public function moveToParent(int $id, ?int $parentId): Model
    {
        $category = $this->model->newQuery()->findOrFail($id);

        if ($parentId === null) {
            $category->saveAsRoot();
        } else {
            if ($parentId === $id) {
                throw new \InvalidArgumentException('Không thể di chuyển danh mục vào chính nó.');
            }

            $parent = $this->model->newQuery()->findOrFail($parentId);

            if ($parent->isDescendantOf($category)) {
                throw new \InvalidArgumentException('Không thể di chuyển danh mục vào con của nó.');
            }

            $category->appendToNode($parent)->save();
        }

        $category->refresh();

        return $category;
    }

    public function findBySlug(string $slug, bool $activeOnly = false): ?Model
    {
        $query = $this->model->newQuery()->where('slug', $slug);

        if ($activeOnly) {
            $query->active();
        }

        return $query->first();
    }

    public function toggleStatus(int $id): Model
    {
        $category = $this->model->newQuery()->findOrFail($id);
        $category->update(['status' => $category->status === 1 ? 0 : 1]);
        $category->refresh();

        return $category;
    }

    public function delete(int $id): bool
    {
        $category = $this->model->newQuery()->withCount('children')->findOrFail($id);

        if ($category->children_count > 0) {
            throw new \RuntimeException('Không thể xóa danh mục đang có danh mục con.');
        }

        $courseCount = Course::whereHas('categories', fn ($q) => $q->where('categories.id', $id))->count();
        if ($courseCount > 0) {
            throw new \RuntimeException("Không thể xóa danh mục đang được dùng bởi {$courseCount} khóa học.");
        }

        return (bool) $category->delete();
    }

    public function deleteMany(array $ids): int
    {
        $count = 0;

        foreach ($ids as $id) {
            try {
                if ($this->delete($id)) {
                    $count++;
                }
            } catch (\RuntimeException) {
                // Skip categories that fail the constraint check
            }
        }

        return $count;
    }

    public function restore(int $id): bool
    {
        $category = $this->model->newQuery()->onlyTrashed()->findOrFail($id);

        if ($category->parent_id !== null) {
            $parentExists = $this->model->newQuery()->withoutTrashed()->find($category->parent_id);
            if (! $parentExists) {
                throw new \RuntimeException('Vui lòng khôi phục danh mục cha trước.');
            }
        }

        return (bool) $category->restore();
    }

    public function restoreMany(array $ids): int
    {
        $count = 0;

        foreach ($ids as $id) {
            try {
                if ($this->restore($id)) {
                    $count++;
                }
            } catch (\RuntimeException) {
                // Skip items whose parent is still deleted
            }
        }

        return $count;
    }

    public function getIdsHavingPublishedCourses(): array
    {
        return DB::table('categories_courses')
            ->join('courses', 'courses.id', '=', 'categories_courses.course_id')
            ->where('courses.status', 1)
            ->whereNull('courses.deleted_at')
            ->pluck('categories_courses.category_id')
            ->unique()
            ->values()
            ->all();
    }
}
