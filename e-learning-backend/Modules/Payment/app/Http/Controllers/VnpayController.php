<?php

namespace Modules\Payment\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Payment\Services\VnpayService;

class VnpayController extends Controller
{
    use ApiResponse;

    public function __construct(
        private VnpayService $vnpayService,
    ) {}

    // IPN không reach được localhost, nên return URL cũng gọi handleIpn làm fallback.
    // handleIpn có idempotent check nên an toàn khi gọi lại.
    public function return(Request $request): \Illuminate\Http\RedirectResponse
    {
        $vnpData = $request->query();

        // Xử lý IPN logic ngay tại return (fallback cho localhost)
        // handleIpn đã có idempotent check, nên gọi lại ở đây an toàn
        $this->vnpayService->handleIpn($vnpData);

        $result = $this->vnpayService->handleReturn($vnpData);

        // Redirect user về frontend payment result page
        $frontendUrl = config('vnpay.frontend_result_url');
        $queryParams = http_build_query([
            'order_code' => $result['order_code'],
            'status' => $result['status'],
            'message' => $result['message'],
        ]);

        return redirect()->away("{$frontendUrl}?{$queryParams}");
    }

    public function ipn(Request $request): JsonResponse
    {
        $vnpData = $request->query();

        $result = $this->vnpayService->handleIpn($vnpData);

        return response()->json($result);
    }
}
