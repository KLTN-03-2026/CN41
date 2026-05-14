<?php

namespace Modules\Payment\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Coupons\Models\Coupon;
use Modules\Course\Models\Course;
use Modules\Payment\Models\Order;
use Modules\Payment\Models\Transaction;
use Modules\Payment\Repositories\OrderRepositoryInterface;

class OrderService
{
    public function __construct(
        private OrderRepositoryInterface $repository,
        private VnpayService $vnpayService,
    ) {}

    public function createOrder(int $studentId, array $courseIds, ?string $couponCode, string $paymentMethod = 'vnpay'): array
    {
        $courses = Course::whereIn('id', $courseIds)->published()->get();

        if ($courses->isEmpty()) {
            throw new \Exception('Không tìm thấy khóa học nào.', 404);
        }

        $items = [];
        $subtotal = 0;

        foreach ($courses as $course) {
            $price = (float) $course->price;
            $salePrice = $course->sale_price ? (float) $course->sale_price : null;
            $finalPrice = $salePrice ?? $price;

            $items[] = [
                'course_id' => $course->id,
                'price' => $price,
                'sale_price' => $salePrice,
                'final_price' => $finalPrice,
            ];

            $subtotal += $finalPrice;
        }

        $orderCode = 'ORD-'.now()->format('Ymd').'-'.strtoupper(Str::random(5));

        return DB::transaction(function () use ($studentId, $orderCode, $subtotal, $items, $couponCode, $paymentMethod) {
            $discountAmount = 0;
            $coupon = null;

            if ($couponCode) {
                $coupon = Coupon::where('code', $couponCode)->lockForUpdate()->first();

                if (! $coupon) {
                    throw new \Exception('Mã giảm giá không tồn tại.', 404);
                }

                if (! $coupon->isValid()) {
                    throw new \Exception('Mã giảm giá không hợp lệ hoặc đã hết hạn.', 422);
                }

                if ($coupon->min_order_value && $subtotal < $coupon->min_order_value) {
                    throw new \Exception('Mã giảm giá yêu cầu đơn hàng tối thiểu '.number_format($coupon->min_order_value).'đ.', 422);
                }

                $discountAmount = $coupon->calculateDiscount($subtotal);
            }

            $totalAmount = max(0, $subtotal - $discountAmount);

            $order = $this->repository->createWithItems([
                'order_code' => $orderCode,
                'student_id' => $studentId,
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'coupon_code' => $couponCode,
                'total_amount' => $totalAmount,
                'status' => 'pending',
                'payment_method' => $totalAmount > 0 ? $paymentMethod : 'free',
            ], $items);

            if ($totalAmount > 0) {
                Transaction::create([
                    'order_id' => $order->id,
                    'gateway'  => $paymentMethod,
                    'amount'   => $totalAmount,
                    'status'   => 'pending',
                ]);
            }

            if ($coupon) {
                $coupon->increment('used_count');
            }

            return ['order' => $order, 'totalAmount' => $totalAmount];
        });
    }

    public function handleFreeOrder(Order $order): Order
    {
        $order = $this->repository->update($order->id, [
            'status' => 'paid',
            'payment_method' => 'free',
            'paid_at' => now(),
        ]);

        $this->vnpayService->enrollStudent($order);
        $order->load(['items.course']);

        return $order;
    }

    public function retryPayment(Order $order): void
    {
        DB::transaction(function () use ($order) {
            if ($order->isFailed()) {
                $this->repository->update($order->id, ['status' => 'pending']);
            }

            Transaction::create([
                'order_id' => $order->id,
                'gateway'  => $order->payment_method,
                'amount'   => $order->total_amount,
                'status'   => 'pending',
            ]);
        });
    }
}
