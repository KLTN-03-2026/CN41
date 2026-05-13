<?php

namespace Modules\Dashboard\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Modules\Dashboard\Repositories\DashboardRepository;

class DashboardController extends Controller
{
    use ApiResponse;

    public function __construct(private DashboardRepository $repository) {}

    public function getStats(): JsonResponse
    {
        return $this->success([
            'summary' => $this->repository->getSummary(),
            'monthly_revenue' => $this->repository->getMonthlyRevenue((int) date('Y')),
            'top_courses' => $this->repository->getTopCourses(),
            'recent_orders' => $this->repository->getRecentOrders(),
        ], 'Lấy thống kê thành công.');
    }
}
