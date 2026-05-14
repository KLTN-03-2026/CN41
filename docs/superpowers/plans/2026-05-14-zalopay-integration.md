# ZaloPay Integration Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add ZaloPay as a second payment gateway alongside VNPAY, with full backend service, callback handler, and enabled frontend option.

**Architecture:** New `ZalopayService` mirrors `VnpayService` pattern; new `ZalopayController` handles callback (IPN) and redirect. `OrderController` dispatches to the correct service based on `payment_method` from the request.

**Tech Stack:** Laravel 12, PHP Http facade (ZaloPay API), HMAC-SHA256 (vs VNPAY's SHA512), Vue 3 + TypeScript frontend.

---

## File Map

```
New files:
  e-learning-backend/Modules/Payment/config/zalopay.php
  e-learning-backend/Modules/Payment/app/Services/ZalopayService.php
  e-learning-backend/Modules/Payment/app/Http/Controllers/ZalopayController.php
  e-learning-backend/tests/Feature/Payment/ZalopayCallbackTest.php

Modified files:
  e-learning-backend/.env
  e-learning-backend/Modules/Payment/app/Services/OrderService.php
  e-learning-backend/Modules/Payment/app/Http/Controllers/OrderController.php
  e-learning-backend/Modules/Payment/app/Http/Requests/CreateOrderRequest.php
  e-learning-backend/Modules/Payment/routes/api.php
  e-learning-frontend/src/components/forms/PaymentMethodSelector.vue
  e-learning-frontend/src/services/order.service.ts
  e-learning-frontend/src/composables/useCheckout.ts
```

---

## Task 1: ZaloPay Config File + .env Variables

**Files:**
- Create: `Modules/Payment/config/zalopay.php`
- Modify: `.env`

- [ ] **Step 1: Create config file**

Create `e-learning-backend/Modules/Payment/config/zalopay.php`:

```php
<?php

return [
    'app_id'              => env('ZALOPAY_APP_ID'),
    'key1'                => env('ZALOPAY_KEY1'),
    'key2'                => env('ZALOPAY_KEY2'),
    'endpoint'            => env('ZALOPAY_ENDPOINT', 'https://sb.zalopay.vn/v001/tpe/createorder'),
    'callback_url'        => env('ZALOPAY_CALLBACK_URL', 'http://localhost:8000/api/v1/payment/zalopay/callback'),
    'redirect_url'        => env('ZALOPAY_REDIRECT_URL', 'http://localhost:8000/api/v1/payment/zalopay/redirect'),
    'frontend_result_url' => env('VNPAY_FRONTEND_RESULT_URL', 'http://localhost:5173/payment/result'),
];
```

- [ ] **Step 2: Add .env variables**

Append to `e-learning-backend/.env`:

```
ZALOPAY_APP_ID=2553
ZALOPAY_KEY1=PcY4iZIKFCIdgZvA6ueMcMHHUbRLYjPL
ZALOPAY_KEY2=kLtgPl8HHhfvMuDHPwKfgfsY4Tz2ljW2
ZALOPAY_ENDPOINT=https://sb.zalopay.vn/v001/tpe/createorder
ZALOPAY_CALLBACK_URL=http://localhost:8000/api/v1/payment/zalopay/callback
ZALOPAY_REDIRECT_URL=http://localhost:8000/api/v1/payment/zalopay/redirect
```

> Note: `VNPAY_FRONTEND_RESULT_URL` is already in `.env` — both gateways share the same frontend result page.

- [ ] **Step 3: Verify config loads**

```bash
wsl.exe -d Ubuntu -- bash -c "php artisan tinker --execute=\"dump(config('zalopay.app_id'))\" 2>&1" | cat
```

Expected output: `2553`

- [ ] **Step 4: Commit**

```bash
git add Modules/Payment/config/zalopay.php .env
git commit -m "feat(payment): add ZaloPay config and sandbox env vars"
```

---

## Task 2: Write Failing Tests for ZaloPay Callback

**Files:**
- Create: `tests/Feature/Payment/ZalopayCallbackTest.php`

- [ ] **Step 1: Create the test file**

Create `e-learning-backend/tests/Feature/Payment/ZalopayCallbackTest.php`:

```php
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
            'order_code'     => $orderCode,
            'student_id'     => $student->id,
            'subtotal'       => $amount,
            'discount_amount'=> 0,
            'total_amount'   => $amount,
            'status'         => 'pending',
            'payment_method' => 'zalopay',
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

        $appTransId = now()->format('ymd') . '_ORD-ZP-SUCCESS';
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

        $appTransId = now()->format('ymd') . '_ORD-ZP-DUPE';
        $dataStr = $this->makeCallbackData($appTransId, 200000, 240514000000002, $student->id);
        $mac = $this->generateMac($dataStr);

        $response = $this->postJson($this->baseUrl, ['data' => $dataStr, 'mac' => $mac]);

        $response->assertStatus(200)
            ->assertJson(['return_code' => 2, 'return_message' => 'Order already confirmed']);
    }

    public function test_callback_returns_error_when_order_not_found(): void
    {
        $appTransId = now()->format('ymd') . '_ORD-ZP-GHOST';
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

        $appTransId = now()->format('ymd') . '_ORD-ZP-DOUBLE';
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
```

- [ ] **Step 2: Run tests — expect all to fail with class not found**

```bash
wsl.exe -d Ubuntu -- bash -c "php artisan test tests/Feature/Payment/ZalopayCallbackTest.php 2>&1" | cat
```

Expected: FAIL — `ZalopayController` not found / route not found.

- [ ] **Step 3: Commit failing tests**

```bash
git add tests/Feature/Payment/ZalopayCallbackTest.php
git commit -m "test(payment): add failing ZaloPay callback tests"
```

---

## Task 3: ZalopayService

**Files:**
- Create: `Modules/Payment/app/Services/ZalopayService.php`

- [ ] **Step 1: Create ZalopayService**

Create `e-learning-backend/Modules/Payment/app/Services/ZalopayService.php`:

```php
<?php

namespace Modules\Payment\Services;

use App\Events\PaymentSuccessful;
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
        $appId     = (int) config('zalopay.app_id');
        $key1      = config('zalopay.key1');
        $appTransId = now('Asia/Ho_Chi_Minh')->format('ymd') . '_' . $order->order_code;
        $appTime   = (int) (microtime(true) * 1000);
        $amount    = (int) $order->total_amount;
        $embedData = json_encode(['redirecturl' => config('zalopay.redirect_url')]);
        $item      = '[]';

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
            'app_id'       => $appId,
            'app_trans_id' => $appTransId,
            'app_user'     => (string) $order->student_id,
            'app_time'     => $appTime,
            'amount'       => $amount,
            'item'         => $item,
            'embed_data'   => $embedData,
            'description'  => 'Thanh toán đơn hàng ' . $order->order_code,
            'callback_url' => config('zalopay.callback_url'),
            'mac'          => $mac,
        ];

        $response = Http::post(config('zalopay.endpoint'), $payload);

        if ($response->failed() || (int) $response->json('return_code') !== 1) {
            Log::error('[ZaloPay] createPaymentUrl failed', [
                'order_code' => $order->order_code,
                'response'   => $response->json(),
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
        $mac     = $payload['mac'] ?? '';

        Log::info('[ZaloPay] Callback received', ['data' => $dataStr]);

        if (! $this->verifyCallbackMac($dataStr, $mac)) {
            Log::warning('[ZaloPay] Callback MAC invalid');

            return ['return_code' => -1, 'return_message' => 'mac not equal'];
        }

        $data       = json_decode($dataStr, true);
        $appTransId = $data['app_trans_id'] ?? '';
        $zpTransId  = (string) ($data['zp_trans_id'] ?? '');

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
                    'status'           => 'success',
                    'transaction_code' => $zpTransId,
                    'gateway_response' => $data,
                    'paid_at'          => now(),
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
                    'student_id'  => $order->student_id,
                    'course_id'   => $item->course_id,
                    'enrolled_at' => now(),
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);

                Course::where('id', $item->course_id)->increment('total_students');
            }
        }
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add Modules/Payment/app/Services/ZalopayService.php
git commit -m "feat(payment): add ZalopayService with createPaymentUrl, verifyCallbackMac, handleCallback"
```

---

## Task 4: ZalopayController + Routes

**Files:**
- Create: `Modules/Payment/app/Http/Controllers/ZalopayController.php`
- Modify: `Modules/Payment/routes/api.php`

- [ ] **Step 1: Create ZalopayController**

Create `e-learning-backend/Modules/Payment/app/Http/Controllers/ZalopayController.php`:

```php
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
        $appTransId  = $request->query('apptransid', '');
        $frontendUrl = config('zalopay.frontend_result_url');

        // app_trans_id format: yymmdd_ORDER_CODE — strip 7-char prefix
        $orderCode = strlen($appTransId) > 7 ? substr($appTransId, 7) : '';

        if (! $orderCode) {
            return redirect()->away(
                $frontendUrl . '?' . http_build_query([
                    'status'  => 'failed',
                    'message' => 'Yêu cầu không hợp lệ',
                ])
            );
        }

        $order    = Order::where('order_code', $orderCode)->first();
        $isSuccess = $order?->status === 'paid';

        return redirect()->away(
            $frontendUrl . '?' . http_build_query([
                'order_code' => $orderCode,
                'status'     => $isSuccess ? 'success' : 'failed',
                'message'    => $isSuccess ? 'Thanh toán thành công' : 'Thanh toán thất bại',
            ])
        );
    }
}
```

- [ ] **Step 2: Register routes in api.php**

Open `e-learning-backend/Modules/Payment/routes/api.php`.

Add the import at the top (after existing imports):
```php
use Modules\Payment\Http\Controllers\ZalopayController;
```

Add two public routes at the bottom of the file (after the VNPAY IPN route):
```php
/*
|--------------------------------------------------------------------------
| ZaloPay Callback (POST) — ZaloPay server-to-server IPN
|--------------------------------------------------------------------------
*/
Route::post('payment/zalopay/callback', [ZalopayController::class, 'callback']);

/*
|--------------------------------------------------------------------------
| ZaloPay Redirect (GET) — ZaloPay redirects user here after payment
|--------------------------------------------------------------------------
*/
Route::get('payment/zalopay/redirect', [ZalopayController::class, 'redirect']);
```

- [ ] **Step 3: Run the callback tests — they should pass now**

```bash
wsl.exe -d Ubuntu -- bash -c "php artisan test tests/Feature/Payment/ZalopayCallbackTest.php 2>&1" | cat
```

Expected: All 5 tests PASS.

If any test fails, read the error output and fix before continuing.

- [ ] **Step 4: Commit**

```bash
git add Modules/Payment/app/Http/Controllers/ZalopayController.php Modules/Payment/routes/api.php
git commit -m "feat(payment): add ZalopayController (callback + redirect) and register routes"
```

---

## Task 5: Update OrderService — payment_method param + Transaction gateway

**Files:**
- Modify: `Modules/Payment/app/Services/OrderService.php`

Current code hardcodes `'payment_method' => 'vnpay'` and `'gateway' => 'vnpay'`. Fix both.

- [ ] **Step 1: Update `createOrder` signature and internals**

Open `Modules/Payment/app/Services/OrderService.php`.

Change the method signature (line 20):
```php
// Before
public function createOrder(int $studentId, array $courseIds, ?string $couponCode): array

// After
public function createOrder(int $studentId, array $courseIds, ?string $couponCode, string $paymentMethod = 'vnpay'): array
```

Inside the `DB::transaction` closure, find `'payment_method' => $totalAmount > 0 ? 'vnpay' : 'free'` (line 80) and replace with:
```php
'payment_method' => $totalAmount > 0 ? $paymentMethod : 'free',
```

Find `'gateway' => 'vnpay'` in the Transaction::create block (line 87) and replace with:
```php
'gateway' => $paymentMethod,
```

- [ ] **Step 2: Update `retryPayment` to use order's payment_method**

Find the `Transaction::create` call inside `retryPayment` (line ~122):

```php
// Before
Transaction::create([
    'order_id' => $order->id,
    'gateway'  => 'vnpay',
    'amount'   => $order->total_amount,
    'status'   => 'pending',
]);

// After
Transaction::create([
    'order_id' => $order->id,
    'gateway'  => $order->payment_method,
    'amount'   => $order->total_amount,
    'status'   => 'pending',
]);
```

- [ ] **Step 3: Run existing VNPAY tests to verify no regression**

```bash
wsl.exe -d Ubuntu -- bash -c "php artisan test tests/Feature/Payment/ 2>&1" | cat
```

Expected: All tests PASS (both VnpayIpnTest and ZalopayCallbackTest).

- [ ] **Step 4: Commit**

```bash
git add Modules/Payment/app/Services/OrderService.php
git commit -m "feat(payment): pass payment_method through OrderService to order + transaction"
```

---

## Task 6: Update CreateOrderRequest + OrderController

**Files:**
- Modify: `Modules/Payment/app/Http/Requests/CreateOrderRequest.php`
- Modify: `Modules/Payment/app/Http/Controllers/OrderController.php`

- [ ] **Step 1: Add payment_method rule to CreateOrderRequest**

Open `e-learning-backend/Modules/Payment/app/Http/Requests/CreateOrderRequest.php`.

In the `rules()` method, add after `'coupon_code'` rule:
```php
'payment_method' => 'nullable|string|in:vnpay,zalopay',
```

In `messages()`, add:
```php
'payment_method.in' => 'Phương thức thanh toán không hợp lệ.',
```

- [ ] **Step 2: Update OrderController**

Open `e-learning-backend/Modules/Payment/app/Http/Controllers/OrderController.php`.

**2a — Add the import** at the top (after the existing `use` statements):
```php
use Modules\Payment\Services\ZalopayService;
```

**2b — Replace the constructor** (4 lines after `use ApiResponse;`):
```php
// Before
public function __construct(
    private OrderRepositoryInterface $repository,
    private OrderService $orderService,
    private VnpayService $vnpayService,
) {}

// After
public function __construct(
    private OrderRepositoryInterface $repository,
    private OrderService $orderService,
    private VnpayService $vnpayService,
    private ZalopayService $zalopayService,
) {}
```

**2c — Replace the entire `store()` method** with:
```php
public function store(CreateOrderRequest $request): JsonResponse
{
    $paymentMethod = $request->input('payment_method', 'vnpay');

    try {
        $result = $this->orderService->createOrder(
            auth('api')->id(),
            $request->validated()['course_ids'],
            $request->input('coupon_code'),
            $paymentMethod
        );
    } catch (\Exception $e) {
        return $this->error($e->getMessage(), $e->getCode() ?: 422);
    }

    $order = $result['order'];

    if ($result['totalAmount'] <= 0) {
        $order = $this->orderService->handleFreeOrder($order);

        return $this->success([
            'order'       => new OrderResource($order),
            'payment_url' => null,
        ], 'Đơn hàng miễn phí đã được xử lý. Bạn có thể vào học ngay!', 201);
    }

    $paymentUrl = $paymentMethod === 'zalopay'
        ? $this->zalopayService->createPaymentUrl($order, $request->ip())
        : $this->vnpayService->createPaymentUrl($order, $request->ip());

    $order->load(['items.course']);

    return $this->success([
        'order'       => new OrderResource($order),
        'payment_url' => $paymentUrl,
    ], 'Đơn hàng đã được tạo. Vui lòng thanh toán.', 201);
}
```

**2d — In `retryPayment()`**, replace the single line:
```php
// Before
$paymentUrl = $this->vnpayService->createPaymentUrl($order, $request->ip());

// After
$paymentUrl = $order->payment_method === 'zalopay'
    ? $this->zalopayService->createPaymentUrl($order, $request->ip())
    : $this->vnpayService->createPaymentUrl($order, $request->ip());
```

- [ ] **Step 3: Run all payment tests**

```bash
wsl.exe -d Ubuntu -- bash -c "php artisan test tests/Feature/Payment/ 2>&1" | cat
```

Expected: All tests PASS.

- [ ] **Step 4: Run full test suite to check for regressions**

```bash
wsl.exe -d Ubuntu -- bash -c "php artisan test 2>&1" | cat
```

Expected: All tests PASS.

- [ ] **Step 5: Commit**

```bash
git add Modules/Payment/app/Http/Requests/CreateOrderRequest.php \
        Modules/Payment/app/Http/Controllers/OrderController.php
git commit -m "feat(payment): inject ZalopayService into OrderController, dispatch by payment_method"
```

---

## Task 7: Frontend Changes

**Files:**
- Modify: `e-learning-frontend/src/components/forms/PaymentMethodSelector.vue`
- Modify: `e-learning-frontend/src/services/order.service.ts`
- Modify: `e-learning-frontend/src/composables/useCheckout.ts`

- [ ] **Step 1: Enable ZaloPay option in PaymentMethodSelector.vue**

Open `e-learning-frontend/src/components/forms/PaymentMethodSelector.vue`.

Replace the entire ZaloPay `<label>` block (lines 31–49 — the disabled block) with:

```vue
<!-- ZaloPay -->
<label
  class="flex items-center gap-3 p-3 rounded-xl border cursor-pointer transition-all"
  :class="modelValue === 'zalopay'
    ? 'border-blue-500 bg-blue-50/50 ring-1 ring-blue-200'
    : 'border-gray-200 hover:border-gray-300'"
>
  <input
    type="radio"
    :value="'zalopay'"
    :checked="modelValue === 'zalopay'"
    @change="$emit('update:modelValue', 'zalopay')"
    class="w-4 h-4 text-blue-600 focus:ring-blue-500"
  />
  <div class="flex items-center gap-2 flex-1">
    <div class="w-10 h-7 bg-white rounded border border-gray-100 flex items-center justify-center">
      <span class="text-[10px] font-bold text-blue-500">Zalo</span>
    </div>
    <div>
      <p class="text-sm font-medium text-gray-800">ZaloPay</p>
      <p class="text-xs text-gray-500">Ví điện tử ZaloPay</p>
    </div>
  </div>
</label>
```

- [ ] **Step 2: Add payment_method to order.service.ts createOrder**

Open `e-learning-frontend/src/services/order.service.ts`.

Replace the `createOrder` method signature:

```ts
// Before
createOrder: (data: Record<string, unknown>): Promise<...> =>
  http.post('/orders', data),

// After
createOrder: (data: {
  course_ids: number[]
  coupon_code?: string
  payment_method?: string
}): Promise<AxiosResponse<ApiResponse<{ payment_url: string | null; order: { order_code: string } }>>> =>
  http.post('/orders', data),
```

- [ ] **Step 3: Pass payment_method in useCheckout.ts handleCheckout**

Open `e-learning-frontend/src/composables/useCheckout.ts`.

In `handleCheckout()`, find:
```ts
const payload: Record<string, unknown> = { course_ids: cartStore.courseIds }
if (appliedCoupon.value) {
  payload.coupon_code = appliedCoupon.value.code
}
const res = await orderService.createOrder(payload)
```

Replace with:
```ts
const payload: {
  course_ids: number[]
  coupon_code?: string
  payment_method?: string
} = {
  course_ids: cartStore.courseIds,
  payment_method: paymentMethod.value,
}
if (appliedCoupon.value) {
  payload.coupon_code = appliedCoupon.value.code
}
const res = await orderService.createOrder(payload)
```

- [ ] **Step 4: Lint frontend**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning/e-learning-frontend && npm run lint 2>&1" | cat
```

Expected: No errors.

- [ ] **Step 5: Build frontend to check for type errors**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning/e-learning-frontend && npm run build 2>&1" | cat
```

Expected: Build succeeds with no TypeScript errors.

- [ ] **Step 6: Commit**

```bash
git add e-learning-frontend/src/components/forms/PaymentMethodSelector.vue \
        e-learning-frontend/src/services/order.service.ts \
        e-learning-frontend/src/composables/useCheckout.ts
git commit -m "feat(payment): enable ZaloPay option in frontend checkout"
```

---

## Task 8: Final Verification

- [ ] **Step 1: Run full backend test suite**

```bash
wsl.exe -d Ubuntu -- bash -c "php artisan test 2>&1" | cat
```

Expected: All tests PASS. No regressions.

- [ ] **Step 2: Run pint (PHP code style)**

```bash
wsl.exe -d Ubuntu -- bash -c "./vendor/bin/pint --test 2>&1" | cat
```

If any issues found, run `./vendor/bin/pint` (without `--test`) to auto-fix, then stage and commit:
```bash
wsl.exe -d Ubuntu -- bash -c "./vendor/bin/pint 2>&1" | cat
git add -u
git commit -m "chore(payment): fix code style after ZaloPay integration"
```

- [ ] **Step 3: Run frontend tests**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning/e-learning-frontend && npm run test 2>&1" | cat
```

Expected: All tests PASS.

- [ ] **Step 4: Verify routes registered**

```bash
wsl.exe -d Ubuntu -- bash -c "php artisan route:list --path=zalopay 2>&1" | cat
```

Expected output includes:
```
POST  api/v1/payment/zalopay/callback
GET   api/v1/payment/zalopay/redirect
```

- [ ] **Step 5: Final commit (if any remaining changes)**

```bash
git status
```

If clean, done. Otherwise stage and commit remaining changes.
