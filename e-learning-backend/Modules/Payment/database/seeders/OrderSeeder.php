<?php

namespace Modules\Payment\Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Course\Models\Course;
use Modules\Payment\Models\Order;
use Modules\Payment\Models\OrderItem;
use Modules\Payment\Models\Transaction;
use Modules\Students\Models\Student;

class OrderSeeder extends Seeder
{
    // Track enrolled (student_id → [course_ids]) to prevent duplicates
    private array $enrolled = [];

    public function run(): void
    {
        $students = Student::where('email_verified_at', '!=', null)->get();

        if ($students->isEmpty()) {
            $this->command->warn('OrderSeeder: No verified students found. Run StudentsDatabaseSeeder first.');

            return;
        }

        $paidCourses = Course::where('status', 1)->where('price', '>', 0)->get(['id', 'price', 'sale_price']);
        $freeCourses = Course::where('status', 1)->where('price', 0)->get(['id', 'price', 'sale_price']);

        if ($paidCourses->isEmpty()) {
            $this->command->warn('OrderSeeder: No paid courses found.');

            return;
        }

        // Enroll all verified students in free courses directly (no order needed)
        foreach ($students as $student) {
            foreach ($freeCourses as $course) {
                $this->enroll($student->id, $course->id, now()->subDays(180));
            }
        }

        // Monthly order distribution: 12 months ending May 2026, total = 150
        $monthlyPlan = [
            [2025, 6, 6],
            [2025, 7, 8],
            [2025, 8, 9],
            [2025, 9, 10],
            [2025, 10, 11],
            [2025, 11, 13],
            [2025, 12, 15],
            [2026, 1, 12],
            [2026, 2, 13],
            [2026, 3, 15],
            [2026, 4, 18],
            [2026, 5, 20],
        ];

        $bankCodes = ['NCB', 'VIETCOMBANK', 'TECHCOMBANK', 'MBBANK', 'VCB'];
        $studentIds = $students->pluck('id')->toArray();

        foreach ($monthlyPlan as [$year, $month, $count]) {
            $monthStart = Carbon::create($year, $month, 1, 0, 0, 0);
            $monthEnd = $monthStart->copy()->endOfMonth()->setTime(23, 59, 59);

            for ($i = 0; $i < $count; $i++) {
                $studentId = $studentIds[array_rand($studentIds)];
                $status = $this->randomStatus();
                $gateway = rand(1, 10) <= 7 ? 'vnpay' : 'zalopay';

                // Random timestamp within month
                $createdAt = Carbon::createFromTimestamp(
                    rand($monthStart->timestamp, $monthEnd->timestamp)
                );
                $paidAt = $status === 'paid'
                    ? $createdAt->copy()->addMinutes(rand(1, 15))
                    : null;

                // Pick 1–3 courses this student doesn't own yet
                $ownedIds = $this->enrolled[$studentId] ?? [];
                $available = $paidCourses->reject(fn ($c) => in_array($c->id, $ownedIds))->values();

                if ($available->isEmpty()) {
                    continue; // student owns everything, skip
                }

                $numCourses = min(rand(1, 3), $available->count());
                $selected = collect($available->random($numCourses));

                $subtotal = $selected->sum(fn ($c) => $c->sale_price ?? $c->price);

                $order = Order::create([
                    'order_code' => 'ORD-'.$createdAt->format('Ymd').'-'.strtoupper(Str::random(5)),
                    'student_id' => $studentId,
                    'subtotal' => $subtotal,
                    'discount_amount' => 0,
                    'total_amount' => $subtotal,
                    'status' => $status,
                    'payment_method' => $gateway,
                    'paid_at' => $paidAt,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);

                foreach ($selected as $course) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'course_id' => $course->id,
                        'price' => $course->price,
                        'sale_price' => $course->sale_price,
                        'final_price' => $course->sale_price ?? $course->price,
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ]);

                    if ($status === 'paid') {
                        $this->enroll($studentId, $course->id, $paidAt);
                        Course::where('id', $course->id)->increment('total_students');
                    }
                }

                if ($status === 'paid') {
                    Transaction::create([
                        'order_id' => $order->id,
                        'gateway' => $gateway,
                        'transaction_code' => strtoupper(Str::random(12)),
                        'bank_code' => $gateway === 'vnpay' ? $bankCodes[array_rand($bankCodes)] : null,
                        'amount' => $order->total_amount,
                        'status' => 'success',
                        'paid_at' => $paidAt,
                        'gateway_response' => ['seeded' => true],
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ]);
                }
            }
        }

        $paid = Order::where('status', 'paid')->count();
        $total = Order::count();
        $revenue = Order::where('status', 'paid')->sum('total_amount');
        $enrCount = DB::table('students_course')->count();
        $this->command->info("OrderSeeder: {$total} orders ({$paid} paid), revenue: ".number_format($revenue).' VNĐ, '.$enrCount.' enrollments.');
    }

    private function randomStatus(): string
    {
        $rand = rand(1, 100);

        return match (true) {
            $rand <= 70 => 'paid',
            $rand <= 85 => 'pending',
            $rand <= 95 => 'failed',
            default => 'cancelled',
        };
    }

    private function enroll(int $studentId, int $courseId, ?Carbon $enrolledAt): void
    {
        if (isset($this->enrolled[$studentId]) && in_array($courseId, $this->enrolled[$studentId])) {
            return;
        }

        $this->enrolled[$studentId][] = $courseId;

        DB::table('students_course')->insertOrIgnore([
            'student_id' => $studentId,
            'course_id' => $courseId,
            'enrolled_at' => $enrolledAt ?? now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
