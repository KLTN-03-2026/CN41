<?php

namespace Modules\Commission\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface CommissionRepositoryInterface
{
    public function getAvailableBalance(int $teacherId): float;

    public function getTotalEarned(int $teacherId): float;

    public function getTotalPaid(int $teacherId): float;

    public function getPendingPayoutAmount(int $teacherId): float;

    public function getEarningsForTeacher(int $teacherId, int $perPage): LengthAwarePaginator;

    public function getTeachersSummary(int $perPage): LengthAwarePaginator;
}
