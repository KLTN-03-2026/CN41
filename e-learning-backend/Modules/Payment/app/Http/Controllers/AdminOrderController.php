<?php

namespace Modules\Payment\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Payment\Events\OrderRefunded;
use Modules\Payment\Exports\OrdersExport;
use Modules\Payment\Http\Requests\AdminIndexOrderRequest;
use Modules\Payment\Http\Requests\AdminTrashedOrderRequest;
use Modules\Payment\Http\Requests\BulkDeleteOrdersRequest;
use Modules\Payment\Http\Requests\ExportOrdersRequest;
use Modules\Payment\Http\Requests\RevenueStatsRequest;
use Modules\Payment\Http\Requests\UpdateOrderStatusRequest;
use Modules\Payment\Http\Resources\OrderResource;
use Modules\Payment\Repositories\OrderRepositoryInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AdminOrderController extends Controller
{
    use ApiResponse;

    public function __construct(
        private OrderRepositoryInterface $repository,
    ) {}

    public function index(AdminIndexOrderRequest $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);
        $filters = $request->only(['search', 'status', 'from', 'to', 'payment_method']);

        $data = $this->repository->getFiltered($filters, $perPage);
        $data->setCollection(OrderResource::collection($data->getCollection())->collection);

        return $this->paginated($data);
    }

    public function show(int $id): JsonResponse
    {
        $order = $this->repository->findOrFail($id, ['*'], ['student', 'items.course', 'transactions']);

        return $this->success(new OrderResource($order));
    }

    public function updateStatus(UpdateOrderStatusRequest $request, int $id): JsonResponse
    {
        $order = $this->repository->findOrFail($id);
        $oldStatus = $order->status;
        $newStatus = $request->validated()['status'];

        $updateData = ['status' => $newStatus];

        if ($request->filled('note')) {
            $updateData['note'] = $request->input('note');
        }

        if ($newStatus === 'paid' && $oldStatus !== 'paid') {
            $updateData['paid_at'] = now();
        }

        $updated = $this->repository->updateOrderStatus($id, $updateData);

        if ($newStatus === 'refunded' && $oldStatus !== 'refunded') {
            $updated->load('items.course.teacher');
            event(new OrderRefunded($updated));
        }

        return $this->success(
            new OrderResource($updated),
            "Trạng thái đơn hàng đã cập nhật: {$oldStatus} → {$newStatus}."
        );
    }

    public function trashed(AdminTrashedOrderRequest $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);
        $data = $this->repository->paginateTrashed($perPage, ['*'], ['student', 'items.course']);
        $data->setCollection(OrderResource::collection($data->getCollection())->collection);

        return $this->paginated($data);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->repository->delete($id);

        return $this->success(null, 'Đơn hàng đã được xoá thành công.');
    }

    public function restore(int $id): JsonResponse
    {
        $this->repository->restore($id);

        return $this->success(null, 'Đơn hàng đã được khôi phục thành công.');
    }

    public function bulkDelete(BulkDeleteOrdersRequest $request): JsonResponse
    {
        $deleted = DB::transaction(function () use ($request) {
            return $this->repository->deleteMany($request->validated()['ids']);
        });

        return $this->success(
            ['deleted_count' => $deleted],
            "Đã xoá {$deleted} đơn hàng thành công."
        );
    }

    public function revenueStats(RevenueStatsRequest $request): JsonResponse
    {
        $period = $request->query('period', 'monthly');
        $from = $request->query('from');
        $to = $request->query('to');

        $stats = $this->repository->getRevenueStats($period, $from, $to);

        return $this->success($stats, 'Lấy thống kê doanh thu thành công.');
    }

    public function export(ExportOrdersRequest $request): BinaryFileResponse
    {
        $from = $request->query('from', now()->startOfMonth()->format('Y-m-d'));
        $to = $request->query('to', now()->format('Y-m-d'));

        $filename = "don-hang_{$from}_{$to}.xlsx";

        return Excel::download(
            new OrdersExport($from, $to, $request->query('status')),
            $filename
        );
    }
}
