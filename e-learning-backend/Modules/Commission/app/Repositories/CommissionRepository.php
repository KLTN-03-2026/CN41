<?php

namespace Modules\Commission\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Commission\Models\TeacherEarning;
use Modules\Commission\Models\TeacherPayout;

class CommissionRepository implements CommissionRepositoryInterface
{
    public function getAvailableBalance(int $teacherId): float
    {
        $totalEarned = TeacherEarning::where('teacher_id', $teacherId)->where('type', 'credit')->sum('amount');
        $totalDeducted = TeacherEarning::where('teacher_id', $teacherId)->where('type', 'debit')->sum('amount');
        $pendingPayouts = TeacherPayout::where('teacher_id', $teacherId)->whereIn('status', ['pending', 'approved'])->sum('amount');

        return (float) max(0, $totalEarned - $totalDeducted - $pendingPayouts);
    }

    public function getTotalEarned(int $teacherId): float
    {
        return (float) TeacherEarning::where('teacher_id', $teacherId)->where('type', 'credit')->sum('amount');
    }

    public function getTotalPaid(int $teacherId): float
    {
        return (float) TeacherPayout::where('teacher_id', $teacherId)->where('status', 'paid')->sum('amount');
    }

    public function getPendingPayoutAmount(int $teacherId): float
    {
        return (float) TeacherPayout::where('teacher_id', $teacherId)->whereIn('status', ['pending', 'approved'])->sum('amount');
    }

    public function getEarningsForTeacher(int $teacherId, int $perPage): LengthAwarePaginator
    {
        $perPage = max(1, min($perPage, 100));

        return TeacherEarning::where('teacher_id', $teacherId)->latest()->paginate($perPage);
    }

    public function getTeachersSummary(): Collection
    {
        // Correlated subqueries avoid cartesian product from joining both tables simultaneously
        return DB::table('teachers')
            ->whereNull('teachers.deleted_at')
            ->select([
                'teachers.id',
                'teachers.name',
                DB::raw("COALESCE((SELECT SUM(amount) FROM teacher_earnings WHERE teacher_id = teachers.id AND type = 'credit'), 0) as total_earned"),
                DB::raw("COALESCE((SELECT SUM(amount) FROM teacher_earnings WHERE teacher_id = teachers.id AND type = 'debit'), 0) as total_deducted"),
                DB::raw("COALESCE((SELECT SUM(amount) FROM teacher_payouts WHERE teacher_id = teachers.id AND status IN ('pending', 'approved')), 0) as pending_payout"),
            ])
            ->get()
            ->map(function ($row) {
                $row->available_balance = max(0, $row->total_earned - $row->total_deducted - $row->pending_payout);

                return $row;
            });
    }
}
