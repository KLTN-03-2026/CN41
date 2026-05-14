<?php

namespace Modules\Payment\Services;

use App\Events\PaymentSuccessful;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Course\Models\Course;
use Modules\Payment\Events\OrderPlaced;
use Modules\Payment\Models\Order;
use Modules\Payment\Models\Transaction;

class ZalopayService
{
    public function createPaymentUrl(Order $order, string $ipAddress): string
    {
        $appId = (int) config('zalopay.app_id');
        $key1 = config('zalopay.key1');
        $appTransId = now('Asia/Ho_Chi_Minh')->format('ymd').'_'.$order->order_code;
        $appTime = (int) (microtime(true) * 1000);
        $amount = (int) $order->total_amount;
        $embedData = json_encode(['redirecturl' => config('zalopay.redirect_url')]);
        $item = '[]';

        $macData = implode('|', [
            $appId,
            $appTransId,
            (string) $order->student_id,
            $amount,
            $appTime,
            $embedData,
            $item,
        ]);

        $mac = hash_hmac('sha256', $macData, $key1);

        $payload = [
            'app_id' => $appId,
            'app_trans_id' => $appTransId,
            'app_user' => (string) $order->student_id,
            'app_time' => $appTime,
            'amount' => $amount,
            'item' => $item,
            'embed_data' => $embedData,
            'description' => 'Thanh toán đơn hàng '.$order->order_code,
            'callback_url' => config('zalopay.callback_url'),
            'mac' => $mac,
        ];

        try {
            $response = Http::timeout(15)->asForm()->post(config('zalopay.endpoint'), $payload);
        } catch (ConnectionException $e) {
            Log::error('[ZaloPay] Network connection failed', [
                'order_code' => $order->order_code,
                'error' => $e->getMessage(),
            ]);
            throw new \Exception('Không thể kết nối đến cổng thanh toán ZaloPay. Vui lòng thử lại sau.');
        }

        if ($response->failed() || (int) $response->json('returncode') !== 1) {
            Log::error('[ZaloPay] createPaymentUrl failed', [
                'order_code' => $order->order_code,
                'response' => $response->json(),
            ]);
            throw new \Exception('Không thể tạo liên kết thanh toán ZaloPay.');
        }

        // Lưu app_trans_id vào transaction để tra cứu sau
        Transaction::where('order_id', $order->id)
            ->where('status', 'pending')
            ->latest()
            ->first()
            ?->update(['transaction_code' => $appTransId]);

        return (string) $response->json('orderurl');
    }

    public function verifyCallbackMac(string $data, string $mac): bool
    {
        $expected = hash_hmac('sha256', $data, config('zalopay.key2'));

        return hash_equals($expected, $mac);
    }

    public function handleCallback(array $payload): array
    {
        $dataStr = $payload['data'] ?? '';
        $mac = $payload['mac'] ?? '';

        Log::info('[ZaloPay] Callback received', ['data' => $dataStr]);

        if (! $this->verifyCallbackMac($dataStr, $mac)) {
            Log::warning('[ZaloPay] Callback MAC invalid');

            return ['return_code' => -1, 'return_message' => 'mac not equal'];
        }

        $data = json_decode($dataStr, true);
        $appTransId = $data['app_trans_id'] ?? '';
        $zpTransId = (string) ($data['zp_trans_id'] ?? '');

        // app_trans_id format: yymmdd_ORDER_CODE — 7-char prefix (6 digits + underscore)
        $orderCode = strlen($appTransId) > 7 ? substr($appTransId, 7) : '';

        $order = Order::where('order_code', $orderCode)->first();

        if (! $order) {
            Log::warning('[ZaloPay] Order not found', ['order_code' => $orderCode]);

            return ['return_code' => -1, 'return_message' => 'order not found'];
        }

        // Optimistic check trước khi acquire lock
        if ($order->status !== 'pending') {
            return ['return_code' => 2, 'return_message' => 'Order already confirmed'];
        }

        // lockForUpdate() trong DB::transaction() — tránh race condition duplicate callback
        $paid = DB::transaction(function () use ($order, $zpTransId, $data) {
            $lockedOrder = Order::where('id', $order->id)->lockForUpdate()->first();

            if ($lockedOrder->status !== 'pending') {
                return null;
            }

            Transaction::where('order_id', $lockedOrder->id)
                ->where('status', 'pending')
                ->latest()
                ->first()
                ?->update([
                    'status' => 'success',
                    'transaction_code' => $zpTransId,
                    'gateway_response' => $data,
                    'paid_at' => now(),
                ]);

            $lockedOrder->update(['status' => 'paid', 'paid_at' => now()]);

            return $lockedOrder;
        });

        if ($paid === null) {
            return ['return_code' => 2, 'return_message' => 'Order already confirmed'];
        }

        $this->enrollStudent($paid);
        OrderPlaced::dispatch($paid);
        event(new PaymentSuccessful($paid, $data));
        Log::info('[ZaloPay] Payment SUCCESS', ['order_code' => $orderCode]);

        return ['return_code' => 1, 'return_message' => 'success'];
    }

    public function enrollStudent(Order $order): void
    {
        $order->load('items');

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
            }
        }
    }
}
