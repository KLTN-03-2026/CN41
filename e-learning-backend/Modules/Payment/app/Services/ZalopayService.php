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
        $zalopayAttempts = Transaction::where('order_id', $order->id)
            ->where('gateway', 'zalopay')
            ->count();
        $appTransId = now('Asia/Ho_Chi_Minh')->format('ymd').'_'.$order->order_code;
        if ($zalopayAttempts > 1) {
            $appTransId .= '_'.$zalopayAttempts;
        }
        $appTime = (int) (microtime(true) * 1000);
        $amount = (int) $order->total_amount;
        $embedData = json_encode(['redirecturl' => config('zalopay.redirect_url')], JSON_UNESCAPED_SLASHES);
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

        if ($response->failed() || (int) $response->json('return_code') !== 1) {
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

        return (string) $response->json('order_url');
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

        $orderCode = self::extractOrderCode($appTransId);

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
        $paid = $this->markOrderAsPaid($order, $zpTransId, $data);

        if ($paid === null) {
            return ['return_code' => 2, 'return_message' => 'Order already confirmed'];
        }

        $this->enrollStudent($paid);
        OrderPlaced::dispatch($paid);
        event(new PaymentSuccessful($paid, $data));
        Log::info('[ZaloPay] Payment SUCCESS', ['order_code' => $orderCode]);

        return ['return_code' => 1, 'return_message' => 'success'];
    }

    private function markOrderAsPaid(Order $order, string $zpTransId, array $gatewayData): ?Order
    {
        return DB::transaction(function () use ($order, $zpTransId, $gatewayData) {
            $locked = Order::where('id', $order->id)->lockForUpdate()->first();

            if ($locked->status !== 'pending') {
                return null;
            }

            Transaction::where('order_id', $locked->id)
                ->where('status', 'pending')
                ->latest()
                ->first()
                ?->update([
                    'status' => 'success',
                    'transaction_code' => $zpTransId,
                    'gateway_response' => $gatewayData,
                    'paid_at' => now(),
                ]);

            $locked->update(['status' => 'paid', 'paid_at' => now()]);

            return $locked;
        });
    }

    public function queryAndConfirmPayment(string $appTransId, string $orderCode = ''): bool
    {
        $appId = (int) config('zalopay.app_id');
        $key1 = config('zalopay.key1');
        $mac = hash_hmac('sha256', $appId.'|'.$appTransId.'|'.$key1, $key1);

        try {
            $response = Http::timeout(10)->asForm()->post(
                config('zalopay.query_endpoint', 'https://sb-openapi.zalopay.vn/v2/query'),
                ['app_id' => $appId, 'app_trans_id' => $appTransId, 'mac' => $mac]
            );
        } catch (ConnectionException $e) {
            Log::warning('[ZaloPay] Query connection failed', ['app_trans_id' => $appTransId]);

            return false;
        }

        if ($response->failed() || (int) $response->json('return_code') !== 1) {
            Log::info('[ZaloPay] Query: not confirmed', [
                'app_trans_id' => $appTransId,
                'return_code' => $response->json('return_code'),
            ]);

            return false;
        }

        $zpTransId = (string) ($response->json('zp_trans_id') ?? '');
        $data = $response->json();

        $resolvedCode = $orderCode ?: self::extractOrderCode($appTransId);
        $order = Order::where('order_code', $resolvedCode)->first();
        if (! $order) {
            return false;
        }

        if ($order->status === 'paid') {
            return true;
        }

        $paid = $this->markOrderAsPaid($order, $zpTransId, $data);
        if ($paid) {
            $this->enrollStudent($paid);
            OrderPlaced::dispatch($paid);
            event(new PaymentSuccessful($paid, $data));
            Log::info('[ZaloPay] Payment confirmed via redirect query', ['order_code' => $orderCode]);
        }

        return (bool) $paid;
    }

    private static function extractOrderCode(string $appTransId): string
    {
        // Format: yymmdd_ORDER_CODE or yymmdd_ORDER_CODE_N (retry attempt N)
        // Order codes use hyphens (e.g. ORD-20260514-XXXXX), never end with _\d+
        $withoutPrefix = strlen($appTransId) > 7 ? substr($appTransId, 7) : '';

        return preg_replace('/_\d+$/', '', $withoutPrefix);
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

        // Auto-cancel các pending/failed orders khác chứa cùng courses (student đã sở hữu rồi)
        $courseIds = $order->items->pluck('course_id')->toArray();
        if (! empty($courseIds)) {
            $siblingIds = \Modules\Payment\Models\OrderItem::whereIn('course_id', $courseIds)
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
