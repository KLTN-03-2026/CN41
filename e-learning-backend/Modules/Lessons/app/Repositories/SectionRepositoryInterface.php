<?php

namespace Modules\Lessons\Repositories;

use App\Repositories\RepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection as SupportCollection;

interface SectionRepositoryInterface extends RepositoryInterface
{
    /**
     * Danh sách sections của 1 course (có filter, phân trang).
     */
    public function getByCourse(int $courseId, array $filters = [], int $perPage = 15): LengthAwarePaginator;

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

    /**
     * Tìm section trong thùng rác (onlyTrashed).
     */
    public function findTrashed(int $id): Model;

    /**
     * Kiểm tra section có thuộc course không.
     */
    public function belongsToCourse(int $sectionId, int $courseId): bool;

    /**
     * Lấy danh sách course_id distinct từ các section ids.
     */
    public function getDistinctCourseIds(array $ids): SupportCollection;

    /**
     * Lấy sections published kèm lessons published của course, theo thứ tự.
     */
    public function getPublishedWithLessons(int $courseId): Collection;

    /**
     * Lấy sections published của course, theo thứ tự (không kèm lessons).
     */
    public function getPublishedOrdered(int $courseId): Collection;

    /**
     * Đếm số sections của course.
     */
    public function countByCourse(int $courseId): int;
}
