<?php

namespace Modules\Payment\Exports;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Modules\Payment\Models\OrderItem;

class OrdersExport implements FromQuery, WithHeadings, WithMapping
{
    private int $rowNumber = 0;

    public function __construct(
        private readonly ?string $from,
        private readonly ?string $to,
        private readonly ?string $status,
    ) {}

    public function query(): Builder
    {
        return OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('students', 'orders.student_id', '=', 'students.id')
            ->join('courses', 'order_items.course_id', '=', 'courses.id')
            ->select(
                'order_items.id',
                'orders.order_code',
                'orders.subtotal',
                'orders.discount_amount',
                'orders.total_amount',
                'orders.payment_method',
                'orders.status as order_status',
                'orders.paid_at',
                'students.name as student_name',
                'students.email as student_email',
                'courses.name as course_name',
            )
            ->when($this->status, fn ($q) => $q->where('orders.status', $this->status))
            ->when($this->from, fn ($q) => $q->whereRaw(
                'COALESCE(orders.paid_at, orders.created_at) >= ?',
                [$this->from.' 00:00:00']
            ))
            ->when($this->to, fn ($q) => $q->whereRaw(
                'COALESCE(orders.paid_at, orders.created_at) <= ?',
                [$this->to.' 23:59:59']
            ))
            ->orderBy('orders.created_at', 'desc');
    }

    public function headings(): array
    {
        return [
            '#',
            'Mã đơn hàng',
            'Học viên',
            'Email',
            'Khóa học',
            'Tổng tiền (₫)',
            'Giảm giá (₫)',
            'Thanh toán (₫)',
            'Phương thức',
            'Trạng thái',
            'Ngày thanh toán',
        ];
    }

    public function map($row): array
    {
        $this->rowNumber++;

        $statusMap = [
            'paid' => 'Đã thanh toán',
            'pending' => 'Chờ thanh toán',
            'failed' => 'Thất bại',
            'cancelled' => 'Đã hủy',
            'refunded' => 'Hoàn tiền',
        ];

        return [
            $this->rowNumber,
            $row->order_code,
            $row->student_name,
            $row->student_email,
            $row->course_name,
            (int) $row->subtotal,
            (int) $row->discount_amount,
            (int) $row->total_amount,
            $row->payment_method,
            $statusMap[$row->order_status] ?? $row->order_status,
            $row->paid_at ? Carbon::parse($row->paid_at)->format('d/m/Y H:i') : '',
        ];
    }
}
