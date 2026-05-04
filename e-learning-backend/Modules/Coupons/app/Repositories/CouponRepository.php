<?php

namespace Modules\Coupons\Repositories;

use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Modules\Coupons\Models\Coupon;

class CouponRepository extends BaseRepository implements CouponRepositoryInterface
{
    public function __construct(Coupon $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritDoc}
     */
    public function getFiltered(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $perPage = max(1, min($perPage, static::MAX_PER_PAGE));

        $query = $this->model->newQuery()->latest();

        // Filter theo code
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%');
            });
        }

        // Filter theo status
        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', (int) $filters['status']);
        }

        // Filter theo type
        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        return $query->paginate($perPage);
    }

    /**
     * {@inheritDoc}
     */
    public function getAvailable(): Collection
    {
        return $this->model->newQuery()
            ->valid()
            ->orderByRaw('end_date IS NULL ASC') // mã có hạn hiển thị trước
            ->orderBy('end_date', 'asc')
            ->limit(20)
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function findByCode(string $code): ?Model
    {
        return $this->model->newQuery()
            ->where('code', strtoupper($code))
            ->first();
    }

    /**
     * {@inheritDoc}
     */
    public function incrementUsedCount(int $id): void
    {
        $this->model->newQuery()
            ->where('id', $id)
            ->increment('used_count');
    }

    /**
     * {@inheritDoc}
     */
    public function toggleStatus(int $id): Model
    {
        $coupon = $this->model->newQuery()->findOrFail($id);
        $coupon->update(['status' => $coupon->status === 1 ? 0 : 1]);
        $coupon->refresh();

        return $coupon;
    }
}
