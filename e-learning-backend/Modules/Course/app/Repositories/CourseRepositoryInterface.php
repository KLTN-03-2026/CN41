<?php

namespace Modules\Course\Repositories;

use App\Repositories\RepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection as SupportCollection;

/**
 * Interface CourseRepositoryInterface
 *
 * Contract cho Course Repository.
 * Extends RepositoryInterface (base methods chuẩn).
 * Thêm các method riêng cho Course.
 */
interface CourseRepositoryInterface extends RepositoryInterface
{
    /**
     * Danh sách courses (phân trang) có filter (Admin).
     */
    public function getFiltered(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Danh sách courses đã published (Public).
     * Filter: search, category_id, level, per_page.
     */
    public function getPublished(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Tìm course theo slug, kèm teacher + categories.
     */
    public function findBySlug(string $slug, bool $publishedOnly = false): ?Model;

    /**
     * Lấy courses theo teacher_id.
     */
    public function getByTeacher(int $teacherId, int $perPage = 15): LengthAwarePaginator;

    /**
     * Toggle trạng thái draft/published.
     */
    public function toggleStatus(int $id): Model;

    /**
     * Tăng counter total_students.
     */
    public function incrementStudentCount(int $courseId): void;

    /**
     * Giảm counter total_students.
     */
    public function decrementStudentCount(int $courseId): void;

    /**
     * Sync danh sách categories cho course (pivot).
     */
    public function syncCategories(int $courseId, array $categoryIds): void;

    /**
     * Danh sách courses học viên đã enroll (phân trang).
     */
    public function getByStudent(int $studentId, int $perPage = 15): LengthAwarePaginator;

    /**
     * Tìm course đã soft-delete theo id.
     */
    public function findTrashed(int $id): Model;

    /**
     * Tìm nhiều courses đã soft-delete theo ids.
     */
    public function findManyTrashed(array $ids): Collection;

    /**
     * Lấy courses nổi bật: published, sort theo rating DESC, giới hạn $limit.
     */
    public function getFeatured(int $limit = 8): Collection;

    /**
     * Lấy danh sách sections + lessons public của course (status = 1).
     */
    public function getPublicSectionsWithLessons(int $courseId): SupportCollection;

    /**
     * Tìm lesson preview trong course theo slug (status = 1, kèm video + document).
     */
    public function findPublicPreviewLesson(int $courseId, string $lessonSlug): ?Model;

    /**
     * Kiểm tra học viên đã enroll course chưa.
     */
    public function isEnrolled(int $courseId, int $studentId): bool;

    /**
     * Enroll học viên vào course (attach pivot + tăng total_students).
     */
    public function enrollStudent(int $courseId, int $studentId): void;

    /**
     * Force delete nhiều courses đã soft-delete (cascade qua model event).
     */
    public function bulkForceDelete(array $ids): int;
}
