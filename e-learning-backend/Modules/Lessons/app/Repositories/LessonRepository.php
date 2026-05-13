<?php

namespace Modules\Lessons\Repositories;

use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;
use Modules\Lessons\Models\Lesson;
use Modules\Lessons\Models\LessonProgress;

class LessonRepository extends BaseRepository implements LessonRepositoryInterface
{
    public function __construct(Lesson $model)
    {
        parent::__construct($model);
    }

    public function getByCourse(int $courseId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $perPage = max(1, min($perPage, static::MAX_PER_PAGE));

        $query = $this->model->newQuery()
            ->where('course_id', $courseId)
            ->ordered();

        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', (int) $filters['status']);
        }

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        return $query->paginate($perPage);
    }

    public function getPublishedByCourse(int $courseId): Collection
    {
        return $this->model->newQuery()
            ->where('course_id', $courseId)
            ->published()
            ->ordered()
            ->get();
    }

    public function findBySlug(string $slug): ?Model
    {
        return $this->model->newQuery()
            ->where('slug', $slug)
            ->first();
    }

    public function toggleStatus(int $id): Model
    {
        $lesson = $this->model->newQuery()->findOrFail($id);
        $lesson->update(['status' => $lesson->status === 1 ? 0 : 1]);
        $lesson->refresh();

        return $lesson;
    }

    public function reorder(array $orders): void
    {
        DB::transaction(function () use ($orders) {
            foreach ($orders as $item) {
                Lesson::where('id', $item['id'])->update(['order' => $item['order']]);
            }
        });
    }

    public function findTrashed(int $id): Model
    {
        return $this->model->newQuery()->onlyTrashed()->findOrFail($id);
    }

    public function countInScope(int $courseId, ?int $sectionId = null): int
    {
        $query = $this->model->newQuery()->where('course_id', $courseId);

        if ($sectionId !== null) {
            $query->where('section_id', $sectionId);
        }

        return $query->count();
    }

    public function getByIds(array $ids): Collection
    {
        return $this->model->newQuery()->whereIn('id', $ids)->get();
    }

    public function getManyTrashed(array $ids): Collection
    {
        return $this->model->newQuery()->onlyTrashed()->whereIn('id', $ids)->get();
    }

    public function getDistinctCourseIds(array $ids, bool $onlyTrashed = false): SupportCollection
    {
        $query = $this->model->newQuery()->whereIn('id', $ids);

        if ($onlyTrashed) {
            $query->onlyTrashed();
        }

        return $query->distinct()->pluck('course_id');
    }

    public function assignSection(array $ids, ?int $sectionId): int
    {
        return $this->model->newQuery()->whereIn('id', $ids)->update(['section_id' => $sectionId]);
    }

    public function getProgressMap(int $studentId, int $courseId): SupportCollection
    {
        return LessonProgress::where('student_id', $studentId)
            ->where('course_id', $courseId)
            ->get()
            ->keyBy('lesson_id');
    }

    public function findProgress(int $studentId, int $lessonId): ?LessonProgress
    {
        return LessonProgress::where('student_id', $studentId)
            ->where('lesson_id', $lessonId)
            ->first();
    }

    public function updateOrCreateProgress(int $studentId, int $lessonId, int $courseId, array $data): LessonProgress
    {
        return LessonProgress::updateOrCreate(
            ['student_id' => $studentId, 'lesson_id' => $lessonId],
            array_merge($data, ['course_id' => $courseId])
        );
    }

    public function getOrphanPublished(int $courseId): Collection
    {
        return $this->model->newQuery()
            ->where('course_id', $courseId)
            ->whereNull('section_id')
            ->where('status', 1)
            ->ordered()
            ->get();
    }

    public function findPublishedByCourseAndSlug(int $courseId, string $slug): ?Model
    {
        return $this->model->newQuery()
            ->where('course_id', $courseId)
            ->where('slug', $slug)
            ->where('status', 1)
            ->with(['video', 'document'])
            ->first();
    }
}
