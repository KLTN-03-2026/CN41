<?php

namespace Modules\Commission\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Commission\Repositories\CommissionRepositoryInterface;

class TeacherEarningsController extends Controller
{
    use ApiResponse;

    public function __construct(private CommissionRepositoryInterface $repository) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min((int) $request->query('per_page', 15), 100));

        return $this->paginated($this->repository->getTeachersSummary($perPage), 'Tổng hợp hoa hồng giảng viên.');
    }
}
