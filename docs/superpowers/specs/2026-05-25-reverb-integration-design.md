# Laravel Reverb Integration — Design Spec

**Date:** 2026-05-25  
**Approach:** B — Per-job private channels

---

## 1. Scope

Three real-time features using Laravel Reverb (WebSocket):

1. **Notification bell** — admin/teacher nhận thông báo real-time (infrastructure gần như đã có)
2. **Quiz generation** — thay thế HTTP polling bằng WebSocket event khi AI sinh câu hỏi xong
3. **HLS transcoding progress** — broadcast trạng thái transcode video real-time

---

## 2. Current State

Đã có sẵn (không cần thay đổi):

- `.env`: `BROADCAST_CONNECTION=reverb` với credentials đầy đủ
- `config/reverb.php`, `config/broadcasting.php`
- `bootstrap/app.php`: khai báo `channels: routes/channels.php` → tự đăng ký `/broadcasting/auth`
- `routes/channels.php`: channel auth cho `admin.{userId}` và `teacher.{teacherId}`
- `Modules/Notifications/app/Services/NotificationService.php`: đã dùng `Broadcast::on()->send()`
- `laravel/reverb` trong `composer.json`
- Frontend: `src/plugins/echo.ts`, `src/composables/useNotifications.ts`, `src/components/layout/header/NotificationMenu.vue`
- Frontend: `laravel-echo`, `pusher-js` trong `package.json`

---

## 3. Architecture

```
                    BACKEND                              FRONTEND
┌─────────────────────────────────┐      ┌────────────────────────────────┐
│                                 │      │                                │
│  Reverb Server (:8080)  ←───────┼──────┼── Echo (pusher-js + WS)       │
│         │                       │      │         │                      │
│   /broadcasting/auth ←──────────┼──────┼── Auth  │ (Bearer adminToken) │
│   [middleware: auth:admin]      │      │         │                      │
│                                 │      │  ┌──────▼─────────────────┐   │
│  channels.php                   │      │  │ Private channels:       │   │
│  ├── admin.{userId}             │◄─────┼──┤ admin.{userId}         │   │
│  ├── teacher.{teacherId}        │◄─────┼──┤ teacher.{teacherId}    │   │
│  ├── quiz-job.{jobId}      NEW  │◄─────┼──┤ quiz-job.{jobId}       │   │
│  └── hls.{mediaId}        NEW  │◄─────┼──┤ hls.{mediaId}          │   │
│                                 │      │  └────────────────────────┘   │
│  Broadcast sources:             │      │                                │
│  NotificationService ──────────►│      │  useNotifications (bell)       │
│  GenerateQuizJob ──────────────►│      │  useQuizJobChannel    NEW      │
│  TranscodeToHlsJob ────────────►│      │  useHlsChannel         NEW     │
└─────────────────────────────────┘      └────────────────────────────────┘
```

**Nguyên tắc Echo instance:** `useNotifications` tạo một Echo instance duy nhất và lưu qua `setEcho()`. Các composable mới dùng lại qua `getEcho()`. Nếu chưa có, tự tạo bằng `createEcho(token)`.

---

## 4. Backend Changes

### 4a. Fix Broadcasting Auth

**Vấn đề:** Route `/broadcasting/auth` mặc định dùng web middleware — không xác thực được Sanctum Bearer token.

**Giải pháp:** Override trong `Modules/Notifications/routes/api.php` (thêm vào đầu file):

```php
use Illuminate\Support\Facades\Broadcast;

Broadcast::routes(['middleware' => ['auth:admin']]);
```

Một dòng này cho phép Sanctum `adminToken` xác thực tất cả private channel.

### 4b. Thêm Channel Auth — `routes/channels.php`

```php
// Quiz job channel
Broadcast::channel('quiz-job.{jobId}', function ($user, $jobId) {
    return \Modules\Quiz\Models\QuizGenerationJob::where('id', $jobId)->exists();
}, ['guards' => ['admin']]);

// HLS media channel
Broadcast::channel('hls.{mediaId}', function ($user, $mediaId) {
    return \Modules\Upload\Models\MediaFile::where('id', $mediaId)->exists();
}, ['guards' => ['admin']]);
```

Authorization rule: bất kỳ admin đã xác thực đều có thể subscribe — không cần ownership check vì job_id và media_id không thể đoán được.

### 4c. `GenerateQuizJob` — Broadcast khi Done/Failed

Thêm inline broadcast sau mỗi `$jobRecord->update(...)`, dùng pattern giống `NotificationService`:

```php
// Khi done:
Broadcast::on("private-quiz-job.{$this->jobRecordId}")
    ->as('QuizGenerationCompleted')
    ->with([
        'status'    => 'done',
        'quiz_id'   => $quiz->id,
        'questions' => $quiz->questions->map(...)->values()->all(),
    ])
    ->send();

// Khi failed:
Broadcast::on("private-quiz-job.{$this->jobRecordId}")
    ->as('QuizGenerationCompleted')
    ->with([
        'status' => 'failed',
        'error'  => $this->friendlyError($e->getMessage()),
    ])
    ->send();
```

### 4d. `TranscodeToHlsJob` — Broadcast Progress

Broadcast 3 mốc: start (processing), done, failed.

```php
// handle() — sau khi update hls_status='processing':
Broadcast::on("private-hls.{$this->mediaId}")
    ->as('HlsProgress')
    ->with(['status' => 'processing', 'percent' => 0])
    ->send();

// handle() — sau khi transcode xong:
Broadcast::on("private-hls.{$this->mediaId}")
    ->as('HlsProgress')
    ->with(['status' => 'done', 'percent' => 100])
    ->send();

// failed() — khi job thất bại:
Broadcast::on("private-hls.{$this->mediaId}")
    ->as('HlsProgress')
    ->with(['status' => 'failed', 'percent' => 0])
    ->send();
```

---

## 5. Frontend Changes

### 5a. `src/composables/useQuizJobChannel.ts` (mới)

Token lấy từ `localStorage.getItem('adminToken')` — không cần truyền qua param để tránh phụ thuộc vào auth store trong các component dùng composable này.

```ts
export interface QuizJobResult {
  status: 'done' | 'failed'
  quiz_id?: number
  questions?: unknown[]
  error?: string
}

export function useQuizJobChannel() {
  function waitForJob(jobId: number): Promise<QuizJobResult> {
    return new Promise((resolve, reject) => {
      const token = localStorage.getItem('adminToken') ?? ''
      const echo = getEcho() ?? createEcho(token)

      echo.private(`quiz-job.${jobId}`)
        .listen('.QuizGenerationCompleted', (event: QuizJobResult) => {
          echo.leave(`quiz-job.${jobId}`)
          if (event.status === 'done') resolve(event)
          else reject(new Error(event.error ?? 'Sinh câu hỏi thất bại'))
        })

      // Fallback timeout 3 phút
      setTimeout(() => {
        echo.leave(`quiz-job.${jobId}`)
        reject(new Error('Hết thời gian chờ. Vui lòng thử lại.'))
      }, 180_000)
    })
  }

  return { waitForJob }
}
```

### 5b. Sửa `LessonQuizManager.vue` và `TeacherLessonQuizManager.vue`

Cả hai component đều có `pollJobStatus` với cùng logic. Thay đổi giống nhau cho cả hai:

- Import `useQuizJobChannel`
- Thay `await pollJobStatus(jobId)` → `await waitForJob(jobId)`
- Xóa hàm `pollJobStatus`, hằng `INTERVAL_MS`, hằng `MAX_POLLS`

Không thay đổi gì khác trong component.

### 5c. `src/composables/useHlsChannel.ts` (mới)

```ts
export function useHlsChannel() {
  const hlsStatus = ref<'idle' | 'processing' | 'done' | 'failed'>('idle')

  function subscribeHls(mediaId: number): void {
    hlsStatus.value = 'processing'
    const token = localStorage.getItem('adminToken') ?? ''
    const echo = getEcho() ?? createEcho(token)

    echo.private(`hls.${mediaId}`)
      .listen('.HlsProgress', (event: { status: string }) => {
        hlsStatus.value = event.status as typeof hlsStatus.value
        if (event.status === 'done' || event.status === 'failed') {
          echo.leave(`hls.${mediaId}`)
        }
      })
  }

  function unsubscribeHls(mediaId: number): void {
    getEcho()?.leave(`hls.${mediaId}`)
    hlsStatus.value = 'idle'
  }

  return { hlsStatus, subscribeHls, unsubscribeHls }
}
```

### 5d. Sửa `LessonFormModal.vue`

- Import `useHlsChannel`
- Gọi `subscribeHls(mediaId)` ngay sau khi upload video thành công (khi backend dispatch `TranscodeToHlsJob`)
- Dùng `hlsStatus` để hiển thị badge trạng thái bên cạnh video: `Đang xử lý...` / `Hoàn thành` / `Lỗi transcode`
- Gọi `unsubscribeHls(mediaId)` trong `onUnmounted`

---

## 6. Error Handling

| Tình huống | Xử lý |
|-----------|-------|
| WebSocket mất kết nối giữa chừng | `setTimeout` 3 phút trong `waitForJob` — tự reject, toast lỗi |
| Job failed (AI lỗi, transcode lỗi) | Backend broadcast `status: 'failed'` → frontend reject/hiển thị lỗi |
| `/broadcasting/auth` trả 403 | Echo không subscribe → `waitForJob` timeout 3 phút |
| Reverb chưa start | WebSocket fail → không ảnh hưởng HTTP, mất real-time |

---

## 7. Testing

### Backend (PHPUnit)
- `GenerateQuizJob`: dùng `Event::fake()` hoặc `Broadcast::fake()` → `assertBroadcasted('QuizGenerationCompleted')` trên đúng channel
- `TranscodeToHlsJob`: tương tự cho `HlsProgress`

### Manual
1. `php artisan reverb:start`
2. **Notification bell**: trigger enrollment → bell nhận notification real-time
3. **Quiz generation**: Generate quiz → Network tab không thấy poll HTTP mỗi 2s, nhận WebSocket message khi AI xong
4. **HLS**: Upload video → badge trạng thái thay đổi real-time

---

## 8. Files Changed

| File | Thay đổi |
|------|---------|
| `Modules/Notifications/routes/api.php` | Thêm `Broadcast::routes()` |
| `routes/channels.php` | Thêm 2 channel mới |
| `Modules/Quiz/app/Jobs/GenerateQuizJob.php` | Thêm broadcast sau update status |
| `Modules/Upload/app/Jobs/TranscodeToHlsJob.php` | Thêm broadcast 3 mốc |
| `src/composables/useQuizJobChannel.ts` | Tạo mới |
| `src/composables/useHlsChannel.ts` | Tạo mới |
| `src/components/forms/LessonQuizManager.vue` | Thay pollJobStatus → waitForJob |
| `src/components/forms/TeacherLessonQuizManager.vue` | Thay pollJobStatus → waitForJob |
| `src/components/forms/LessonFormModal.vue` | Thêm HLS status UI |
