<?php

namespace Modules\Payment\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Payment\Models\Order;
use Modules\Payment\Models\OrderItem;
use Modules\Students\Models\Student;
use Modules\Course\Models\Course;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $students = Student::pluck('id')->toArray();
        $courses  = Course::where('status', 1)->get(['id', 'price', 'sale_price']);

        if (empty($students) || $courses->isEmpty()) {
            $this->command->warn('OrderSeeder: Cần có students và courses trước.');
            return;
        }

        // Tạo 20 đơn hàng mẫu trải đều 4 tháng gần nhất
        for ($i = 0; $i < 20; $i++) {
            $studentId = $students[array_rand($students)];
            $status    = $this->randomStatus();
            $monthsAgo = rand(0, 3);
            $createdAt = now()->subMonths($monthsAgo)->subDays(rand(0, 28))->subHours(rand(0, 23));

            // Chọn 1-3 khóa học ngẫu nhiên cho đơn
            $orderCourses = $courses->random(min(rand(1, 3), $courses->count()));

            $subtotal = 0;
            $items = [];

            foreach ($orderCourses as $course) {
                $finalPrice = $course->sale_price ?? $course->price;
                $subtotal  += $finalPrice;

                $items[] = [
                    'course_id'   => $course->id,
                    'price'       => $course->price,
                    'sale_price'  => $course->sale_price,
                    'final_price' => $finalPrice,
                ];
            }

            $order = Order::create([
                'order_code'      => 'ORD-' . strtoupper(Str::random(8)),
                'student_id'      => $studentId,
                'subtotal'        => $subtotal,
                'discount_amount' => 0,
                'total_amount'    => $subtotal,
                'status'          => $status,
                'payment_method'  => 'vnpay',
                'paid_at'         => $status === 'paid' ? $createdAt->addMinutes(rand(1, 10)) : null,
                'created_at'      => $createdAt,
                'updated_at'      => $createdAt,
            ]);

            foreach ($items as $item) {
                OrderItem::create(array_merge($item, [
                    'order_id'   => $order->id,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]));
            }
        }

        $paidCount    = Order::where('status', 'paid')->count();
        $totalRevenue = Order::where('status', 'paid')->sum('total_amount');
        $this->command->info("OrderSeeder: Đã tạo 20 đơn hàng ({$paidCount} paid, tổng doanh thu: " . number_format($totalRevenue) . " VNĐ)");
    }

    private function randomStatus(): string
    {
        // 60% paid, 25% pending, 15% failed
        $rand = rand(1, 100);
        if ($rand <= 60) return 'paid';
        if ($rand <= 85) return 'pending';
        return 'failed';
    }
}
