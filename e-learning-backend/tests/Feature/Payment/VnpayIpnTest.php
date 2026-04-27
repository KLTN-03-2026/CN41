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

class VnpayIpnTest extends TestCase
{
    use RefreshDatabase;

    private string $baseUrl = '/api/v1/payment/vnpay/ipn';

    private string $secretKey = 'TEST_SECRET_KEY';

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('vnpay.hash_secret', $this->secretKey);
    }

    private function generateValidHash(array $data): string
    {
        ksort($data);
        $hashData = '';
        $i = 0;
        foreach ($data as $key => $value) {
            if ($i == 1) {
                $hashData .= '&'.urlencode($key).'='.urlencode($value);
            } else {
                $hashData .= urlencode($key).'='.urlencode($value);
                $i = 1;
            }
        }

        return hash_hmac('sha512', $hashData, $this->secretKey);
    }

    public function test_ipn_success_updates_order_and_enrolls_student()
    {
        $student = Student::forceCreate([
            'name' => 'Test Student',
            'email' => 'ipn_test@test.com',
            'password' => 'password123',
        ]);

        $teacher = DB::table('teachers')->insertGetId([
            'name' => 'Teacher Test',
            'slug' => 'teacher-test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $course = Course::create([
            'name' => 'Test Course',
            'slug' => 'test-course',
            'price' => 200000,
            'teacher_id' => $teacher,
            'status' => 1, // Published
        ]);

        $order = Order::create([
            'order_code' => 'ORD-SUCCESS',
            'student_id' => $student->id,
            'subtotal' => 200000,
            'discount_amount' => 0,
            'total_amount' => 200000,
            'status' => 'pending',
            'payment_method' => 'vnpay',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'course_id' => $course->id,
            'price' => 200000,
            'final_price' => 200000,
        ]);

        Transaction::create([
            'order_id' => $order->id,
            'gateway' => 'vnpay',
            'amount' => 200000,
            'status' => 'pending',
        ]);

        $vnpData = [
            'vnp_Amount' => 20000000, // VND * 100
            'vnp_BankCode' => 'NCB',
            'vnp_BankTranNo' => 'VNP12345678',
            'vnp_CardType' => 'ATM',
            'vnp_OrderInfo' => 'Thanh toan don hang ORD-SUCCESS',
            'vnp_PayDate' => '20260427160000',
            'vnp_ResponseCode' => '00',
            'vnp_TmnCode' => 'TEST_TMN',
            'vnp_TransactionNo' => '12345678',
            'vnp_TransactionStatus' => '00',
            'vnp_TxnRef' => 'ORD-SUCCESS',
        ];

        $vnpData['vnp_SecureHash'] = $this->generateValidHash($vnpData);

        $response = $this->getJson($this->baseUrl.'?'.http_build_query($vnpData));

        $response->assertStatus(200)
            ->assertJson(['RspCode' => '00', 'Message' => 'Confirm Success']);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'paid',
        ]);

        $this->assertDatabaseHas('transactions', [
            'order_id' => $order->id,
            'status' => 'success',
            'transaction_code' => '12345678',
        ]);

        $this->assertDatabaseHas('students_course', [
            'student_id' => $student->id,
        ]);
    }

    public function test_ipn_fails_with_invalid_checksum()
    {
        $vnpData = [
            'vnp_TxnRef' => 'ORD-ANY',
            'vnp_SecureHash' => 'WRONG_HASH',
        ];

        $response = $this->getJson($this->baseUrl.'?'.http_build_query($vnpData));

        $response->assertStatus(200) // VNPAY expects 200 even for logic errors
            ->assertJson(['RspCode' => '97', 'Message' => 'Invalid Checksum']);
    }

    public function test_ipn_fails_with_amount_mismatch()
    {
        $student = Student::forceCreate([
            'name' => 'Test Student',
            'email' => 'ipn_test_mismatch@test.com',
            'password' => 'password123',
        ]);

        $order = Order::create([
            'order_code' => 'ORD-AMOUNT-MISMATCH',
            'student_id' => $student->id,
            'total_amount' => 200000,
            'status' => 'pending',
        ]);

        $vnpData = [
            'vnp_Amount' => 10000000, // 100k instead of 200k
            'vnp_ResponseCode' => '00',
            'vnp_TxnRef' => 'ORD-AMOUNT-MISMATCH',
        ];

        $vnpData['vnp_SecureHash'] = $this->generateValidHash($vnpData);

        $response = $this->getJson($this->baseUrl.'?'.http_build_query($vnpData));

        $response->assertStatus(200)
            ->assertJson(['RspCode' => '04', 'Message' => 'Invalid Amount']);
    }

    public function test_ipn_handles_double_call_gracefully()
    {
        $student = Student::forceCreate([
            'name' => 'Test Student',
            'email' => 'ipn_test_double@test.com',
            'password' => 'password123',
        ]);

        $order = Order::create([
            'order_code' => 'ORD-DOUBLE',
            'student_id' => $student->id,
            'total_amount' => 200000,
            'status' => 'paid', // Already processed
        ]);

        $vnpData = [
            'vnp_Amount' => 20000000,
            'vnp_ResponseCode' => '00',
            'vnp_TxnRef' => 'ORD-DOUBLE',
        ];

        $vnpData['vnp_SecureHash'] = $this->generateValidHash($vnpData);

        $response = $this->getJson($this->baseUrl.'?'.http_build_query($vnpData));

        $response->assertStatus(200)
            ->assertJson(['RspCode' => '02', 'Message' => 'Order already confirmed']);
    }
}
