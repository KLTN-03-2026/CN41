<?php

namespace Modules\Course\Repositories;

use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;
use Modules\Categories\Models\Category;
use Modules\Course\Models\Course;
use Modules\Lessons\Models\Lesson;
use Modules\Lessons\Models\Section;

/**
 * Class CourseRepository
 *
 * Eloquent implementation cho CourseRepositoryInterface.
 * Extends BaseRepository (đã có sẵn base methods + clamp perPage, soft-delete support).
 */
class CourseRepository extends BaseRepository implements CourseRepositoryInterface
{
    public function __construct(Course $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritDoc}
     */
    public function getFiltered(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $perPage = max(1, min($perPage, static::MAX_PER_PAGE));

        $query = $this->model->newQuery()
            ->with(['teacher', 'categories'])
            ->latest();

        if (! empty($filters['search'])) {
            $query->where('name', 'like', '%'.$filters['search'].'%');
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', (int) $filters['status']);
        }

        if (! empty($filters['teacher_id'])) {
            $query->where('teacher_id', (int) $filters['teacher_id']);
        }

        if (! empty($filters['category_id'])) {
            $category = Category::find((int) $filters['category_id']);
            if ($category) {
                $categoryIds = $category->descendants()->pluck('id')->push($category->id)->toArray();
                $query->whereHas('categories', function ($q) use ($categoryIds) {
                    $q->whereIn('categories.id', $categoryIds);
                });
            }
        }

        if (! empty($filters['level'])) {
            $query->where('level', $filters['level']);
        }

        return $query->paginate($perPage);
    }

    /**
     * {@inheritDoc}
     */
    public function getPublished(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $perPage = max(1, min($perPage, static::MAX_PER_PAGE));

        $query = $this->model->newQuery()
            ->published()
            ->with(['teacher', 'categories'])
            ->latest();

        if (! empty($filters['search'])) {
            $query->where('name', 'like', '%'.$filters['search'].'%');
        }

        if (! empty($filters['category_id'])) {
            $category = Category::find((int) $filters['category_id']);
            if ($category) {
                $categoryIds = $category->descendants()->pluck('id')->push($category->id)->toArray();
                $query->whereHas('categories', function ($q) use ($categoryIds) {
                    $q->whereIn('categories.id', $categoryIds);
                });
            }
        }

        if (! empty($filters['level'])) {
            $query->where('level', $filters['level']);
        }

        return $query->paginate($perPage);
    }

    /**
     * {@inheritDoc}
     */
    public function findBySlug(string $slug, bool $publishedOnly = false): ?Model
    {
        $query = $this->model->newQuery()
            ->where('slug', $slug)
            ->with(['teacher', 'categories.ancestors']);

        if ($publishedOnly) {
            $query->published();
        }

        return $query->first();
    }

    /**
     * {@inheritDoc}
     */
    public function getByTeacher(int $teacherId, int $perPage = 15): LengthAwarePaginator
    {
        $perPage = max(1, min($perPage, static::MAX_PER_PAGE));

        return $this->model->newQuery()
            ->where('teacher_id', $teacherId)
            ->with(['categories'])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * {@inheritDoc}
     */
    public function toggleStatus(int $id): Model
    {
        $course = $this->model->newQuery()->findOrFail($id);
        $course->update(['status' => $course->status === 1 ? 0 : 1]);
        $course->refresh();
        $course->load(['teacher', 'categories']);

        return $course;
    }

    /**
     * {@inheritDoc}
     */
    public function incrementStudentCount(int $courseId): void
    {
        $this->model->newQuery()->where('id', $courseId)->increment('total_students');
    }

    /**
     * {@inheritDoc}
     */
    public function decrementStudentCount(int $courseId): void
    {
        $this->model->newQuery()->where('id', $courseId)->where('total_students', '>', 0)->decrement('total_students');
    }

    /**
     * {@inheritDoc}
     */
    public function syncCategories(int $courseId, array $categoryIds): void
    {
        $course = $this->model->newQuery()->findOrFail($courseId);
        $course->categories()->sync($categoryIds);
    }

    /**
     * {@inheritDoc}
     */
    public function getByStudent(int $studentId, int $perPage = 15): LengthAwarePaginator
    {
        $perPage = max(1, min($perPage, static::MAX_PER_PAGE));

        return $this->model->newQuery()
            ->whereHas('students', fn ($q) => $q->where('students.id', $studentId))
            ->with(['teacher', 'categories'])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * {@inheritDoc}
     */
    public function findTrashed(int $id): Model
    {
        return $this->model->newQuery()->onlyTrashed()->findOrFail($id);
    }

    /**
     * {@inheritDoc}
     */
    public function findManyTrashed(array $ids): Collection
    {
        return $this->model->newQuery()->onlyTrashed()->whereIn('id', $ids)->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getFeatured(int $limit = 8): Collection
    {
        return $this->model->newQuery()
            ->published()
            ->with(['teacher', 'categories'])
            ->orderByDesc('rating')
            ->limit(max(1, min($limit, 20)))
            ->get();
    }

    /**
     * Override restoreMany để trigger model events (restoring/restored)
     * cho từng Course, từ đó cascade restore sections & lessons.
     *
     * BaseRepository dùng mass query .restore() — không trigger events.
     */
    public function restoreMany(array $ids): int
    {
        $count = 0;

        $this->model->newQuery()
            ->onlyTrashed()
            ->whereIn('id', $ids)
            ->each(function (Course $course) use (&$count) {
                if ($course->restore()) {
                    $count++;
                }
            });

        return $count;
    }

    /**
     * {@inheritDoc}
     */
    public function getPublicSectionsWithLessons(int $courseId): SupportCollection
    {
        return Section::where('course_id', $courseId)
            ->where('status', 1)
            ->with(['lessons' => fn ($q) => $q->where('status', 1)])
            ->ordered()
            ->get()
            ->map(fn (Section $section) => [
                'id' => $section->id,
                'title' => $section->title,
                'order' => $section->order,
                'lessons' => $section->lessons->map(fn (Lesson $lesson) => [
                    'id' => $lesson->id,
                    'title' => $lesson->title,
                    'slug' => $lesson->slug,
                    'type' => $lesson->type,
                    'order' => $lesson->order,
                    'is_preview' => $lesson->is_preview,
                    'duration' => $lesson->duration,
                ])->values(),
            ])
            ->values();
    }

    /**
     * {@inheritDoc}
     */
    public function findPublicPreviewLesson(int $courseId, string $lessonSlug): ?Model
    {
        return Lesson::where('course_id', $courseId)
            ->where('slug', $lessonSlug)
            ->where('status', 1)
            ->with(['video', 'document'])
            ->first();
    }

    /**
     * {@inheritDoc}
     */
    public function isEnrolled(int $courseId, int $studentId): bool
    {
        return DB::table('students_course')
            ->where('course_id', $courseId)
            ->where('student_id', $studentId)
            ->exists();
    }

    /**
     * {@inheritDoc}
     */
    public function enrollStudent(int $courseId, int $studentId): void
    {
        DB::transaction(function () use ($courseId, $studentId) {
            $course = $this->model->newQuery()->findOrFail($courseId);
            $result = $course->students()->syncWithoutDetaching([$studentId => ['enrolled_at' => now()]]);
            if (! empty($result['attached'])) {
                $this->incrementStudentCount($courseId);
            }
        });
    }

    /**
     * {@inheritDoc}
     */
    public function bulkForceDelete(array $ids): int
    {
        $courses = $this->findManyTrashed($ids);

        DB::transaction(function () use ($courses) {
            foreach ($courses as $course) {
                $course->forceDelete();
            }
        });

        return $courses->count();
    }
}
