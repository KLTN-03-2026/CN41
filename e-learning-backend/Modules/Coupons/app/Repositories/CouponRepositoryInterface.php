<?php

namespace Modules\Coupons\Repositories;

use App\Repositories\RepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

interface CouponRepositoryInterface extends RepositoryInterface
{
    /**
     * Danh sách coupons (phân trang) có filter theo code, status, type (Admin).
     */
    public function getFiltered(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Danh sách mã giảm giá còn hiệu lực (public, cho student xem).
     */
    public function getAvailable(): Collection;

    /**
     * Tìm coupon theo code.
     */
    public function findByCode(string $code): ?Model;

    /**
     * Tăng used_count sau khi áp dụng coupon thành công.
     */
    public function incrementUsedCount(int $id): void;

    /**
     * Toggle trạng thái active/inactive.
     */
    public function toggleStatus(int $id): Model;
}
