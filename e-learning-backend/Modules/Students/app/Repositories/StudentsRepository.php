<?php

namespace Modules\Students\Repositories;

use App\Repositories\BaseRepository;
use Modules\Students\Models\Student;

/**
 * Class StudentsRepository
 *
 * Eloquent implementation cho StudentsRepositoryInterface.
 * Extends BaseRepository (đã có sẵn 9 methods chuẩn + clamp perPage, soft-delete support).
 * Thêm các method riêng cho Students tại đây.
 */
class StudentsRepository extends BaseRepository implements StudentsRepositoryInterface
{
    public function __construct(Student $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritDoc}
     */
    public function getFiltered(array $filters = [], int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $perPage = max(1, min($perPage, static::MAX_PER_PAGE));

        $query = $this->model->newQuery()
            ->latest();

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                  ->orWhere('email', 'like', '%'.$search.'%');
            });
        }

        return $query->paginate($perPage);
    }
}
