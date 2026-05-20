<?php

namespace Tests\Feature\Commission;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Commission\Models\CommissionSetting;
use Modules\Commission\Models\TeacherEarning;
use Modules\Commission\Services\CommissionService;
use Modules\Course\Models\Course;
use Modules\Payment\Models\Order;
use Modules\Payment\Models\OrderItem;
use Modules\Students\Models\Student;
use Modules\Teachers\Models\Teachers;
use Tests\TestCase;

class CommissionServiceTest extends TestCase
{
    use RefreshDatabase;

    private CommissionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(CommissionService::class);
    }

    private function makeOrder(): Order
    {
        $student = Student::forceCreate(['name' => 'Student A', 'email' => 'student-a@test.com', 'password' => 'password']);
        $teacher = Teachers::create(['name' => 'Teacher A', 'slug' => 'teacher-a', 'exp' => 3, 'status' => 1]);
        $course = Course::create(['teacher_id' => $teacher->id, 'name' => 'Laravel Cơ bản', 'slug' => 'laravel-co-ban', 'price' => 500000, 'level' => 'beginner', 'status' => 1]);
        $order = Order::create(['order_code' => 'TEST001', 'student_id' => $student->id, 'subtotal' => 500000, 'discount_amount' => 0, 'total_amount' => 500000, 'status' => 'paid', 'payment_method' => 'vnpay']);
        OrderItem::create(['order_id' => $order->id, 'course_id' => $course->id, 'price' => 500000, 'sale_price' => null, 'final_price' => 500000]);

        return $order->load('items.course.teacher');
    }

    public function test_record_earnings_creates_credit_for_each_item(): void
    {
        CommissionSetting::create(['teacher_rate' => 70.00]);
        $order = $this->makeOrder();

        $this->service->recordEarnings($order);

        $this->assertDatabaseHas('teacher_earnings', ['type' => 'credit', 'amount' => '350000.00', 'commission_rate' => '70.00']);
    }

    public function test_record_earnings_uses_rate_snapshot_from_settings(): void
    {
        CommissionSetting::create(['teacher_rate' => 80.00]);
        $order = $this->makeOrder();

        $this->service->recordEarnings($order);

        $earning = TeacherEarning::first();
        $this->assertEquals('80.00', $earning->commission_rate);
        $this->assertEquals('400000.00', $earning->amount);
    }

    public function test_reverse_earnings_creates_debit_matching_credit(): void
    {
        CommissionSetting::create(['teacher_rate' => 70.00]);
        $order = $this->makeOrder();
        $this->service->recordEarnings($order);

        $this->service->reverseEarnings($order);

        $this->assertDatabaseHas('teacher_earnings', ['type' => 'debit', 'amount' => '350000.00']);
        $this->assertDatabaseCount('teacher_earnings', 2);
    }

    public function test_get_available_balance_is_credit_minus_debit(): void
    {
        CommissionSetting::create(['teacher_rate' => 70.00]);
        $order = $this->makeOrder();
        $teacherId = $order->items->first()->course->teacher->id;

        $this->service->recordEarnings($order);
        $balance = $this->service->getAvailableBalance($teacherId);

        $this->assertEquals(350000.0, $balance);
    }
}
