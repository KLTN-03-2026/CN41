# 💳 Kế Hoạch Tích Hợp Thanh Toán VNPAY

> **Ước tính:** 2–3 tuần | **Ưu tiên:** 🔴 Cao
> **Module Backend:** `Modules/Payment`
> **Phụ thuộc:** Module Course, Module Students (đã hoàn thành)

---

## Mục Lục

1. [Thiết kế Database](#1-thiết-kế-database)
2. [Backend — Module Payment](#2-backend--module-payment)
3. [Frontend](#3-frontend)
4. [Luồng xử lý VNPAY](#4-luồng-xử-lý-vnpay)
5. [Edge Cases & Bảo mật](#5-edge-cases--bảo-mật)
6. [Thứ tự thực hiện](#6-thứ-tự-thực-hiện)

---

## 1. Thiết kế Database

### 1.1 Bảng `orders`

Lưu đơn hàng của sinh viên khi mua khóa học.

```sql
CREATE TABLE orders (
    id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_code        VARCHAR(32) NOT NULL UNIQUE,          -- Mã đơn: ORD-20260409-XXXXX
    student_id        BIGINT UNSIGNED NOT NULL,
    
    -- Tài chính
    subtotal          DECIMAL(12,2) NOT NULL DEFAULT 0,     -- Tổng trước giảm
    discount_amount   DECIMAL(12,2) NOT NULL DEFAULT 0,     -- Số tiền giảm giá
    total_amount      DECIMAL(12,2) NOT NULL DEFAULT 0,     -- Tổng thanh toán
    
    -- Mã giảm giá (nullable, cho sau khi có module Coupon)
    coupon_code       VARCHAR(50) NULL,
    
    -- Trạng thái
    status            ENUM('pending','paid','failed','cancelled','refunded') NOT NULL DEFAULT 'pending',
    payment_method    VARCHAR(20) NOT NULL DEFAULT 'vnpay', -- vnpay, momo, free
    
    -- Metadata
    note              TEXT NULL,
    paid_at           TIMESTAMP NULL,
    
    created_at        TIMESTAMP NULL,
    updated_at        TIMESTAMP NULL,
    deleted_at        TIMESTAMP NULL,                       -- Soft delete

    INDEX idx_student (student_id),
    INDEX idx_status (status),
    INDEX idx_order_code (order_code),
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);
```

### 1.2 Bảng `order_items`

Mỗi đơn hàng chứa 1 hoặc nhiều khóa học.

```sql
CREATE TABLE order_items (
    id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id          BIGINT UNSIGNED NOT NULL,
    course_id         BIGINT UNSIGNED NOT NULL,
    
    price             DECIMAL(12,2) NOT NULL,               -- Giá gốc tại thời điểm mua
    sale_price        DECIMAL(12,2) NULL,                   -- Giá sale tại thời điểm mua
    final_price       DECIMAL(12,2) NOT NULL,               -- Giá thực trả (sale_price ?? price)
    
    created_at        TIMESTAMP NULL,
    updated_at        TIMESTAMP NULL,

    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    UNIQUE KEY unique_order_course (order_id, course_id)
);
```

### 1.3 Bảng `transactions`

Ghi log mọi giao dịch với cổng thanh toán (1 đơn có thể có nhiều transaction nếu thử lại).

```sql
CREATE TABLE transactions (
    id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id          BIGINT UNSIGNED NOT NULL,
    
    -- VNPAY info
    gateway           VARCHAR(20) NOT NULL DEFAULT 'vnpay',
    transaction_code  VARCHAR(100) NULL,                    -- vnp_TransactionNo
    bank_code         VARCHAR(20) NULL,                     -- vnp_BankCode
    card_type         VARCHAR(20) NULL,                     -- vnp_CardType
    
    amount            DECIMAL(12,2) NOT NULL,               -- Số tiền giao dịch (VND)
    status            ENUM('pending','success','failed') NOT NULL DEFAULT 'pending',
    
    -- VNPAY response raw
    gateway_response  JSON NULL,                            -- Toàn bộ response từ VNPAY
    response_code     VARCHAR(10) NULL,                     -- vnp_ResponseCode (00 = success)
    
    paid_at           TIMESTAMP NULL,
    created_at        TIMESTAMP NULL,
    updated_at        TIMESTAMP NULL,

    INDEX idx_order (order_id),
    INDEX idx_transaction_code (transaction_code),
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);
```

### 1.4 Migration Plan

Tạo 3 migration files trong `Modules/Payment/database/migrations/`:

```
2026_04_10_000001_create_orders_table.php
2026_04_10_000002_create_order_items_table.php
2026_04_10_000003_create_transactions_table.php
```

### 1.5 Quan hệ với bảng hiện có

```
students ──1:N──> orders ──1:N──> order_items ──N:1──> courses
                         ──1:N──> transactions

Sau khi order.status = 'paid' → tạo record students_course (enroll)
```

**Liên kết:**
- `orders.student_id` → `students.id` (CASCADE)
- `order_items.course_id` → `courses.id` (CASCADE)
- `transactions.order_id` → `orders.id` (CASCADE)
- Khi order `status = 'paid'` → tạo record trong `students_course` (enroll)

---

## 2. Backend — Module Payment

### 2.1 Cấu trúc Module

```
Modules/Payment/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── AdminOrderController.php
│   │   │   ├── OrderController.php         (Student-facing)
│   │   │   └── VnpayController.php         (Callback/Webhook)
│   │   ├── Requests/
│   │   │   ├── CreateOrderRequest.php
│   │   │   └── ApplyCouponRequest.php      (Phase sau)
│   │   └── Resources/
│   │       ├── OrderResource.php
│   │       ├── OrderItemResource.php
│   │       └── TransactionResource.php
│   ├── Models/
│   │   ├── Order.php
│   │   ├── OrderItem.php
│   │   └── Transaction.php
│   ├── Repositories/
│   │   ├── OrderRepositoryInterface.php
│   │   └── OrderRepository.php
│   ├── Services/
│   │   └── VnpayService.php                (Logic tạo URL, verify checksum)
│   └── Providers/
│       ├── PaymentServiceProvider.php
│       └── RouteServiceProvider.php
├── config/
│   └── vnpay.php                           (tmn_code, hash_secret, url, return_url)
├── database/
│   └── migrations/
├── routes/
│   └── api.php
└── module.json
```

### 2.2 Routes

```php
// routes/api.php — Module Payment

/*
|--------------------------------------------------------------------------
| Admin Routes (auth:admin)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:admin'])->prefix('admin')->group(function () {
    // Extra routes trước để tránh conflict
    Route::get('orders/trashed',                [AdminOrderController::class, 'trashed']);
    Route::delete('orders/bulk-delete',         [AdminOrderController::class, 'bulkDelete']);

    // Danh sách + chi tiết
    Route::get('orders',                        [AdminOrderController::class, 'index']);
    Route::get('orders/{id}',                   [AdminOrderController::class, 'show']);
    Route::patch('orders/{id}/status',          [AdminOrderController::class, 'updateStatus']);
    Route::delete('orders/{id}',                [AdminOrderController::class, 'destroy']);
    Route::patch('orders/{id}/restore',         [AdminOrderController::class, 'restore']);

    // Thống kê doanh thu (cho dashboard)
    Route::get('orders/stats/revenue',          [AdminOrderController::class, 'revenueStats']);
});

/*
|--------------------------------------------------------------------------
| Student Routes (auth:api + email.verified)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:api', 'email.verified'])->group(function () {
    // Tạo đơn hàng → nhận URL VNPAY
    Route::post('orders',                       [OrderController::class, 'store']);

    // Lịch sử đơn hàng của sinh viên
    Route::get('my-orders',                     [OrderController::class, 'myOrders']);
    Route::get('my-orders/{orderCode}',         [OrderController::class, 'show']);

    // Thanh toán lại đơn pending
    Route::post('orders/{orderCode}/retry-payment', [OrderController::class, 'retryPayment']);
});

/*
|--------------------------------------------------------------------------
| VNPAY Callback (public — VNPAY redirect user về đây)
|--------------------------------------------------------------------------
*/
Route::get('payment/vnpay/return',              [VnpayController::class, 'return']);

/*
|--------------------------------------------------------------------------
| VNPAY IPN — Webhook (server-to-server, public, không cần auth)
|--------------------------------------------------------------------------
*/
Route::get('payment/vnpay/ipn',                 [VnpayController::class, 'ipn']);
```

### 2.3 Controllers

#### `OrderController` (Student-facing)

| Method | Chức năng |
|--------|-----------|
| `store(CreateOrderRequest)` | Tạo order + order_items + transaction pending → gọi VnpayService tạo URL → trả `payment_url` |
| `myOrders(Request)` | Lấy danh sách đơn hàng (paginated) của student đang login |
| `show(string $orderCode)` | Xem chi tiết 1 đơn hàng (chỉ chủ đơn) |
| `retryPayment(string $orderCode)` | Tạo transaction mới cho order pending → trả `payment_url` mới |

#### `VnpayController`

| Method | Chức năng |
|--------|-----------|
| `return(Request)` | VNPAY redirect user về FE kèm query params → verify checksum → redirect FE `/payment/result?...` |
| `ipn(Request)` | Webhook server-to-server → verify checksum → cập nhật transaction + order → enroll student |

#### `AdminOrderController`

| Method | Chức năng |
|--------|-----------|
| `index(Request)` | Danh sách tất cả orders (filter: status, date range, search by order_code/student) |
| `show(int $id)` | Chi tiết order + items + transactions |
| `updateStatus(Request, int $id)` | Admin thay đổi status (VD: refund thủ công) |
| `trashed(Request)` | Danh sách order đã xóa |
| `destroy(int $id)` | Soft delete |
| `bulkDelete(Request)` | Bulk soft delete |
| `restore(int $id)` | Restore |
| `revenueStats(Request)` | Thống kê doanh thu theo tháng/ngày |

### 2.4 Form Requests

#### `CreateOrderRequest`

```php
public function rules(): array
{
    return [
        'course_ids'   => 'required|array|min:1',
        'course_ids.*' => 'integer|exists:courses,id',
        'coupon_code'  => 'nullable|string|max:50',       // Phase sau
    ];
}
```

### 2.5 API Resources

#### `OrderResource`

```php
return [
    'id'              => $this->id,
    'order_code'      => $this->order_code,
    'subtotal'        => $this->subtotal,
    'discount_amount' => $this->discount_amount,
    'total_amount'    => $this->total_amount,
    'coupon_code'     => $this->coupon_code,
    'status'          => $this->status,
    'payment_method'  => $this->payment_method,
    'paid_at'         => $this->paid_at?->toISOString(),
    'items'           => OrderItemResource::collection($this->whenLoaded('items')),
    'transactions'    => TransactionResource::collection($this->whenLoaded('transactions')),
    'student'         => new StudentResource($this->whenLoaded('student')),
    'created_at'      => $this->created_at?->toISOString(),
];
```

#### `OrderItemResource`

```php
return [
    'id'          => $this->id,
    'course'      => new CourseResource($this->whenLoaded('course')),
    'price'       => $this->price,
    'sale_price'  => $this->sale_price,
    'final_price' => $this->final_price,
];
```

### 2.6 Repository

```php
interface OrderRepositoryInterface extends RepositoryInterface
{
    public function getFiltered(array $filters, int $perPage): LengthAwarePaginator;
    public function getByStudent(int $studentId, int $perPage): LengthAwarePaginator;
    public function findByOrderCode(string $orderCode): ?Order;
    public function createWithItems(array $orderData, array $items): Order;
    public function markAsPaid(int $orderId): Order;
    public function getRevenueStats(string $period, ?string $from, ?string $to): array;
    public function checkDuplicateEnrollment(int $studentId, array $courseIds): array;
}
```

### 2.7 VnpayService

```php
class VnpayService
{
    // Tạo payment URL cho VNPAY
    public function createPaymentUrl(Order $order, string $ipAddress): string;

    // Verify checksum từ VNPAY response
    public function verifyChecksum(array $vnpData): bool;

    // Xử lý IPN callback
    public function handleIpn(array $vnpData): array;

    // Xử lý return URL (user redirect)
    public function handleReturn(array $vnpData): array;
}
```

### 2.8 Models

#### `Order`

```php
class Order extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'order_code', 'student_id', 'subtotal', 'discount_amount',
        'total_amount', 'coupon_code', 'status', 'payment_method',
        'note', 'paid_at',
    ];

    protected $casts = [
        'subtotal'        => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount'    => 'decimal:2',
        'paid_at'         => 'datetime',
    ];

    // Relationships
    public function student()      { return $this->belongsTo(Student::class); }
    public function items()        { return $this->hasMany(OrderItem::class); }
    public function transactions() { return $this->hasMany(Transaction::class); }

    // Scopes
    public function scopePaid($q)    { return $q->where('status', 'paid'); }
    public function scopePending($q) { return $q->where('status', 'pending'); }

    // Helper
    public function isPaid(): bool    { return $this->status === 'paid'; }
    public function isPending(): bool { return $this->status === 'pending'; }
}
```

#### `OrderItem`

```php
class OrderItem extends Model
{
    protected $fillable = ['order_id', 'course_id', 'price', 'sale_price', 'final_price'];

    public function order()  { return $this->belongsTo(Order::class); }
    public function course() { return $this->belongsTo(Course::class); }
}
```

#### `Transaction`

```php
class Transaction extends Model
{
    protected $fillable = [
        'order_id', 'gateway', 'transaction_code', 'bank_code',
        'card_type', 'amount', 'status', 'gateway_response',
        'response_code', 'paid_at',
    ];

    protected $casts = [
        'gateway_response' => 'json',
        'amount'           => 'decimal:2',
        'paid_at'          => 'datetime',
    ];

    public function order() { return $this->belongsTo(Order::class); }
}
```

### 2.9 Config `vnpay.php`

```php
return [
    'tmn_code'    => env('VNPAY_TMN_CODE', ''),
    'hash_secret' => env('VNPAY_HASH_SECRET', ''),
    'url'         => env('VNPAY_URL', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html'),
    'return_url'  => env('VNPAY_RETURN_URL', 'http://localhost:8000/api/v1/payment/vnpay/return'),
    'api_url'     => env('VNPAY_API_URL', 'https://sandbox.vnpayment.vn/merchant_webapi/api/transaction'),
    'version'     => '2.1.0',
    'command'     => 'pay',
    'curr_code'   => 'VND',
    'locale'      => 'vn',
];
```

**Env variables cần thêm vào `.env`:**

```env
VNPAY_TMN_CODE=your_tmn_code
VNPAY_HASH_SECRET=your_hash_secret
VNPAY_URL=https://sandbox.vnpayment.vn/paymentv2/vpcpay.html
VNPAY_RETURN_URL=http://localhost:8000/api/v1/payment/vnpay/return
```

---

## 3. Frontend

### 3.1 API Module

Tạo file `src/api/ordersApi.js`:

```javascript
// ordersApi.js
import axios from './axios'

export const ordersApi = {
  // Student
  createOrder:    (data)       => axios.post('/orders', data),
  myOrders:       (params)     => axios.get('/my-orders', { params }),
  orderDetail:    (orderCode)  => axios.get(`/my-orders/${orderCode}`),
  retryPayment:   (orderCode)  => axios.post(`/orders/${orderCode}/retry-payment`),

  // Admin
  adminList:      (params)     => axios.get('/admin/orders', { params }),
  adminShow:      (id)         => axios.get(`/admin/orders/${id}`),
  adminUpdateStatus: (id, data) => axios.patch(`/admin/orders/${id}/status`, data),
  adminDelete:    (id)         => axios.delete(`/admin/orders/${id}`),
  adminBulkDelete:(ids)        => axios.delete('/admin/orders/bulk-delete', { data: { ids } }),
  adminTrashed:   (params)     => axios.get('/admin/orders/trashed', { params }),
  adminRestore:   (id)         => axios.patch(`/admin/orders/${id}/restore`),
  revenueStats:   (params)     => axios.get('/admin/orders/stats/revenue', { params }),
}
```

### 3.2 Pages cần tạo/cập nhật

| Page | Trạng thái | Mô tả |
|------|-----------|-------|
| `CartPage.vue` | 🔄 Viết lại | Hiển thị giỏ hàng từ Pinia store, xóa item, tổng tiền, nút Thanh toán |
| `CheckoutPage.vue` | 🔄 Viết lại | Xác nhận đơn, ô nhập mã giảm giá (phase sau), chọn phương thức, nút Thanh toán |
| `PaymentResultPage.vue` | 🔄 Viết lại | Nhận kết quả từ VNPAY, hiển thị thành công/thất bại, link vào học |
| `MyOrdersPage.vue` | ✨ Mới | Lịch sử đơn hàng của sinh viên |
| `OrdersPage.vue` (admin) | 🔄 Viết lại | Danh sách đơn hàng, filter, chi tiết đơn, cập nhật trạng thái |
| `CourseDetailPage.vue` | 📝 Cập nhật | Cập nhật nút "Thêm vào giỏ" → redirect đúng flow |
| `DashboardPage.vue` (admin) | 📝 Cập nhật | Thêm widget doanh thu từ API stats |

### 3.3 Components cần tạo

| Component | Mô tả |
|-----------|-------|
| `CartItemCard.vue` | Card hiển thị 1 khóa học trong giỏ (thumbnail, tên, giá, nút xóa) |
| `OrderStatusBadge.vue` | Badge màu theo status (pending=vàng, paid=xanh, failed=đỏ, ...) |
| `OrderDetailModal.vue` | Modal xem chi tiết đơn hàng (admin) |
| `PaymentMethodSelector.vue` | Radio chọn VNPAY / MoMo (mặc định VNPAY, MoMo disable) |

### 3.4 Store cần cập nhật

File `src/stores/cart.js` — đã có, cần bổ sung:

```javascript
// Thêm action áp dụng coupon (phase sau)
applyCoupon(code) { ... },
removeCoupon() { ... },

// Thêm getter
originalTotal: (state) => state.items.reduce((sum, item) => sum + item.price, 0),
```

### 3.5 Routes cần thêm (router/index.js)

```javascript
// Trong ClientLayout children:
{ path: 'my-orders', component: () => import('@/pages/client/MyOrdersPage.vue'),
  meta: { requiresAuth: true, guard: 'student' } },

// payment/result — đã có, KHÔNG cần auth (VNPAY redirect user)
```

### 3.6 Luồng người dùng (User Flow)

```
[Trang chi tiết khóa học]
        ↓ "Thêm vào giỏ"
[CartPage — giỏ hàng]
        ↓ "Thanh toán"
[CheckoutPage — xác nhận đơn]
        ↓ POST /orders → nhận payment_url
[Redirect → VNPAY sandbox]
        ↓ Thanh toán trên VNPAY
[VNPAY redirect → Backend return_url]
        ↓ Backend verify → redirect FE
[PaymentResultPage]
    ├── Thành công → "Vào học ngay" → LearnPage
    └── Thất bại → "Thử lại" hoặc "Về trang chủ"
```

---

## 4. Luồng xử lý VNPAY

### 4.1 Tạo đơn hàng

1. Student chọn khóa học → thêm vào giỏ (Pinia store, localStorage)
2. Vào CheckoutPage → xác nhận đơn
3. FE gọi `POST /api/v1/orders` với `{ course_ids: [1, 2, 3] }`
4. BE:
   - Kiểm tra student đã mua khóa nào chưa → loại bỏ trùng
   - Tạo record `orders` + `order_items` (snapshot giá tại thời điểm mua)
   - Tạo `transactions` status=pending
   - Gọi `VnpayService::createPaymentUrl()` → sinh URL VNPAY
   - Trả response: `{ order_code, payment_url }`
5. FE nhận `payment_url` → `window.location.href = payment_url`

### 4.2 VNPAY Return (user redirect)

1. Sau khi thanh toán xong, VNPAY redirect user về `return_url` kèm query params
2. `VnpayController@return`:
   - Verify secure hash
   - Đọc `vnp_ResponseCode`: `00` = thành công
   - Redirect FE: `http://localhost:5173/payment/result?order_code=XXX&status=success|failed`
3. **KHÔNG cập nhật order ở đây** — chỉ redirect user, để IPN xử lý

### 4.3 VNPAY IPN (Webhook — server-to-server)

1. VNPAY gọi đến `GET /api/v1/payment/vnpay/ipn` với query params
2. `VnpayController@ipn`:
   - Verify secure hash → nếu sai: trả `RspCode: 97`
   - Tìm order theo `vnp_TxnRef` → nếu không tìm thấy: trả `RspCode: 01`
   - Kiểm tra amount khớp → nếu sai: trả `RspCode: 04`
   - Kiểm tra order đã xử lý chưa (idempotent) → nếu rồi: trả `RspCode: 02`
   - Nếu `vnp_ResponseCode == '00'`:
     - Cập nhật `transactions`: status=success, lưu response
     - Cập nhật `orders`: status=paid, paid_at=now
     - **Enroll student** → tạo records `students_course` cho tất cả course trong order
     - Xóa giỏ hàng phía server (nếu có)
   - Nếu `vnp_ResponseCode != '00'`:
     - Cập nhật `transactions`: status=failed, lưu response
     - Cập nhật `orders`: status=failed
   - Trả `RspCode: 00` (đã nhận)

### 4.4 IPN Response Format (theo chuẩn VNPAY)

```json
{ "RspCode": "00", "Message": "Confirm Success" }
```

| RspCode | Ý nghĩa |
|---------|---------|
| 00 | Xác nhận thành công |
| 01 | Order không tồn tại |
| 02 | Order đã được xử lý |
| 04 | Số tiền không hợp lệ |
| 97 | Checksum không hợp lệ |
| 99 | Lỗi không xác định |

---

## 5. Edge Cases & Bảo mật

### 5.1 Thanh toán thất bại

- User hủy ở trang VNPAY → `vnp_ResponseCode != '00'`
- BE cập nhật order status = `failed`
- FE hiển thị thông báo thất bại + nút "Thử lại" (`retryPayment`)

### 5.2 Timeout / User đóng tab giữa chừng

- Order vẫn `pending` trong DB
- IPN vẫn gọi về dù user không quay lại → order vẫn được cập nhật đúng
- Nếu IPN cũng không gọi (hiếm): order giữ `pending`, sinh viên có thể retry
- Thêm cron job xử lý đơn hàng `pending` quá 30 phút → chuyển `cancelled`

### 5.3 Trùng lặp (Duplicate IPN)

- Kiểm tra `order.status` trước khi xử lý
- Nếu order đã `paid` → trả `RspCode: 02`, không enroll lại
- Dùng DB transaction + lock (`lockForUpdate()`) để tránh race condition

### 5.4 Student đã mua khóa học rồi

- Khi tạo order, kiểm tra `students_course` — loại bỏ khóa đã enroll
- Nếu tất cả đều đã mua → trả lỗi 422: "Bạn đã sở hữu tất cả khóa học này"

### 5.5 Bảo mật

- **Verify checksum** ở cả return URL và IPN bằng HMAC-SHA512
- **Không tin return URL** — chỉ IPN mới cập nhật trạng thái order
- Amount VNPAY tính bằng **VND × 100** (không có thập phân)
- Lưu `gateway_response` đầy đủ để audit trail
- Rate limit tạo order: `throttle:5,1` (5 đơn/phút)
- Validate `vnp_TxnRef` = `order_code` để chống giả mạo

### 5.6 Xử lý đơn hàng giá = 0 (sau giảm giá)

- Nếu tổng = 0 sau coupon → không redirect VNPAY
- Auto mark `paid` + enroll ngay → status=paid, payment_method=free

---

## 6. Thứ tự thực hiện

### Phase 1: Database & Module Skeleton (Ngày 1–2)

- [ ] Tạo module `Modules/Payment` (scaffold)
- [ ] Tạo 3 migration files (orders, order_items, transactions)
- [ ] Tạo 3 Models (Order, OrderItem, Transaction) với relationships
- [ ] Tạo Repository (interface + implementation)
- [ ] Đăng ký bindings trong `PaymentServiceProvider`
- [ ] Tạo config `vnpay.php` + thêm env variables
- [ ] Chạy `php artisan migrate` → verify bảng

> **Test:** Kiểm tra migration chạy thành công, model relationships hoạt động qua tinker

### Phase 2: VnpayService + Tạo đơn hàng (Ngày 3–5)

- [ ] Implement `VnpayService` (createPaymentUrl, verifyChecksum)
- [ ] Implement `CreateOrderRequest`
- [ ] Implement `OrderController@store` (tạo order + redirect URL)
- [ ] Implement `VnpayController@return` (redirect FE)
- [ ] Implement `VnpayController@ipn` (webhook xử lý + enroll)
- [ ] Tạo routes `api.php`
- [ ] Đăng ký VNPAY sandbox account để test

> **Test:** Dùng Postman tạo order → lấy URL → mở VNPAY sandbox → callback

### Phase 3: API Resources + Admin endpoints (Ngày 6–8)

- [ ] Implement `OrderResource`, `OrderItemResource`, `TransactionResource`
- [ ] Implement `AdminOrderController` (index, show, updateStatus, destroy, ...)
- [ ] Implement `OrderController@myOrders`, `OrderController@show`
- [ ] Implement `OrderController@retryPayment`

> **Test:** Dùng Postman test mọi admin + student endpoints

### Phase 4: Frontend — CartPage & CheckoutPage (Ngày 9–11)

- [ ] Cập nhật `cart.js` store (nếu cần)
- [ ] Viết lại `CartPage.vue` (UI giỏ hàng đầy đủ)
- [ ] Viết lại `CheckoutPage.vue` (xác nhận + gọi API tạo đơn + redirect)
- [ ] Tạo component `CartItemCard.vue`
- [ ] Tạo component `PaymentMethodSelector.vue`
- [ ] Tạo `src/api/ordersApi.js`

> **Test:** Thêm khóa vào giỏ → checkout → redirect VNPAY sandbox → quay về

### Phase 5: Frontend — PaymentResult & MyOrders (Ngày 12–14)

- [ ] Viết lại `PaymentResultPage.vue` (đọc query params, hiển thị kết quả)
- [ ] Tạo `MyOrdersPage.vue` (lịch sử đơn hàng sinh viên)
- [ ] Tạo component `OrderStatusBadge.vue`
- [ ] Cập nhật router (thêm `/my-orders`)
- [ ] Cập nhật `CourseDetailPage.vue` (nút mua, check đã mua)
- [ ] Clear giỏ hàng sau khi thanh toán thành công

> **Test:** Full flow end-to-end: xem khóa → giỏ → checkout → VNPAY → kết quả → vào học

### Phase 6: Admin OrdersPage + Dashboard (Ngày 15–17)

- [ ] Viết lại `OrdersPage.vue` (admin) — bảng đơn hàng, filter, chi tiết
- [ ] Tạo component `OrderDetailModal.vue`
- [ ] Cập nhật `DashboardPage.vue` — widget doanh thu
- [ ] Thêm cron job cancel đơn pending quá 30 phút (optional)

> **Test:** Admin xem danh sách đơn, lọc, cập nhật trạng thái

---

## Tổng kết

| Deliverable | Số lượng |
|-------------|---------|
| Migration files | 3 |
| Models | 3 |
| Controllers | 3 |
| Service class | 1 |
| Form Requests | 1–2 |
| API Resources | 3 |
| Repository (interface + impl) | 2 |
| Frontend Pages (mới + viết lại) | 5 |
| Frontend Components (mới) | 4 |
| API module (FE) | 1 |
| Config file | 1 |
