<?php

namespace Modules\Lessons\Repositories;

use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;
use Modules\Lessons\Models\Section;

class SectionRepository extends BaseRepository implements SectionRepositoryInterface
{
    public function __construct(Section $model)
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

        return $query->paginate($perPage);
    }

    public function toggleStatus(int $id): Model
    {
        $section = $this->model->newQuery()->findOrFail($id);
        $section->update(['status' => $section->status === 1 ? 0 : 1]);
        $section->refresh();

        return $section;
    }

    public function reorder(array $orders): void
    {
        DB::transaction(function () use ($orders) {
            foreach ($orders as $item) {
                Section::where('id', $item['id'])->update(['order' => $item['order']]);
            }
        });
    }

    /**
     * Xóa chương nhưng giữ lại các bài giảng (chuyển về Chưa phân chương).
     */
    public function delete(int $id): bool
    {
        $section = $this->model->newQuery()->findOrFail($id);

        DB::transaction(function () use ($section) {
            DB::table('lessons')
                ->where('section_id', $section->id)
                ->whereNull('deleted_at')
                ->update(['section_id' => null]);

            $section->delete();
        });

        return true;
    }

    public function findTrashed(int $id): Model
    {
        return $this->model->newQuery()->onlyTrashed()->findOrFail($id);
    }

    public function belongsToCourse(int $sectionId, int $courseId): bool
    {
        return $this->model->newQuery()
            ->where('id', $sectionId)
            ->where('course_id', $courseId)
            ->exists();
    }

    public function getDistinctCourseIds(array $ids): SupportCollection
    {
        return $this->model->newQuery()
            ->whereIn('id', $ids)
            ->distinct()
            ->pluck('course_id');
    }

    public function getPublishedWithLessons(int $courseId): Collection
    {
        return $this->model->newQuery()
            ->where('course_id', $courseId)
            ->where('status', 1)
            ->ordered()
            ->with(['lessons' => fn ($q) => $q->where('status', 1)->ordered()])
            ->get();
    }

    public function getPublishedOrdered(int $courseId): Collection
    {
        return $this->model->newQuery()
            ->where('course_id', $courseId)
            ->where('status', 1)
            ->ordered()
            ->get();
    }

    public function countByCourse(int $courseId): int
    {
        return $this->model->newQuery()->where('course_id', $courseId)->count();
    }
}
