# Laravel Horizon Integration Design

## Goal

Switch the queue driver from `database` to Redis, and install Laravel Horizon to provide a real-time dashboard for monitoring and managing queued jobs (`GenerateQuizJob` and `TranscodeToHlsJob`).

## Architecture

**Queue driver change:** Replace MySQL `jobs` table with Redis as the queue backend. Redis is already configured in `.env` (`REDIS_HOST`, `REDIS_PORT`); the only changes are installing the Redis server binary and switching the client library from `phpredis` (PHP extension) to `predis` (Composer package).

**Named queues:** Jobs are assigned to dedicated queues to allow independent monitoring and resource allocation. Horizon runs one supervisor per queue.

**Dashboard:** Horizon registers `/horizon` automatically. Access is gated by the `admin` Sanctum guard — only logged-in admin staff can view it.

**Scope:** Backend-only change. No Vue frontend modifications required.

---

## Tech Stack

- `predis/predis` — PHP Redis client (Composer package, no PHP extension needed)
- `laravel/horizon` — Queue dashboard (Composer package)
- Redis server — installed in WSL Ubuntu via `apt`

---

## Components

### 1. Infrastructure

**Redis server (WSL):**
```bash
sudo apt update && sudo apt install -y redis-server
sudo service redis-server start
```

Redis listens on `127.0.0.1:6379` (default) — matches existing `.env` values.

No systemd auto-start needed for dev; developer starts Redis manually each session.

### 2. Package Installation

```bash
composer require predis/predis laravel/horizon
php artisan horizon:install    # publishes config/horizon.php and assets
php artisan migrate            # creates horizon_* tables (monitors, snapshots, tags)
```

### 3. Environment Changes

**`.env` (development):**
```
QUEUE_CONNECTION=redis
REDIS_CLIENT=predis      # was: phpredis
```

**`.env.example`** — update both keys with the new values.

**`phpunit.xml`** — unchanged. Already has `QUEUE_CONNECTION=sync`; tests are not affected.

### 4. Named Queues

Each long-running job type is assigned its own queue:

| Job | Property | Queue | Reason |
|-----|----------|-------|--------|
| `GenerateQuizJob` | `public string $queue = 'ai'` | `ai` | AI calls — 120s timeout |
| `TranscodeToHlsJob` | `public string $queue = 'hls'` | `hls` | Video transcode — 600s timeout |
| Future jobs | (none set) | `default` | General purpose |

Both jobs currently have `$tries = 1`. This is intentional — `GenerateQuizJob` broadcasts failure via WebSocket and the user can retry from the UI; `TranscodeToHlsJob` marks `hls_status = 'failed'` which the user can see in the admin panel.

### 5. Horizon Configuration (`config/horizon.php`)

Three supervisors under `local` environment:

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

`timeout` values match job `$timeout` properties with a small buffer (130s for AI = `QUEUE_AI_TIMEOUT + 0`; 650s for HLS = `TranscodeToHlsJob::$timeout + 50`).

`balance: simple` — fixed worker count, appropriate for dev. (Use `balance: auto` for production autoscaling.)

### 6. Dashboard Auth

In `app/Providers/HorizonServiceProvider.php` (published by `horizon:install`):

`horizon:install` publishes this provider with a `gate()` method stub. Override it to check the `admin` guard:

```php
protected function gate(): bool
{
    return auth('admin')->check();
}
```

`horizon:install` also auto-registers `HorizonServiceProvider` in `bootstrap/providers.php` — no manual step needed.

Dashboard URL: `http://localhost:8000/horizon`

**Auth note:** Horizon's web routes use Laravel's `web` middleware (cookie session), not Sanctum Bearer token. To access the dashboard, the admin must have a valid Laravel session cookie — this works when accessing via browser directly (the admin SPA login sets both the `adminToken` in localStorage AND a session cookie, since Sanctum's `stateful` config includes `localhost`). No additional auth integration is needed.

### 7. Failed Jobs

Horizon captures failed jobs automatically. The existing `failed_jobs` table continues to be used. Failed jobs are visible in the Horizon dashboard under "Failed Jobs".

No changes to `GenerateQuizJob::failed()` or `TranscodeToHlsJob::failed()` — they already broadcast failure status via WebSocket and update the DB record.

---

## Dev Workflow (after setup)

```bash
# WSL — start Redis (once per session)
sudo service redis-server start

# Terminal 1 — Horizon (replaces `php artisan queue:work`)
php artisan horizon

# Terminal 2 — Backend
php artisan serve

# Terminal 3 — Frontend
cd e-learning-frontend && npm run dev
```

Dashboard: `http://localhost:8000/horizon`

---

## Files Modified / Created

| File | Action |
|------|--------|
| `composer.json` | Add `predis/predis`, `laravel/horizon` |
| `.env` | `QUEUE_CONNECTION=redis`, `REDIS_CLIENT=predis` |
| `.env.example` | Same keys |
| `config/horizon.php` | Published — configure 3 supervisors |
| `bootstrap/providers.php` | Auto-updated by `horizon:install` |
| `app/Providers/HorizonServiceProvider.php` | Published by `horizon:install` — override `gate()` method |
| `Modules/Quiz/app/Jobs/GenerateQuizJob.php` | Add `public string $queue = 'ai'` |
| `Modules/Upload/app/Jobs/TranscodeToHlsJob.php` | Add `public string $queue = 'hls'` |

---

## Out of Scope

- Auto-starting Redis via systemd (not needed for dev/thesis)
- Horizon in production environment (no `production` env block)
- Vue admin panel integration (Horizon dashboard is standalone)
- `horizon:snapshot` scheduled command (optional, useful for graphs — can add manually)
