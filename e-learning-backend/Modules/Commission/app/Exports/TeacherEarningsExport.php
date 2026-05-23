<?php

namespace Modules\Commission\Exports;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Modules\Commission\Models\TeacherEarning;

class TeacherEarningsExport implements FromQuery, WithHeadings, WithMapping
{
    private int $rowNumber = 0;

    public function __construct(
        private readonly ?string $from,
        private readonly ?string $to,
        private readonly ?int $teacherId,
        private readonly bool $showTeacherColumn,
    ) {}

    public function query(): Builder
    {
        return TeacherEarning::query()
            ->join('teachers', 'teacher_earnings.teacher_id', '=', 'teachers.id')
            ->join('order_items', 'teacher_earnings.order_item_id', '=', 'order_items.id')
            ->join('courses', 'order_items.course_id', '=', 'courses.id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->select(
                'teacher_earnings.id',
                'teacher_earnings.type',
                'teacher_earnings.amount',
                'teacher_earnings.commission_rate',
                'teacher_earnings.created_at',
                'teachers.name as teacher_name',
                'courses.name as course_name',
                'orders.order_code',
                'order_items.final_price as revenue',
            )
            ->when($this->teacherId, fn ($q) => $q->where('teacher_earnings.teacher_id', $this->teacherId))
            ->when($this->from, fn ($q) => $q->whereDate('teacher_earnings.created_at', '>=', $this->from))
            ->when($this->to, fn ($q) => $q->whereDate('teacher_earnings.created_at', '<=', $this->to))
            ->orderBy('teacher_earnings.created_at', 'desc');
    }

    public function headings(): array
    {
        $columns = ['#', 'Khóa học', 'Mã đơn hàng', 'Doanh thu (₫)', 'Tỷ lệ HH (%)', 'Thu nhập (₫)', 'Loại', 'Ngày'];

        if ($this->showTeacherColumn) {
            array_splice($columns, 1, 0, ['Giảng viên']);
        }

        return $columns;
    }

    public function map($row): array
    {
        $this->rowNumber++;

        $typeMap = [
            'credit' => 'Thu nhập',
            'debit' => 'Hoàn trả',
        ];

        $data = [
            $this->rowNumber,
            $row->course_name,
            $row->order_code,
            (int) $row->revenue,
            $row->commission_rate,
            (int) $row->amount,
            $typeMap[$row->type] ?? $row->type,
            Carbon::parse($row->created_at)->format('d/m/Y'),
        ];

        if ($this->showTeacherColumn) {
            array_splice($data, 1, 0, [$row->teacher_name]);
        }

        return $data;
    }
}
