<?php

namespace Modules\Payment\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Payment\Models\Order;
use Modules\Payment\Services\ZalopayService;

class ZalopayController extends Controller
{
    public function __construct(
        private ZalopayService $zalopayService,
    ) {}

    // POST /payment/zalopay/callback — ZaloPay server-to-server IPN
    // ZaloPay expects HTTP 200 with JSON body. Any other status triggers retry.
    public function callback(Request $request): JsonResponse
    {
        $result = $this->zalopayService->handleCallback($request->all());

        return response()->json($result);
    }

    // GET /payment/zalopay/redirect — ZaloPay redirects user here after payment
    // Checks DB order status (IPN should have already processed) then forwards to frontend.
    public function redirect(Request $request): RedirectResponse
    {
        $appTransId = $request->query('apptransid', '');
        $frontendUrl = config('zalopay.frontend_result_url');

        // app_trans_id format: yymmdd_ORDER_CODE — strip 7-char prefix
        $orderCode = strlen($appTransId) > 7 ? substr($appTransId, 7) : '';

        if (! $orderCode) {
            return redirect()->away(
                $frontendUrl.'?'.http_build_query([
                    'status' => 'failed',
                    'message' => 'Yêu cầu không hợp lệ',
                ])
            );
        }

        $order = Order::where('order_code', $orderCode)->first();
        $isSuccess = $order?->status === 'paid';

        return redirect()->away(
            $frontendUrl.'?'.http_build_query([
                'order_code' => $orderCode,
                'status' => $isSuccess ? 'success' : 'failed',
                'message' => $isSuccess ? 'Thanh toán thành công' : 'Thanh toán thất bại',
            ])
        );
    }
}
