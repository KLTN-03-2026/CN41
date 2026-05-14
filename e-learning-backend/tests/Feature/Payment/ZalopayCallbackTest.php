<?php

namespace Tests\Feature\Payment;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Modules\Course\Models\Course;
use Modules\Payment\Models\Order;
use Modules\Payment\Models\OrderItem;
use Modules\Payment\Models\Transaction;
use Modules\Students\Models\Student;
use Tests\TestCase;

class ZalopayCallbackTest extends TestCase
{
    use RefreshDatabase;

    private string $baseUrl = '/api/v1/payment/zalopay/callback';

    private string $key2 = 'TEST_KEY2';

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('zalopay.key2', $this->key2);
    }

    private function generateMac(string $data): string
    {
        return hash_hmac('sha256', $data, $this->key2);
    }

    private function makeCallbackData(string $appTransId, int $amount, int $zpTransId, int $studentId): string
    {
        return json_encode([
            'app_id'           => 2553,
            'app_trans_id'     => $appTransId,
            'app_time'         => now()->timestamp * 1000,
            'app_user'         => (string) $studentId,
            'amount'           => $amount,
            'embed_data'       => '{}',
            'item'             => '[]',
            'zp_trans_id'      => $zpTransId,
            'server_time'      => now()->timestamp * 1000,
            'channel'          => 39,
            'merchant_user_id' => 'test',
            'user_fee_amount'  => 0,
            'discount_amount'  => 0,
        ]);
    }

    private function createOrderFixture(string $orderCode, int $amount): array
    {
        $student = Student::forceCreate([
            'name'     => 'ZP Student',
            'email'    => "zp_{$orderCode}@test.com",
            'password' => 'password',
        ]);

        $teacherId = DB::table('teachers')->insertGetId([
            'name'       => 'Teacher',
            'slug'       => "teacher-{$orderCode}",
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $course = Course::create([
            'name'       => 'Course',
            'slug'       => "course-{$orderCode}",
            'price'      => $amount,
            'teacher_id' => $teacherId,
            'status'     => 1,
        ]);

        $order = Order::create([
            'order_code'      => $orderCode,
            'student_id'      => $student->id,
            'subtotal'        => $amount,
            'discount_amount' => 0,
            'total_amount'    => $amount,
            'status'          => 'pending',
            'payment_method'  => 'zalopay',
        ]);

        OrderItem::create([
            'order_id'    => $order->id,
            'course_id'   => $course->id,
            'price'       => $amount,
            'final_price' => $amount,
        ]);

        Transaction::create([
            'order_id' => $order->id,
            'gateway'  => 'zalopay',
            'amount'   => $amount,
            'status'   => 'pending',
        ]);

        return compact('student', 'order', 'course');
    }

    public function test_callback_success_updates_order_and_enrolls_student(): void
    {
        ['student' => $student, 'order' => $order] = $this->createOrderFixture('ORD-ZP-SUCCESS', 200000);

        $appTransId = now()->format('ymd').'_ORD-ZP-SUCCESS';
        $dataStr = $this->makeCallbackData($appTransId, 200000, 240514000000001, $student->id);
        $mac = $this->generateMac($dataStr);

        $response = $this->postJson($this->baseUrl, ['data' => $dataStr, 'mac' => $mac]);

        $response->assertStatus(200)
            ->assertJson(['return_code' => 1, 'return_message' => 'success']);

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'paid']);
        $this->assertDatabaseHas('transactions', ['order_id' => $order->id, 'status' => 'success']);
        $this->assertDatabaseHas('students_course', ['student_id' => $student->id]);
    }

    public function test_callback_rejects_invalid_mac(): void
    {
        $response = $this->postJson($this->baseUrl, [
            'data' => '{"app_trans_id":"260514_ORD-ZP-FAKE"}',
            'mac'  => 'WRONG_MAC',
        ]);

        $response->assertStatus(200)
            ->assertJson(['return_code' => -1, 'return_message' => 'mac not equal']);
    }

    public function test_callback_idempotent_when_order_already_paid(): void
    {
        ['student' => $student, 'order' => $order] = $this->createOrderFixture('ORD-ZP-DUPE', 200000);
        $order->update(['status' => 'paid']);

        $appTransId = now()->format('ymd').'_ORD-ZP-DUPE';
        $dataStr = $this->makeCallbackData($appTransId, 200000, 240514000000002, $student->id);
        $mac = $this->generateMac($dataStr);

        $response = $this->postJson($this->baseUrl, ['data' => $dataStr, 'mac' => $mac]);

        $response->assertStatus(200)
            ->assertJson(['return_code' => 2, 'return_message' => 'Order already confirmed']);
    }

    public function test_callback_returns_error_when_order_not_found(): void
    {
        $appTransId = now()->format('ymd').'_ORD-ZP-GHOST';
        $dataStr = $this->makeCallbackData($appTransId, 200000, 240514000000003, 999);
        $mac = $this->generateMac($dataStr);

        $response = $this->postJson($this->baseUrl, ['data' => $dataStr, 'mac' => $mac]);

        $response->assertStatus(200)
            ->assertJson(['return_code' => -1, 'return_message' => 'order not found']);
    }

    public function test_callback_does_not_double_enroll_student(): void
    {
        ['student' => $student, 'order' => $order, 'course' => $course] =
            $this->createOrderFixture('ORD-ZP-DOUBLE', 200000);

        // Pre-enroll the student
        DB::table('students_course')->insert([
            'student_id'  => $student->id,
            'course_id'   => $course->id,
            'enrolled_at' => now(),
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        $appTransId = now()->format('ymd').'_ORD-ZP-DOUBLE';
        $dataStr = $this->makeCallbackData($appTransId, 200000, 240514000000004, $student->id);
        $mac = $this->generateMac($dataStr);

        $response = $this->postJson($this->baseUrl, ['data' => $dataStr, 'mac' => $mac]);

        $response->assertStatus(200)
            ->assertJson(['return_code' => 1, 'return_message' => 'success']);

        // Exactly one enrollment record (not duplicated)
        $this->assertSame(
            1,
            DB::table('students_course')
                ->where('student_id', $student->id)
                ->where('course_id', $course->id)
                ->count()
        );
    }
}
