<?php

namespace Modules\Lessons\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Modules\Course\Models\Course;
use Modules\Course\Repositories\CourseRepositoryInterface;
use Modules\Lessons\Http\Requests\BulkActionLessonRequest;
use Modules\Lessons\Http\Requests\BulkDeleteLessonRequest;
use Modules\Lessons\Http\Requests\BulkForceDeleteLessonRequest;
use Modules\Lessons\Http\Requests\BulkRestoreLessonRequest;
use Modules\Lessons\Http\Requests\IndexLessonRequest;
use Modules\Lessons\Http\Requests\ReorderLessonRequest;
use Modules\Lessons\Http\Requests\StoreLessonRequest;
use Modules\Lessons\Http\Requests\StoreNoteRequest;
use Modules\Lessons\Http\Requests\UpdateLessonRequest;
use Modules\Lessons\Http\Requests\UpdateNoteRequest;
use Modules\Lessons\Http\Requests\UpdateProgressRequest;
use Modules\Lessons\Http\Resources\LessonResource;
use Modules\Lessons\Models\LessonNote;
use Modules\Lessons\Repositories\LessonRepositoryInterface;
use Modules\Lessons\Repositories\SectionRepositoryInterface;

class LessonController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected LessonRepositoryInterface $repository,
        protected CourseRepositoryInterface $courseRepo,
        protected SectionRepositoryInterface $sectionRepo,
    ) {}

    public function index(IndexLessonRequest $request, int $course_id): JsonResponse
    {
        $this->courseRepo->findOrFail($course_id);

        $perPage = (int) $request->query('per_page', 15);
        $filters = $request->only(['status', 'type']);

        $data = $this->repository->getByCourse($course_id, $filters, $perPage);

        return $this->paginated($data, 'Lấy danh sách bài giảng thành công.');
    }

    public function store(StoreLessonRequest $request, int $course_id): JsonResponse
    {
        $this->courseRepo->findOrFail($course_id);

        $validated = $request->validated();
        $validated['course_id'] = $course_id;
        $validated['slug'] = Str::slug($validated['title']).'-'.uniqid();

        if (! empty($validated['section_id'])) {
            if (! $this->sectionRepo->belongsToCourse($validated['section_id'], $course_id)) {
                return $this->error('Chương không thuộc khóa học này.', 422);
            }
        }

        if (! isset($validated['order'])) {
            $validated['order'] = $this->repository->countInScope(
                $course_id,
                ! empty($validated['section_id']) ? $validated['section_id'] : null
            );
        }

        $lesson = DB::transaction(function () use ($validated, $course_id) {
            $lesson = $this->repository->create($validated);
            Course::where('id', $course_id)->increment('total_lessons');

            return $lesson;
        });

        return $this->success(new LessonResource($lesson), 'Tạo bài giảng thành công.', 201);
    }

    public function show(int $id): JsonResponse
    {
        $lesson = $this->repository->findOrFail($id, ['*'], ['video', 'document']);

        return $this->success(new LessonResource($lesson), 'Lấy chi tiết bài giảng thành công.');
    }

    public function update(UpdateLessonRequest $request, int $id): JsonResponse
    {
        $lesson = $this->repository->update($id, $request->validated());

        return $this->success(new LessonResource($lesson), 'Cập nhật bài giảng thành công.');
    }

    public function destroy(int $id): JsonResponse
    {
        $lesson = $this->repository->findOrFail($id);

        DB::transaction(function () use ($lesson, $id) {
            $this->repository->delete($id);
            Course::where('id', $lesson->course_id)->decrement('total_lessons');
        });

        return $this->success(null, 'Xóa bài giảng thành công.');
    }

    public function toggleStatus(int $id): JsonResponse
    {
        $lesson = $this->repository->toggleStatus($id);

        return $this->success(
            ['id' => $lesson->id, 'status' => $lesson->status],
            'Cập nhật trạng thái thành công.'
        );
    }

    public function trashed(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);
        $data = $this->repository->paginateTrashed($perPage);

        return $this->paginated($data, 'Lấy danh sách bài giảng đã xóa thành công.');
    }

    public function restore(int $id): JsonResponse
    {
        $lesson = $this->repository->findTrashed($id);

        DB::transaction(function () use ($lesson, $id) {
            $this->repository->restore($id);
            Course::where('id', $lesson->course_id)->increment('total_lessons');
        });

        return $this->success(null, 'Khôi phục bài giảng thành công.');
    }

    public function forceDelete(int $id): JsonResponse
    {
        $this->repository->forceDeleteById($id);

        return $this->success(null, 'Xóa vĩnh viễn bài giảng thành công.');
    }

    public function reorder(ReorderLessonRequest $request): JsonResponse
    {
        $ids = collect($request->orders)->pluck('id')->toArray();
        $courseIds = $this->repository->getDistinctCourseIds($ids);

        if ($courseIds->count() > 1) {
            return $this->error('Không thể sắp xếp bài giảng của nhiều khóa học cùng lúc.', 422);
        }

        $this->repository->reorder($request->orders);

        return $this->success(null, 'Sắp xếp bài giảng thành công.');
    }

    public function bulkAction(BulkActionLessonRequest $request): JsonResponse
    {
        if ($request->action === 'assign-section') {
            $sectionId = $request->section_id;

            $courseIds = $this->repository->getDistinctCourseIds($request->ids);
            if ($courseIds->count() > 1) {
                return $this->error('Các bài giảng phải thuộc cùng một khóa học.', 422);
            }

            if ($sectionId) {
                $courseId = $courseIds->first();
                if (! $this->sectionRepo->belongsToCourse($sectionId, $courseId)) {
                    return $this->error('Chương không thuộc khóa học này.', 422);
                }
            }

            $count = $this->repository->assignSection($request->ids, $sectionId);

            $message = $sectionId
                ? "Đã gán {$count} bài giảng vào chương thành công."
                : "Đã bỏ phân chương {$count} bài giảng thành công.";

            return $this->success(null, $message);
        }

        $count = $this->repository->actionMany($request->ids, $request->action);

        return $this->success(null, "Cập nhật trạng thái hàng loạt {$count} bài giảng thành công.");
    }

    public function bulkDelete(BulkDeleteLessonRequest $request): JsonResponse
    {
        $lessons = $this->repository->getByIds($request->ids);

        $count = DB::transaction(function () use ($request, $lessons) {
            $count = $this->repository->deleteMany($request->ids);
            foreach ($lessons->groupBy('course_id') as $courseId => $group) {
                Course::where('id', $courseId)->decrement('total_lessons', $group->count());
            }

            return $count;
        });

        return $this->success(null, "Xóa hàng loạt {$count} bài giảng thành công.");
    }

    public function bulkRestore(BulkRestoreLessonRequest $request): JsonResponse
    {
        // Chỉ lấy trashed lessons để count đúng cho increment
        $lessons = $this->repository->getManyTrashed($request->ids);

        $count = DB::transaction(function () use ($request, $lessons) {
            $count = $this->repository->restoreMany($request->ids);
            foreach ($lessons->groupBy('course_id') as $courseId => $group) {
                Course::where('id', $courseId)->increment('total_lessons', $group->count());
            }

            return $count;
        });

        return $this->success(null, "Khôi phục hàng loạt {$count} bài giảng thành công.");
    }

    public function bulkForceDelete(BulkForceDeleteLessonRequest $request): JsonResponse
    {
        $count = $this->repository->forceDeleteMany($request->ids);

        return $this->success(null, "Xóa vĩnh viễn hàng loạt {$count} bài giảng thành công.");
    }

    public function myLessons(Request $request, string $slug): JsonResponse
    {
        $course = $this->courseRepo->findBySlug($slug, true);

        if (! $course) {
            return $this->error('Khóa học không tồn tại.', 404);
        }

        if (! $this->courseRepo->isEnrolled($course->id, auth('api')->id())) {
            return $this->error('Bạn chưa mua khóa học này.', 403);
        }

        $studentId = auth('api')->id();

        // Load tất cả progress 1 lần — tránh N+1
        $progressMap = $this->repository->getProgressMap($studentId, $course->id);

        $sections = $this->sectionRepo->getPublishedWithLessons($course->id)
            ->map(function ($section) use ($progressMap) {
                return [
                    'id' => $section->id,
                    'title' => $section->title,
                    'order' => $section->order,
                    'lessons' => $section->lessons->map(fn ($lesson) => $this->formatLesson($lesson, $progressMap)),
                ];
            });

        $orphanLessons = $this->repository->getOrphanPublished($course->id)
            ->map(fn ($lesson) => $this->formatLesson($lesson, $progressMap));

        return $this->success([
            'sections' => $sections,
            'orphan_lessons' => $orphanLessons,
        ], 'Lấy danh sách bài giảng thành công.');
    }

    public function myLessonDetail(Request $request, string $slug, string $lessonSlug): JsonResponse
    {
        $course = $this->courseRepo->findBySlug($slug, true);

        if (! $course) {
            return $this->error('Khóa học không tồn tại.', 404);
        }

        if (! $this->courseRepo->isEnrolled($course->id, auth('api')->id())) {
            return $this->error('Bạn chưa mua khóa học này.', 403);
        }

        $lesson = $this->repository->findPublishedByCourseAndSlug($course->id, $lessonSlug);

        if (! $lesson) {
            return $this->error('Bài học không tồn tại.', 404);
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
            'document_id' => $lesson->document ? $lesson->document->id : null,
            'content' => $lesson->content,
            'course_name' => $course->name,
        ], 'Lấy chi tiết bài học thành công.');
    }

    public function updateProgress(UpdateProgressRequest $request, int $id): JsonResponse
    {
        $lesson = $this->repository->find($id);

        if (! $lesson) {
            return $this->error('Bài giảng không tồn tại.', 404);
        }

        if (! $this->courseRepo->isEnrolled($lesson->course_id, auth('api')->id())) {
            return $this->error('Bạn chưa mua khóa học này.', 403);
        }

        $studentId = auth('api')->id();

        // Lấy progress hiện tại để xử lý completed_at
        $existingProgress = $this->repository->findProgress($studentId, $id);

        $isCompleted = $request->boolean('is_completed', false);

        // completed_at chỉ set lần đầu khi is_completed=true, không ghi đè nếu đã có
        $completedAt = $isCompleted && ! $existingProgress?->completed_at
            ? now()
            : ($existingProgress?->completed_at ?? null);

        $progress = $this->repository->updateOrCreateProgress($studentId, $id, $lesson->course_id, [
            'watched_seconds' => $request->watched_seconds,
            'is_completed' => $isCompleted,
            'completed_at' => $completedAt,
        ]);

        return $this->success([
            'lesson_id' => $id,
            'course_id' => $lesson->course_id,
            'is_completed' => (bool) $progress->is_completed,
            'watched_seconds' => $progress->watched_seconds,
            'completed_at' => $progress->completed_at,
        ], 'Cập nhật tiến độ thành công.');
    }

    public function courseProgress(Request $request, string $slug): JsonResponse
    {
        $course = $this->courseRepo->findBySlug($slug, true);

        if (! $course) {
            return $this->error('Khóa học không tồn tại.', 404);
        }

        if (! $this->courseRepo->isEnrolled($course->id, auth('api')->id())) {
            return $this->error('Bạn chưa mua khóa học này.', 403);
        }

        $studentId = auth('api')->id();

        $lessons = $this->repository->getPublishedByCourse($course->id);
        $progressMap = $this->repository->getProgressMap($studentId, $course->id);

        $totalLessons = $lessons->count();
        $completedLessons = $progressMap->where('is_completed', 1)->count();
        $percent = $totalLessons > 0 ? round($completedLessons / $totalLessons * 100) : 0;

        $sections = $this->sectionRepo->getPublishedOrdered($course->id)
            ->map(function ($section) use ($lessons, $progressMap) {
                $sectionLessons = $lessons->where('section_id', $section->id)->values();

                return [
                    'id' => $section->id,
                    'title' => $section->title,
                    'order' => $section->order,
                    'total' => $sectionLessons->count(),
                    'completed' => $sectionLessons->filter(fn ($l) => $progressMap->has($l->id) && $progressMap[$l->id]->is_completed)->count(),
                    'lessons' => $sectionLessons->map(fn ($lesson) => [
                        'id' => $lesson->id,
                        'title' => $lesson->title,
                        'is_completed' => $progressMap->has($lesson->id) && (bool) $progressMap[$lesson->id]->is_completed,
                        'watched_seconds' => $progressMap[$lesson->id]->watched_seconds ?? 0,
                    ]),
                ];
            });

        return $this->success([
            'course_id' => $course->id,
            'total_lessons' => $totalLessons,
            'completed_lessons' => $completedLessons,
            'percent' => $percent,
            'sections' => $sections,
        ], 'Lấy tiến độ học thành công.');
    }

    public function myNotes(int $id): JsonResponse
    {
        $lesson = $this->repository->find($id);

        if (! $lesson) {
            return $this->error('Bài giảng không tồn tại.', 404);
        }

        if (! $this->courseRepo->isEnrolled($lesson->course_id, auth('api')->id())) {
            return $this->error('Bạn chưa mua khóa học này.', 403);
        }

        $notes = LessonNote::where('student_id', auth('api')->id())
            ->where('lesson_id', $id)
            ->orderBy('timestamp_seconds')
            ->orderBy('created_at')
            ->get(['id', 'content', 'timestamp_seconds', 'created_at', 'updated_at']);

        return $this->success($notes, 'Lấy danh sách ghi chú thành công.');
    }

    public function storeNote(StoreNoteRequest $request, int $id): JsonResponse
    {
        $lesson = $this->repository->find($id);

        if (! $lesson) {
            return $this->error('Bài giảng không tồn tại.', 404);
        }

        if (! $this->courseRepo->isEnrolled($lesson->course_id, auth('api')->id())) {
            return $this->error('Bạn chưa mua khóa học này.', 403);
        }

        $note = LessonNote::create([
            'student_id' => auth('api')->id(),
            'lesson_id' => $id,
            'content' => $request->validated()['content'],
            'timestamp_seconds' => $request->validated()['timestamp_seconds'] ?? null,
        ]);

        return $this->success(
            $note->only(['id', 'content', 'timestamp_seconds', 'created_at', 'updated_at']),
            'Đã thêm ghi chú.',
            201
        );
    }

    public function updateNote(UpdateNoteRequest $request, int $noteId): JsonResponse
    {
        $note = LessonNote::where('id', $noteId)
            ->where('student_id', auth('api')->id())
            ->first();

        if (! $note) {
            return $this->error('Ghi chú không tồn tại.', 404);
        }

        $note->update(['content' => $request->validated()['content']]);

        return $this->success(
            $note->refresh()->only(['id', 'content', 'timestamp_seconds', 'created_at', 'updated_at']),
            'Đã cập nhật ghi chú.'
        );
    }

    public function destroyNote(int $noteId): JsonResponse
    {
        $deleted = LessonNote::where('id', $noteId)
            ->where('student_id', auth('api')->id())
            ->delete();

        if (! $deleted) {
            return $this->error('Ghi chú không tồn tại.', 404);
        }

        return $this->success(null, 'Đã xóa ghi chú.');
    }

    private function formatLesson($lesson, $progressMap): array
    {
        $progress = $progressMap->get($lesson->id);

        return [
            'id' => $lesson->id,
            'title' => $lesson->title,
            'slug' => $lesson->slug,
            'type' => $lesson->type,
            'order' => $lesson->order,
            'is_preview' => $lesson->is_preview,
            'duration' => $lesson->duration,
            'progress' => $progress ? [
                'is_completed' => (bool) $progress->is_completed,
                'watched_seconds' => $progress->watched_seconds,
                'completed_at' => $progress->completed_at,
            ] : null,
        ];
    }
}
