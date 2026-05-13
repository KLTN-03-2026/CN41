<?php

namespace Modules\Categories\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Categories\Http\Requests\BulkDeleteCategoriesRequest;
use Modules\Categories\Http\Requests\BulkForceDeleteCategoriesRequest;
use Modules\Categories\Http\Requests\BulkRestoreCategoriesRequest;
use Modules\Categories\Http\Requests\IndexCategoriesRequest;
use Modules\Categories\Http\Requests\MoveCategoryRequest;
use Modules\Categories\Http\Requests\StoreCategoriesRequest;
use Modules\Categories\Http\Requests\UpdateCategoriesRequest;
use Modules\Categories\Http\Resources\CategoryResource;
use Modules\Categories\Repositories\CategoriesRepositoryInterface;

class CategoriesController extends Controller
{
    use ApiResponse;

    protected CategoriesRepositoryInterface $repository;

    public function __construct(CategoriesRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function index(IndexCategoriesRequest $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 15);
        $search = $request->input('search');
        $data = $this->repository->paginate($perPage, ['*'], [], $search);
        $data->setCollection(CategoryResource::collection($data->getCollection())->collection);

        return $this->paginated($data);
    }

    public function tree(): JsonResponse
    {
        $tree = $this->repository->getTree();

        return $this->success(CategoryResource::collection($tree), 'Lấy cây danh mục thành công.');
    }

    public function flatTree(): JsonResponse
    {
        $categories = $this->repository->getFlatTree();

        return $this->success(CategoryResource::collection($categories), 'Lấy danh sách danh mục thành công.');
    }

    public function store(StoreCategoriesRequest $request): JsonResponse
    {
        $data = $request->validated();

        $category = $this->repository->create($data);
        $category->refresh();

        return $this->success(new CategoryResource($category), 'Danh mục đã được tạo thành công.', 201);
    }

    public function show(int $id): JsonResponse
    {
        $category = $this->repository->findOrFail($id);
        $category->load('ancestors', 'children');

        return $this->success(new CategoryResource($category));
    }

    public function update(UpdateCategoriesRequest $request, int $id): JsonResponse
    {
        $category = $this->repository->update($id, $request->validated());

        return $this->success(new CategoryResource($category), 'Danh mục đã được cập nhật thành công.');
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->repository->delete($id);
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), 400);
        }

        return $this->success(null, 'Danh mục đã được xoá thành công.');
    }

    public function move(MoveCategoryRequest $request, int $id): JsonResponse
    {
        try {
            $category = $this->repository->moveToParent($id, $request->parent_id);
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        }

        return $this->success(new CategoryResource($category), 'Đã di chuyển danh mục thành công.');
    }

    public function ancestors(int $id): JsonResponse
    {
        $ancestors = $this->repository->getAncestors($id);

        return $this->success(CategoryResource::collection($ancestors));
    }

    public function descendants(int $id): JsonResponse
    {
        $descendants = $this->repository->getDescendants($id);

        return $this->success(CategoryResource::collection($descendants));
    }

    public function toggleStatus(int $id): JsonResponse
    {
        $category = $this->repository->toggleStatus($id);

        $statusText = $category->status === 1 ? 'kích hoạt' : 'vô hiệu hoá';

        return $this->success(new CategoryResource($category), "Danh mục đã được {$statusText}.");
    }

    public function trashed(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);
        $data = $this->repository->paginateTrashed($perPage);
        $data->setCollection(CategoryResource::collection($data->getCollection())->collection);

        return $this->paginated($data);
    }

    public function restore(int $id): JsonResponse
    {
        try {
            $this->repository->restore($id);
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), 400);
        }

        return $this->success(null, 'Danh mục đã được khôi phục thành công.');
    }

    public function forceDelete(int $id): JsonResponse
    {
        $this->repository->forceDeleteById($id);

        return $this->success(null, 'Danh mục đã bị xoá vĩnh viễn.');
    }

    public function bulkDelete(BulkDeleteCategoriesRequest $request): JsonResponse
    {
        $deleted = $this->repository->deleteMany($request->ids);

        return $this->success(
            ['deleted_count' => $deleted, 'deleted_ids' => $request->ids],
            "Đã xoá {$deleted} danh mục thành công."
        );
    }

    public function bulkRestore(BulkRestoreCategoriesRequest $request): JsonResponse
    {
        $restored = $this->repository->restoreMany($request->ids);

        return $this->success(
            ['restored_count' => $restored, 'restored_ids' => $request->ids],
            "Đã khôi phục {$restored} danh mục thành công."
        );
    }

    public function bulkForceDelete(BulkForceDeleteCategoriesRequest $request): JsonResponse
    {
        $deleted = $this->repository->forceDeleteMany($request->ids);

        return $this->success(
            ['deleted_count' => $deleted, 'deleted_ids' => $request->ids],
            "Đã xoá vĩnh viễn {$deleted} danh mục."
        );
    }

    public function publicIndex(): JsonResponse
    {
        $hasCourseIds = $this->repository->getIdsHavingPublishedCourses();
        $categories = $this->repository->getFlatTree(true);

        $validIds = [];
        foreach ($categories as $cat) {
            foreach ($hasCourseIds as $cId) {
                $child = $categories->firstWhere('id', $cId);
                if ($child && $child->_lft >= $cat->_lft && $child->_rgt <= $cat->_rgt) {
                    $validIds[] = $cat->id;
                    break;
                }
            }
        }

        $filteredCategories = $categories->filter(fn ($c) => in_array($c->id, $validIds))->values();

        return $this->success(CategoryResource::collection($filteredCategories));
    }

    public function publicTree(): JsonResponse
    {
        $tree = $this->repository->getTree(true);

        return $this->success(CategoryResource::collection($tree));
    }

    public function publicShow(string $slug): JsonResponse
    {
        $category = $this->repository->findBySlug($slug, true);

        if (! $category) {
            return $this->error('Danh mục không tồn tại.', 404);
        }

        $category->load('ancestors', 'children');

        return $this->success(new CategoryResource($category));
    }
}
