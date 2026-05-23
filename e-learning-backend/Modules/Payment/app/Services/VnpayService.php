<?php

namespace Modules\Payment\Services;

use App\Events\PaymentSuccessful;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Course\Models\Course;
use Modules\Notifications\Services\NotificationService;
use Modules\Payment\Events\OrderPlaced;
use Modules\Payment\Models\Order;
use Modules\Payment\Models\OrderItem;
use Modules\Payment\Models\Transaction;

class VnpayService
{
    public function createPaymentUrl(Order $order, string $ipAddress): string
    {
        $vnpUrl = config('vnpay.url');
        $secretKey = config('vnpay.hash_secret');

        // BẮT BUỘC dùng GMT+7 (Asia/Ho_Chi_Minh) cho vnp_CreateDate / vnp_ExpireDate
        $createDate = Carbon::now('Asia/Ho_Chi_Minh')->format('YmdHis');
        $expireDate = Carbon::now('Asia/Ho_Chi_Minh')->addMinutes(15)->format('YmdHis');

        // VNPAY yêu cầu amount = VND × 100 (không có thập phân)
        $amount = (int) ($order->total_amount * 100);

        $inputData = [
            'vnp_Version' => config('vnpay.version'),
            'vnp_TmnCode' => config('vnpay.tmn_code'),
            'vnp_Amount' => $amount,
            'vnp_Command' => config('vnpay.command'),
            'vnp_CreateDate' => $createDate,
            'vnp_CurrCode' => config('vnpay.curr_code'),
            'vnp_IpAddr' => $ipAddress,
            'vnp_Locale' => config('vnpay.locale'),
            'vnp_OrderInfo' => 'Thanh toan don hang '.$order->order_code,
            'vnp_OrderType' => 'other',
            'vnp_ReturnUrl' => config('vnpay.return_url'),
            'vnp_TxnRef' => $order->order_code,
            'vnp_ExpireDate' => $expireDate,
        ];

        // params phải ksort() trước khi hash HMAC-SHA512
        ksort($inputData);

        $hashData = '';
        $query = '';
        $i = 0;

        foreach ($inputData as $key => $value) {
            if ($i === 1) {
                $hashData .= '&'.urlencode($key).'='.urlencode($value);
            } else {
                $hashData .= urlencode($key).'='.urlencode($value);
                $i = 1;
            }
            $query .= urlencode($key).'='.urlencode($value).'&';
        }

        $vnpSecureHash = hash_hmac('sha512', $hashData, $secretKey);

        return $vnpUrl.'?'.$query.'vnp_SecureHash='.$vnpSecureHash;
    }

    public function verifyChecksum(array $vnpData): bool
    {
        $secretKey = config('vnpay.hash_secret');
        $secureHash = $vnpData['vnp_SecureHash'] ?? '';

        unset($vnpData['vnp_SecureHash']);
        unset($vnpData['vnp_SecureHashType']);

        ksort($vnpData);

        $hashData = '';
        $i = 0;

        foreach ($vnpData as $key => $value) {
            if (str_starts_with($key, 'vnp_')) {
                if ($i === 1) {
                    $hashData .= '&'.urlencode($key).'='.urlencode($value);
                } else {
                    $hashData .= urlencode($key).'='.urlencode($value);
                    $i = 1;
                }
            }
        }

        return hash_equals(hash_hmac('sha512', $hashData, $secretKey), $secureHash);
    }

    public function handleIpn(array $vnpData): array
    {
        Log::channel('vnpay')->info('IPN received', $vnpData);

        if (! $this->verifyChecksum($vnpData)) {
            Log::channel('vnpay')->warning('IPN checksum invalid', $vnpData);

            return ['RspCode' => '97', 'Message' => 'Invalid Checksum'];
        }

        $orderCode = $vnpData['vnp_TxnRef'] ?? '';
        $vnpAmount = (int) ($vnpData['vnp_Amount'] ?? 0);
        $responseCode = $vnpData['vnp_ResponseCode'] ?? '';
        $transCode = $vnpData['vnp_TransactionNo'] ?? '';

        $order = Order::where('order_code', $orderCode)->first();

        if (! $order) {
            Log::channel('vnpay')->warning('IPN order not found', ['order_code' => $orderCode]);

            return ['RspCode' => '01', 'Message' => 'Order not Found'];
        }

        $expectedAmount = (int) ($order->total_amount * 100);

        if ($vnpAmount !== $expectedAmount) {
            Log::channel('vnpay')->warning('IPN amount mismatch', [
                'order_code' => $orderCode,
                'expected' => $expectedAmount,
                'received' => $vnpAmount,
            ]);

            return ['RspCode' => '04', 'Message' => 'Invalid Amount'];
        }

        // Optimistic check trước khi acquire lock
        if ($order->status !== 'pending') {
            Log::channel('vnpay')->info('IPN order already processed', [
                'order_code' => $orderCode,
                'status' => $order->status,
            ]);

            return ['RspCode' => '02', 'Message' => 'Order already confirmed'];
        }

        // lockForUpdate() phải nằm trong DB::transaction() để lock có hiệu lực
        // cho đến khi cả block commit — tránh race condition khi nhận duplicate IPN
        $paid = DB::transaction(function () use ($orderCode, $responseCode, $transCode, $vnpData) {
            $order = Order::where('order_code', $orderCode)->lockForUpdate()->first();

            if ($order->status !== 'pending') {
                return null;
            }

            $transaction = Transaction::where('order_id', $order->id)
                ->where('status', 'pending')
                ->latest()
                ->first();

            if ($responseCode === '00') {
                $transaction?->update([
                    'status' => 'success',
                    'transaction_code' => $transCode,
                    'bank_code' => $vnpData['vnp_BankCode'] ?? null,
                    'card_type' => $vnpData['vnp_CardType'] ?? null,
                    'response_code' => $responseCode,
                    'gateway_response' => $vnpData,
                    'paid_at' => now(),
                ]);
                $order->update(['status' => 'paid', 'paid_at' => now()]);
            } else {
                $transaction?->update([
                    'status' => 'failed',
                    'transaction_code' => $transCode,
                    'bank_code' => $vnpData['vnp_BankCode'] ?? null,
                    'card_type' => $vnpData['vnp_CardType'] ?? null,
                    'response_code' => $responseCode,
                    'gateway_response' => $vnpData,
                ]);
                $order->update(['status' => 'failed']);
            }

            return $order;
        });

        if ($paid === null) {
            return ['RspCode' => '02', 'Message' => 'Order already confirmed'];
        }

        if ($responseCode === '00') {
            $this->enrollStudent($paid);
            OrderPlaced::dispatch($paid);
            event(new PaymentSuccessful($paid, $vnpData));
            Log::channel('vnpay')->info('IPN payment SUCCESS', ['order_code' => $orderCode]);
        } else {
            Log::channel('vnpay')->info('IPN payment FAILED', [
                'order_code' => $orderCode,
                'response_code' => $responseCode,
            ]);
        }

        return ['RspCode' => '00', 'Message' => 'Confirm Success'];
    }

    public function handleReturn(array $vnpData): array
    {
        Log::channel('vnpay')->info('Return URL received', $vnpData);

        $orderCode = $vnpData['vnp_TxnRef'] ?? '';
        $responseCode = $vnpData['vnp_ResponseCode'] ?? '';

        if (! $this->verifyChecksum($vnpData)) {
            return [
                'order_code' => $orderCode,
                'status' => 'failed',
                'message' => 'Checksum không hợp lệ',
            ];
        }

        return [
            'order_code' => $orderCode,
            'status' => $responseCode === '00' ? 'success' : 'failed',
            'message' => $responseCode === '00'
                ? 'Thanh toán thành công'
                : 'Thanh toán thất bại (mã lỗi: '.$responseCode.')',
        ];
    }

    public function enrollStudent(Order $order): void
    {
        $order->load(['items', 'student']);

        foreach ($order->items as $item) {
            $exists = DB::table('students_course')
                ->where('student_id', $order->student_id)
                ->where('course_id', $item->course_id)
                ->exists();

            if (! $exists) {
                DB::table('students_course')->insert([
                    'student_id' => $order->student_id,
                    'course_id' => $item->course_id,
                    'enrolled_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                Course::where('id', $item->course_id)->increment('total_students');

                // Notify the teacher about the new enrollment
                $course = Course::select('id', 'name', 'teacher_id')->find($item->course_id);
                if ($course && $course->teacher_id) {
                    $studentName = $order->student?->name ?? 'Học viên';
                    try {
                        app(NotificationService::class)->notifyEnrollment(
                            teacherId: $course->teacher_id,
                            studentName: $studentName,
                            courseTitle: $course->name,
                            courseId: $course->id,
                        );
                    } catch (\Throwable) {
                        // Notifications are non-critical — never block enrollment
                    }
                }
            }
        }

        // Auto-cancel các pending/failed orders khác chứa cùng courses (student đã sở hữu rồi)
        $courseIds = $order->items->pluck('course_id')->toArray();
        if (! empty($courseIds)) {
            $siblingIds = OrderItem::whereIn('course_id', $courseIds)
                ->whereHas('order', fn ($q) => $q
                    ->where('student_id', $order->student_id)
                    ->whereIn('status', ['pending', 'failed'])
                    ->where('id', '!=', $order->id)
                )
                ->pluck('order_id')
                ->unique();

            if ($siblingIds->isNotEmpty()) {
                Order::whereIn('id', $siblingIds)->update(['status' => 'cancelled']);
            }
        }
    }
}
