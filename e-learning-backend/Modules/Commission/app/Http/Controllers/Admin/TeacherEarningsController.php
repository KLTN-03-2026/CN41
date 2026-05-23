<?php

namespace Modules\Commission\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Commission\Exports\TeacherEarningsExport;
use Modules\Commission\Http\Requests\ExportEarningsRequest;
use Modules\Commission\Repositories\CommissionRepositoryInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TeacherEarningsController extends Controller
{
    use ApiResponse;

    public function __construct(private CommissionRepositoryInterface $repository) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min((int) $request->query('per_page', 15), 100));

        return $this->paginated($this->repository->getTeachersSummary($perPage), 'Tổng hợp hoa hồng giảng viên.');
    }

    public function export(ExportEarningsRequest $request): BinaryFileResponse
    {
        $from = $request->query('from', now()->startOfMonth()->format('Y-m-d'));
        $to = $request->query('to', now()->format('Y-m-d'));

        $filename = "thu-nhap_{$from}_{$to}.xlsx";

        return Excel::download(
            new TeacherEarningsExport(
                from: $from,
                to: $to,
                teacherId: $request->query('teacher_id') ? (int) $request->query('teacher_id') : null,
                showTeacherColumn: true,
            ),
            $filename
        );
    }
}
