<?php

namespace Modules\Commission\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Modules\Commission\Repositories\CommissionRepositoryInterface;

class TeacherEarningsController extends Controller
{
    use ApiResponse;

    public function __construct(private CommissionRepositoryInterface $repository) {}

    public function index(): JsonResponse
    {
        return $this->success($this->repository->getTeachersSummary(), 'Tổng hợp hoa hồng giảng viên.');
    }
}
