# Dashboard Module Fix Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Fix 5 issues in the Dashboard module: extract queries into DashboardRepository, use ApiResponse trait, remove numbered comments, clear stale web routes, add permission middleware.

**Architecture:** Create a plain `DashboardRepository` class (no interface, no BaseRepository — it queries multiple unrelated models) injected via Laravel IoC. Controller becomes a thin orchestrator delegating all DB work to the repository. Routes get `permission:dashboard.view` middleware.

**Tech Stack:** Laravel 12, Nwidart Modules, Eloquent, ApiResponse trait, Spatie Permission

---

## Files

| Action | Path | Responsibility |
|--------|------|----------------|
| Create | `Modules/Dashboard/app/Repositories/DashboardRepository.php` | All 7 DB queries (summary counts, monthly revenue, top courses, recent orders) |
| Modify | `Modules/Dashboard/app/Http/Controllers/DashboardController.php` | Inject DashboardRepository, call `$this->success()`, remove comments |
| Modify | `Modules/Dashboard/routes/api.php` | Add `permission:dashboard.view` |
| Modify | `Modules/Dashboard/routes/web.php` | Remove stale resource route |
| Modify | `tests/Feature/Admin/DashboardTest.php` | Add `message` to assertJsonStructure, add `assertJsonPath('success', true)` |

---

### Task 1: Create DashboardRepository

**Files:**
- Create: `e-learning-backend/Modules/Dashboard/app/Repositories/DashboardRepository.php`

- [ ] **Step 1: Create the repository file**

```php
<?php

namespace Modules\Dashboard\Repositories;

use Illuminate\Support\Facades\DB;
use Modules\Course\Models\Course;
use Modules\Payment\Models\Order;
use Modules\Payment\Models\OrderItem;
use Modules\Students\Models\Student;

class DashboardRepository
{
    public function getSummary(): array
    {
        return [
            'total_students' => Student::count(),
            'total_courses'  => Course::where('status', 1)->count(),
            'total_orders'   => Order::where('status', 'paid')->count(),
            'total_revenue'  => (float) Order::where('status', 'paid')->sum('total_amount'),
        ];
    }

    public function getMonthlyRevenue(int $year): array
    {
        $monthExpression = DB::getDriverName() === 'sqlite'
            ? "CAST(strftime('%m', created_at) AS INTEGER)"
            : 'MONTH(created_at)';

        $rows = Order::select(
            DB::raw("$monthExpression as month"),
            DB::raw('SUM(total_amount) as revenue')
        )
            ->where('status', 'paid')
            ->whereYear('created_at', $year)
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $monthsMap = $rows->pluck('revenue', 'month')->toArray();

        $result = [];
        for ($i = 1; $i <= 12; $i++) {
            $result[] = [
                'month'   => $i,
                'revenue' => isset($monthsMap[$i]) ? (float) $monthsMap[$i] : 0,
            ];
        }

        return $result;
    }

    public function getTopCourses(int $limit = 5): array
    {
        return OrderItem::select(
            'course_id',
            DB::raw('SUM(final_price) as total_revenue'),
            DB::raw('COUNT(*) as sales_count')
        )
            ->whereHas('order', fn ($q) => $q->where('status', 'paid'))
            ->with(['course:id,name,thumbnail,price'])
            ->groupBy('course_id')
            ->orderByDesc('total_revenue')
            ->take($limit)
            ->get()
            ->map(fn ($item) => [
                'id'          => $item->course_id,
                'title'       => $item->course->name ?? 'Unknown',
                'thumbnail'   => $item->course->thumbnail ?? null,
                'price'       => $item->course->price ?? 0,
                'sales_count' => $item->sales_count,
                'revenue'     => (float) $item->total_revenue,
            ])
            ->values()
            ->all();
    }

    public function getRecentOrders(int $limit = 5): array
    {
        return Order::with(['student:id,name,email', 'items.course:id,name'])
            ->orderByDesc('created_at')
            ->take($limit)
            ->get()
            ->map(fn ($order) => [
                'id'            => $order->id,
                'order_code'    => $order->order_code,
                'student_name'  => $order->student->name ?? 'Unknown',
                'student_email' => $order->student->email ?? 'Unknown',
                'course_title'  => $order->items->first()?->course->name ?? 'N/A',
                'amount'        => (float) $order->total_amount,
                'status'        => $order->status,
                'created_at'    => $order->created_at->toIso8601String(),
            ])
            ->values()
            ->all();
    }
}
```

- [ ] **Step 2: Verify the file was created**

```bash
wsl.exe -d Ubuntu -- bash -c "php -l /home/vanthanh/DATN/e-learning/e-learning-backend/Modules/Dashboard/app/Repositories/DashboardRepository.php 2>&1" | cat
```

Expected: `No syntax errors detected`

---

### Task 2: Refactor DashboardController

**Files:**
- Modify: `e-learning-backend/Modules/Dashboard/app/Http/Controllers/DashboardController.php`

- [ ] **Step 1: Replace the entire controller**

```php
<?php

namespace Modules\Dashboard\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Dashboard\Repositories\DashboardRepository;

class DashboardController extends Controller
{
    public function __construct(private DashboardRepository $repository) {}

    public function getStats(): JsonResponse
    {
        return $this->success([
            'summary'         => $this->repository->getSummary(),
            'monthly_revenue' => $this->repository->getMonthlyRevenue((int) date('Y')),
            'top_courses'     => $this->repository->getTopCourses(),
            'recent_orders'   => $this->repository->getRecentOrders(),
        ], 'Lấy thống kê thành công.');
    }
}
```

- [ ] **Step 2: Verify syntax**

```bash
wsl.exe -d Ubuntu -- bash -c "php -l /home/vanthanh/DATN/e-learning/e-learning-backend/Modules/Dashboard/app/Http/Controllers/DashboardController.php 2>&1" | cat
```

Expected: `No syntax errors detected`

---

### Task 3: Fix routes/api.php — add permission middleware

**Files:**
- Modify: `e-learning-backend/Modules/Dashboard/routes/api.php`

- [ ] **Step 1: Replace the route file**

```php
<?php

use Illuminate\Support\Facades\Route;
use Modules\Dashboard\Http\Controllers\DashboardController;

Route::middleware(['auth:admin'])->prefix('v1/admin/dashboard')->group(function () {
    Route::get('stats', [DashboardController::class, 'getStats'])->middleware('permission:dashboard.view');
});
```

---

### Task 4: Clear stale web.php

**Files:**
- Modify: `e-learning-backend/Modules/Dashboard/routes/web.php`

- [ ] **Step 1: Replace the web route file with an empty scaffold**

```php
<?php

use Illuminate\Support\Facades\Route;
```

---

### Task 5: Update test assertions and run tests

**Files:**
- Modify: `e-learning-backend/tests/Feature/Admin/DashboardTest.php`

- [ ] **Step 1: Update `test_dashboard_returns_stats_structure` to include `message`**

Find:
```php
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'summary' => ['total_students', 'total_courses', 'total_orders', 'total_revenue'],
                    'monthly_revenue',
                    'top_courses',
                    'recent_orders',
                ],
            ]);
```

Replace with:
```php
        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'summary' => ['total_students', 'total_courses', 'total_orders', 'total_revenue'],
                    'monthly_revenue',
                    'top_courses',
                    'recent_orders',
                ],
            ]);
```

- [ ] **Step 2: Run tests to verify everything passes**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning/e-learning-backend && php artisan test tests/Feature/Admin/DashboardTest.php 2>&1" | cat
```

Expected: `4 passed`

- [ ] **Step 3: Run full test suite to check for regressions**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning/e-learning-backend && php artisan test 2>&1" | cat
```

Expected: All tests pass (same count as before).

- [ ] **Step 4: Run Pint to fix any style issues**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning/e-learning-backend && ./vendor/bin/pint Modules/Dashboard/ tests/Feature/Admin/DashboardTest.php 2>&1" | cat
```

- [ ] **Step 5: Commit**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning && git add e-learning-backend/Modules/Dashboard/app/Repositories/DashboardRepository.php e-learning-backend/Modules/Dashboard/app/Http/Controllers/DashboardController.php e-learning-backend/Modules/Dashboard/routes/api.php e-learning-backend/Modules/Dashboard/routes/web.php e-learning-backend/tests/Feature/Admin/DashboardTest.php && git commit -m 'refactor(dashboard): extract repository, use ApiResponse, add permission middleware, clear stale web routes'" | cat
```

