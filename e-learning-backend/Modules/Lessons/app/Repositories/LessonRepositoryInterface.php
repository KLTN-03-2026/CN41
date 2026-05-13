<?php

namespace Modules\Lessons\Repositories;

use App\Repositories\RepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection as SupportCollection;
use Modules\Lessons\Models\LessonProgress;

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

    /**
     * Tìm lesson trong thùng rác (onlyTrashed).
     */
    public function findTrashed(int $id): Model;

    /**
     * Đếm số lessons trong phạm vi (course hoặc section).
     */
    public function countInScope(int $courseId, ?int $sectionId = null): int;

    /**
     * Lấy nhiều lessons theo ids.
     */
    public function getByIds(array $ids): Collection;

    /**
     * Lấy nhiều lessons đã xóa theo ids (onlyTrashed).
     */
    public function getManyTrashed(array $ids): Collection;

    /**
     * Lấy danh sách course_id distinct từ các lesson ids.
     */
    public function getDistinctCourseIds(array $ids, bool $onlyTrashed = false): SupportCollection;

    /**
     * Gán section_id hàng loạt cho các lessons.
     */
    public function assignSection(array $ids, ?int $sectionId): int;

    /**
     * Lấy map tiến độ học của student trong course, keyed by lesson_id.
     */
    public function getProgressMap(int $studentId, int $courseId): SupportCollection;

    /**
     * Tìm bản ghi tiến độ học của student cho 1 lesson.
     */
    public function findProgress(int $studentId, int $lessonId): ?LessonProgress;

    /**
     * Tạo hoặc cập nhật tiến độ học.
     */
    public function updateOrCreateProgress(int $studentId, int $lessonId, int $courseId, array $data): LessonProgress;

    /**
     * Lấy các lessons published chưa gán chương (section_id = null) của course.
     */
    public function getOrphanPublished(int $courseId): Collection;

    /**
     * Tìm lesson published theo course và slug, kèm video + document.
     */
    public function findPublishedByCourseAndSlug(int $courseId, string $slug): ?Model;
}
