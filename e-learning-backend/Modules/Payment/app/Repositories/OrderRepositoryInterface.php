<?php

namespace Modules\Payment\Repositories;

use App\Repositories\RepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Payment\Models\Order;

interface OrderRepositoryInterface extends RepositoryInterface
{
    public function getFiltered(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function getByStudent(int $studentId, int $perPage = 15): LengthAwarePaginator;

    public function findByOrderCode(string $orderCode): ?Order;

    public function createWithItems(array $orderData, array $items): Order;

    public function markAsPaid(int $orderId): Order;

    public function updateOrderStatus(int $id, array $data): Order;

    public function getRevenueStats(string $period = 'monthly', ?string $from = null, ?string $to = null): array;

    public function checkDuplicateEnrollment(int $studentId, array $courseIds): array;
}
