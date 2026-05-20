# Teacher Commission System — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a teacher commission system that automatically records earnings when courses are sold, allows teachers to request payouts via dashboard, and gives admins full visibility and control over the payout workflow.

**Architecture:** New Nwidart module `Commission` using a ledger-based balance design — every sale creates a `credit` row in `teacher_earnings`, every refund creates a `debit` row. Available balance = SUM(credits) − SUM(debits) − SUM(pending/approved payouts). No cached balance column, no race conditions.

**Tech Stack:** Laravel 12, Nwidart Modules, Spatie Permission, Vue 3 + TypeScript, Pinia, Axios, vue-toastification

---

## File Structure

**New — Backend (`e-learning-backend/`)**
```
Modules/Commission/
├── module.json
├── app/
│   ├── Providers/
│   │   ├── CommissionServiceProvider.php
│   │   └── RouteServiceProvider.php
│   ├── Models/
│   │   ├── CommissionSetting.php
│   │   ├── TeacherEarning.php
│   │   └── TeacherPayout.php
│   ├── Repositories/
│   │   ├── CommissionRepositoryInterface.php
│   │   └── CommissionRepository.php
│   ├── Services/
│   │   └── CommissionService.php
│   ├── Listeners/
│   │   └── CommissionListener.php
│   └── Http/
│       ├── Controllers/Admin/
│       │   ├── CommissionSettingsController.php
│       │   ├── PayoutController.php
│       │   └── TeacherEarningsController.php
│       ├── Controllers/Teacher/
│       │   └── EarningsController.php
│       ├── Requests/
│       │   └── StorePayoutRequest.php
│       └── Resources/
│           ├── TeacherEarningResource.php
│           └── TeacherPayoutResource.php
├── database/
│   ├── migrations/ (4 files)
│   └── seeders/CommissionSettingSeeder.php
└── routes/api.php
```

**Modify — Backend**
- `Modules/Payment/app/Events/OrderRefunded.php` — create new event
- `Modules/Payment/app/Http/Controllers/AdminOrderController.php` — fire OrderRefunded when status → refunded
- `database/seeders/DatabaseSeeder.php` — call CommissionSettingSeeder

**New — Frontend (`e-learning-frontend/src/`)**
```
services/commission.service.ts
composables/usePayouts.ts
composables/useTeacherEarnings.ts
composables/useEarnings.ts
views/admin/PayoutsPage.vue
views/admin/TeacherEarningsPage.vue
views/admin/CommissionSettingsPage.vue
views/teacher/EarningsPage.vue
```

**Modify — Frontend**
- `router/index.js` — add 4 new routes

---

### Task 1: Module scaffold + migrations + models

**Files:**
- Create: `Modules/Commission/module.json`
- Create: `Modules/Commission/app/Providers/CommissionServiceProvider.php`
- Create: `Modules/Commission/app/Providers/RouteServiceProvider.php`
- Create: `Modules/Commission/routes/api.php`
- Create: 4 migration files
- Create: `Modules/Commission/app/Models/CommissionSetting.php`
- Create: `Modules/Commission/app/Models/TeacherEarning.php`
- Create: `Modules/Commission/app/Models/TeacherPayout.php`

- [ ] **Step 1.1: Create module directory structure**

```bash
wsl.exe -d Ubuntu -- bash -c "mkdir -p /home/vanthanh/DATN/e-learning/e-learning-backend/Modules/Commission/{app/{Providers,Models,Repositories,Services,Listeners,Http/{Controllers/{Admin,Teacher},Requests,Resources}},database/{migrations,seeders},routes}" | cat
```

- [ ] **Step 1.2: Create module.json**

File: `Modules/Commission/module.json`
```json
{
    "name": "Commission",
    "alias": "commission",
    "description": "Teacher commission and payout management",
    "keywords": [],
    "priority": 0,
    "providers": [
        "Modules\\Commission\\Providers\\CommissionServiceProvider"
    ],
    "files": []
}
```

- [ ] **Step 1.3: Create CommissionServiceProvider**

File: `Modules/Commission/app/Providers/CommissionServiceProvider.php`
```php
<?php

namespace Modules\Commission\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Modules\Commission\Listeners\CommissionListener;
use Modules\Commission\Repositories\CommissionRepository;
use Modules\Commission\Repositories\CommissionRepositoryInterface;
use Modules\Payment\Events\OrderPlaced;
use Modules\Payment\Events\OrderRefunded;

class CommissionServiceProvider extends ServiceProvider
{
    protected string $name = 'Commission';

    public function boot(): void
    {
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));

        Event::listen(OrderPlaced::class, [CommissionListener::class, 'handleOrderPlaced']);
        Event::listen(OrderRefunded::class, [CommissionListener::class, 'handleOrderRefunded']);
    }

    public function register(): void
    {
        $this->app->bind(CommissionRepositoryInterface::class, CommissionRepository::class);
        $this->app->register(RouteServiceProvider::class);
    }
}
```

- [ ] **Step 1.4: Create RouteServiceProvider**

File: `Modules/Commission/app/Providers/RouteServiceProvider.php`
```php
<?php

namespace Modules\Commission\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    protected string $name = 'Commission';

    public function boot(): void
    {
        parent::boot();
    }

    public function map(): void
    {
        Route::middleware('api')->prefix('api/v1')->name('api.')->group(module_path($this->name, '/routes/api.php'));
    }
}
```

- [ ] **Step 1.5: Create routes/api.php stub**

File: `Modules/Commission/routes/api.php`
```php
<?php

use Illuminate\Support\Facades\Route;

// Commission routes — populated in Task 5–8
```

- [ ] **Step 1.6: Create migration 1 — commission_settings**

File: `Modules/Commission/database/migrations/2026_05_20_000001_create_commission_settings_table.php`
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commission_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('teacher_rate', 5, 2)->default(70.00);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_settings');
    }
};
```

- [ ] **Step 1.7: Create migration 2 — teacher_earnings**

File: `Modules/Commission/database/migrations/2026_05_20_000002_create_teacher_earnings_table.php`
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_earnings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('teacher_id');
            $table->unsignedBigInteger('order_item_id')->nullable();
            $table->enum('type', ['credit', 'debit']);
            $table->decimal('amount', 12, 2);
            $table->decimal('commission_rate', 5, 2);
            $table->string('description')->nullable();
            $table->timestamps();

            $table->foreign('teacher_id')->references('id')->on('teachers')->onDelete('cascade');
            $table->foreign('order_item_id')->references('id')->on('order_items')->onDelete('set null');
            $table->index('teacher_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_earnings');
    }
};
```

- [ ] **Step 1.8: Create migration 3 — teacher_payouts**

File: `Modules/Commission/database/migrations/2026_05_20_000003_create_teacher_payouts_table.php`
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_payouts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('teacher_id');
            $table->decimal('amount', 12, 2);
            $table->enum('status', ['pending', 'approved', 'rejected', 'paid'])->default('pending');
            $table->text('teacher_note')->nullable();
            $table->text('admin_note')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->foreign('teacher_id')->references('id')->on('teachers')->onDelete('cascade');
            $table->index(['teacher_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_payouts');
    }
};
```

- [ ] **Step 1.9: Create migration 4 — bank fields on teachers**

File: `Modules/Commission/database/migrations/2026_05_20_000004_add_bank_fields_to_teachers_table.php`
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->string('bank_name')->nullable()->after('status');
            $table->string('bank_account_number')->nullable()->after('bank_name');
            $table->string('bank_account_name')->nullable()->after('bank_account_number');
        });
    }

    public function down(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->dropColumn(['bank_name', 'bank_account_number', 'bank_account_name']);
        });
    }
};
```

- [ ] **Step 1.10: Create CommissionSetting model**

File: `Modules/Commission/app/Models/CommissionSetting.php`
```php
<?php

namespace Modules\Commission\Models;

use Illuminate\Database\Eloquent\Model;

class CommissionSetting extends Model
{
    protected $table = 'commission_settings';

    protected $fillable = ['teacher_rate'];

    protected $casts = ['teacher_rate' => 'decimal:2'];

    public static function current(): self
    {
        return static::firstOrCreate([], ['teacher_rate' => 70.00]);
    }
}
```

- [ ] **Step 1.11: Create TeacherEarning model**

File: `Modules/Commission/app/Models/TeacherEarning.php`
```php
<?php

namespace Modules\Commission\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Payment\Models\OrderItem;
use Modules\Teachers\Models\Teachers;

class TeacherEarning extends Model
{
    protected $table = 'teacher_earnings';

    protected $fillable = ['teacher_id', 'order_item_id', 'type', 'amount', 'commission_rate', 'description'];

    protected $casts = ['amount' => 'decimal:2', 'commission_rate' => 'decimal:2'];

    public function teacher()
    {
        return $this->belongsTo(Teachers::class, 'teacher_id');
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }
}
```

- [ ] **Step 1.12: Create TeacherPayout model**

File: `Modules/Commission/app/Models/TeacherPayout.php`
```php
<?php

namespace Modules\Commission\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Teachers\Models\Teachers;

class TeacherPayout extends Model
{
    protected $table = 'teacher_payouts';

    protected $fillable = ['teacher_id', 'amount', 'status', 'teacher_note', 'admin_note', 'processed_at'];

    protected $casts = ['amount' => 'decimal:2', 'processed_at' => 'datetime'];

    public function teacher()
    {
        return $this->belongsTo(Teachers::class, 'teacher_id');
    }
}
```

- [ ] **Step 1.13: Run migrations and verify**

```bash
wsl.exe -d Ubuntu -- bash -c "php artisan migrate 2>&1" | cat
```
Expected: 4 new migrations run without errors. Tables `commission_settings`, `teacher_earnings`, `teacher_payouts` created; columns `bank_name`, `bank_account_number`, `bank_account_name` added to `teachers`.

- [ ] **Step 1.14: Commit**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning && git add e-learning-backend/Modules/Commission/ && git commit -m 'feat(backend): scaffold Commission module with migrations and models'" | cat
```

---

### Task 2: CommissionRepository + CommissionService + tests

**Files:**
- Create: `Modules/Commission/app/Repositories/CommissionRepositoryInterface.php`
- Create: `Modules/Commission/app/Repositories/CommissionRepository.php`
- Create: `Modules/Commission/app/Services/CommissionService.php`
- Create: `tests/Feature/Commission/CommissionServiceTest.php`

- [ ] **Step 2.1: Write failing test**

File: `tests/Feature/Commission/CommissionServiceTest.php`
```php
<?php

namespace Tests\Feature\Commission;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Commission\Models\CommissionSetting;
use Modules\Commission\Models\TeacherEarning;
use Modules\Commission\Services\CommissionService;
use Modules\Course\Models\Course;
use Modules\Payment\Models\Order;
use Modules\Payment\Models\OrderItem;
use Modules\Teachers\Models\Teachers;
use Tests\TestCase;

class CommissionServiceTest extends TestCase
{
    use RefreshDatabase;

    private CommissionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(CommissionService::class);
    }

    private function makeOrder(): Order
    {
        $teacher = Teachers::create(['name' => 'Teacher A', 'slug' => 'teacher-a', 'exp' => 3, 'status' => 1]);
        $course = Course::create(['teacher_id' => $teacher->id, 'name' => 'Laravel Cơ bản', 'slug' => 'laravel-co-ban', 'price' => 500000, 'level' => 'beginner', 'status' => 1]);
        $order = Order::create(['order_code' => 'TEST001', 'student_id' => 1, 'subtotal' => 500000, 'discount_amount' => 0, 'total_amount' => 500000, 'status' => 'paid', 'payment_method' => 'vnpay']);
        OrderItem::create(['order_id' => $order->id, 'course_id' => $course->id, 'price' => 500000, 'sale_price' => null, 'final_price' => 500000]);

        return $order->load('items.course.teacher');
    }

    public function test_record_earnings_creates_credit_for_each_item(): void
    {
        CommissionSetting::create(['teacher_rate' => 70.00]);
        $order = $this->makeOrder();

        $this->service->recordEarnings($order);

        $this->assertDatabaseHas('teacher_earnings', ['type' => 'credit', 'amount' => '350000.00', 'commission_rate' => '70.00']);
    }

    public function test_record_earnings_uses_rate_snapshot_from_settings(): void
    {
        CommissionSetting::create(['teacher_rate' => 80.00]);
        $order = $this->makeOrder();

        $this->service->recordEarnings($order);

        $earning = TeacherEarning::first();
        $this->assertEquals('80.00', $earning->commission_rate);
        $this->assertEquals('400000.00', $earning->amount);
    }

    public function test_reverse_earnings_creates_debit_matching_credit(): void
    {
        CommissionSetting::create(['teacher_rate' => 70.00]);
        $order = $this->makeOrder();
        $this->service->recordEarnings($order);

        $this->service->reverseEarnings($order);

        $this->assertDatabaseHas('teacher_earnings', ['type' => 'debit', 'amount' => '350000.00']);
        $this->assertDatabaseCount('teacher_earnings', 2);
    }

    public function test_get_available_balance_is_credit_minus_debit(): void
    {
        CommissionSetting::create(['teacher_rate' => 70.00]);
        $order = $this->makeOrder();
        $teacherId = $order->items->first()->course->teacher->id;

        $this->service->recordEarnings($order);
        $balance = $this->service->getAvailableBalance($teacherId);

        $this->assertEquals(350000.0, $balance);
    }
}
```

- [ ] **Step 2.2: Run test — confirm it fails**

```bash
wsl.exe -d Ubuntu -- bash -c "php artisan test tests/Feature/Commission/CommissionServiceTest.php 2>&1" | cat
```
Expected: FAIL — `CommissionService` class not found.

- [ ] **Step 2.3: Create CommissionRepositoryInterface**

File: `Modules/Commission/app/Repositories/CommissionRepositoryInterface.php`
```php
<?php

namespace Modules\Commission\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface CommissionRepositoryInterface
{
    public function getAvailableBalance(int $teacherId): float;
    public function getTotalEarned(int $teacherId): float;
    public function getTotalPaid(int $teacherId): float;
    public function getPendingPayoutAmount(int $teacherId): float;
    public function getEarningsForTeacher(int $teacherId, int $perPage): LengthAwarePaginator;
    public function getTeachersSummary(): Collection;
}
```

- [ ] **Step 2.4: Create CommissionRepository**

File: `Modules/Commission/app/Repositories/CommissionRepository.php`
```php
<?php

namespace Modules\Commission\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Commission\Models\TeacherEarning;
use Modules\Commission\Models\TeacherPayout;

class CommissionRepository implements CommissionRepositoryInterface
{
    public function getAvailableBalance(int $teacherId): float
    {
        $totalEarned = TeacherEarning::where('teacher_id', $teacherId)->where('type', 'credit')->sum('amount');
        $totalDeducted = TeacherEarning::where('teacher_id', $teacherId)->where('type', 'debit')->sum('amount');
        $pendingPayouts = TeacherPayout::where('teacher_id', $teacherId)->whereIn('status', ['pending', 'approved'])->sum('amount');

        return (float) max(0, $totalEarned - $totalDeducted - $pendingPayouts);
    }

    public function getTotalEarned(int $teacherId): float
    {
        return (float) TeacherEarning::where('teacher_id', $teacherId)->where('type', 'credit')->sum('amount');
    }

    public function getTotalPaid(int $teacherId): float
    {
        return (float) TeacherPayout::where('teacher_id', $teacherId)->where('status', 'paid')->sum('amount');
    }

    public function getPendingPayoutAmount(int $teacherId): float
    {
        return (float) TeacherPayout::where('teacher_id', $teacherId)->whereIn('status', ['pending', 'approved'])->sum('amount');
    }

    public function getEarningsForTeacher(int $teacherId, int $perPage): LengthAwarePaginator
    {
        $perPage = max(1, min($perPage, 100));

        return TeacherEarning::where('teacher_id', $teacherId)->latest()->paginate($perPage);
    }

    public function getTeachersSummary(): Collection
    {
        // Correlated subqueries avoid cartesian product from joining both tables simultaneously
        return DB::table('teachers')
            ->select([
                'teachers.id',
                'teachers.name',
                DB::raw("COALESCE((SELECT SUM(amount) FROM teacher_earnings WHERE teacher_id = teachers.id AND type = 'credit'), 0) as total_earned"),
                DB::raw("COALESCE((SELECT SUM(amount) FROM teacher_earnings WHERE teacher_id = teachers.id AND type = 'debit'), 0) as total_deducted"),
                DB::raw("COALESCE((SELECT SUM(amount) FROM teacher_payouts WHERE teacher_id = teachers.id AND status IN ('pending', 'approved')), 0) as pending_payout"),
            ])
            ->get()
            ->map(function ($row) {
                $row->available_balance = max(0, $row->total_earned - $row->total_deducted - $row->pending_payout);

                return $row;
            });
    }
}
```

- [ ] **Step 2.5: Create CommissionService**

File: `Modules/Commission/app/Services/CommissionService.php`
```php
<?php

namespace Modules\Commission\Services;

use Modules\Commission\Models\CommissionSetting;
use Modules\Commission\Models\TeacherEarning;
use Modules\Commission\Repositories\CommissionRepositoryInterface;
use Modules\Payment\Models\Order;

class CommissionService
{
    public function __construct(private CommissionRepositoryInterface $repository) {}

    public function recordEarnings(Order $order): void
    {
        $rate = CommissionSetting::current()->teacher_rate;

        foreach ($order->items as $item) {
            $teacher = $item->course?->teacher;
            if (! $teacher) {
                continue;
            }

            TeacherEarning::create([
                'teacher_id'      => $teacher->id,
                'order_item_id'   => $item->id,
                'type'            => 'credit',
                'amount'          => round((float) $item->final_price * (float) $rate / 100, 2),
                'commission_rate' => $rate,
                'description'     => 'Hoa hồng từ: ' . $item->course->name,
            ]);
        }
    }

    public function reverseEarnings(Order $order): void
    {
        foreach ($order->items as $item) {
            $original = TeacherEarning::where('order_item_id', $item->id)->where('type', 'credit')->first();

            if (! $original) {
                continue;
            }

            TeacherEarning::create([
                'teacher_id'      => $original->teacher_id,
                'order_item_id'   => $item->id,
                'type'            => 'debit',
                'amount'          => $original->amount,
                'commission_rate' => $original->commission_rate,
                'description'     => 'Hoàn tiền: ' . ($item->course->name ?? 'Khóa học'),
            ]);
        }
    }

    public function getAvailableBalance(int $teacherId): float
    {
        return $this->repository->getAvailableBalance($teacherId);
    }
}
```

- [ ] **Step 2.6: Run test — confirm it passes**

```bash
wsl.exe -d Ubuntu -- bash -c "php artisan test tests/Feature/Commission/CommissionServiceTest.php 2>&1" | cat
```
Expected: 4 tests PASS ✓.

- [ ] **Step 2.7: Commit**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning && git add e-learning-backend/Modules/Commission/app/Repositories/ e-learning-backend/Modules/Commission/app/Services/ e-learning-backend/tests/Feature/Commission/ && git commit -m 'feat(backend): add CommissionRepository and CommissionService with tests'" | cat
```

---

### Task 3: CommissionListener + OrderPlaced integration

**Files:**
- Create: `Modules/Commission/app/Listeners/CommissionListener.php`
- Modify: `tests/Feature/Commission/CommissionServiceTest.php` (add 1 test)

- [ ] **Step 3.1: Add listener integration test**

Append to `tests/Feature/Commission/CommissionServiceTest.php` inside the class:
```php
public function test_order_placed_event_triggers_commission_recording(): void
{
    CommissionSetting::create(['teacher_rate' => 70.00]);
    $order = $this->makeOrder();

    event(new \Modules\Payment\Events\OrderPlaced($order));

    $this->assertDatabaseHas('teacher_earnings', ['type' => 'credit']);
}
```

- [ ] **Step 3.2: Run new test — confirm it fails**

```bash
wsl.exe -d Ubuntu -- bash -c "php artisan test --filter=test_order_placed_event_triggers_commission_recording 2>&1" | cat
```
Expected: FAIL — no earnings row created (listener not created yet).

- [ ] **Step 3.3: Create CommissionListener**

File: `Modules/Commission/app/Listeners/CommissionListener.php`
```php
<?php

namespace Modules\Commission\Listeners;

use Modules\Commission\Services\CommissionService;
use Modules\Payment\Events\OrderPlaced;
use Modules\Payment\Events\OrderRefunded;

class CommissionListener
{
    public function __construct(private CommissionService $service) {}

    public function handleOrderPlaced(OrderPlaced $event): void
    {
        $event->order->loadMissing('items.course.teacher');
        $this->service->recordEarnings($event->order);
    }

    public function handleOrderRefunded(OrderRefunded $event): void
    {
        $event->order->loadMissing('items.course.teacher');
        $this->service->reverseEarnings($event->order);
    }
}
```

- [ ] **Step 3.4: Run all Commission tests — confirm they pass**

```bash
wsl.exe -d Ubuntu -- bash -c "php artisan test tests/Feature/Commission/ 2>&1" | cat
```
Expected: All 5 tests PASS ✓.

- [ ] **Step 3.5: Commit**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning && git add e-learning-backend/Modules/Commission/app/Listeners/ e-learning-backend/tests/Feature/Commission/CommissionServiceTest.php && git commit -m 'feat(backend): add CommissionListener wired to OrderPlaced event'" | cat
```

---

### Task 4: OrderRefunded event + fire from AdminOrderController

**Files:**
- Create: `Modules/Payment/app/Events/OrderRefunded.php`
- Modify: `Modules/Payment/app/Http/Controllers/AdminOrderController.php`
- Create: `tests/Feature/Commission/OrderRefundedTest.php`

- [ ] **Step 4.1: Write failing test**

File: `tests/Feature/Commission/OrderRefundedTest.php`
```php
<?php

namespace Tests\Feature\Commission;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Commission\Models\CommissionSetting;
use Modules\Commission\Models\TeacherEarning;
use Modules\Course\Models\Course;
use Modules\Payment\Models\Order;
use Modules\Payment\Models\OrderItem;
use Modules\Teachers\Models\Teachers;
use Tests\TestCase;
use Tests\Traits\HasAdminUser;

class OrderRefundedTest extends TestCase
{
    use RefreshDatabase, HasAdminUser;

    public function test_updating_order_to_refunded_creates_debit_earnings(): void
    {
        CommissionSetting::create(['teacher_rate' => 70.00]);
        $this->setupAdmin();

        $teacher = Teachers::create(['name' => 'T', 'slug' => 'teacher-t', 'exp' => 1, 'status' => 1]);
        $course = Course::create(['teacher_id' => $teacher->id, 'name' => 'C', 'slug' => 'course-c', 'price' => 200000, 'level' => 'beginner', 'status' => 1]);
        $order = Order::create(['order_code' => 'ORD001', 'student_id' => 1, 'subtotal' => 200000, 'discount_amount' => 0, 'total_amount' => 200000, 'status' => 'paid', 'payment_method' => 'vnpay']);
        $item = OrderItem::create(['order_id' => $order->id, 'course_id' => $course->id, 'price' => 200000, 'final_price' => 200000]);

        // Simulate existing credit from original sale
        TeacherEarning::create(['teacher_id' => $teacher->id, 'order_item_id' => $item->id, 'type' => 'credit', 'amount' => 140000, 'commission_rate' => 70.00]);

        $this->patchJson("/api/v1/admin/orders/{$order->id}/status", ['status' => 'refunded']);

        $this->assertDatabaseHas('teacher_earnings', ['type' => 'debit', 'amount' => '140000.00']);
    }
}
```

- [ ] **Step 4.2: Run test — confirm it fails**

```bash
wsl.exe -d Ubuntu -- bash -c "php artisan test tests/Feature/Commission/OrderRefundedTest.php 2>&1" | cat
```
Expected: FAIL — no debit earning created.

- [ ] **Step 4.3: Create OrderRefunded event**

File: `Modules/Payment/app/Events/OrderRefunded.php`
```php
<?php

namespace Modules\Payment\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Payment\Models\Order;

class OrderRefunded
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Order $order,
    ) {}
}
```

- [ ] **Step 4.4: Fire OrderRefunded in AdminOrderController::updateStatus**

In `Modules/Payment/app/Http/Controllers/AdminOrderController.php`:

Add import at top with other `use` statements:
```php
use Modules\Payment\Events\OrderRefunded;
```

In the `updateStatus()` method, add after `$updated = $this->repository->updateOrderStatus($id, $updateData);`:
```php
if ($newStatus === 'refunded' && $oldStatus !== 'refunded') {
    $updated->load('items.course.teacher');
    event(new OrderRefunded($updated));
}
```

- [ ] **Step 4.5: Run test — confirm it passes**

```bash
wsl.exe -d Ubuntu -- bash -c "php artisan test tests/Feature/Commission/OrderRefundedTest.php 2>&1" | cat
```
Expected: PASS ✓.

- [ ] **Step 4.6: Run full test suite — confirm no regressions**

```bash
wsl.exe -d Ubuntu -- bash -c "php artisan test 2>&1" | cat
```
Expected: All tests pass.

- [ ] **Step 4.7: Commit**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning && git add e-learning-backend/Modules/Payment/app/Events/OrderRefunded.php e-learning-backend/Modules/Payment/app/Http/Controllers/AdminOrderController.php e-learning-backend/tests/Feature/Commission/OrderRefundedTest.php && git commit -m 'feat(payment): add OrderRefunded event and fire on order refund'" | cat
```

---

### Task 5: Admin API — CommissionSettings endpoint

**Files:**
- Create: `Modules/Commission/app/Http/Controllers/Admin/CommissionSettingsController.php`
- Modify: `Modules/Commission/routes/api.php`
- Create: `tests/Feature/Commission/CommissionSettingsTest.php`

- [ ] **Step 5.1: Write failing test**

File: `tests/Feature/Commission/CommissionSettingsTest.php`
```php
<?php

namespace Tests\Feature\Commission;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Commission\Models\CommissionSetting;
use Tests\TestCase;
use Tests\Traits\HasAdminUser;

class CommissionSettingsTest extends TestCase
{
    use RefreshDatabase, HasAdminUser;

    public function test_get_returns_current_rate(): void
    {
        $this->setupAdmin();
        CommissionSetting::create(['teacher_rate' => 70.00]);

        $response = $this->getJson('/api/v1/admin/commission-settings');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.teacher_rate', '70.00');
    }

    public function test_patch_updates_rate(): void
    {
        $this->setupAdmin();
        CommissionSetting::create(['teacher_rate' => 70.00]);

        $response = $this->patchJson('/api/v1/admin/commission-settings', ['teacher_rate' => 75.00]);

        $response->assertStatus(200)->assertJsonPath('success', true);
        $this->assertDatabaseHas('commission_settings', ['teacher_rate' => 75.00]);
    }

    public function test_patch_rejects_rate_above_100(): void
    {
        $this->setupAdmin();

        $response = $this->patchJson('/api/v1/admin/commission-settings', ['teacher_rate' => 110]);

        $response->assertStatus(422);
    }
}
```

- [ ] **Step 5.2: Run test — confirm it fails**

```bash
wsl.exe -d Ubuntu -- bash -c "php artisan test tests/Feature/Commission/CommissionSettingsTest.php 2>&1" | cat
```
Expected: FAIL — 404 (route not registered).

- [ ] **Step 5.3: Create CommissionSettingsController**

File: `Modules/Commission/app/Http/Controllers/Admin/CommissionSettingsController.php`
```php
<?php

namespace Modules\Commission\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Commission\Models\CommissionSetting;

class CommissionSettingsController extends Controller
{
    use ApiResponse;

    public function show(): JsonResponse
    {
        return $this->success(CommissionSetting::current());
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'teacher_rate' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $settings = CommissionSetting::current();
        $settings->update($validated);

        return $this->success($settings, 'Cài đặt hoa hồng đã được cập nhật.');
    }
}
```

- [ ] **Step 5.4: Update routes/api.php with all commission routes**

File: `Modules/Commission/routes/api.php`
```php
<?php

use Illuminate\Support\Facades\Route;
use Modules\Commission\Http\Controllers\Admin\CommissionSettingsController;
use Modules\Commission\Http\Controllers\Admin\PayoutController;
use Modules\Commission\Http\Controllers\Admin\TeacherEarningsController;
use Modules\Commission\Http\Controllers\Teacher\EarningsController;

Route::middleware(['auth:admin'])->prefix('admin')->group(function () {
    Route::get('commission-settings', [CommissionSettingsController::class, 'show']);
    Route::patch('commission-settings', [CommissionSettingsController::class, 'update']);

    // Static routes before parameterized
    Route::get('payouts', [PayoutController::class, 'index']);
    Route::patch('payouts/{id}/approve', [PayoutController::class, 'approve']);
    Route::patch('payouts/{id}/reject', [PayoutController::class, 'reject']);
    Route::patch('payouts/{id}/mark-paid', [PayoutController::class, 'markPaid']);

    Route::get('teacher-earnings', [TeacherEarningsController::class, 'index']);
});

Route::middleware(['auth:admin', 'role:teacher'])->prefix('teacher')->group(function () {
    Route::get('earnings', [EarningsController::class, 'index']);
    Route::get('payouts', [EarningsController::class, 'myPayouts']);
    Route::post('payouts', [EarningsController::class, 'requestPayout']);
});
```

- [ ] **Step 5.5: Run test — confirm it passes**

```bash
wsl.exe -d Ubuntu -- bash -c "php artisan test tests/Feature/Commission/CommissionSettingsTest.php 2>&1" | cat
```
Expected: 3 tests PASS ✓.

- [ ] **Step 5.6: Commit**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning && git add e-learning-backend/Modules/Commission/ e-learning-backend/tests/Feature/Commission/CommissionSettingsTest.php && git commit -m 'feat(backend): add admin commission settings API'" | cat
```

---

### Task 6: Admin API — Payouts management

**Files:**
- Create: `Modules/Commission/app/Http/Resources/TeacherPayoutResource.php`
- Create: `Modules/Commission/app/Http/Controllers/Admin/PayoutController.php`
- Create: `tests/Feature/Commission/AdminPayoutTest.php`

- [ ] **Step 6.1: Write failing test**

File: `tests/Feature/Commission/AdminPayoutTest.php`
```php
<?php

namespace Tests\Feature\Commission;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Commission\Models\TeacherPayout;
use Modules\Teachers\Models\Teachers;
use Tests\TestCase;
use Tests\Traits\HasAdminUser;

class AdminPayoutTest extends TestCase
{
    use RefreshDatabase, HasAdminUser;

    private function makeTeacher(): Teachers
    {
        return Teachers::create(['name' => 'T', 'slug' => 'teacher-t', 'exp' => 1, 'status' => 1]);
    }

    public function test_admin_can_list_payouts(): void
    {
        $this->setupAdmin();
        $teacher = $this->makeTeacher();
        TeacherPayout::create(['teacher_id' => $teacher->id, 'amount' => 100000, 'status' => 'pending']);

        $this->getJson('/api/v1/admin/payouts')->assertStatus(200)->assertJsonPath('success', true);
    }

    public function test_admin_can_approve_payout(): void
    {
        $this->setupAdmin();
        $payout = TeacherPayout::create(['teacher_id' => $this->makeTeacher()->id, 'amount' => 100000, 'status' => 'pending']);

        $this->patchJson("/api/v1/admin/payouts/{$payout->id}/approve", ['admin_note' => 'OK'])
            ->assertStatus(200)->assertJsonPath('success', true);

        $this->assertDatabaseHas('teacher_payouts', ['id' => $payout->id, 'status' => 'approved']);
    }

    public function test_admin_can_reject_payout(): void
    {
        $this->setupAdmin();
        $payout = TeacherPayout::create(['teacher_id' => $this->makeTeacher()->id, 'amount' => 100000, 'status' => 'pending']);

        $this->patchJson("/api/v1/admin/payouts/{$payout->id}/reject", ['admin_note' => 'Thiếu TK'])
            ->assertStatus(200);

        $this->assertDatabaseHas('teacher_payouts', ['id' => $payout->id, 'status' => 'rejected']);
    }

    public function test_admin_can_mark_payout_as_paid(): void
    {
        $this->setupAdmin();
        $payout = TeacherPayout::create(['teacher_id' => $this->makeTeacher()->id, 'amount' => 100000, 'status' => 'approved']);

        $this->patchJson("/api/v1/admin/payouts/{$payout->id}/mark-paid")->assertStatus(200);

        $this->assertDatabaseHas('teacher_payouts', ['id' => $payout->id, 'status' => 'paid']);
    }

    public function test_cannot_approve_non_pending_payout(): void
    {
        $this->setupAdmin();
        $payout = TeacherPayout::create(['teacher_id' => $this->makeTeacher()->id, 'amount' => 100000, 'status' => 'approved']);

        $this->patchJson("/api/v1/admin/payouts/{$payout->id}/approve")->assertStatus(422);
    }
}
```

- [ ] **Step 6.2: Run test — confirm it fails**

```bash
wsl.exe -d Ubuntu -- bash -c "php artisan test tests/Feature/Commission/AdminPayoutTest.php 2>&1" | cat
```
Expected: FAIL — controllers not found.

- [ ] **Step 6.3: Create TeacherPayoutResource**

File: `Modules/Commission/app/Http/Resources/TeacherPayoutResource.php`
```php
<?php

namespace Modules\Commission\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TeacherPayoutResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                  => $this->id,
            'teacher_id'          => $this->teacher_id,
            'teacher_name'        => $this->whenLoaded('teacher', fn () => $this->teacher->name),
            'bank_name'           => $this->whenLoaded('teacher', fn () => $this->teacher->bank_name),
            'bank_account_number' => $this->whenLoaded('teacher', fn () => $this->teacher->bank_account_number),
            'bank_account_name'   => $this->whenLoaded('teacher', fn () => $this->teacher->bank_account_name),
            'amount'              => $this->amount,
            'status'              => $this->status,
            'teacher_note'        => $this->teacher_note,
            'admin_note'          => $this->admin_note,
            'processed_at'        => $this->processed_at?->toDateTimeString(),
            'created_at'          => $this->created_at->toDateTimeString(),
        ];
    }
}
```

- [ ] **Step 6.4: Create PayoutController**

File: `Modules/Commission/app/Http/Controllers/Admin/PayoutController.php`
```php
<?php

namespace Modules\Commission\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Commission\Http\Resources\TeacherPayoutResource;
use Modules\Commission\Models\TeacherPayout;

class PayoutController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min((int) $request->query('per_page', 15), 100));
        $query = TeacherPayout::with('teacher')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }
        if ($request->filled('teacher_id')) {
            $query->where('teacher_id', $request->query('teacher_id'));
        }

        $data = $query->paginate($perPage);
        $data->setCollection(TeacherPayoutResource::collection($data->getCollection())->collection);

        return $this->paginated($data);
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $payout = TeacherPayout::findOrFail($id);

        if ($payout->status !== 'pending') {
            return $this->error('Chỉ có thể duyệt yêu cầu đang chờ.', 422);
        }

        $payout->update(['status' => 'approved', 'admin_note' => $request->input('admin_note'), 'processed_at' => now()]);

        return $this->success(new TeacherPayoutResource($payout->load('teacher')), 'Yêu cầu đã được duyệt.');
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        $payout = TeacherPayout::findOrFail($id);

        if (! in_array($payout->status, ['pending', 'approved'])) {
            return $this->error('Không thể từ chối yêu cầu này.', 422);
        }

        $payout->update(['status' => 'rejected', 'admin_note' => $request->input('admin_note'), 'processed_at' => now()]);

        return $this->success(new TeacherPayoutResource($payout->load('teacher')), 'Yêu cầu đã bị từ chối.');
    }

    public function markPaid(int $id): JsonResponse
    {
        $payout = TeacherPayout::findOrFail($id);

        if ($payout->status !== 'approved') {
            return $this->error('Chỉ có thể đánh dấu đã thanh toán cho yêu cầu đã duyệt.', 422);
        }

        $payout->update(['status' => 'paid', 'processed_at' => now()]);

        return $this->success(new TeacherPayoutResource($payout->load('teacher')), 'Đã đánh dấu thanh toán.');
    }
}
```

- [ ] **Step 6.5: Run test — confirm it passes**

```bash
wsl.exe -d Ubuntu -- bash -c "php artisan test tests/Feature/Commission/AdminPayoutTest.php 2>&1" | cat
```
Expected: 5 tests PASS ✓.

- [ ] **Step 6.6: Commit**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning && git add e-learning-backend/Modules/Commission/app/Http/ e-learning-backend/tests/Feature/Commission/AdminPayoutTest.php && git commit -m 'feat(backend): add admin payout management API (approve/reject/mark-paid)'" | cat
```

---

### Task 7: Admin API — TeacherEarnings overview + Resources

**Files:**
- Create: `Modules/Commission/app/Http/Resources/TeacherEarningResource.php`
- Create: `Modules/Commission/app/Http/Controllers/Admin/TeacherEarningsController.php`
- Create: `tests/Feature/Commission/TeacherEarningsTest.php`

- [ ] **Step 7.1: Write failing test**

File: `tests/Feature/Commission/TeacherEarningsTest.php`
```php
<?php

namespace Tests\Feature\Commission;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Commission\Models\TeacherEarning;
use Modules\Teachers\Models\Teachers;
use Tests\TestCase;
use Tests\Traits\HasAdminUser;

class TeacherEarningsTest extends TestCase
{
    use RefreshDatabase, HasAdminUser;

    public function test_admin_can_get_teacher_earnings_summary(): void
    {
        $this->setupAdmin();
        $teacher = Teachers::create(['name' => 'T', 'slug' => 'teacher-t', 'exp' => 1, 'status' => 1]);
        TeacherEarning::create(['teacher_id' => $teacher->id, 'type' => 'credit', 'amount' => 500000, 'commission_rate' => 70]);

        $response = $this->getJson('/api/v1/admin/teacher-earnings');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonFragment(['total_earned' => '500000']);
    }
}
```

- [ ] **Step 7.2: Run test — confirm it fails**

```bash
wsl.exe -d Ubuntu -- bash -c "php artisan test tests/Feature/Commission/TeacherEarningsTest.php 2>&1" | cat
```

- [ ] **Step 7.3: Create TeacherEarningResource**

File: `Modules/Commission/app/Http/Resources/TeacherEarningResource.php`
```php
<?php

namespace Modules\Commission\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TeacherEarningResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'              => $this->id,
            'type'            => $this->type,
            'amount'          => $this->amount,
            'commission_rate' => $this->commission_rate,
            'description'     => $this->description,
            'created_at'      => $this->created_at->toDateTimeString(),
        ];
    }
}
```

- [ ] **Step 7.4: Create TeacherEarningsController**

File: `Modules/Commission/app/Http/Controllers/Admin/TeacherEarningsController.php`
```php
<?php

namespace Modules\Commission\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Modules\Commission\Repositories\CommissionRepositoryInterface;

class TeacherEarningsController extends Controller
{
    use ApiResponse;

    public function __construct(private CommissionRepositoryInterface $repository) {}

    public function index(): JsonResponse
    {
        return $this->success($this->repository->getTeachersSummary(), 'Tổng hợp hoa hồng giảng viên.');
    }
}
```

- [ ] **Step 7.5: Run test — confirm it passes**

```bash
wsl.exe -d Ubuntu -- bash -c "php artisan test tests/Feature/Commission/TeacherEarningsTest.php 2>&1" | cat
```

- [ ] **Step 7.6: Commit**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning && git add e-learning-backend/Modules/Commission/app/Http/ e-learning-backend/tests/Feature/Commission/TeacherEarningsTest.php && git commit -m 'feat(backend): add admin teacher earnings summary API'" | cat
```

---

### Task 8: Teacher API — earnings dashboard + payout request

**Files:**
- Create: `Modules/Commission/app/Http/Requests/StorePayoutRequest.php`
- Create: `Modules/Commission/app/Http/Controllers/Teacher/EarningsController.php`
- Create: `tests/Feature/Commission/TeacherPortalTest.php`

- [ ] **Step 8.1: Write failing test**

File: `tests/Feature/Commission/TeacherPortalTest.php`
```php
<?php

namespace Tests\Feature\Commission;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Commission\Models\CommissionSetting;
use Modules\Commission\Models\TeacherEarning;
use Modules\Teachers\Models\Teachers;
use Modules\Users\Models\User;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TeacherPortalTest extends TestCase
{
    use RefreshDatabase;

    private function setupTeacher(): array
    {
        $user = User::forceCreate(['name' => 'Teacher User', 'email' => 'teacher@test.com', 'password' => 'password']);
        $role = Role::firstOrCreate(['name' => 'teacher', 'guard_name' => 'admin']);
        $user->assignRole($role);
        $teacher = Teachers::create(['name' => 'Teacher', 'slug' => 'teacher-t', 'exp' => 1, 'status' => 1, 'user_id' => $user->id]);
        $this->actingAs($user, 'admin');

        return [$user, $teacher];
    }

    public function test_teacher_can_view_own_earnings_with_balance(): void
    {
        [, $teacher] = $this->setupTeacher();
        TeacherEarning::create(['teacher_id' => $teacher->id, 'type' => 'credit', 'amount' => 300000, 'commission_rate' => 70]);

        $response = $this->getJson('/api/v1/teacher/earnings');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.balance.available', 300000.0);
    }

    public function test_teacher_can_request_payout_within_balance(): void
    {
        [, $teacher] = $this->setupTeacher();
        CommissionSetting::create(['teacher_rate' => 70]);
        TeacherEarning::create(['teacher_id' => $teacher->id, 'type' => 'credit', 'amount' => 500000, 'commission_rate' => 70]);

        $response = $this->postJson('/api/v1/teacher/payouts', ['amount' => 300000]);

        $response->assertStatus(201)->assertJsonPath('success', true);
        $this->assertDatabaseHas('teacher_payouts', ['teacher_id' => $teacher->id, 'amount' => 300000, 'status' => 'pending']);
    }

    public function test_teacher_cannot_request_payout_exceeding_balance(): void
    {
        [, $teacher] = $this->setupTeacher();
        TeacherEarning::create(['teacher_id' => $teacher->id, 'type' => 'credit', 'amount' => 100000, 'commission_rate' => 70]);

        $response = $this->postJson('/api/v1/teacher/payouts', ['amount' => 500000]);

        $response->assertStatus(422);
    }
}
```

- [ ] **Step 8.2: Run test — confirm it fails**

```bash
wsl.exe -d Ubuntu -- bash -c "php artisan test tests/Feature/Commission/TeacherPortalTest.php 2>&1" | cat
```

- [ ] **Step 8.3: Create StorePayoutRequest**

File: `Modules/Commission/app/Http/Requests/StorePayoutRequest.php`
```php
<?php

namespace Modules\Commission\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StorePayoutRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'amount'       => ['required', 'numeric', 'min:1000'],
            'teacher_note' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return ['amount.min' => 'Số tiền tối thiểu là 1,000 VNĐ.'];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Dữ liệu không hợp lệ.',
            'errors'  => $validator->errors(),
        ], 422));
    }
}
```

- [ ] **Step 8.4: Create Teacher/EarningsController**

File: `Modules/Commission/app/Http/Controllers/Teacher/EarningsController.php`
```php
<?php

namespace Modules\Commission\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Commission\Http\Requests\StorePayoutRequest;
use Modules\Commission\Http\Resources\TeacherEarningResource;
use Modules\Commission\Http\Resources\TeacherPayoutResource;
use Modules\Commission\Models\TeacherPayout;
use Modules\Commission\Repositories\CommissionRepositoryInterface;
use Modules\Teachers\Models\Teachers;

class EarningsController extends Controller
{
    use ApiResponse;

    public function __construct(private CommissionRepositoryInterface $repository) {}

    public function index(Request $request): JsonResponse
    {
        $user = auth('admin')->user();
        $teacher = Teachers::where('user_id', $user->id)->firstOrFail();
        $perPage = max(1, min((int) $request->query('per_page', 15), 100));

        $earnings = $this->repository->getEarningsForTeacher($teacher->id, $perPage);
        $earnings->setCollection(TeacherEarningResource::collection($earnings->getCollection())->collection);

        return $this->success([
            'balance' => [
                'available'      => $this->repository->getAvailableBalance($teacher->id),
                'total_earned'   => $this->repository->getTotalEarned($teacher->id),
                'total_paid'     => $this->repository->getTotalPaid($teacher->id),
                'pending_payout' => $this->repository->getPendingPayoutAmount($teacher->id),
            ],
            'earnings'   => $earnings->items(),
            'pagination' => [
                'current_page' => $earnings->currentPage(),
                'last_page'    => $earnings->lastPage(),
                'per_page'     => $earnings->perPage(),
                'total'        => $earnings->total(),
            ],
        ]);
    }

    public function myPayouts(Request $request): JsonResponse
    {
        $user = auth('admin')->user();
        $teacher = Teachers::where('user_id', $user->id)->firstOrFail();
        $perPage = max(1, min((int) $request->query('per_page', 15), 100));

        $data = TeacherPayout::where('teacher_id', $teacher->id)->latest()->paginate($perPage);
        $data->setCollection(TeacherPayoutResource::collection($data->getCollection())->collection);

        return $this->paginated($data);
    }

    public function requestPayout(StorePayoutRequest $request): JsonResponse
    {
        $user = auth('admin')->user();
        $teacher = Teachers::where('user_id', $user->id)->firstOrFail();
        $available = $this->repository->getAvailableBalance($teacher->id);

        if ($request->amount > $available) {
            return $this->error(
                'Số dư khả dụng không đủ. Hiện có: ' . number_format($available) . ' VNĐ.',
                422
            );
        }

        $payout = TeacherPayout::create([
            'teacher_id'   => $teacher->id,
            'amount'       => $request->amount,
            'teacher_note' => $request->teacher_note,
            'status'       => 'pending',
        ]);

        return $this->success(new TeacherPayoutResource($payout), 'Yêu cầu rút tiền đã được gửi.', 201);
    }
}
```

- [ ] **Step 8.5: Run test — confirm it passes**

```bash
wsl.exe -d Ubuntu -- bash -c "php artisan test tests/Feature/Commission/TeacherPortalTest.php 2>&1" | cat
```

- [ ] **Step 8.6: Run full test suite**

```bash
wsl.exe -d Ubuntu -- bash -c "php artisan test 2>&1" | cat
```
Expected: All tests pass.

- [ ] **Step 8.7: Commit**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning && git add e-learning-backend/Modules/Commission/ e-learning-backend/tests/Feature/Commission/ && git commit -m 'feat(backend): add teacher earnings portal and payout request API'" | cat
```

---

### Task 9: CommissionSettingSeeder

**Files:**
- Create: `Modules/Commission/database/seeders/CommissionSettingSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php`

- [ ] **Step 9.1: Create CommissionSettingSeeder**

File: `Modules/Commission/database/seeders/CommissionSettingSeeder.php`
```php
<?php

namespace Modules\Commission\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Commission\Models\CommissionSetting;

class CommissionSettingSeeder extends Seeder
{
    public function run(): void
    {
        CommissionSetting::firstOrCreate([], ['teacher_rate' => 70.00]);
    }
}
```

- [ ] **Step 9.2: Add to DatabaseSeeder**

In `database/seeders/DatabaseSeeder.php`, add inside `run()`:
```php
$this->call(\Modules\Commission\Database\Seeders\CommissionSettingSeeder::class);
```

- [ ] **Step 9.3: Verify seed runs clean**

```bash
wsl.exe -d Ubuntu -- bash -c "php artisan migrate:fresh --seed 2>&1" | cat
```
Expected: Completes without errors. `commission_settings` has 1 row with `teacher_rate = 70.00`.

- [ ] **Step 9.4: Commit**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning && git add e-learning-backend/Modules/Commission/database/seeders/ e-learning-backend/database/seeders/DatabaseSeeder.php && git commit -m 'feat(backend): add CommissionSettingSeeder with default 70% rate'" | cat
```

---

### Task 10: Frontend — commission.service.ts + composables

**Files:**
- Create: `e-learning-frontend/src/services/commission.service.ts`
- Create: `e-learning-frontend/src/composables/usePayouts.ts`
- Create: `e-learning-frontend/src/composables/useTeacherEarnings.ts`
- Create: `e-learning-frontend/src/composables/useEarnings.ts`

- [ ] **Step 10.1: Create commission.service.ts**

File: `e-learning-frontend/src/services/commission.service.ts`
```typescript
import http from '@/plugins/axios'

export const commissionService = {
  // Admin
  getSettings: () =>
    http.get('/admin/commission-settings'),
  updateSettings: (data: { teacher_rate: number }) =>
    http.patch('/admin/commission-settings', data),
  getAdminPayouts: (params: Record<string, unknown>) =>
    http.get('/admin/payouts', { params }),
  approvePayout: (id: number, data: { admin_note?: string }) =>
    http.patch(`/admin/payouts/${id}/approve`, data),
  rejectPayout: (id: number, data: { admin_note?: string }) =>
    http.patch(`/admin/payouts/${id}/reject`, data),
  markPaid: (id: number) =>
    http.patch(`/admin/payouts/${id}/mark-paid`),
  getTeacherEarningsSummary: () =>
    http.get('/admin/teacher-earnings'),

  // Teacher portal
  getMyEarnings: (params: Record<string, unknown>) =>
    http.get('/teacher/earnings', { params }),
  getMyPayouts: (params: Record<string, unknown>) =>
    http.get('/teacher/payouts', { params }),
  requestPayout: (data: { amount: number; teacher_note?: string }) =>
    http.post('/teacher/payouts', data),
}
```

- [ ] **Step 10.2: Create usePayouts.ts (admin composable)**

File: `e-learning-frontend/src/composables/usePayouts.ts`
```typescript
import { reactive, ref } from 'vue'
import { useToast } from 'vue-toastification'
import { commissionService } from '@/services/commission.service'

export function usePayouts() {
  const toast = useToast()
  const payouts = ref<any[]>([])
  const loading = ref(false)
  const pagination = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 })
  const filters = reactive({ status: '', teacher_id: '', page: 1, per_page: 15 })

  async function loadPayouts() {
    if (loading.value) return
    loading.value = true
    try {
      const res = await commissionService.getAdminPayouts(filters)
      payouts.value = res.data.data
      pagination.value = res.data.pagination
    } finally {
      loading.value = false
    }
  }

  async function approvePayout(id: number, adminNote = '') {
    await commissionService.approvePayout(id, { admin_note: adminNote })
    toast.success('Đã duyệt yêu cầu rút tiền.')
    await loadPayouts()
  }

  async function rejectPayout(id: number, adminNote = '') {
    await commissionService.rejectPayout(id, { admin_note: adminNote })
    toast.success('Đã từ chối yêu cầu.')
    await loadPayouts()
  }

  async function markPaid(id: number) {
    await commissionService.markPaid(id)
    toast.success('Đã đánh dấu đã thanh toán.')
    await loadPayouts()
  }

  return { payouts, loading, pagination, filters, loadPayouts, approvePayout, rejectPayout, markPaid }
}
```

- [ ] **Step 10.3: Create useTeacherEarnings.ts (admin overview composable)**

File: `e-learning-frontend/src/composables/useTeacherEarnings.ts`
```typescript
import { ref } from 'vue'
import { commissionService } from '@/services/commission.service'

export function useTeacherEarnings() {
  const summary = ref<any[]>([])
  const loading = ref(false)

  async function loadSummary() {
    if (loading.value) return
    loading.value = true
    try {
      const res = await commissionService.getTeacherEarningsSummary()
      summary.value = res.data.data
    } finally {
      loading.value = false
    }
  }

  return { summary, loading, loadSummary }
}
```

- [ ] **Step 10.4: Create useEarnings.ts (teacher portal composable)**

File: `e-learning-frontend/src/composables/useEarnings.ts`
```typescript
import { ref } from 'vue'
import { useToast } from 'vue-toastification'
import { commissionService } from '@/services/commission.service'

export function useEarnings() {
  const toast = useToast()
  const balance = ref({ available: 0, total_earned: 0, total_paid: 0, pending_payout: 0 })
  const earnings = ref<any[]>([])
  const payouts = ref<any[]>([])
  const loading = ref(false)
  const payoutLoading = ref(false)
  const pagination = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 })

  async function loadEarnings(params = {}) {
    if (loading.value) return
    loading.value = true
    try {
      const res = await commissionService.getMyEarnings(params)
      balance.value = res.data.data.balance
      earnings.value = res.data.data.earnings
      pagination.value = res.data.data.pagination
    } finally {
      loading.value = false
    }
  }

  async function loadMyPayouts(params = {}) {
    const res = await commissionService.getMyPayouts(params)
    payouts.value = res.data.data
  }

  async function requestPayout(amount: number, teacherNote = ''): Promise<boolean> {
    if (payoutLoading.value) return false
    payoutLoading.value = true
    try {
      await commissionService.requestPayout({ amount, teacher_note: teacherNote })
      toast.success('Yêu cầu rút tiền đã được gửi thành công.')
      await loadEarnings()
      return true
    } catch (err: any) {
      toast.error(err.response?.data?.message || 'Đã có lỗi xảy ra.')
      return false
    } finally {
      payoutLoading.value = false
    }
  }

  return { balance, earnings, payouts, loading, payoutLoading, pagination, loadEarnings, loadMyPayouts, requestPayout }
}
```

- [ ] **Step 10.5: Commit**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning && git add e-learning-frontend/src/services/commission.service.ts e-learning-frontend/src/composables/usePayouts.ts e-learning-frontend/src/composables/useTeacherEarnings.ts e-learning-frontend/src/composables/useEarnings.ts && git commit -m 'feat(frontend): add commission service and composables'" | cat
```

---

### Task 11: Frontend Admin — PayoutsPage.vue

**Files:**
- Create: `e-learning-frontend/src/views/admin/PayoutsPage.vue`

- [ ] **Step 11.1: Create PayoutsPage.vue**

File: `e-learning-frontend/src/views/admin/PayoutsPage.vue`
```vue
<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { usePayouts } from '@/composables/usePayouts'

const { payouts, loading, filters, loadPayouts, approvePayout, rejectPayout, markPaid } = usePayouts()

const confirmModal = ref({ show: false, id: 0, action: '', note: '' })

function openModal(id: number, action: string) {
  confirmModal.value = { show: true, id, action, note: '' }
}

async function confirmAction() {
  const { id, action, note } = confirmModal.value
  if (action === 'approve') await approvePayout(id, note)
  else if (action === 'reject') await rejectPayout(id, note)
  else if (action === 'mark-paid') await markPaid(id)
  confirmModal.value.show = false
}

const statusLabel: Record<string, string> = {
  pending: 'Chờ duyệt', approved: 'Đã duyệt', rejected: 'Từ chối', paid: 'Đã thanh toán',
}
const statusClass: Record<string, string> = {
  pending: 'bg-yellow-100 text-yellow-800',
  approved: 'bg-blue-100 text-blue-800',
  rejected: 'bg-red-100 text-red-800',
  paid: 'bg-green-100 text-green-800',
}

onMounted(loadPayouts)
</script>

<template>
  <div class="p-6">
    <h1 class="text-2xl font-bold mb-6">Quản lý rút tiền</h1>

    <div class="flex gap-4 mb-4">
      <select v-model="filters.status" @change="loadPayouts" class="border rounded px-3 py-2 text-sm">
        <option value="">Tất cả</option>
        <option value="pending">Chờ duyệt</option>
        <option value="approved">Đã duyệt</option>
        <option value="rejected">Từ chối</option>
        <option value="paid">Đã thanh toán</option>
      </select>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-600">
          <tr>
            <th class="px-4 py-3 text-left">Giảng viên</th>
            <th class="px-4 py-3 text-right">Số tiền</th>
            <th class="px-4 py-3 text-left">Ngân hàng</th>
            <th class="px-4 py-3 text-left">Trạng thái</th>
            <th class="px-4 py-3 text-left">Ngày yêu cầu</th>
            <th class="px-4 py-3 text-left">Thao tác</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading">
            <td colspan="6" class="px-4 py-8 text-center text-gray-400">Đang tải...</td>
          </tr>
          <tr v-else-if="!payouts.length">
            <td colspan="6" class="px-4 py-8 text-center text-gray-400">Chưa có yêu cầu nào.</td>
          </tr>
          <tr v-for="p in payouts" :key="p.id" class="border-t hover:bg-gray-50">
            <td class="px-4 py-3 font-medium">{{ p.teacher_name }}</td>
            <td class="px-4 py-3 text-right font-semibold text-green-700">
              {{ Number(p.amount).toLocaleString('vi-VN') }} ₫
            </td>
            <td class="px-4 py-3 text-xs text-gray-500">
              <span v-if="p.bank_name">{{ p.bank_name }} – {{ p.bank_account_number }}</span>
              <span v-else class="text-red-400">Chưa có TK ngân hàng</span>
            </td>
            <td class="px-4 py-3">
              <span :class="['px-2 py-1 rounded-full text-xs font-medium', statusClass[p.status]]">
                {{ statusLabel[p.status] }}
              </span>
            </td>
            <td class="px-4 py-3 text-gray-500 text-xs">
              {{ new Date(p.created_at).toLocaleDateString('vi-VN') }}
            </td>
            <td class="px-4 py-3 flex gap-2">
              <button v-if="p.status === 'pending'" @click="openModal(p.id, 'approve')"
                class="px-3 py-1 bg-green-100 text-green-700 rounded text-xs hover:bg-green-200">Duyệt</button>
              <button v-if="p.status === 'pending'" @click="openModal(p.id, 'reject')"
                class="px-3 py-1 bg-red-100 text-red-700 rounded text-xs hover:bg-red-200">Từ chối</button>
              <button v-if="p.status === 'approved'" @click="openModal(p.id, 'mark-paid')"
                class="px-3 py-1 bg-blue-100 text-blue-700 rounded text-xs hover:bg-blue-200">Đã TT</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="confirmModal.show" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 w-96">
        <h3 class="font-semibold mb-3">
          {{ confirmModal.action === 'approve' ? 'Duyệt yêu cầu' : confirmModal.action === 'reject' ? 'Từ chối yêu cầu' : 'Xác nhận đã thanh toán' }}
        </h3>
        <textarea v-if="confirmModal.action !== 'mark-paid'" v-model="confirmModal.note"
          class="w-full border rounded px-3 py-2 text-sm mb-4" rows="3" placeholder="Ghi chú (tùy chọn)" />
        <div class="flex justify-end gap-2">
          <button @click="confirmModal.show = false" class="px-4 py-2 border rounded text-sm">Hủy</button>
          <button @click="confirmAction" class="px-4 py-2 bg-blue-600 text-white rounded text-sm">Xác nhận</button>
        </div>
      </div>
    </div>
  </div>
</template>
```

- [ ] **Step 11.2: Commit**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning && git add e-learning-frontend/src/views/admin/PayoutsPage.vue && git commit -m 'feat(frontend): add admin PayoutsPage'" | cat
```

---

### Task 12: Frontend Admin — TeacherEarningsPage.vue + CommissionSettingsPage.vue

**Files:**
- Create: `e-learning-frontend/src/views/admin/TeacherEarningsPage.vue`
- Create: `e-learning-frontend/src/views/admin/CommissionSettingsPage.vue`

- [ ] **Step 12.1: Create TeacherEarningsPage.vue**

File: `e-learning-frontend/src/views/admin/TeacherEarningsPage.vue`
```vue
<script setup lang="ts">
import { onMounted } from 'vue'
import { useTeacherEarnings } from '@/composables/useTeacherEarnings'

const { summary, loading, loadSummary } = useTeacherEarnings()

onMounted(loadSummary)
</script>

<template>
  <div class="p-6">
    <h1 class="text-2xl font-bold mb-6">Hoa hồng giảng viên</h1>
    <div class="bg-white rounded-lg shadow overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-600">
          <tr>
            <th class="px-4 py-3 text-left">Giảng viên</th>
            <th class="px-4 py-3 text-right">Tổng đã kiếm</th>
            <th class="px-4 py-3 text-right">Đã thanh toán</th>
            <th class="px-4 py-3 text-right">Đang chờ duyệt</th>
            <th class="px-4 py-3 text-right">Số dư khả dụng</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading">
            <td colspan="5" class="px-4 py-8 text-center text-gray-400">Đang tải...</td>
          </tr>
          <tr v-else-if="!summary.length">
            <td colspan="5" class="px-4 py-8 text-center text-gray-400">Chưa có dữ liệu.</td>
          </tr>
          <tr v-for="row in summary" :key="row.id" class="border-t hover:bg-gray-50">
            <td class="px-4 py-3 font-medium">{{ row.name }}</td>
            <td class="px-4 py-3 text-right">{{ Number(row.total_earned).toLocaleString('vi-VN') }} ₫</td>
            <td class="px-4 py-3 text-right text-gray-500">{{ Number(row.total_paid ?? 0).toLocaleString('vi-VN') }} ₫</td>
            <td class="px-4 py-3 text-right text-yellow-700">{{ Number(row.pending_payout).toLocaleString('vi-VN') }} ₫</td>
            <td class="px-4 py-3 text-right font-semibold text-green-700">{{ Number(row.available_balance).toLocaleString('vi-VN') }} ₫</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
```

- [ ] **Step 12.2: Create CommissionSettingsPage.vue**

File: `e-learning-frontend/src/views/admin/CommissionSettingsPage.vue`
```vue
<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useToast } from 'vue-toastification'
import { commissionService } from '@/services/commission.service'

const toast = useToast()
const teacherRate = ref<number>(70)
const loading = ref(false)
const saving = ref(false)
const platformRate = computed(() => (100 - teacherRate.value).toFixed(2))

async function load() {
  loading.value = true
  try {
    const res = await commissionService.getSettings()
    teacherRate.value = Number(res.data.data.teacher_rate)
  } finally {
    loading.value = false
  }
}

async function save() {
  if (saving.value) return
  saving.value = true
  try {
    await commissionService.updateSettings({ teacher_rate: teacherRate.value })
    toast.success('Cài đặt đã được lưu.')
  } catch {
    toast.error('Lưu thất bại.')
  } finally {
    saving.value = false
  }
}

onMounted(load)
</script>

<template>
  <div class="p-6 max-w-md">
    <h1 class="text-2xl font-bold mb-6">Cài đặt hoa hồng</h1>
    <div class="bg-white rounded-lg shadow p-6">
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-1">Tỷ lệ giảng viên (%)</label>
        <div class="flex items-center gap-3">
          <input v-model.number="teacherRate" type="number" min="0" max="100" step="0.5"
            class="border rounded px-3 py-2 w-32 text-sm" />
          <span class="text-sm text-gray-500">→ Nền tảng nhận: <strong>{{ platformRate }}%</strong></span>
        </div>
      </div>
      <button @click="save" :disabled="saving"
        class="px-4 py-2 bg-blue-600 text-white rounded text-sm disabled:opacity-50">
        {{ saving ? 'Đang lưu...' : 'Lưu cài đặt' }}
      </button>
    </div>
  </div>
</template>
```

- [ ] **Step 12.3: Commit**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning && git add e-learning-frontend/src/views/admin/TeacherEarningsPage.vue e-learning-frontend/src/views/admin/CommissionSettingsPage.vue && git commit -m 'feat(frontend): add admin TeacherEarningsPage and CommissionSettingsPage'" | cat
```

---

### Task 13: Frontend Teacher — EarningsPage.vue

**Files:**
- Create: `e-learning-frontend/src/views/teacher/EarningsPage.vue`

- [ ] **Step 13.1: Create directory and EarningsPage.vue**

```bash
wsl.exe -d Ubuntu -- bash -c "mkdir -p /home/vanthanh/DATN/e-learning/e-learning-frontend/src/views/teacher" | cat
```

File: `e-learning-frontend/src/views/teacher/EarningsPage.vue`
```vue
<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useEarnings } from '@/composables/useEarnings'

const { balance, earnings, payouts, loading, payoutLoading, loadEarnings, loadMyPayouts, requestPayout } = useEarnings()

const showPayoutModal = ref(false)
const payoutAmount = ref<number>(0)
const payoutNote = ref('')

async function submitPayout() {
  const ok = await requestPayout(payoutAmount.value, payoutNote.value)
  if (ok) {
    showPayoutModal.value = false
    payoutAmount.value = 0
    payoutNote.value = ''
    await loadMyPayouts()
  }
}

const statusLabel: Record<string, string> = {
  pending: 'Chờ duyệt', approved: 'Đã duyệt', rejected: 'Từ chối', paid: 'Đã thanh toán',
}
const statusClass: Record<string, string> = {
  pending: 'bg-yellow-100 text-yellow-800', approved: 'bg-blue-100 text-blue-800',
  rejected: 'bg-red-100 text-red-800', paid: 'bg-green-100 text-green-800',
}

onMounted(async () => {
  await loadEarnings()
  await loadMyPayouts()
})
</script>

<template>
  <div class="p-6">
    <h1 class="text-2xl font-bold mb-6">Thu nhập của tôi</h1>

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
      <div class="bg-green-50 border border-green-200 rounded-lg p-4">
        <div class="text-xs text-gray-500 mb-1">Số dư khả dụng</div>
        <div class="text-2xl font-bold text-green-700">{{ Number(balance.available).toLocaleString('vi-VN') }} ₫</div>
      </div>
      <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="text-xs text-gray-500 mb-1">Tổng đã kiếm</div>
        <div class="text-2xl font-bold text-blue-700">{{ Number(balance.total_earned).toLocaleString('vi-VN') }} ₫</div>
      </div>
      <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
        <div class="text-xs text-gray-500 mb-1">Đang chờ duyệt</div>
        <div class="text-2xl font-bold text-yellow-700">{{ Number(balance.pending_payout).toLocaleString('vi-VN') }} ₫</div>
      </div>
    </div>

    <button @click="showPayoutModal = true"
      class="mb-6 px-4 py-2 bg-blue-600 text-white rounded text-sm hover:bg-blue-700">
      + Yêu cầu rút tiền
    </button>

    <!-- Earnings History -->
    <h2 class="text-lg font-semibold mb-3">Lịch sử hoa hồng</h2>
    <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-600">
          <tr>
            <th class="px-4 py-3 text-left">Mô tả</th>
            <th class="px-4 py-3 text-left">Loại</th>
            <th class="px-4 py-3 text-right">Số tiền</th>
            <th class="px-4 py-3 text-left">Ngày</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading">
            <td colspan="4" class="px-4 py-8 text-center text-gray-400">Đang tải...</td>
          </tr>
          <tr v-else-if="!earnings.length">
            <td colspan="4" class="px-4 py-8 text-center text-gray-400">Chưa có giao dịch nào.</td>
          </tr>
          <tr v-for="e in earnings" :key="e.id" class="border-t hover:bg-gray-50">
            <td class="px-4 py-3">{{ e.description }}</td>
            <td class="px-4 py-3">
              <span :class="['px-2 py-0.5 rounded-full text-xs font-medium', e.type === 'credit' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700']">
                {{ e.type === 'credit' ? 'Thu' : 'Trừ (hoàn tiền)' }}
              </span>
            </td>
            <td class="px-4 py-3 text-right font-medium" :class="e.type === 'credit' ? 'text-green-700' : 'text-red-700'">
              {{ e.type === 'credit' ? '+' : '−' }}{{ Number(e.amount).toLocaleString('vi-VN') }} ₫
            </td>
            <td class="px-4 py-3 text-gray-500 text-xs">{{ new Date(e.created_at).toLocaleDateString('vi-VN') }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Payout History -->
    <h2 class="text-lg font-semibold mb-3">Lịch sử rút tiền</h2>
    <div class="bg-white rounded-lg shadow overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-600">
          <tr>
            <th class="px-4 py-3 text-right">Số tiền</th>
            <th class="px-4 py-3 text-left">Trạng thái</th>
            <th class="px-4 py-3 text-left">Ghi chú Admin</th>
            <th class="px-4 py-3 text-left">Ngày yêu cầu</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="!payouts.length">
            <td colspan="4" class="px-4 py-8 text-center text-gray-400">Chưa có yêu cầu rút nào.</td>
          </tr>
          <tr v-for="p in payouts" :key="p.id" class="border-t hover:bg-gray-50">
            <td class="px-4 py-3 text-right font-semibold">{{ Number(p.amount).toLocaleString('vi-VN') }} ₫</td>
            <td class="px-4 py-3">
              <span :class="['px-2 py-0.5 rounded-full text-xs font-medium', statusClass[p.status]]">{{ statusLabel[p.status] }}</span>
            </td>
            <td class="px-4 py-3 text-gray-500 text-xs">{{ p.admin_note || '—' }}</td>
            <td class="px-4 py-3 text-gray-500 text-xs">{{ new Date(p.created_at).toLocaleDateString('vi-VN') }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Payout Request Modal -->
    <div v-if="showPayoutModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 w-96">
        <h3 class="font-semibold mb-4">Yêu cầu rút tiền</h3>
        <p class="text-sm text-gray-500 mb-4">
          Số dư khả dụng: <strong class="text-green-700">{{ Number(balance.available).toLocaleString('vi-VN') }} ₫</strong>
        </p>
        <div class="mb-3">
          <label class="block text-sm font-medium mb-1">Số tiền (VNĐ)</label>
          <input v-model.number="payoutAmount" type="number" min="1000" :max="balance.available"
            class="w-full border rounded px-3 py-2 text-sm" />
        </div>
        <div class="mb-4">
          <label class="block text-sm font-medium mb-1">Ghi chú (tùy chọn)</label>
          <textarea v-model="payoutNote" rows="2" class="w-full border rounded px-3 py-2 text-sm" />
        </div>
        <div class="flex justify-end gap-2">
          <button @click="showPayoutModal = false" class="px-4 py-2 border rounded text-sm">Hủy</button>
          <button @click="submitPayout" :disabled="payoutLoading || payoutAmount <= 0"
            class="px-4 py-2 bg-blue-600 text-white rounded text-sm disabled:opacity-50">
            {{ payoutLoading ? 'Đang gửi...' : 'Gửi yêu cầu' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
```

- [ ] **Step 13.2: Commit**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning && git add e-learning-frontend/src/views/teacher/ && git commit -m 'feat(frontend): add teacher EarningsPage with payout request modal'" | cat
```

---

### Task 14: Frontend router updates

**Files:**
- Modify: `e-learning-frontend/src/router/index.js`

- [ ] **Step 14.1: Add commission routes to router**

In `e-learning-frontend/src/router/index.js`, find the admin routes children array and add:
```js
// Commission — admin
{
  path: 'payouts',
  name: 'admin.payouts',
  component: () => import('@/views/admin/PayoutsPage.vue'),
  meta: { requiresAuth: true, guard: 'admin' },
},
{
  path: 'teacher-earnings',
  name: 'admin.teacher-earnings',
  component: () => import('@/views/admin/TeacherEarningsPage.vue'),
  meta: { requiresAuth: true, guard: 'admin' },
},
{
  path: 'commission-settings',
  name: 'admin.commission-settings',
  component: () => import('@/views/admin/CommissionSettingsPage.vue'),
  meta: { requiresAuth: true, guard: 'admin' },
},
// Teacher portal
{
  path: 'teacher/earnings',
  name: 'teacher.earnings',
  component: () => import('@/views/teacher/EarningsPage.vue'),
  meta: { requiresAuth: true, guard: 'admin' },
},
```

- [ ] **Step 14.2: Run lint to verify no errors**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning/e-learning-frontend && npm run lint 2>&1" | cat
```
Expected: No errors.

- [ ] **Step 14.3: Commit**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning && git add e-learning-frontend/src/router/index.js && git commit -m 'feat(frontend): add commission and teacher portal routes'" | cat
```
