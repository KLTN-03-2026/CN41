# Laravel Horizon Integration Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Switch the queue driver from `database` to Redis and install Laravel Horizon for real-time monitoring, routing `GenerateQuizJob` to the `ai` queue and `TranscodeToHlsJob` to the `hls` queue.

**Architecture:** `predis/predis` (Composer package) replaces the `phpredis` PHP extension as the Redis client — no PHP extension management needed. Horizon runs three independent supervisors (`default`, `ai`, `hls`) each sized to the job's timeout. The dashboard at `/horizon` is protected by `auth('admin')->check()` so only logged-in admin staff can access it.

**Tech Stack:** `predis/predis` ^2.0, `laravel/horizon` ^5.0, Redis server (WSL Ubuntu via apt)

---

## File Map

| File | Action | Purpose |
|------|--------|---------|
| WSL `/usr/bin/redis-server` | Install (apt) | Redis server binary |
| `e-learning-backend/composer.json` | Auto-updated by composer | Add predis + horizon |
| `e-learning-backend/.env` | Modify | Switch QUEUE_CONNECTION + REDIS_CLIENT |
| `e-learning-backend/.env.example` | Modify | Same keys (committed) |
| `e-learning-backend/config/horizon.php` | Published + configured | 3-supervisor Horizon config |
| `e-learning-backend/app/Providers/HorizonServiceProvider.php` | Published + overridden | gate() for admin guard |
| `e-learning-backend/bootstrap/providers.php` | Auto-updated by horizon:install | Register HorizonServiceProvider |
| `e-learning-backend/Modules/Quiz/app/Jobs/GenerateQuizJob.php` | Modify | Add `public string $queue = 'ai'` |
| `e-learning-backend/Modules/Upload/app/Jobs/TranscodeToHlsJob.php` | Modify | Add `public string $queue = 'hls'` |
| `e-learning-backend/tests/Unit/Jobs/QueueAssignmentTest.php` | Create | Assert queue property values |
| `e-learning-backend/tests/Feature/Admin/HorizonDashboardTest.php` | Create | Assert gate() blocks/allows access |

---

## Task 1: Install and verify Redis server in WSL Ubuntu

No PHP files modified — infrastructure only. Cannot be committed.

**Files:**
- WSL: `/usr/bin/redis-server` (installed by apt — not a project file)

- [ ] **Step 1: Install redis-server**

```bash
wsl.exe -d Ubuntu -- bash -c "sudo apt-get update && sudo apt-get install -y redis-server 2>&1" | cat
```

Expected output ends with something like:
```
Setting up redis-server (X:Y.Z...) ...
```

- [ ] **Step 2: Start the Redis service**

```bash
wsl.exe -d Ubuntu -- bash -c "sudo service redis-server start 2>&1" | cat
```

Expected: `* Starting redis-server redis-server` or `redis-server is running`

- [ ] **Step 3: Verify Redis responds**

```bash
wsl.exe -d Ubuntu -- bash -c "redis-cli ping 2>&1" | cat
```

Expected: `PONG`

If Redis was already installed, Step 3 alone is sufficient to verify.

---

## Task 2: Install PHP packages

**Files:**
- Modify: `e-learning-backend/composer.json` (auto)
- Modify: `e-learning-backend/composer.lock` (auto)

- [ ] **Step 1: Run composer require**

Run from the `e-learning-backend` directory:

```bash
wsl.exe -d Ubuntu -- bash -c "composer require predis/predis laravel/horizon 2>&1" | cat
```

Expected output contains:
```
  - Installing predis/predis (...)
  - Installing laravel/horizon (...)
```

- [ ] **Step 2: Verify both packages are installed**

```bash
wsl.exe -d Ubuntu -- bash -c "composer show predis/predis laravel/horizon 2>&1" | cat
```

Expected: Both packages listed with their version numbers.

---

## Task 3: Publish Horizon assets and run migrations

This runs two artisan commands. `horizon:install` publishes config, the service provider stub, and dashboard JS/CSS assets; it also auto-registers `HorizonServiceProvider` in `bootstrap/providers.php`. `migrate` creates the tables Horizon needs.

**Files:**
- Create: `e-learning-backend/config/horizon.php`
- Create: `e-learning-backend/app/Providers/HorizonServiceProvider.php`
- Auto-modify: `e-learning-backend/bootstrap/providers.php`
- Create: migration files in `e-learning-backend/database/migrations/`

- [ ] **Step 1: Run horizon:install**

```bash
wsl.exe -d Ubuntu -- bash -c "php artisan horizon:install 2>&1" | cat
```

Expected:
```
Published Horizon's configuration.
Published Horizon's service provider.
Published Horizon's assets.
```

- [ ] **Step 2: Verify the three published files exist**

```bash
wsl.exe -d Ubuntu -- bash -c "ls app/Providers/HorizonServiceProvider.php config/horizon.php public/vendor/horizon/ 2>&1" | cat
```

Expected: All paths listed without error.

- [ ] **Step 3: Verify HorizonServiceProvider was added to bootstrap/providers.php**

```bash
wsl.exe -d Ubuntu -- bash -c "grep 'HorizonServiceProvider' bootstrap/providers.php 2>&1" | cat
```

Expected: A line containing `HorizonServiceProvider::class`.

- [ ] **Step 4: Run migrations**

```bash
wsl.exe -d Ubuntu -- bash -c "php artisan migrate 2>&1" | cat
```

Expected: Runs without error. May include Horizon-related table creation (e.g. `job_batches` if not already present).

---

## Task 4: Update environment files

**Files:**
- Modify: `e-learning-backend/.env`
- Modify: `e-learning-backend/.env.example`

Context: Currently `.env` has `QUEUE_CONNECTION=database` and `REDIS_CLIENT=phpredis`. Both need updating. `.env` is gitignored; `.env.example` is committed.

- [ ] **Step 1: Update `.env`**

In `e-learning-backend/.env`, make these two changes:

```
# Change:
QUEUE_CONNECTION=database
# To:
QUEUE_CONNECTION=redis

# Change:
REDIS_CLIENT=phpredis
# To:
REDIS_CLIENT=predis
```

- [ ] **Step 2: Update `.env.example`**

In `e-learning-backend/.env.example`, make these two changes:

```
# Change:
QUEUE_CONNECTION=
# To:
QUEUE_CONNECTION=redis

# Change:
REDIS_CLIENT=
# To:
REDIS_CLIENT=predis
```

- [ ] **Step 3: Verify the queue config resolves to redis**

```bash
wsl.exe -d Ubuntu -- bash -c "php artisan tinker --execute=\"echo config('queue.default');\" 2>&1" | cat
```

Expected: `redis`

---

## Task 5: Configure Horizon supervisors in config/horizon.php

The published `config/horizon.php` has a default `'environments'` key with generic supervisors. Replace it with three named supervisors sized to match each job's timeout.

**Files:**
- Modify: `e-learning-backend/config/horizon.php`

- [ ] **Step 1: Replace the `'environments'` array in config/horizon.php**

Open `e-learning-backend/config/horizon.php` and find the `'environments'` key (it will be toward the bottom of the file). Replace the entire key and its value with:

```php
'environments' => [
    'local' => [
        'supervisor-default' => [
            'connection' => 'redis',
            'queue'      => ['default'],
            'balance'    => 'simple',
            'processes'  => 2,
            'tries'      => 3,
            'timeout'    => 60,
        ],
        'supervisor-ai' => [
            'connection' => 'redis',
            'queue'      => ['ai'],
            'balance'    => 'simple',
            'processes'  => 2,
            'tries'      => 1,
            'timeout'    => 130,
        ],
        'supervisor-hls' => [
            'connection' => 'redis',
            'queue'      => ['hls'],
            'balance'    => 'simple',
            'processes'  => 2,
            'tries'      => 1,
            'timeout'    => 650,
        ],
    ],
],
```

Rationale:
- `supervisor-ai` timeout 130 = `GenerateQuizJob::$timeout` (120) + 10s buffer
- `supervisor-hls` timeout 650 = `TranscodeToHlsJob::$timeout` (600) + 50s buffer
- `supervisor-default` uses 3 tries (general tasks can be retried safely)
- `supervisor-ai` and `supervisor-hls` use 1 try (both jobs have `$tries = 1` and handle failures via WebSocket broadcast)

- [ ] **Step 2: Verify no PHP syntax errors**

```bash
wsl.exe -d Ubuntu -- bash -c "php artisan config:clear 2>&1 && php artisan tinker --execute=\"echo 'ok';\" 2>&1" | cat
```

Expected: `ok` — no parse error or exception.

---

## Task 6: TDD — Horizon dashboard gate

Override the published `HorizonServiceProvider::gate()` to use the `admin` Sanctum guard. The default published `gate()` checks a hardcoded email allowlist (empty by default) — the test for admin access will fail until we override it.

**Context:** `phpunit.xml` sets `APP_ENV=testing`, so Horizon's `app()->environment('local')` check is false in tests — the gate IS exercised.

**Files:**
- Create: `e-learning-backend/tests/Feature/Admin/HorizonDashboardTest.php`
- Modify: `e-learning-backend/app/Providers/HorizonServiceProvider.php`

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/Admin/HorizonDashboardTest.php`:

```php
<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\HasAdminUser;

class HorizonDashboardTest extends TestCase
{
    use RefreshDatabase, HasAdminUser;

    public function test_unauthenticated_user_cannot_access_horizon(): void
    {
        $response = $this->get('/horizon');

        $this->assertContains($response->status(), [401, 403]);
    }

    public function test_admin_user_can_access_horizon(): void
    {
        $this->setupAdmin();

        $response = $this->get('/horizon');

        $response->assertStatus(200);
    }
}
```

Note: `setupAdmin()` from `HasAdminUser` both creates the user AND calls `actingAs($admin, 'admin')` internally — no additional `actingAs` call is needed.

- [ ] **Step 2: Run the tests to verify the right one fails**

```bash
wsl.exe -d Ubuntu -- bash -c "php artisan test tests/Feature/Admin/HorizonDashboardTest.php --verbose 2>&1" | cat
```

Expected: `test_unauthenticated_user_cannot_access_horizon` **passes** (empty email list blocks all). `test_admin_user_can_access_horizon` **fails** (admin email not in the empty allowlist). This is the correct TDD failure.

- [ ] **Step 3: Override gate() in HorizonServiceProvider**

Open `e-learning-backend/app/Providers/HorizonServiceProvider.php`. Replace the body of the `gate()` method:

```php
protected function gate(): bool
{
    return auth('admin')->check();
}
```

The full `gate()` method after the change (the rest of the file stays as published):

```php
/**
 * Register the Horizon gate.
 *
 * This gate determines who can access Horizon in non-local environments.
 */
protected function gate(): bool
{
    return auth('admin')->check();
}
```

- [ ] **Step 4: Run the tests — both should pass**

```bash
wsl.exe -d Ubuntu -- bash -c "php artisan test tests/Feature/Admin/HorizonDashboardTest.php --verbose 2>&1" | cat
```

Expected:
```
  PASS  Tests\Feature\Admin\HorizonDashboardTest
  ✓ unauthenticated user cannot access horizon
  ✓ admin user can access horizon
```

- [ ] **Step 5: Fix any Pint style issues**

```bash
wsl.exe -d Ubuntu -- bash -c "./vendor/bin/pint app/Providers/HorizonServiceProvider.php tests/Feature/Admin/HorizonDashboardTest.php 2>&1" | cat
```

Expected: Files already match Pint rules, or Pint auto-fixes and reports "1 file reformatted".

- [ ] **Step 6: Commit**

```bash
git add app/Providers/HorizonServiceProvider.php tests/Feature/Admin/HorizonDashboardTest.php
git commit -m "feat(backend): gate Horizon dashboard behind admin guard"
```

---

## Task 7: TDD — Job queue properties

Add `public string $queue = 'ai'` to `GenerateQuizJob` and `public string $queue = 'hls'` to `TranscodeToHlsJob`. Both jobs currently have no queue property and fall through to the `default` queue.

**Files:**
- Create: `e-learning-backend/tests/Unit/Jobs/QueueAssignmentTest.php`
- Modify: `e-learning-backend/Modules/Quiz/app/Jobs/GenerateQuizJob.php`
- Modify: `e-learning-backend/Modules/Upload/app/Jobs/TranscodeToHlsJob.php`

- [ ] **Step 1: Write the failing tests**

Create `tests/Unit/Jobs/QueueAssignmentTest.php`:

```php
<?php

namespace Tests\Unit\Jobs;

use Modules\Quiz\Jobs\GenerateQuizJob;
use Modules\Upload\Jobs\TranscodeToHlsJob;
use Tests\TestCase;

class QueueAssignmentTest extends TestCase
{
    public function test_generate_quiz_job_is_on_ai_queue(): void
    {
        $job = new GenerateQuizJob(1);

        $this->assertSame('ai', $job->queue);
    }

    public function test_transcode_hls_job_is_on_hls_queue(): void
    {
        $job = new TranscodeToHlsJob(1);

        $this->assertSame('hls', $job->queue);
    }
}
```

- [ ] **Step 2: Run the tests to verify they fail**

```bash
wsl.exe -d Ubuntu -- bash -c "php artisan test tests/Unit/Jobs/QueueAssignmentTest.php --verbose 2>&1" | cat
```

Expected: Both tests **fail** with something like:
```
Failed asserting that null is identical to 'ai'.
Failed asserting that null is identical to 'hls'.
```

- [ ] **Step 3: Add queue property to GenerateQuizJob**

In `Modules/Quiz/app/Jobs/GenerateQuizJob.php`, add `public string $queue` after `$tries`:

```php
public int $timeout = 120;

public int $tries = 1;

public string $queue = 'ai';

public function __construct(private int $jobRecordId) {}
```

- [ ] **Step 4: Add queue property to TranscodeToHlsJob**

In `Modules/Upload/app/Jobs/TranscodeToHlsJob.php`, add `public string $queue` after `$timeout`:

```php
public int $tries = 1;

public int $timeout = 600; // 10 minutes — large videos need time

public string $queue = 'hls';

public function __construct(public readonly int $mediaId) {}
```

- [ ] **Step 5: Run the queue assignment tests — both should pass**

```bash
wsl.exe -d Ubuntu -- bash -c "php artisan test tests/Unit/Jobs/QueueAssignmentTest.php --verbose 2>&1" | cat
```

Expected:
```
  PASS  Tests\Unit\Jobs\QueueAssignmentTest
  ✓ generate quiz job is on ai queue
  ✓ transcode hls job is on hls queue
```

- [ ] **Step 6: Run the full test suite to confirm no regressions**

Jobs with `$queue` set still dispatch and execute normally in `QUEUE_CONNECTION=sync` mode (sync ignores queue names) — existing quiz and upload tests must still pass.

```bash
wsl.exe -d Ubuntu -- bash -c "php artisan test 2>&1" | cat
```

Expected: All tests pass with no failures.

- [ ] **Step 7: Fix any Pint style issues**

```bash
wsl.exe -d Ubuntu -- bash -c "./vendor/bin/pint Modules/Quiz/app/Jobs/GenerateQuizJob.php Modules/Upload/app/Jobs/TranscodeToHlsJob.php tests/Unit/Jobs/QueueAssignmentTest.php 2>&1" | cat
```

Expected: Files already comply, or Pint reformats and reports the count.

- [ ] **Step 8: Commit**

```bash
git add Modules/Quiz/app/Jobs/GenerateQuizJob.php Modules/Upload/app/Jobs/TranscodeToHlsJob.php tests/Unit/Jobs/QueueAssignmentTest.php
git commit -m "feat(backend): assign GenerateQuizJob→ai queue and TranscodeToHlsJob→hls queue"
```

---

## Task 8: Commit config and env changes

This task commits the remaining changed files: `config/horizon.php`, `.env.example`, and `bootstrap/providers.php`. Note: `.env` is gitignored and must NOT be committed.

**Files:**
- Commit: `e-learning-backend/config/horizon.php`
- Commit: `e-learning-backend/.env.example`
- Commit: `e-learning-backend/bootstrap/providers.php`
- Commit: `e-learning-backend/composer.json` + `composer.lock`

- [ ] **Step 1: Run Pint across all modified PHP files**

```bash
wsl.exe -d Ubuntu -- bash -c "./vendor/bin/pint config/horizon.php bootstrap/providers.php 2>&1" | cat
```

Expected: No issues, or auto-fixed.

- [ ] **Step 2: Commit**

```bash
git add config/horizon.php bootstrap/providers.php .env.example composer.json composer.lock
git commit -m "feat(backend): install Laravel Horizon with Redis queue driver and 3 supervisors"
```

Do NOT include `.env` — it is gitignored for a reason.

---

## Task 9: Manual smoke test (dev environment)

Verify the complete stack works end-to-end. This cannot be automated with PHPUnit.

- [ ] **Step 1: Ensure Redis is running**

```bash
wsl.exe -d Ubuntu -- bash -c "redis-cli ping 2>&1" | cat
```

Expected: `PONG`. If not: `sudo service redis-server start`

- [ ] **Step 2: Start Horizon in a WSL terminal (replaces `php artisan queue:work`)**

In a WSL terminal window:
```bash
cd /home/vanthanh/DATN/e-learning/e-learning-backend
php artisan horizon
```

Expected output:
```
Horizon started successfully.
```

Three supervisor processes appear: `supervisor-default`, `supervisor-ai`, `supervisor-hls`.

- [ ] **Step 3: Start the backend server in a second terminal**

```bash
cd /home/vanthanh/DATN/e-learning/e-learning-backend
php artisan serve
```

- [ ] **Step 4: Log in as admin in the browser**

Open `http://localhost:5173/admin/login` and log in with `superadmin@elearning.com` / `password`. This sets the session cookie that Horizon's dashboard uses.

- [ ] **Step 5: Open the Horizon dashboard**

Navigate to `http://localhost:8000/horizon`

Expected: Horizon dashboard loads and shows all three supervisors running. The "Queues" section should show `default`, `ai`, and `hls`.

If you see a 403 error: log in at the admin frontend first (Step 4), then retry. The Horizon dashboard uses a session cookie, not a Bearer token — browser session must exist.

- [ ] **Step 6: (Optional) Trigger a quiz generation job and verify it appears on the `ai` queue**

From the admin panel, open any quiz lesson and trigger "Sinh câu hỏi AI". In the Horizon dashboard, click "Monitoring" → `ai` queue. The `GenerateQuizJob` should appear as processing or completed.

---

## Dev Workflow Going Forward

After this integration, replace `php artisan queue:work` with `php artisan horizon`:

```bash
# Terminal 1 — start Redis (once per WSL session)
sudo service redis-server start

# Terminal 2 — Horizon (queue workers + dashboard)
cd e-learning-backend && php artisan horizon

# Terminal 3 — API server
cd e-learning-backend && php artisan serve

# Terminal 4 — Frontend
cd e-learning-frontend && npm run dev
```

Dashboard: `http://localhost:8000/horizon`
