# Excel Export (Orders & Teacher Payouts) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add Excel (.xlsx) export for orders, teacher payouts, and teacher earnings — accessible by admins and teachers, with date-range + status filters.

**Architecture:** Install `maatwebsite/excel`; create three Export classes (FromQuery + WithHeadings + WithMapping) in the Payment and Commission modules; add one export method per controller + route; build a shared `ExportExcelModal.vue` component used by 4 frontend pages.

**Tech Stack:** Laravel 12, Nwidart Modules, maatwebsite/excel ^3.1, Vue 3 + TypeScript, Tailwind CSS, axios (responseType: blob)

---

## File Map

**New files:**
```
e-learning-backend/
├── Modules/Payment/app/Exports/OrdersExport.php
├── Modules/Payment/app/Http/Requests/ExportOrdersRequest.php
├── Modules/Commission/app/Exports/PayoutsExport.php
├── Modules/Commission/app/Exports/TeacherEarningsExport.php
├── Modules/Commission/app/Http/Requests/ExportPayoutsRequest.php
├── Modules/Commission/app/Http/Requests/ExportEarningsRequest.php
└── tests/Feature/Admin/ExcelExportTest.php

e-learning-frontend/
└── src/components/common/ExportExcelModal.vue
```

**Modified files:**
```
e-learning-backend/
├── composer.json                                                      ← add maatwebsite/excel
├── Modules/Users/database/seeders/RolePermissionSeeder.php            ← add 3 export permissions
├── Modules/Payment/app/Http/Controllers/AdminOrderController.php      ← add export()
├── Modules/Payment/routes/api.php                                     ← add export route BEFORE {id}
├── Modules/Commission/app/Http/Controllers/Admin/PayoutController.php ← add export()
├── Modules/Commission/app/Http/Controllers/Admin/TeacherEarningsController.php ← add export()
├── Modules/Commission/app/Http/Controllers/Teacher/EarningsController.php      ← add export()
└── Modules/Commission/routes/api.php                                  ← add 3 export routes

e-learning-frontend/
├── src/views/admin/OrdersPage.vue
├── src/views/admin/PayoutsPage.vue
├── src/views/admin/TeacherEarningsPage.vue
└── src/views/teacher/EarningsPage.vue
```

---

### Task 1: Install maatwebsite/excel + add permissions

**Files:**
- Modify: `e-learning-backend/composer.json`
- Modify: `Modules/Users/database/seeders/RolePermissionSeeder.php`

- [ ] **Step 1: Install maatwebsite/excel**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning/e-learning-backend && composer require maatwebsite/excel 2>&1" | cat
```

Expected: `Package operations: 1 install, 0 updates, 0 removals` or similar.

- [ ] **Step 2: Publish Excel config**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning/e-learning-backend && php artisan vendor:publish --provider='Maatwebsite\Excel\ExcelServiceProvider' --tag=config 2>&1" | cat
```

Expected: `Copied File [...] To [/config/excel.php]`

- [ ] **Step 3: Add export permissions to RolePermissionSeeder**

In `Modules/Users/database/seeders/RolePermissionSeeder.php`, add three permissions to the `$permissions` array (after the `'orders.view', 'orders.edit',` line):

```php
// Orders & Coupons
'orders.view', 'orders.edit', 'orders.export',
'coupons.view', 'coupons.create', 'coupons.edit', 'coupons.delete',
```

And after the `'teacher_earnings.view',` line:

```php
// Commission module
'payouts.view', 'payouts.approve', 'payouts.export',
'teacher_earnings.view', 'teacher_earnings.export',
'commission_settings.view', 'commission_settings.update',
```

Also add `teacher_earnings.export` to the teacher role permissions at the bottom of `RolePermissionSeeder.php`:

```php
// teacher manages only their own courses/lessons (portal at /teacher/*)
$teacher->syncPermissions([
    'courses.view', 'courses.create', 'courses.edit', 'courses.delete',
    'lessons.view', 'lessons.create', 'lessons.edit', 'lessons.delete',
    'quizzes.view', 'quizzes.create', 'quizzes.edit',
    'dashboard.view',
    'course_categories.view',
    'teacher_earnings.export',   // ← add this
]);
```

- [ ] **Step 4: Verify seeder runs without errors**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning/e-learning-backend && php artisan db:seed --class='Modules\Users\Database\Seeders\RolePermissionSeeder' 2>&1" | cat
```

Expected: no errors.

- [ ] **Step 5: Commit**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning && git add e-learning-backend/composer.json e-learning-backend/composer.lock e-learning-backend/config/excel.php e-learning-backend/Modules/Users/database/seeders/RolePermissionSeeder.php && git commit -m 'feat(backend): install maatwebsite/excel and add export permissions'" | cat
```

---

### Task 2: OrdersExport class + export endpoint

**Files:**
- Create: `Modules/Payment/app/Exports/OrdersExport.php`
- Create: `Modules/Payment/app/Http/Requests/ExportOrdersRequest.php`
- Modify: `Modules/Payment/app/Http/Controllers/AdminOrderController.php`
- Modify: `Modules/Payment/routes/api.php`
- Test: `tests/Feature/Admin/ExcelExportTest.php` (start this file here)

- [ ] **Step 1: Write the failing test**

Create `e-learning-backend/tests/Feature/Admin/ExcelExportTest.php`:

```php
<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Payment\Models\Order;
use Modules\Payment\Models\OrderItem;
use Modules\Course\Models\Course;
use Modules\Students\Models\Student;
use Modules\Teachers\Models\Teachers;
use Tests\TestCase;
use Tests\Traits\HasAdminUser;

class ExcelExportTest extends TestCase
{
    use HasAdminUser, RefreshDatabase;

    public function test_admin_can_export_orders_as_excel(): void
    {
        Excel::fake();
        $this->setupAdmin();

        $teacher = Teachers::create(['name' => 'T', 'slug' => 't-slug', 'exp' => 1, 'status' => 1]);
        $course = Course::create(['teacher_id' => $teacher->id, 'name' => 'PHP', 'slug' => 'php', 'price' => 100000, 'status' => 1]);
        $student = Student::forceCreate(['name' => 'S', 'email' => 's@test.com', 'password' => 'p']);
        $order = Order::create([
            'order_code' => 'ORD001',
            'student_id' => $student->id,
            'subtotal' => 100000,
            'discount_amount' => 0,
            'total_amount' => 100000,
            'status' => 'paid',
            'payment_method' => 'vnpay',
            'paid_at' => now(),
        ]);
        OrderItem::create(['order_id' => $order->id, 'course_id' => $course->id, 'price' => 100000, 'sale_price' => 100000, 'final_price' => 100000]);

        $from = now()->startOfMonth()->format('Y-m-d');
        $to = now()->format('Y-m-d');
        $this->getJson("/api/v1/admin/orders/export?from={$from}&to={$to}")
            ->assertStatus(200);

        Excel::assertDownloaded("don-hang_{$from}_{$to}.xlsx");
    }

    public function test_admin_without_orders_export_permission_gets_403(): void
    {
        Excel::fake();
        $admin = $this->setupAdmin();
        $admin->revokePermissionTo('orders.export');

        $this->getJson('/api/v1/admin/orders/export')->assertStatus(403);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning/e-learning-backend && php artisan test tests/Feature/Admin/ExcelExportTest.php 2>&1" | cat
```

Expected: FAIL — `test_admin_can_export_orders_as_excel` fails (route/class not found).

- [ ] **Step 3: Create ExportOrdersRequest**

Create `Modules/Payment/app/Http/Requests/ExportOrdersRequest.php`:

```php
<?php

namespace Modules\Payment\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ExportOrdersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from'   => 'nullable|date_format:Y-m-d',
            'to'     => 'nullable|date_format:Y-m-d|after_or_equal:from',
            'status' => 'nullable|in:paid,pending,failed,cancelled,refunded',
        ];
    }

    public function messages(): array
    {
        return [
            'from.date_format'    => 'Ngày bắt đầu không đúng định dạng (YYYY-MM-DD).',
            'to.date_format'      => 'Ngày kết thúc không đúng định dạng (YYYY-MM-DD).',
            'to.after_or_equal'   => 'Ngày kết thúc phải sau hoặc bằng ngày bắt đầu.',
            'status.in'           => 'Trạng thái không hợp lệ.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Tham số không hợp lệ.',
            'errors'  => $validator->errors(),
        ], 422));
    }
}
```

- [ ] **Step 4: Create OrdersExport class**

Create `Modules/Payment/app/Exports/OrdersExport.php`:

```php
<?php

namespace Modules\Payment\Exports;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Modules\Payment\Models\OrderItem;

class OrdersExport implements FromQuery, WithHeadings, WithMapping
{
    private int $rowNumber = 0;

    public function __construct(
        private readonly ?string $from,
        private readonly ?string $to,
        private readonly ?string $status,
    ) {}

    public function query(): Builder
    {
        return OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('students', 'orders.student_id', '=', 'students.id')
            ->join('courses', 'order_items.course_id', '=', 'courses.id')
            ->select(
                'order_items.id',
                'orders.order_code',
                'orders.subtotal',
                'orders.discount_amount',
                'orders.total_amount',
                'orders.payment_method',
                'orders.status as order_status',
                'orders.paid_at',
                'students.name as student_name',
                'students.email as student_email',
                'courses.name as course_name',
            )
            ->when($this->status, fn ($q) => $q->where('orders.status', $this->status))
            ->when($this->from, fn ($q) => $q->whereRaw(
                'COALESCE(orders.paid_at, orders.created_at) >= ?',
                [$this->from . ' 00:00:00']
            ))
            ->when($this->to, fn ($q) => $q->whereRaw(
                'COALESCE(orders.paid_at, orders.created_at) <= ?',
                [$this->to . ' 23:59:59']
            ))
            ->orderBy('orders.created_at', 'desc');
    }

    public function headings(): array
    {
        return [
            '#',
            'Mã đơn hàng',
            'Học viên',
            'Email',
            'Khóa học',
            'Tổng tiền (₫)',
            'Giảm giá (₫)',
            'Thanh toán (₫)',
            'Phương thức',
            'Trạng thái',
            'Ngày thanh toán',
        ];
    }

    public function map($row): array
    {
        $this->rowNumber++;

        $statusMap = [
            'paid'      => 'Đã thanh toán',
            'pending'   => 'Chờ thanh toán',
            'failed'    => 'Thất bại',
            'cancelled' => 'Đã hủy',
            'refunded'  => 'Hoàn tiền',
        ];

        return [
            $this->rowNumber,
            $row->order_code,
            $row->student_name,
            $row->student_email,
            $row->course_name,
            (int) $row->subtotal,
            (int) $row->discount_amount,
            (int) $row->total_amount,
            $row->payment_method,
            $statusMap[$row->order_status] ?? $row->order_status,
            $row->paid_at ? Carbon::parse($row->paid_at)->format('d/m/Y H:i') : '',
        ];
    }
}
```

- [ ] **Step 5: Add export() method to AdminOrderController**

In `Modules/Payment/app/Http/Controllers/AdminOrderController.php`, add the import and method:

Add at top of imports:
```php
use Maatwebsite\Excel\Facades\Excel;
use Modules\Payment\Exports\OrdersExport;
use Modules\Payment\Http\Requests\ExportOrdersRequest;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
```

Add the method to `AdminOrderController`:
```php
public function export(ExportOrdersRequest $request): BinaryFileResponse
{
    $from = $request->query('from', now()->startOfMonth()->format('Y-m-d'));
    $to   = $request->query('to', now()->format('Y-m-d'));

    $filename = "don-hang_{$from}_{$to}.xlsx";

    return Excel::download(
        new OrdersExport($from, $to, $request->query('status')),
        $filename
    );
}
```

- [ ] **Step 6: Add export route to Payment/routes/api.php**

In `Modules/Payment/routes/api.php`, add the export route BEFORE `orders/{id}` (it must come before parameterized routes):

```php
Route::middleware(['auth:admin'])->prefix('admin')->group(function () {
    // Extra routes trước để tránh conflict với {id}
    Route::get('orders/export', [AdminOrderController::class, 'export'])->middleware('permission:orders.export'); // ← ADD THIS FIRST
    Route::get('orders/trashed', [AdminOrderController::class, 'trashed'])->middleware('permission:orders.view');
    Route::delete('orders/bulk-delete', [AdminOrderController::class, 'bulkDelete'])->middleware('permission:orders.edit');
    Route::get('orders/stats/revenue', [AdminOrderController::class, 'revenueStats'])->middleware('permission:orders.view');
    // ...rest unchanged
```

- [ ] **Step 7: Run tests**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning/e-learning-backend && php artisan test tests/Feature/Admin/ExcelExportTest.php 2>&1" | cat
```

Expected: 2 tests, 2 passed.

- [ ] **Step 8: Lint**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning/e-learning-backend && ./vendor/bin/pint Modules/Payment/app/Exports/ Modules/Payment/app/Http/Requests/ExportOrdersRequest.php Modules/Payment/app/Http/Controllers/AdminOrderController.php 2>&1" | cat
```

- [ ] **Step 9: Commit**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning && git add e-learning-backend/Modules/Payment/app/Exports/ e-learning-backend/Modules/Payment/app/Http/Requests/ExportOrdersRequest.php e-learning-backend/Modules/Payment/app/Http/Controllers/AdminOrderController.php e-learning-backend/Modules/Payment/routes/api.php e-learning-backend/tests/Feature/Admin/ExcelExportTest.php && git commit -m 'feat(payment): add orders Excel export endpoint'" | cat
```

---

### Task 3: PayoutsExport class + export endpoint

**Files:**
- Create: `Modules/Commission/app/Exports/PayoutsExport.php`
- Create: `Modules/Commission/app/Http/Requests/ExportPayoutsRequest.php`
- Modify: `Modules/Commission/app/Http/Controllers/Admin/PayoutController.php`
- Modify: `Modules/Commission/routes/api.php`
- Modify: `tests/Feature/Admin/ExcelExportTest.php`

- [ ] **Step 1: Write failing test — add to ExcelExportTest.php**

Append these two test methods to `ExcelExportTest.php` (inside the class, after the orders tests):

```php
public function test_admin_can_export_payouts_as_excel(): void
{
    Excel::fake();
    $this->setupAdmin();

    $teacher = Teachers::create(['name' => 'T2', 'slug' => 't2-slug', 'exp' => 1, 'status' => 1]);
    \Modules\Commission\Models\TeacherPayout::create([
        'teacher_id' => $teacher->id,
        'amount' => 200000,
        'status' => 'paid',
    ]);

    $from = now()->startOfMonth()->format('Y-m-d');
    $to = now()->format('Y-m-d');
    $this->getJson("/api/v1/admin/payouts/export?from={$from}&to={$to}")
        ->assertStatus(200);

    Excel::assertDownloaded("rut-tien_{$from}_{$to}.xlsx");
}

public function test_admin_without_payouts_export_permission_gets_403(): void
{
    Excel::fake();
    $admin = $this->setupAdmin();
    $admin->revokePermissionTo('payouts.export');

    $this->getJson('/api/v1/admin/payouts/export')->assertStatus(403);
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning/e-learning-backend && php artisan test --filter=test_admin_can_export_payouts_as_excel 2>&1" | cat
```

Expected: FAIL.

- [ ] **Step 3: Create ExportPayoutsRequest**

Create `Modules/Commission/app/Http/Requests/ExportPayoutsRequest.php`:

```php
<?php

namespace Modules\Commission\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ExportPayoutsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from'   => 'nullable|date_format:Y-m-d',
            'to'     => 'nullable|date_format:Y-m-d|after_or_equal:from',
            'status' => 'nullable|in:pending,approved,rejected,paid',
        ];
    }

    public function messages(): array
    {
        return [
            'from.date_format'  => 'Ngày bắt đầu không đúng định dạng (YYYY-MM-DD).',
            'to.date_format'    => 'Ngày kết thúc không đúng định dạng (YYYY-MM-DD).',
            'to.after_or_equal' => 'Ngày kết thúc phải sau hoặc bằng ngày bắt đầu.',
            'status.in'         => 'Trạng thái không hợp lệ.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Tham số không hợp lệ.',
            'errors'  => $validator->errors(),
        ], 422));
    }
}
```

- [ ] **Step 4: Create PayoutsExport class**

Create `Modules/Commission/app/Exports/PayoutsExport.php`:

```php
<?php

namespace Modules\Commission\Exports;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Modules\Commission\Models\TeacherPayout;

class PayoutsExport implements FromQuery, WithHeadings, WithMapping
{
    private int $rowNumber = 0;

    public function __construct(
        private readonly ?string $from,
        private readonly ?string $to,
        private readonly ?string $status,
    ) {}

    public function query(): Builder
    {
        return TeacherPayout::query()
            ->join('teachers', 'teacher_payouts.teacher_id', '=', 'teachers.id')
            ->join('users', 'teachers.user_id', '=', 'users.id')
            ->select(
                'teacher_payouts.*',
                'teachers.name as teacher_name',
                'users.email as teacher_email',
            )
            ->when($this->status, fn ($q) => $q->where('teacher_payouts.status', $this->status))
            ->when($this->from, fn ($q) => $q->whereDate('teacher_payouts.created_at', '>=', $this->from))
            ->when($this->to, fn ($q) => $q->whereDate('teacher_payouts.created_at', '<=', $this->to))
            ->orderBy('teacher_payouts.created_at', 'desc');
    }

    public function headings(): array
    {
        return [
            '#',
            'Giảng viên',
            'Email',
            'Số tiền (₫)',
            'Trạng thái',
            'Ghi chú GV',
            'Ghi chú Admin',
            'Ngày xử lý',
            'Ngày tạo',
        ];
    }

    public function map($row): array
    {
        $this->rowNumber++;

        $statusMap = [
            'pending'  => 'Chờ duyệt',
            'approved' => 'Đã duyệt',
            'rejected' => 'Từ chối',
            'paid'     => 'Đã thanh toán',
        ];

        return [
            $this->rowNumber,
            $row->teacher_name,
            $row->teacher_email,
            (int) $row->amount,
            $statusMap[$row->status] ?? $row->status,
            $row->teacher_note ?? '',
            $row->admin_note ?? '',
            $row->processed_at ? Carbon::parse($row->processed_at)->format('d/m/Y H:i') : '',
            Carbon::parse($row->created_at)->format('d/m/Y H:i'),
        ];
    }
}
```

- [ ] **Step 5: Add export() to PayoutController**

In `Modules/Commission/app/Http/Controllers/Admin/PayoutController.php`, add imports and method.

At top of imports:
```php
use Maatwebsite\Excel\Facades\Excel;
use Modules\Commission\Exports\PayoutsExport;
use Modules\Commission\Http\Requests\ExportPayoutsRequest;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
```

Add to class:
```php
public function export(ExportPayoutsRequest $request): BinaryFileResponse
{
    $from = $request->query('from', now()->startOfMonth()->format('Y-m-d'));
    $to   = $request->query('to', now()->format('Y-m-d'));

    $filename = "rut-tien_{$from}_{$to}.xlsx";

    return Excel::download(
        new PayoutsExport($from, $to, $request->query('status')),
        $filename
    );
}
```

- [ ] **Step 6: Add export route to Commission/routes/api.php**

In the admin group of `Modules/Commission/routes/api.php`, add the export route BEFORE `payouts/{id}/...`:

```php
Route::middleware(['auth:admin'])->prefix('admin')->group(function () {
    // ...commission-settings routes unchanged...

    Route::get('payouts/export', [PayoutController::class, 'export'])   // ← ADD BEFORE {id} routes
        ->middleware('permission:payouts.export');
    Route::get('payouts', [PayoutController::class, 'index'])
        ->middleware('permission:payouts.view');
    Route::patch('payouts/{id}/approve', [PayoutController::class, 'approve'])
        ->middleware('permission:payouts.approve');
    // ...rest unchanged
```

- [ ] **Step 7: Run tests**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning/e-learning-backend && php artisan test tests/Feature/Admin/ExcelExportTest.php 2>&1" | cat
```

Expected: 4 tests, 4 passed.

- [ ] **Step 8: Lint**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning/e-learning-backend && ./vendor/bin/pint Modules/Commission/app/Exports/PayoutsExport.php Modules/Commission/app/Http/Requests/ExportPayoutsRequest.php Modules/Commission/app/Http/Controllers/Admin/PayoutController.php 2>&1" | cat
```

- [ ] **Step 9: Commit**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning && git add e-learning-backend/Modules/Commission/app/Exports/PayoutsExport.php e-learning-backend/Modules/Commission/app/Http/Requests/ExportPayoutsRequest.php e-learning-backend/Modules/Commission/app/Http/Controllers/Admin/PayoutController.php e-learning-backend/Modules/Commission/routes/api.php e-learning-backend/tests/Feature/Admin/ExcelExportTest.php && git commit -m 'feat(commission): add payouts Excel export endpoint'" | cat
```

---

### Task 4: TeacherEarningsExport class + export endpoints (admin + teacher)

**Files:**
- Create: `Modules/Commission/app/Exports/TeacherEarningsExport.php`
- Create: `Modules/Commission/app/Http/Requests/ExportEarningsRequest.php`
- Modify: `Modules/Commission/app/Http/Controllers/Admin/TeacherEarningsController.php`
- Modify: `Modules/Commission/app/Http/Controllers/Teacher/EarningsController.php`
- Modify: `Modules/Commission/routes/api.php`
- Modify: `tests/Feature/Admin/ExcelExportTest.php`

- [ ] **Step 1: Write failing tests — append to ExcelExportTest.php**

```php
public function test_admin_can_export_teacher_earnings(): void
{
    Excel::fake();
    $this->setupAdmin();

    $from = now()->startOfMonth()->format('Y-m-d');
    $to = now()->format('Y-m-d');
    $this->getJson("/api/v1/admin/teacher-earnings/export?from={$from}&to={$to}")
        ->assertStatus(200);

    Excel::assertDownloaded("thu-nhap_{$from}_{$to}.xlsx");
}

public function test_teacher_can_export_own_earnings(): void
{
    Excel::fake();
    $user = \Modules\Users\Models\User::forceCreate([
        'name' => 'Teacher Export', 'email' => 'tex@test.com', 'password' => 'pw',
    ]);
    $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'teacher', 'guard_name' => 'admin']);
    $permission = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'teacher_earnings.export', 'guard_name' => 'admin']);
    $role->givePermissionTo($permission);
    $user->assignRole($role);
    $this->actingAs($user, 'admin');

    $teacher = Teachers::create(['user_id' => $user->id, 'name' => 'T3', 'slug' => 't3-slug', 'exp' => 1, 'status' => 1]);

    $from = now()->startOfMonth()->format('Y-m-d');
    $to = now()->format('Y-m-d');
    $this->getJson("/api/v1/teacher/earnings/export?from={$from}&to={$to}")
        ->assertStatus(200);

    Excel::assertDownloaded("thu-nhap_{$from}_{$to}.xlsx");
}
```

- [ ] **Step 2: Run failing tests**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning/e-learning-backend && php artisan test --filter='test_admin_can_export_teacher_earnings|test_teacher_can_export_own_earnings' 2>&1" | cat
```

Expected: 2 FAIL.

- [ ] **Step 3: Create ExportEarningsRequest**

Create `Modules/Commission/app/Http/Requests/ExportEarningsRequest.php`:

```php
<?php

namespace Modules\Commission\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ExportEarningsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from'       => 'nullable|date_format:Y-m-d',
            'to'         => 'nullable|date_format:Y-m-d|after_or_equal:from',
            'teacher_id' => 'nullable|integer|exists:teachers,id',
        ];
    }

    public function messages(): array
    {
        return [
            'from.date_format'    => 'Ngày bắt đầu không đúng định dạng (YYYY-MM-DD).',
            'to.date_format'      => 'Ngày kết thúc không đúng định dạng (YYYY-MM-DD).',
            'to.after_or_equal'   => 'Ngày kết thúc phải sau hoặc bằng ngày bắt đầu.',
            'teacher_id.exists'   => 'Giảng viên không tồn tại.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Tham số không hợp lệ.',
            'errors'  => $validator->errors(),
        ], 422));
    }
}
```

- [ ] **Step 4: Create TeacherEarningsExport class**

Create `Modules/Commission/app/Exports/TeacherEarningsExport.php`:

```php
<?php

namespace Modules\Commission\Exports;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Modules\Commission\Models\TeacherEarning;

class TeacherEarningsExport implements FromQuery, WithHeadings, WithMapping
{
    private int $rowNumber = 0;

    public function __construct(
        private readonly ?string $from,
        private readonly ?string $to,
        private readonly ?int $teacherId,
        private readonly bool $showTeacherColumn,
    ) {}

    public function query(): Builder
    {
        return TeacherEarning::query()
            ->join('teachers', 'teacher_earnings.teacher_id', '=', 'teachers.id')
            ->join('order_items', 'teacher_earnings.order_item_id', '=', 'order_items.id')
            ->join('courses', 'order_items.course_id', '=', 'courses.id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->select(
                'teacher_earnings.*',
                'teachers.name as teacher_name',
                'courses.name as course_name',
                'orders.order_code',
                'order_items.final_price as revenue',
            )
            ->when($this->teacherId, fn ($q) => $q->where('teacher_earnings.teacher_id', $this->teacherId))
            ->when($this->from, fn ($q) => $q->whereDate('teacher_earnings.created_at', '>=', $this->from))
            ->when($this->to, fn ($q) => $q->whereDate('teacher_earnings.created_at', '<=', $this->to))
            ->orderBy('teacher_earnings.created_at', 'desc');
    }

    public function headings(): array
    {
        $columns = ['#', 'Khóa học', 'Mã đơn hàng', 'Doanh thu (₫)', 'Tỷ lệ HH (%)', 'Thu nhập (₫)', 'Loại', 'Ngày'];

        if ($this->showTeacherColumn) {
            array_splice($columns, 1, 0, ['Giảng viên']);
        }

        return $columns;
    }

    public function map($row): array
    {
        $this->rowNumber++;

        $typeMap = [
            'credit' => 'Thu nhập',
            'debit'  => 'Hoàn trả',
        ];

        $data = [
            $this->rowNumber,
            $row->course_name,
            $row->order_code,
            (int) $row->revenue,
            $row->commission_rate,
            (int) $row->amount,
            $typeMap[$row->type] ?? $row->type,
            Carbon::parse($row->created_at)->format('d/m/Y'),
        ];

        if ($this->showTeacherColumn) {
            array_splice($data, 1, 0, [$row->teacher_name]);
        }

        return $data;
    }
}
```

- [ ] **Step 5: Add export() to TeacherEarningsController (admin)**

In `Modules/Commission/app/Http/Controllers/Admin/TeacherEarningsController.php`, add imports and method:

```php
<?php

namespace Modules\Commission\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Commission\Exports\TeacherEarningsExport;
use Modules\Commission\Http\Requests\ExportEarningsRequest;
use Modules\Commission\Repositories\CommissionRepositoryInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TeacherEarningsController extends Controller
{
    use ApiResponse;

    public function __construct(private CommissionRepositoryInterface $repository) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min((int) $request->query('per_page', 15), 100));

        return $this->paginated($this->repository->getTeachersSummary($perPage), 'Tổng hợp hoa hồng giảng viên.');
    }

    public function export(ExportEarningsRequest $request): BinaryFileResponse
    {
        $from = $request->query('from', now()->startOfMonth()->format('Y-m-d'));
        $to   = $request->query('to', now()->format('Y-m-d'));

        $filename = "thu-nhap_{$from}_{$to}.xlsx";

        return Excel::download(
            new TeacherEarningsExport(
                from: $from,
                to: $to,
                teacherId: $request->query('teacher_id') ? (int) $request->query('teacher_id') : null,
                showTeacherColumn: true,
            ),
            $filename
        );
    }
}
```

Note: add `use Illuminate\Http\Request;` to the imports too.

- [ ] **Step 6: Add export() to EarningsController (teacher self-export)**

In `Modules/Commission/app/Http/Controllers/Teacher/EarningsController.php`, add the import and method. Add these imports at the top:

```php
use Maatwebsite\Excel\Facades\Excel;
use Modules\Commission\Exports\TeacherEarningsExport;
use Modules\Commission\Http\Requests\ExportEarningsRequest;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
```

Add method to the class:
```php
public function export(ExportEarningsRequest $request): BinaryFileResponse
{
    $user    = auth('admin')->user();
    $teacher = Teachers::where('user_id', $user->id)->firstOrFail();

    $from = $request->query('from', now()->startOfMonth()->format('Y-m-d'));
    $to   = $request->query('to', now()->format('Y-m-d'));

    $filename = "thu-nhap_{$from}_{$to}.xlsx";

    return Excel::download(
        new TeacherEarningsExport(
            from: $from,
            to: $to,
            teacherId: $teacher->id,
            showTeacherColumn: false,
        ),
        $filename
    );
}
```

- [ ] **Step 7: Add export routes to Commission/routes/api.php**

In the admin group, add the teacher-earnings export route BEFORE any `{id}` param routes:

```php
Route::get('teacher-earnings/export', [TeacherEarningsController::class, 'export'])  // ← ADD FIRST
    ->middleware('permission:teacher_earnings.export');
Route::get('teacher-earnings', [TeacherEarningsController::class, 'index'])
    ->middleware('permission:teacher_earnings.view');
```

In the teacher group, add the earnings export route BEFORE `earnings` (to be safe):

```php
Route::middleware(['auth:admin', 'role:teacher'])->prefix('teacher')->group(function () {
    Route::get('earnings/export', [EarningsController::class, 'export']);  // ← ADD FIRST
    Route::get('earnings', [EarningsController::class, 'index']);
    // ...rest unchanged
```

- [ ] **Step 8: Run all tests**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning/e-learning-backend && php artisan test tests/Feature/Admin/ExcelExportTest.php 2>&1" | cat
```

Expected: 6 tests, 6 passed.

- [ ] **Step 9: Run full test suite to check no regressions**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning/e-learning-backend && php artisan test 2>&1" | cat
```

Expected: all pass.

- [ ] **Step 10: Lint**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning/e-learning-backend && ./vendor/bin/pint Modules/Commission/app/Exports/ Modules/Commission/app/Http/Requests/ExportEarningsRequest.php Modules/Commission/app/Http/Controllers/Admin/TeacherEarningsController.php Modules/Commission/app/Http/Controllers/Teacher/EarningsController.php 2>&1" | cat
```

- [ ] **Step 11: Commit**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning && git add e-learning-backend/Modules/Commission/app/Exports/ e-learning-backend/Modules/Commission/app/Http/Requests/ExportEarningsRequest.php e-learning-backend/Modules/Commission/app/Http/Controllers/Admin/TeacherEarningsController.php e-learning-backend/Modules/Commission/app/Http/Controllers/Teacher/EarningsController.php e-learning-backend/Modules/Commission/routes/api.php e-learning-backend/tests/Feature/Admin/ExcelExportTest.php && git commit -m 'feat(commission): add teacher earnings Excel export endpoints'" | cat
```

---

### Task 5: ExportExcelModal.vue component

**Files:**
- Create: `e-learning-frontend/src/components/common/ExportExcelModal.vue`

- [ ] **Step 1: Create ExportExcelModal.vue**

Create `e-learning-frontend/src/components/common/ExportExcelModal.vue`:

```vue
<template>
  <Teleport to="body">
    <div
      v-if="show"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
      @click.self="$emit('close')"
    >
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-md mx-4 p-6">
        <div class="flex items-center justify-between mb-5">
          <h3 class="text-lg font-bold text-gray-900">{{ title }}</h3>
          <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600 transition-colors">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div class="space-y-4">
          <!-- Date range -->
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Từ ngày</label>
              <input
                v-model="form.from"
                type="date"
                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-300"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Đến ngày</label>
              <input
                v-model="form.to"
                type="date"
                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-300"
              />
            </div>
          </div>

          <!-- Status filter (optional) -->
          <div v-if="hasStatusFilter">
            <label class="block text-sm font-medium text-gray-700 mb-1">Trạng thái</label>
            <select
              v-model="form.status"
              class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-300"
            >
              <option value="">Tất cả trạng thái</option>
              <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">
                {{ opt.label }}
              </option>
            </select>
          </div>
        </div>

        <div class="flex justify-end gap-3 mt-6">
          <button
            @click="$emit('close')"
            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors"
          >
            Hủy
          </button>
          <button
            @click="handleExport"
            :disabled="loading"
            class="px-5 py-2 text-sm font-medium text-white bg-green-600 hover:bg-green-700 disabled:opacity-50 rounded-xl transition-colors flex items-center gap-2"
          >
            <svg v-if="loading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
            </svg>
            {{ loading ? 'Đang xuất...' : 'Xuất Excel' }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue'
import { useToast } from 'vue-toastification'
import http from '@/plugins/axios'

interface StatusOption {
  value: string
  label: string
}

const props = defineProps<{
  show: boolean
  title: string
  endpoint: string
  extraParams?: Record<string, string | number | undefined>
  hasStatusFilter?: boolean
  statusOptions?: StatusOption[]
}>()

const emit = defineEmits<{
  close: []
}>()

const toast = useToast()
const loading = ref(false)

function defaultFrom(): string {
  const d = new Date()
  d.setDate(1)
  return d.toISOString().slice(0, 10)
}

function defaultTo(): string {
  return new Date().toISOString().slice(0, 10)
}

const form = reactive({
  from: defaultFrom(),
  to: defaultTo(),
  status: '',
})

async function handleExport() {
  loading.value = true
  try {
    const params: Record<string, string | number> = {
      from: form.from,
      to: form.to,
      ...(props.extraParams ?? {}),
    }
    if (props.hasStatusFilter && form.status) {
      params.status = form.status
    }

    const res = await http.get(props.endpoint, {
      params,
      responseType: 'blob',
    })

    const contentDisposition = res.headers['content-disposition'] as string | undefined
    const match = contentDisposition?.match(/filename="?([^";\n]+)"?/)
    const filename = match?.[1] ?? 'export.xlsx'

    const url = URL.createObjectURL(new Blob([res.data]))
    const a = document.createElement('a')
    a.href = url
    a.download = filename
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
    URL.revokeObjectURL(url)

    emit('close')
  } catch {
    toast.error('Xuất file thất bại. Vui lòng thử lại.')
  } finally {
    loading.value = false
  }
}
</script>
```

- [ ] **Step 2: Verify TypeScript compiles**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning/e-learning-frontend && npm run build 2>&1" | cat
```

Expected: no TypeScript errors related to ExportExcelModal.vue.

- [ ] **Step 3: Lint**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning/e-learning-frontend && npm run lint 2>&1" | cat
```

- [ ] **Step 4: Commit**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning && git add e-learning-frontend/src/components/common/ExportExcelModal.vue && git commit -m 'feat(frontend): add ExportExcelModal shared component'" | cat
```

---

### Task 6: Add export button to 4 pages

**Files:**
- Modify: `e-learning-frontend/src/views/admin/OrdersPage.vue`
- Modify: `e-learning-frontend/src/views/admin/PayoutsPage.vue`
- Modify: `e-learning-frontend/src/views/admin/TeacherEarningsPage.vue`
- Modify: `e-learning-frontend/src/views/teacher/EarningsPage.vue`

#### 6a — OrdersPage.vue

- [ ] **Step 1: Add ExportExcelModal import to OrdersPage.vue**

In `<script setup>`, add the import after existing imports:

```ts
import ExportExcelModal from '@/components/common/ExportExcelModal.vue'
```

Add a ref for the modal:

```ts
const showExportModal = ref(false)
```

- [ ] **Step 2: Add export button to OrdersPage.vue template**

In the `<div class="flex items-center justify-between mb-6">` header div, add a button alongside the existing counter span:

```html
<div class="flex items-center gap-3">
  <button
    @click="showExportModal = true"
    class="flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-xl transition-colors"
  >
    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
    </svg>
    Xuất Excel
  </button>
  <div class="flex items-center gap-2 text-sm text-gray-500">
    <span class="font-medium text-gray-800">{{ activePagination?.total ?? 0 }}</span> đơn hàng
  </div>
</div>
```

- [ ] **Step 3: Add ExportExcelModal at bottom of OrdersPage.vue template**

Before `</template>` closing tag, add:

```html
<ExportExcelModal
  :show="showExportModal"
  title="Xuất Excel - Đơn hàng"
  endpoint="/admin/orders/export"
  :has-status-filter="true"
  :status-options="[
    { value: 'paid', label: 'Đã thanh toán' },
    { value: 'pending', label: 'Chờ thanh toán' },
    { value: 'failed', label: 'Thất bại' },
    { value: 'cancelled', label: 'Đã hủy' },
    { value: 'refunded', label: 'Hoàn tiền' },
  ]"
  @close="showExportModal = false"
/>
```

#### 6b — PayoutsPage.vue

- [ ] **Step 4: Add export to PayoutsPage.vue**

In `<script setup>`, add:

```ts
import ExportExcelModal from '@/components/common/ExportExcelModal.vue'
// ...
const showExportModal = ref(false)
```

In the `<template>`, change the h1 line to include a flex wrapper with the button:

```html
<div class="flex items-center justify-between mb-6">
  <h1 class="text-2xl font-bold">Quản lý rút tiền</h1>
  <button
    @click="showExportModal = true"
    class="flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-xl transition-colors"
  >
    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
    </svg>
    Xuất Excel
  </button>
</div>
```

Before `</template>`, add:

```html
<ExportExcelModal
  :show="showExportModal"
  title="Xuất Excel - Yêu cầu rút tiền"
  endpoint="/admin/payouts/export"
  :has-status-filter="true"
  :status-options="[
    { value: 'pending', label: 'Chờ duyệt' },
    { value: 'approved', label: 'Đã duyệt' },
    { value: 'rejected', label: 'Từ chối' },
    { value: 'paid', label: 'Đã thanh toán' },
  ]"
  @close="showExportModal = false"
/>
```

#### 6c — TeacherEarningsPage.vue

- [ ] **Step 5: Add export to TeacherEarningsPage.vue**

In `<script setup>`, add:

```ts
import { ref } from 'vue'   // add ref if not already imported
import ExportExcelModal from '@/components/common/ExportExcelModal.vue'
// ...
const showExportModal = ref(false)
```

Change the `<h1 class="text-2xl font-bold mb-6">` section to a flex header:

```html
<div class="flex items-center justify-between mb-6">
  <h1 class="text-2xl font-bold">Hoa hồng giảng viên</h1>
  <button
    @click="showExportModal = true"
    class="flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-xl transition-colors"
  >
    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
    </svg>
    Xuất Excel
  </button>
</div>
```

Before `</template>`, add:

```html
<ExportExcelModal
  :show="showExportModal"
  title="Xuất Excel - Thu nhập giảng viên"
  endpoint="/admin/teacher-earnings/export"
  :has-status-filter="false"
  @close="showExportModal = false"
/>
```

#### 6d — EarningsPage.vue (teacher)

- [ ] **Step 6: Add export to EarningsPage.vue**

In `<script setup>`, add:

```ts
import ExportExcelModal from '@/components/common/ExportExcelModal.vue'
// ...
const showExportModal = ref(false)
```

Change the `<h1 class="text-2xl font-bold mb-6">Thu nhập của tôi</h1>` to a flex header:

```html
<div class="flex items-center justify-between mb-6">
  <h1 class="text-2xl font-bold">Thu nhập của tôi</h1>
  <button
    @click="showExportModal = true"
    class="flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-xl transition-colors"
  >
    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
    </svg>
    Xuất Excel
  </button>
</div>
```

Before `</template>`, add:

```html
<ExportExcelModal
  :show="showExportModal"
  title="Xuất Excel - Thu nhập của tôi"
  endpoint="/teacher/earnings/export"
  :has-status-filter="false"
  @close="showExportModal = false"
/>
```

- [ ] **Step 7: Build to verify no TypeScript errors**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning/e-learning-frontend && npm run build 2>&1" | cat
```

Expected: Build succeeds with no errors.

- [ ] **Step 8: Lint**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning/e-learning-frontend && npm run lint 2>&1" | cat
```

- [ ] **Step 9: Commit**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning && git add e-learning-frontend/src/views/admin/OrdersPage.vue e-learning-frontend/src/views/admin/PayoutsPage.vue e-learning-frontend/src/views/admin/TeacherEarningsPage.vue e-learning-frontend/src/views/teacher/EarningsPage.vue && git commit -m 'feat(frontend): add Excel export buttons to orders, payouts and earnings pages'" | cat
```

---

## Self-Review Notes

Spec coverage checklist:
- ✅ `maatwebsite/excel` install → Task 1
- ✅ 4 endpoints (orders, payouts, admin-earnings, teacher-earnings) → Tasks 2, 3, 4
- ✅ 3 Export classes (FromQuery + WithHeadings + WithMapping) → Tasks 2, 3, 4
- ✅ Permissions (orders.export, payouts.export, teacher_earnings.export) → Task 1
- ✅ teacher_earnings.export assigned to teacher role → Task 1
- ✅ Route order: export before {id} → Tasks 2, 3, 4 Step 6/7
- ✅ Teacher self-export scoped to own data (teacherId = auth user's teacher) → Task 4 Step 6
- ✅ Admin earnings export shows teacher column, teacher self-export hides it → Tasks 4 Steps 4/5/6
- ✅ Status maps (Vietnamese labels) in all 3 exports → Tasks 2, 3, 4 Step 4
- ✅ Date filter: paid_at/created_at COALESCE for orders → Task 2 Step 4
- ✅ Date filter: created_at for payouts and earnings → Tasks 3, 4 Step 4
- ✅ Filename format: `don-hang_from_to.xlsx`, `rut-tien_...`, `thu-nhap_...` → Tasks 2, 3, 4 Steps 5/6
- ✅ Frontend ExportExcelModal.vue with blob download → Task 5
- ✅ Export button on 4 pages → Task 6
- ✅ Feature tests → Tasks 2, 3, 4 Steps 1–2
- ✅ Permission guard test → Task 2 Step 1
- ✅ Teacher self-export test → Task 4 Step 1
