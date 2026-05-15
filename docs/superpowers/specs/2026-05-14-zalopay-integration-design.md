# ZaloPay Integration Design

**Date:** 2026-05-14
**Scope:** Add ZaloPay as a second payment gateway alongside existing VNPAY

---

## Context

The payment module currently supports VNPAY only. The frontend `PaymentMethodSelector.vue` already has a disabled ZaloPay placeholder (partially modified in git). This spec covers full ZaloPay integration — backend service, webhook handler, and enabling the frontend option.

VNPAY remains active. Both gateways coexist, selected by the student at checkout.

---

## Architecture

Pattern: **Mirror VNPay** — new `ZalopayService` + `ZalopayController` following the existing `VnpayService` / `VnpayController` pattern. `OrderController` dispatches to the correct service based on `payment_method`.

No shared interface abstraction (overkill for current scope).

---

## Backend

### New Files

**`Modules/Payment/config/zalopay.php`**
```php
return [
    'app_id'       => env('ZALOPAY_APP_ID'),
    'key1'         => env('ZALOPAY_KEY1'),
    'key2'         => env('ZALOPAY_KEY2'),
    'endpoint'     => env('ZALOPAY_ENDPOINT', 'https://sb.zalopay.vn/v001/tpe/createorder'),
    'callback_url' => env('ZALOPAY_CALLBACK_URL'),
    'redirect_url' => env('VNPAY_RETURN_URL'), // reuse same frontend result page
];
```

**`Modules/Payment/app/Services/ZalopayService.php`**

Responsibilities:
- `createPaymentUrl(Order $order, string $ipAddress): string`
  - Build payload: `app_id`, `app_trans_id` (`yymmdd_orderCode`), `app_user` (student_id), `app_time` (ms), `item` (JSON array of course ids), `embed_data` (JSON with `redirecturl`), `amount` (VND, no × 100), `description`, `callback_url`
  - MAC = `HMAC-SHA256(key1, "{app_id}|{app_trans_id}|{app_user}|{amount}|{app_time}|{embed_data}|{item}")`
  - POST to `zalopay.endpoint` via HTTP client
  - Return `order_url` from response
- `verifyCallbackMac(string $data, string $mac): bool`
  - MAC = `HMAC-SHA256(key2, data)`
  - Compare with received mac (constant-time)
- `handleCallback(array $payload): array`
  - Decode `data` JSON from callback payload
  - Verify MAC via `verifyCallbackMac()`
  - Extract `app_trans_id` → look up Transaction by `transaction_code`
  - Idempotency: `DB::transaction` + `lockForUpdate()` + double-check `status === 'pending'`
  - On success (`return_code === 1`): update Transaction status → update Order status → `enrollStudent()`
  - On failure: update Transaction + Order to `failed`
  - Return codes per ZaloPay spec:
    - MAC invalid: `['return_code' => -1, 'return_message' => 'mac not equal']`
    - Already processed (idempotent): `['return_code' => 2, 'return_message' => 'Order already confirmed']`
    - Success: `['return_code' => 1, 'return_message' => 'success']`

**`Modules/Payment/app/Http/Controllers/ZalopayController.php`**

- `POST /payment/zalopay/callback` → `handleCallback()`
  - No auth middleware (public, server-to-server)
  - Delegate to `ZalopayService::handleCallback()`
  - Always return HTTP 200 with JSON (ZaloPay retries on non-200)

### Modified Files

**`Modules/Payment/app/Http/Controllers/OrderController.php`** — `store()` method:
```php
$paymentMethod = $request->input('payment_method', 'vnpay'); // 'vnpay' | 'zalopay'

if ($paymentMethod === 'zalopay') {
    $paymentUrl = $this->zalopayService->createPaymentUrl($order, $request->ip());
} else {
    $paymentUrl = $this->vnpayService->createPaymentUrl($order, $request->ip());
}
```
Inject both services via constructor.

`retryPayment()` follows the same dispatch pattern.

**`Modules/Payment/app/Http/Requests/CreateOrderRequest.php`**
Add rule:
```php
'payment_method' => 'nullable|in:vnpay,zalopay',
```

**`Modules/Payment/routes/api.php`**
```php
Route::post('payment/zalopay/callback', [ZalopayController::class, 'handleCallback']);
```
Placed before any auth middleware group (public route).

**Migration** — update `orders.payment_method` enum:
```php
// New migration: add_zalopay_to_orders_payment_method
DB::statement("ALTER TABLE orders MODIFY payment_method ENUM('vnpay','zalopay','free') DEFAULT 'vnpay'");
```

### .env Variables (Sandbox)

```
ZALOPAY_APP_ID=2553
ZALOPAY_KEY1=PcY4iZIKFCIdgZvA6ueMcMHHUbRLYjPL
ZALOPAY_KEY2=kLtgPl8HHhfvMuDHPwKfgfsY4Tz2ljW2
ZALOPAY_ENDPOINT=https://sb.zalopay.vn/v001/tpe/createorder
ZALOPAY_CALLBACK_URL=http://localhost:8000/api/v1/payment/zalopay/callback
```

---

## Frontend

### Modified Files

**`src/components/forms/PaymentMethodSelector.vue`**
- Remove `disabled` attribute and "Sắp ra mắt" badge from ZaloPay option
- ZaloPay option becomes fully selectable (value: `'zalopay'`)
- Keep existing VNPAY option unchanged

**`src/services/order.service.ts`**
- Add `payment_method` to `createOrder` payload:
  ```ts
  createOrder: (data: { course_ids: number[]; coupon_code?: string; payment_method?: string })
  ```

**`src/composables/useCheckout.ts`**
- `paymentMethod` default remains `'vnpay'`
- Pass `payment_method: paymentMethod.value` in `handleCheckout()` call to `orderService.createOrder()`

**`src/views/client/PaymentResultPage.vue`** — No changes needed.
ZaloPay redirect uses the same `/payment/result?status=...&order_code=...` query params set in `embed_data.redirecturl`.

---

## Payment Flow (ZaloPay)

```
Student selects ZaloPay → clicks "Thanh toán ngay"
  ↓
POST /orders { course_ids, payment_method: 'zalopay' }
  ↓
OrderController → ZalopayService.createPaymentUrl()
  ↓
POST https://sb.zalopay.vn/v001/tpe/createorder
  ↓
Response: { order_url, return_code: 1 }
  ↓
Frontend: window.location.href = order_url
  ↓
Student pays on ZaloPay
  ↓
ZaloPay POST → /api/v1/payment/zalopay/callback
  ↓
ZalopayController → ZalopayService.handleCallback()
  → verify MAC (key2)
  → lockForUpdate + idempotency check
  → update transaction + order
  → enrollStudent()
  ↓
ZaloPay redirects student → /payment/result?status=success&order_code=...
```

---

## Key Differences vs VNPAY

| | VNPAY | ZaloPay |
|---|---|---|
| Amount unit | VND × 100 | VND (direct) |
| HMAC algorithm | SHA-512 | SHA-256 |
| Callback method | GET (IPN) | POST |
| Return URL location | Query param | `embed_data.redirecturl` |
| Transaction ID format | `yyyyMMddHHmmss_random` | `yymmdd_orderCode` |

---

## Testing

**`tests/Feature/Payment/ZalopayCallbackTest.php`**

Test cases (mirroring `VnpayIpnTest.php`):
1. Valid callback with correct MAC → order paid, student enrolled
2. Invalid MAC → rejected (return_code -1), order unchanged
3. Duplicate callback (idempotency) → second call ignored, no double-enroll
4. Amount mismatch → order marked failed
5. Failed payment callback (`return_code !== 1`) → order marked failed

Use sandbox credentials in test env (`ZALOPAY_KEY2` from `.env.testing`).

---

## Out of Scope

- ZaloPay production credentials (sandbox only for thesis)
- Refund flow via ZaloPay API
- Admin order management changes (already handles all `payment_method` values generically)
