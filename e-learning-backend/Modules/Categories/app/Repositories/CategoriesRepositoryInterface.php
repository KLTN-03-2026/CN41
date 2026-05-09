<?php

namespace Modules\Categories\Repositories;

use App\Repositories\RepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Interface CategoriesRepositoryInterface
 *
 * Contract cho Categories Repository.
 * Extends RepositoryInterface (9 methods chuẩn).
 * Thêm các method riêng cho Categories (nested set operations).
 */
interface CategoriesRepositoryInterface extends RepositoryInterface
{
    /**
     * Lấy toàn bộ cây category dạng nested (đệ quy).
     * Chỉ lấy các category active nếu $activeOnly = true.
     */
    public function getTree(bool $activeOnly = false): Collection;

    /**
     * Lấy danh sách category dạng flat (có depth).
     * Dùng cho Admin dropdown chọn parent.
     */
    public function getFlatTree(bool $activeOnly = false): Collection;

    /**
     * Lấy danh sách ancestors (tổ tiên) của một category.
     * Dùng cho breadcrumb.
     */
    public function getAncestors(int $id): Collection;

    /**
     * Lấy danh sách descendants (con cháu) của một category.
     */
    public function getDescendants(int $id): Collection;

    /**
     * Di chuyển category thành con của parent mới.
     * Nếu $parentId = null → đưa lên root.
     */
    public function moveToParent(int $id, ?int $parentId): Model;

    /**
     * Tìm category theo slug.
     * Chỉ lấy active nếu $activeOnly = true.
     */
    public function findBySlug(string $slug, bool $activeOnly = false): ?Model;

    /**
     * Cập nhật trạng thái (status) cho một category.
     */
    public function toggleStatus(int $id): Model;
}
