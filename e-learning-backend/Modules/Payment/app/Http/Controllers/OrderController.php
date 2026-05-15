<?php

namespace Modules\Payment\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Payment\Http\Requests\CreateOrderRequest;
use Modules\Payment\Http\Requests\MyOrdersRequest;
use Modules\Payment\Http\Resources\OrderResource;
use Modules\Payment\Repositories\OrderRepositoryInterface;
use Modules\Payment\Services\OrderService;
use Modules\Payment\Services\VnpayService;
use Modules\Payment\Services\ZalopayService;

class OrderController extends Controller
{
    use ApiResponse;

    public function __construct(
        private OrderRepositoryInterface $repository,
        private OrderService $orderService,
        private VnpayService $vnpayService,
        private ZalopayService $zalopayService,
    ) {}

    public function store(CreateOrderRequest $request): JsonResponse
    {
        $paymentMethod = $request->input('payment_method', 'vnpay');

        try {
            $result = $this->orderService->createOrder(
                auth('api')->id(),
                $request->validated()['course_ids'],
                $request->input('coupon_code'),
                $paymentMethod
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), $e->getCode() ?: 422);
        }

        $order = $result['order'];

        if ($result['totalAmount'] <= 0) {
            $order = $this->orderService->handleFreeOrder($order);

            return $this->success([
                'order' => new OrderResource($order),
                'payment_url' => null,
            ], 'Đơn hàng miễn phí đã được xử lý. Bạn có thể vào học ngay!', 201);
        }

        try {
            $paymentUrl = $paymentMethod === 'zalopay'
                ? $this->zalopayService->createPaymentUrl($order, $request->ip())
                : $this->vnpayService->createPaymentUrl($order, $request->ip());
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 503);
        }

        $order->load(['items.course']);

        return $this->success([
            'order' => new OrderResource($order),
            'payment_url' => $paymentUrl,
        ], 'Đơn hàng đã được tạo. Vui lòng thanh toán.', 201);
    }

    public function myOrders(MyOrdersRequest $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);
        $studentId = auth('api')->id();

        $data = $this->repository->getByStudent($studentId, $perPage);
        $data->setCollection(OrderResource::collection($data->getCollection())->collection);

        return $this->paginated($data);
    }

    public function show(string $orderCode): JsonResponse
    {
        $order = $this->repository->findByOrderCode($orderCode);

        if (! $order) {
            return $this->error('Đơn hàng không tồn tại.', 404);
        }

        if ($order->student_id !== auth('api')->id()) {
            return $this->error('Bạn không có quyền xem đơn hàng này.', 403);
        }

        return $this->success(new OrderResource($order));
    }

    public function retryPayment(string $orderCode, Request $request): JsonResponse
    {
        $order = $this->repository->findByOrderCode($orderCode);

        if (! $order) {
            return $this->error('Đơn hàng không tồn tại.', 404);
        }

        if ($order->student_id !== auth('api')->id()) {
            return $this->error('Bạn không có quyền thực hiện thao tác này.', 403);
        }

        if (! $order->isPending() && ! $order->isFailed()) {
            return $this->error('Chỉ đơn hàng đang chờ hoặc thất bại mới có thể thanh toán lại.', 422);
        }

        // Nếu student đã sở hữu tất cả courses → auto-cancel đơn thay vì retry
        $order->load('items');
        $alreadyOwned = $order->items->every(fn ($item) => DB::table('students_course')
            ->where('student_id', $order->student_id)
            ->where('course_id', $item->course_id)
            ->exists()
        );

        if ($alreadyOwned) {
            $order->update(['status' => 'cancelled']);

            return $this->error('Bạn đã sở hữu tất cả khóa học trong đơn hàng này. Đơn hàng đã được hủy tự động.', 422);
        }

        $this->orderService->retryPayment($order);

        try {
            $paymentUrl = $order->payment_method === 'zalopay'
                ? $this->zalopayService->createPaymentUrl($order, $request->ip())
                : $this->vnpayService->createPaymentUrl($order, $request->ip());
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 503);
        }

        return $this->success([
            'order_code' => $order->order_code,
            'payment_url' => $paymentUrl,
        ], 'Đã tạo liên kết thanh toán mới.');
    }
}
