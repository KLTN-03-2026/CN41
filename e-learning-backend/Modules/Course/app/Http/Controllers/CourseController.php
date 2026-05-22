<?php

namespace Modules\Course\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Modules\Course\Http\Requests\BulkDeleteCourseRequest;
use Modules\Course\Http\Requests\BulkForceDeleteCourseRequest;
use Modules\Course\Http\Requests\BulkRestoreCourseRequest;
use Modules\Course\Http\Requests\BulkStatusCourseRequest;
use Modules\Course\Http\Requests\IndexCourseRequest;
use Modules\Course\Http\Requests\IndexPublicCourseRequest;
use Modules\Course\Http\Requests\StoreCourseRequest;
use Modules\Course\Http\Requests\UpdateCourseRequest;
use Modules\Course\Http\Resources\CourseResource;
use Modules\Course\Repositories\CourseRepositoryInterface;

class CourseController extends Controller
{
    use ApiResponse;

    protected CourseRepositoryInterface $repository;

    public function __construct(CourseRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Danh sách Courses (có phân trang + filter).
     */
    public function index(IndexCourseRequest $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);
        $filters = $request->only(['search', 'status', 'teacher_id', 'category_id', 'level']);

        $data = $this->repository->getFiltered($filters, $perPage);
        $data->setCollection(CourseResource::collection($data->getCollection())->collection);

        return $this->paginated($data);
    }

    /**
     * Tạo mới Course.
     */
    public function store(StoreCourseRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $categoryIds = $validated['category_ids'] ?? [];
        unset($validated['category_ids']);

        $course = DB::transaction(function () use ($validated, $categoryIds) {
            $course = $this->repository->create($validated);

            // Sync categories nếu có
            if (! empty($categoryIds)) {
                $this->repository->syncCategories($course->id, $categoryIds);
            }

            return $course;
        });

        $course->refresh();
        $course->load(['teacher', 'categories']);

        return $this->success(new CourseResource($course), 'Khóa học đã được tạo thành công.', 201);
    }

    /**
     * Chi tiết Course.
     */
    public function show(int $id): JsonResponse
    {
        $course = $this->repository->findOrFail($id, ['*'], ['teacher', 'categories']);

        return $this->success(new CourseResource($course));
    }

    /**
     * Cập nhật Course.
     */
    public function update(UpdateCourseRequest $request, int $id): JsonResponse
    {
        $validated = $request->validated();
        $categoryIds = $validated['category_ids'] ?? null;
        unset($validated['category_ids']);

        // Fetch old course to retrieve old thumbnail for cleanup
        $oldCourse = $this->repository->findOrFail($id);
        $oldThumbnail = $oldCourse->thumbnail;

        $course = DB::transaction(function () use ($id, $validated, $categoryIds) {
            $course = $this->repository->update($id, $validated);

            // Sync categories nếu được gửi lên
            if ($categoryIds !== null) {
                $this->repository->syncCategories($course->id, $categoryIds);
            }

            return $course;
        });

        // Cleanup old thumbnail file if it was explicitly modified or removed
        if (array_key_exists('thumbnail', $validated) && $validated['thumbnail'] !== $oldThumbnail) {
            $this->deleteThumbnailFile($oldThumbnail);
        }

        $course->load(['teacher', 'categories']);

        return $this->success(new CourseResource($course), 'Khóa học đã được cập nhật thành công.');
    }

    /**
     * Xoá Course (soft delete).
     */
    public function destroy(int $id): JsonResponse
    {
        $this->repository->delete($id);

        return $this->success(null, 'Khóa học đã được xoá thành công.');
    }

    /**
     * Toggle trạng thái draft/published.
     */
    public function toggleStatus(int $id): JsonResponse
    {
        $course = $this->repository->toggleStatus($id);

        $statusText = $course->status === 1 ? 'xuất bản' : 'chuyển về nháp';

        return $this->success(new CourseResource($course), "Khóa học đã được {$statusText}.");
    }

    /**
     * Cập nhật trạng thái cho nhiều Courses.
     */
    public function bulkStatus(BulkStatusCourseRequest $request): JsonResponse
    {
        $count = $this->repository->actionMany($request->ids, $request->status === 1 ? 'publish' : 'unpublish');

        return $this->success(
            ['updated_count' => $count, 'updated_ids' => $request->ids],
            "Đã cập nhật {$count} khóa học thành công."
        );
    }

    /**
     * Danh sách Courses đã bị soft-delete (thùng rác).
     */
    public function trashed(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);
        $data = $this->repository->paginateTrashed($perPage);
        $data->setCollection(CourseResource::collection($data->getCollection())->collection);

        return $this->paginated($data);
    }

    /**
     * Khôi phục một Course đã soft-delete.
     */
    public function restore(int $id): JsonResponse
    {
        $this->repository->restore($id);

        return $this->success(null, 'Khóa học đã được khôi phục thành công.');
    }

    /**
     * Xoá vĩnh viễn một Course.
     */
    public function forceDelete(int $id): JsonResponse
    {
        $course = $this->repository->findTrashed($id);
        $thumbnail = $course->thumbnail;

        DB::transaction(fn () => $course->forceDelete());

        $this->deleteThumbnailFile($thumbnail);

        return $this->success(null, 'Khóa học đã bị xoá vĩnh viễn.');
    }

    /**
     * Xoá nhiều Courses (soft delete).
     */
    public function bulkDelete(BulkDeleteCourseRequest $request): JsonResponse
    {
        $deleted = $this->repository->deleteMany($request->ids);

        return $this->success(
            ['deleted_count' => $deleted, 'deleted_ids' => $request->ids],
            "Đã xoá {$deleted} khóa học thành công."
        );
    }

    /**
     * Khôi phục nhiều Courses đã soft-delete.
     */
    public function bulkRestore(BulkRestoreCourseRequest $request): JsonResponse
    {
        $restored = $this->repository->restoreMany($request->ids);

        return $this->success(
            ['restored_count' => $restored, 'restored_ids' => $request->ids],
            "Đã khôi phục {$restored} khóa học thành công."
        );
    }

    /**
     * Xoá vĩnh viễn nhiều Courses.
     */
    public function bulkForceDelete(BulkForceDeleteCourseRequest $request): JsonResponse
    {
        $thumbnails = $this->repository->findManyTrashed($request->ids)
            ->pluck('thumbnail')
            ->filter()
            ->values()
            ->all();

        $deleted = $this->repository->bulkForceDelete($request->ids);

        foreach ($thumbnails as $thumbnail) {
            $this->deleteThumbnailFile($thumbnail);
        }

        return $this->success(
            ['deleted_count' => $deleted, 'deleted_ids' => $request->ids],
            "Đã xoá vĩnh viễn {$deleted} khóa học."
        );
    }

    /**
     * Public: Danh sách khóa học đã published.
     */
    public function publicIndex(IndexPublicCourseRequest $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);
        $filters = $request->only(['search', 'category_id', 'level']);

        $data = $this->repository->getPublished($filters, $perPage);
        $data->setCollection(CourseResource::collection($data->getCollection())->collection);

        return $this->paginated($data);
    }

    /**
     * Public: Danh sách khóa học nổi bật (top rating, published).
     */
    public function featuredCourses(Request $request): JsonResponse
    {
        $courses = $this->repository->getFeatured((int) $request->query('limit', 8));

        return $this->success(
            CourseResource::collection($courses),
            'Lấy khóa học nổi bật thành công.'
        );
    }

    /**
     * Public: Chi tiết khóa học theo slug.
     */
    public function publicShow(string $slug): JsonResponse
    {
        if (! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            return $this->error('Khóa học không tồn tại.', 404);
        }

        $course = $this->repository->findBySlug($slug, true);

        if (! $course) {
            return $this->error('Khóa học không tồn tại.', 404);
        }

        if (auth('api')->check()) {
            $course->load(['students' => fn ($q) => $q->where('student_id', auth('api')->id())]);
        }

        return $this->success(new CourseResource($course));
    }

    /**
     * Public: Danh sách bài giảng của khóa học (lock nếu chưa mua).
     */
    public function publicLessons(string $slug): JsonResponse
    {
        if (! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            return $this->error('Khóa học không tồn tại.', 404);
        }

        $course = $this->repository->findBySlug($slug, true);

        if (! $course) {
            return $this->error('Khóa học không tồn tại.', 404);
        }

        $isPurchased = auth('api')->check()
            ? $this->repository->isEnrolled($course->id, auth('api')->id())
            : false;

        $sections = $this->repository->getPublicSectionsWithLessons($course->id);

        return $this->success([
            'is_purchased' => $isPurchased,
            'sections' => $sections,
        ], 'Lấy danh sách bài giảng thành công.');
    }

    /**
     * Client: Đăng ký khóa học miễn phí.
     */
    public function enrollFree(string $slug): JsonResponse
    {
        $course = $this->repository->findBySlug($slug, true);
        if (! $course) {
            return $this->error('Khóa học không tồn tại.', 404);
        }

        if ($course->price > 0) {
            return $this->error('Khóa học này không miễn phí.', 400);
        }

        $this->repository->enrollStudent($course->id, auth('api')->id());

        return $this->success(null, 'Đăng ký thành công! Bạn đã có thể vào học.');
    }

    /**
     * Client: Danh sách khóa học đã mua (auth:api).
     */
    public function myCourses(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);
        $studentId = auth('api')->id();

        $courses = $this->repository->getByStudent($studentId, $perPage);

        $courses->setCollection(CourseResource::collection($courses->getCollection())->collection);

        return $this->paginated($courses);
    }

    /**
     * Public: Xem chi tiết bài học nếu là bài học thử (is_preview = 1).
     */
    public function publicPreviewLesson(string $courseSlug, string $lessonSlug): JsonResponse
    {
        $course = $this->repository->findBySlug($courseSlug, true);
        if (! $course) {
            return $this->error('Khóa học không tồn tại.', 404);
        }

        $lesson = $this->repository->findPublicPreviewLesson($course->id, $lessonSlug);

        if (! $lesson) {
            return $this->error('Bài học không tồn tại.', 404);
        }

        if (! $lesson->is_preview) {
            return $this->error('Đây không phải bài học thử.', 403);
        }

        $videoUrl = null;
        if ($lesson->video) {
            if ($lesson->video->hls_status === 'ready' && $lesson->video->hls_path) {
                $videoUrl = asset('storage/'.$lesson->video->hls_path);
            } else {
                $videoUrl = URL::temporarySignedRoute('api.media.stream', now()->addHours(2), ['id' => $lesson->video->id]);
            }
        }

        return $this->success([
            'id' => $lesson->id,
            'title' => $lesson->title,
            'type' => $lesson->type,
            'video_url' => $videoUrl,
            'document_url' => $lesson->document ? $lesson->document->url : null,
            'content' => $lesson->content,
            'is_preview' => $lesson->is_preview,
        ], 'Lấy bài học thử thành công.');
    }

    /**
     * Xóa file thumbnail khỏi storage.
     * Thumbnail URL dạng /storage/thumbnails/uuid.jpg → path = thumbnails/uuid.jpg
     */
    private function deleteThumbnailFile(?string $thumbnail): void
    {
        if (empty($thumbnail)) {
            return;
        }

        // Chuyển URL /storage/thumbnails/xxx.jpg → thumbnails/xxx.jpg
        $path = preg_replace('#^/storage/#', '', $thumbnail);

        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
