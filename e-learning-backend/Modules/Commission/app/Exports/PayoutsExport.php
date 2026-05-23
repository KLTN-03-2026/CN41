<?php

namespace Modules\Commission\Exports;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Modules\Commission\Models\TeacherPayout;

class PayoutsExport implements FromQuery, WithHeadings, WithMapping
{
    private int $rowNumber = 0;

    public function __construct(
        private readonly ?string $from,
        private readonly ?string $to,
        private readonly ?string $status,
    ) {}

    public function query(): Builder
    {
        return TeacherPayout::query()
            ->join('teachers', 'teacher_payouts.teacher_id', '=', 'teachers.id')
            ->join('users', 'teachers.user_id', '=', 'users.id')
            ->select(
                'teacher_payouts.*',
                'teachers.name as teacher_name',
                'users.email as teacher_email',
            )
            ->when($this->status, fn ($q) => $q->where('teacher_payouts.status', $this->status))
            ->when($this->from, fn ($q) => $q->whereDate('teacher_payouts.created_at', '>=', $this->from))
            ->when($this->to, fn ($q) => $q->whereDate('teacher_payouts.created_at', '<=', $this->to))
            ->orderBy('teacher_payouts.created_at', 'desc');
    }

    public function headings(): array
    {
        return [
            '#',
            'Giảng viên',
            'Email',
            'Số tiền (₫)',
            'Trạng thái',
            'Ghi chú GV',
            'Ghi chú Admin',
            'Ngày xử lý',
            'Ngày tạo',
        ];
    }

    public function map($row): array
    {
        $this->rowNumber++;

        $statusMap = [
            'pending' => 'Chờ duyệt',
            'approved' => 'Đã duyệt',
            'rejected' => 'Từ chối',
            'paid' => 'Đã thanh toán',
        ];

        return [
            $this->rowNumber,
            $row->teacher_name,
            $row->teacher_email,
            (int) $row->amount,
            $statusMap[$row->status] ?? $row->status,
            $row->teacher_note ?? '',
            $row->admin_note ?? '',
            $row->processed_at ? Carbon::parse($row->processed_at)->format('d/m/Y H:i') : '',
            Carbon::parse($row->created_at)->format('d/m/Y H:i'),
        ];
    }
}
