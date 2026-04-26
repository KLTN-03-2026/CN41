<?php

namespace Modules\Dashboard\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Modules\Course\Models\Course;
use Modules\Payment\Models\Order;
use Modules\Payment\Models\OrderItem;
use Modules\Students\Models\Student;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics for admin
     */
    public function getStats(): JsonResponse
    {
        // 1. Total students
        $totalStudents = Student::count();

        // 2. Total courses (published)
        $totalCourses = Course::where('status', 1)->count();

        // 3. Total orders (paid)
        $totalOrders = Order::where('status', 'paid')->count();

        // 4. Total revenue
        $totalRevenue = Order::where('status', 'paid')->sum('total_amount');

        // 5. Monthly revenue (current year)
        $currentYear = date('Y');
        $monthlyRevenueQuery = Order::select(
            DB::raw('MONTH(created_at) as month'),
            DB::raw('SUM(total_amount) as revenue')
        )
            ->where('status', 'paid')
            ->whereYear('created_at', $currentYear)
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $monthlyRevenue = [];
        $monthsMap = $monthlyRevenueQuery->pluck('revenue', 'month')->toArray();
        for ($i = 1; $i <= 12; $i++) {
            $monthlyRevenue[] = [
                'month' => $i,
                'revenue' => isset($monthsMap[$i]) ? (float) $monthsMap[$i] : 0,
            ];
        }

        // 6. Top courses (best selling)
        $topCourses = OrderItem::select('course_id', DB::raw('SUM(final_price) as total_revenue'), DB::raw('COUNT(*) as sales_count'))
            ->whereHas('order', function ($query) {
                $query->where('status', 'paid');
            })
            ->with(['course:id,title,thumbnail,price'])
            ->groupBy('course_id')
            ->orderBy('total_revenue', 'desc')
            ->take(5)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->course_id,
                    'title' => $item->course->title ?? 'Unknown',
                    'thumbnail' => $item->course->thumbnail ?? null,
                    'price' => $item->course->price ?? 0,
                    'sales_count' => $item->sales_count,
                    'revenue' => (float) $item->total_revenue,
                ];
            });

        // 7. Recent orders
        $recentOrders = Order::with(['student:id,name,email', 'items.course:id,title'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'order_code' => $order->order_code,
                    'student_name' => $order->student->name ?? 'Unknown',
                    'student_email' => $order->student->email ?? 'Unknown',
                    'course_title' => $order->items->first()->course->title ?? 'N/A',
                    'amount' => (float) $order->total_amount,
                    'status' => $order->status,
                    'created_at' => $order->created_at->toIso8601String(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => [
                    'total_students' => $totalStudents,
                    'total_courses' => $totalCourses,
                    'total_orders' => $totalOrders,
                    'total_revenue' => (float) $totalRevenue,
                ],
                'monthly_revenue' => $monthlyRevenue,
                'top_courses' => $topCourses,
                'recent_orders' => $recentOrders,
            ],
        ]);
    }
}
