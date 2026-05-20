<?php

namespace Tests\Feature\Commission;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Commission\Models\CommissionSetting;
use Modules\Commission\Models\TeacherEarning;
use Modules\Course\Models\Course;
use Modules\Payment\Models\Order;
use Modules\Payment\Models\OrderItem;
use Modules\Students\Models\Student;
use Modules\Teachers\Models\Teachers;
use Tests\TestCase;
use Tests\Traits\HasAdminUser;

class OrderRefundedTest extends TestCase
{
    use RefreshDatabase, HasAdminUser;

    public function test_updating_order_to_refunded_creates_debit_earnings(): void
    {
        CommissionSetting::create(['teacher_rate' => 70.00]);
        $this->setupAdmin();

        $student = Student::forceCreate(['name' => 'Student R', 'email' => 'student-r@test.com', 'password' => 'password']);
        $teacher = Teachers::create(['name' => 'T', 'slug' => 'teacher-t', 'exp' => 1, 'status' => 1]);
        $course = Course::create(['teacher_id' => $teacher->id, 'name' => 'C', 'slug' => 'course-c', 'price' => 200000, 'level' => 'beginner', 'status' => 1]);
        $order = Order::create(['order_code' => 'ORD001', 'student_id' => $student->id, 'subtotal' => 200000, 'discount_amount' => 0, 'total_amount' => 200000, 'status' => 'paid', 'payment_method' => 'vnpay']);
        $item = OrderItem::create(['order_id' => $order->id, 'course_id' => $course->id, 'price' => 200000, 'final_price' => 200000]);

        // Simulate existing credit from original sale
        TeacherEarning::create(['teacher_id' => $teacher->id, 'order_item_id' => $item->id, 'type' => 'credit', 'amount' => 140000, 'commission_rate' => 70.00]);

        $this->patchJson("/api/v1/admin/orders/{$order->id}/status", ['status' => 'refunded']);

        $this->assertDatabaseHas('teacher_earnings', ['type' => 'debit', 'amount' => '140000.00']);
    }
}
