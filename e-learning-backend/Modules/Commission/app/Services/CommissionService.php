<?php

namespace Modules\Commission\Services;

use Modules\Commission\Models\CommissionSetting;
use Modules\Commission\Models\TeacherEarning;
use Modules\Commission\Repositories\CommissionRepositoryInterface;
use Modules\Payment\Models\Order;

class CommissionService
{
    public function __construct(private CommissionRepositoryInterface $repository) {}

    public function recordEarnings(Order $order): void
    {
        $rate = CommissionSetting::current()->teacher_rate;

        foreach ($order->items as $item) {
            $teacher = $item->course?->teacher;
            if (! $teacher) {
                continue;
            }

            TeacherEarning::create([
                'teacher_id'      => $teacher->id,
                'order_item_id'   => $item->id,
                'type'            => 'credit',
                'amount'          => round((float) $item->final_price * (float) $rate / 100, 2),
                'commission_rate' => $rate,
                'description'     => 'Hoa hồng từ: ' . $item->course->name,
            ]);
        }
    }

    public function reverseEarnings(Order $order): void
    {
        foreach ($order->items as $item) {
            $original = TeacherEarning::where('order_item_id', $item->id)->where('type', 'credit')->first();

            if (! $original) {
                continue;
            }

            TeacherEarning::create([
                'teacher_id'      => $original->teacher_id,
                'order_item_id'   => $item->id,
                'type'            => 'debit',
                'amount'          => $original->amount,
                'commission_rate' => $original->commission_rate,
                'description'     => 'Hoàn tiền: ' . ($item->course->name ?? 'Khóa học'),
            ]);
        }
    }

    public function getAvailableBalance(int $teacherId): float
    {
        return $this->repository->getAvailableBalance($teacherId);
    }
}
