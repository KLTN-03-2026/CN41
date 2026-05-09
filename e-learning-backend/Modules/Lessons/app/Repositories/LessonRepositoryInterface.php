<?php

namespace Modules\Lessons\Repositories;

use App\Repositories\RepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Interface LessonRepositoryInterface
 *
 * Contract cho Lesson Repository.
 * Extends RepositoryInterface (base methods chuẩn).
 * Thêm các method riêng cho Lesson.
 */
interface LessonRepositoryInterface extends RepositoryInterface
{
    /**
     * Danh sách lessons của 1 course (Admin — có filter).
     *
     * @param  array  $filters  Filter theo status, type
     */
    public function getByCourse(int $courseId, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Danh sách lessons published của 1 course (Public/Client).
     * Trả Collection (không phân trang).
     */
    public function getPublishedByCourse(int $courseId): Collection;

    /**
     * Tìm lesson theo slug.
     */
    public function findBySlug(string $slug): ?Model;

    /**
     * Toggle trạng thái draft/published (0 ↔ 1).
     */
    public function toggleStatus(int $id): Model;

    /**
     * Cập nhật order hàng loạt.
     *
     * @param  array  $orders  Array of ['id' => int, 'order' => int]
     */
    public function reorder(array $orders): void;
}
