<?php

namespace Modules\Dashboard\Repositories;

use Illuminate\Support\Facades\DB;
use Modules\Course\Models\Course;
use Modules\Payment\Models\Order;
use Modules\Payment\Models\OrderItem;
use Modules\Students\Models\Student;

class DashboardRepository
{
    public function getSummary(): array
    {
        return [
            'total_students' => Student::count(),
            'total_courses' => Course::where('status', 1)->count(),
            'total_orders' => Order::where('status', 'paid')->count(),
            'total_revenue' => (float) Order::where('status', 'paid')->sum('total_amount'),
        ];
    }

    public function getMonthlyRevenue(int $year): array
    {
        $monthExpression = DB::getDriverName() === 'sqlite'
            ? "CAST(strftime('%m', created_at) AS INTEGER)"
            : 'MONTH(created_at)';

        $rows = Order::select(
            DB::raw("$monthExpression as month"),
            DB::raw('SUM(total_amount) as revenue')
        )
            ->where('status', 'paid')
            ->whereYear('created_at', $year)
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $monthsMap = $rows->pluck('revenue', 'month')->toArray();

        $result = [];
        for ($i = 1; $i <= 12; $i++) {
            $result[] = [
                'month' => $i,
                'revenue' => isset($monthsMap[$i]) ? (float) $monthsMap[$i] : 0,
            ];
        }

        return $result;
    }

    public function getTopCourses(int $limit = 5): array
    {
        return OrderItem::select(
            'course_id',
            DB::raw('SUM(final_price) as total_revenue'),
            DB::raw('COUNT(*) as sales_count')
        )
            ->whereHas('order', fn ($q) => $q->where('status', 'paid'))
            ->with(['course:id,name,thumbnail,price'])
            ->groupBy('course_id')
            ->orderByDesc('total_revenue')
            ->take($limit)
            ->get()
            ->map(fn ($item) => [
                'id' => $item->course_id,
                'title' => $item->course->name ?? 'Unknown',
                'thumbnail' => $item->course->thumbnail ?? null,
                'price' => $item->course->price ?? 0,
                'sales_count' => $item->sales_count,
                'revenue' => (float) $item->total_revenue,
            ])
            ->values()
            ->all();
    }

    public function getRecentOrders(int $limit = 5): array
    {
        return Order::with(['student:id,name,email', 'items.course:id,name'])
            ->orderByDesc('created_at')
            ->take($limit)
            ->get()
            ->map(fn ($order) => [
                'id' => $order->id,
                'order_code' => $order->order_code,
                'student_name' => $order->student->name ?? 'Unknown',
                'student_email' => $order->student->email ?? 'Unknown',
                'course_title' => $order->items->first()?->course->name ?? 'N/A',
                'amount' => (float) $order->total_amount,
                'status' => $order->status,
                'created_at' => $order->created_at->toIso8601String(),
            ])
            ->values()
            ->all();
    }
}
