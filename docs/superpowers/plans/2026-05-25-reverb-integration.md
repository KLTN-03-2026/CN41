# Laravel Reverb Integration Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Integrate Laravel Reverb WebSocket for three real-time features: fix notification bell auth, replace quiz generation HTTP polling with WebSocket events, and add HLS transcoding progress broadcast.

**Architecture:** The broadcasting auth route is fixed by adding `Broadcast::routes(['middleware' => ['auth:admin']])` to the Notifications module, overriding the default web-middleware `/broadcasting/auth` to accept Sanctum Bearer tokens. Two new private channels (`quiz-job.{jobId}`, `hls.{mediaId}`) are added to `routes/channels.php`. Backend jobs broadcast inline events using `Broadcast::on()->as()->with()->send()`; frontend composables subscribe via the shared Laravel Echo instance from `src/plugins/echo.ts`.

**Tech Stack:** Laravel Reverb, Laravel Echo, pusher-js, Vue 3 Composition API, TypeScript

---

## File Map

| File | Action | Purpose |
|------|--------|---------|
| `e-learning-backend/Modules/Notifications/routes/api.php` | Modify | Override `/broadcasting/auth` to use `auth:admin` Sanctum guard |
| `e-learning-backend/routes/channels.php` | Modify | Add `quiz-job.{jobId}` and `hls.{mediaId}` channel auth |
| `e-learning-backend/Modules/Quiz/app/Jobs/GenerateQuizJob.php` | Modify | Broadcast `QuizGenerationCompleted` after done/failed status updates |
| `e-learning-backend/Modules/Upload/app/Jobs/TranscodeToHlsJob.php` | Modify | Broadcast `HlsProgress` at processing start, done, and failed |
| `e-learning-backend/tests/Feature/Jobs/BroadcastTest.php` | Create | PHPUnit tests asserting broadcasts fire on correct channels |
| `e-learning-frontend/src/composables/useQuizJobChannel.ts` | Create | Promise-based WebSocket listener for quiz job completion |
| `e-learning-frontend/src/composables/useHlsChannel.ts` | Create | Reactive WebSocket listener for HLS transcoding progress |
| `e-learning-frontend/src/components/forms/LessonQuizManager.vue` | Modify | Remove `pollJobStatus` polling; call `waitForJob` instead |
| `e-learning-frontend/src/components/forms/TeacherLessonQuizManager.vue` | Modify | Same polling removal as LessonQuizManager (teacher variant) |
| `e-learning-frontend/src/components/forms/LessonFormModal.vue` | Modify | Subscribe to HLS channel after video upload; show status badge |

---

## Task 1: Fix Broadcasting Auth Middleware

**Files:**
- Modify: `e-learning-backend/Modules/Notifications/routes/api.php`

The `/broadcasting/auth` route registered by `bootstrap/app.php` defaults to web middleware, which cannot authenticate a Sanctum Bearer token. Adding `Broadcast::routes(['middleware' => ['auth:admin']])` in the Notifications module file re-registers the route with the correct guard before any channel subscription attempt.

- [ ] **Step 1: Edit the file**

Replace the entire content of `Modules/Notifications/routes/api.php` with:

```php
<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;
use Modules\Notifications\Http\Controllers\NotificationsController;

Broadcast::routes(['middleware' => ['auth:admin']]);

// Admin notification routes
Route::middleware(['auth:admin'])->prefix('api/v1/admin')->group(function () {
    Route::get('notifications', [NotificationsController::class, 'adminIndex']);
    Route::patch('notifications/mark-all-read', [NotificationsController::class, 'adminMarkAllRead']);
    Route::patch('notifications/{id}/read', [NotificationsController::class, 'adminMarkRead']);
});

// Teacher notification routes
Route::middleware(['auth:admin'])->prefix('api/v1/teacher')->group(function () {
    Route::get('notifications', [NotificationsController::class, 'teacherIndex']);
    Route::patch('notifications/mark-all-read', [NotificationsController::class, 'teacherMarkAllRead']);
    Route::patch('notifications/{id}/read', [NotificationsController::class, 'teacherMarkRead']);
});
```

- [ ] **Step 2: Verify style**

```bash
wsl.exe -d Ubuntu -- bash -c "./vendor/bin/pint Modules/Notifications/routes/api.php --test 2>&1" | cat
```
Expected: `PASS` (no style violations).

- [ ] **Step 3: Commit**

```bash
git add e-learning-backend/Modules/Notifications/routes/api.php
git commit -m "fix(backend): override broadcasting auth to use auth:admin guard"
```

---

## Task 2: Add Channel Definitions

**Files:**
- Modify: `e-learning-backend/routes/channels.php`

Authorization rule for both channels: any authenticated admin can subscribe — the `jobId`/`mediaId` values are opaque identifiers not guessable by other clients.

- [ ] **Step 1: Edit the file**

Replace the entire content of `routes/channels.php` with:

```php
<?php

use Illuminate\Support\Facades\Broadcast;

// Admin private channel: private-admin.{userId}
Broadcast::channel('admin.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
}, ['guards' => ['admin']]);

// Teacher private channel: private-teacher.{teacherId}
Broadcast::channel('teacher.{teacherId}', function ($user, $teacherId) {
    return $user->teacher && (int) $user->teacher->id === (int) $teacherId;
}, ['guards' => ['admin']]);

// Quiz job channel — any authenticated admin may subscribe
Broadcast::channel('quiz-job.{jobId}', function ($user, $jobId) {
    return \Modules\Quiz\Models\QuizGenerationJob::where('id', $jobId)->exists();
}, ['guards' => ['admin']]);

// HLS media channel — any authenticated admin may subscribe
Broadcast::channel('hls.{mediaId}', function ($user, $mediaId) {
    return \Modules\Upload\Models\MediaFile::where('id', $mediaId)->exists();
}, ['guards' => ['admin']]);
```

- [ ] **Step 2: Verify style**

```bash
wsl.exe -d Ubuntu -- bash -c "./vendor/bin/pint routes/channels.php --test 2>&1" | cat
```
Expected: `PASS`

- [ ] **Step 3: Commit**

```bash
git add e-learning-backend/routes/channels.php
git commit -m "feat(backend): add quiz-job and hls private channel definitions"
```

---

## Task 3: Write Failing Broadcast Tests

**Files:**
- Create: `e-learning-backend/tests/Feature/Jobs/BroadcastTest.php`

Write tests before implementing broadcasts. They must fail now and pass after Tasks 4 and 5.

`QUEUE_CONNECTION=sync` (set in `phpunit.xml`) runs jobs inline. SQLite (also in `phpunit.xml`) does not enforce foreign key constraints, so `forceCreate` with arbitrary FK values is safe.

- [ ] **Step 1: Create the test file**

```php
<?php

namespace Tests\Feature\Jobs;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Mockery\MockInterface;
use Modules\Lessons\Models\Lesson;
use Modules\Quiz\Jobs\GenerateQuizJob;
use Modules\Quiz\Models\QuizGenerationJob;
use Modules\Quiz\Services\AIQuizService;
use Modules\Upload\Jobs\TranscodeToHlsJob;
use Modules\Upload\Models\MediaFile;
use Modules\Upload\Services\HlsService;
use Tests\TestCase;
use Tests\Traits\HasAdminUser;

class BroadcastTest extends TestCase
{
    use RefreshDatabase, HasAdminUser;

    public function test_generate_quiz_job_broadcasts_on_completion(): void
    {
        Broadcast::fake();
        $this->setupAdmin();

        $lesson = Lesson::forceCreate([
            'course_id' => 1, 'title' => 'Test Lesson', 'slug' => 'test-lesson',
            'type' => 'video', 'order' => 1, 'status' => 1,
        ]);
        $jobRecord = QuizGenerationJob::create([
            'lesson_id' => $lesson->id,
            'status'    => 'pending',
            'payload'   => ['lesson_id' => $lesson->id, 'source' => 'chapter', 'count' => 1, 'max_attempts' => 3],
        ]);

        $this->mock(AIQuizService::class, function (MockInterface $mock) {
            $mock->shouldReceive('extractChapterPdfText')->andReturn('pdf text');
            $mock->shouldReceive('generateFromPdfText')->andReturn([[
                'question' => 'Q', 'option_a' => 'A', 'option_b' => 'B',
                'option_c' => 'C', 'option_d' => 'D', 'correct_option' => 'A',
            ]]);
        });

        GenerateQuizJob::dispatch($jobRecord->id);

        $this->assertDatabaseHas('quiz_generation_jobs', ['id' => $jobRecord->id, 'status' => 'done']);
        Broadcast::assertSentOn('private-quiz-job.'.$jobRecord->id);
    }

    public function test_generate_quiz_job_broadcasts_on_failure(): void
    {
        Broadcast::fake();
        $this->setupAdmin();

        $lesson = Lesson::forceCreate([
            'course_id' => 1, 'title' => 'Test Lesson 2', 'slug' => 'test-lesson-2',
            'type' => 'video', 'order' => 1, 'status' => 1,
        ]);
        $jobRecord = QuizGenerationJob::create([
            'lesson_id' => $lesson->id,
            'status'    => 'pending',
            'payload'   => ['lesson_id' => $lesson->id, 'source' => 'chapter', 'count' => 1, 'max_attempts' => 3],
        ]);

        $this->mock(AIQuizService::class, function (MockInterface $mock) {
            $mock->shouldReceive('extractChapterPdfText')->andReturn('pdf text');
            $mock->shouldReceive('generateFromPdfText')->andThrow(new \Exception('API error'));
        });

        GenerateQuizJob::dispatch($jobRecord->id);

        $this->assertDatabaseHas('quiz_generation_jobs', ['id' => $jobRecord->id, 'status' => 'failed']);
        Broadcast::assertSentOn('private-quiz-job.'.$jobRecord->id);
    }

    public function test_transcode_to_hls_job_broadcasts_progress(): void
    {
        Broadcast::fake();

        $media = MediaFile::forceCreate([
            'disk' => 'local', 'type' => 'video', 'original_name' => 'test.mp4',
            'path' => 'videos/test.mp4', 'url' => '/storage/videos/test.mp4',
            'mime_type' => 'video/mp4', 'size' => 1000000,
        ]);

        $this->mock(HlsService::class, function (MockInterface $mock) {
            $mock->shouldReceive('transcode')->andReturn(null);
        });

        TranscodeToHlsJob::dispatch($media->id);

        Broadcast::assertSentOn('private-hls.'.$media->id);
    }

    public function test_transcode_to_hls_job_broadcasts_on_failure(): void
    {
        Broadcast::fake();

        $media = MediaFile::forceCreate([
            'disk' => 'local', 'type' => 'video', 'original_name' => 'fail.mp4',
            'path' => 'videos/fail.mp4', 'url' => '/storage/videos/fail.mp4',
            'mime_type' => 'video/mp4', 'size' => 1000000,
        ]);

        $this->mock(HlsService::class, function (MockInterface $mock) {
            $mock->shouldReceive('transcode')->andThrow(new \Exception('transcode failed'));
        });

        TranscodeToHlsJob::dispatch($media->id);

        $this->assertDatabaseHas('media_files', ['id' => $media->id, 'hls_status' => 'failed']);
        Broadcast::assertSentOn('private-hls.'.$media->id);
    }
}
```

- [ ] **Step 2: Run to verify all 4 tests fail**

```bash
wsl.exe -d Ubuntu -- bash -c "php artisan test tests/Feature/Jobs/BroadcastTest.php 2>&1" | cat
```
Expected: **4 FAILED** — `Broadcast::assertSentOn` fails because no broadcasts are sent yet.

---

## Task 4: Add Broadcast to GenerateQuizJob

**Files:**
- Modify: `e-learning-backend/Modules/Quiz/app/Jobs/GenerateQuizJob.php`

Add `use Illuminate\Support\Facades\Broadcast` import and two inline broadcast calls inside `handle()`.

- [ ] **Step 1: Edit the file**

Replace the entire content of `Modules/Quiz/app/Jobs/GenerateQuizJob.php` with:

```php
<?php

namespace Modules\Quiz\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Lessons\Models\Lesson;
use Modules\Quiz\Http\Resources\QuizQuestionResource;
use Modules\Quiz\Models\Quiz;
use Modules\Quiz\Models\QuizGenerationJob;
use Modules\Quiz\Services\AIQuizService;

class GenerateQuizJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;

    public int $tries = 1;

    public function __construct(private int $jobRecordId) {}

    public function middleware(): array
    {
        return [new WithoutOverlapping('ai_quiz_generate')];
    }

    public function handle(AIQuizService $aiService): void
    {
        $jobRecord = QuizGenerationJob::findOrFail($this->jobRecordId);
        $jobRecord->update(['status' => 'processing']);

        try {
            $payload = $jobRecord->payload;
            $lesson = Lesson::with('section.lessons.document')->findOrFail($payload['lesson_id']);

            $pdfText = '';
            if ($payload['source'] === 'upload' && ! empty($payload['temp_path'])) {
                $fullPath = Storage::disk('local')->path($payload['temp_path']);
                $pdfText = $aiService->extractPdfTextFromPath($fullPath);
            } elseif (! empty($payload['pdf_ids'])) {
                $pdfText = $aiService->extractPdfTextByIds($payload['pdf_ids']);
            } else {
                $pdfText = $aiService->extractChapterPdfText($lesson);
            }

            if (! empty($payload['custom_prompt'])) {
                $pdfText .= "\n\n".$payload['custom_prompt'];
            }

            $lessonContext = $lesson->title.($lesson->description ? '. '.$lesson->description : '');
            $count = (int) ($payload['count'] ?? 5);

            $questions = empty(trim($pdfText))
                ? $aiService->generateQuestions($lessonContext, $count)
                : $aiService->generateFromPdfText($pdfText, $count, $lessonContext);

            $quiz = DB::transaction(function () use ($lesson, $questions, $payload) {
                $quiz = Quiz::firstOrCreate(
                    ['lesson_id' => $lesson->id],
                    [
                        'title' => 'Bài kiểm tra: '.$lesson->title,
                        'max_attempts' => $payload['max_attempts'] ?? 3,
                        'time_limit' => $payload['time_limit'] ?? null,
                        'status' => 1,
                    ]
                );
                $quiz->questions()->delete();
                foreach ($questions as $q) {
                    $quiz->questions()->create($q);
                }

                return $quiz->fresh(['questions']);
            });

            if (! empty($payload['temp_path'])) {
                Storage::disk('local')->delete($payload['temp_path']);
            }

            $jobRecord->update([
                'status' => 'done',
                'result' => [
                    'quiz_id' => $quiz->id,
                    'questions' => $quiz->questions->map(fn ($q) => (new QuizQuestionResource($q))->resolve())->values()->all(),
                ],
            ]);

            Broadcast::on("private-quiz-job.{$this->jobRecordId}")
                ->as('QuizGenerationCompleted')
                ->with([
                    'status'    => 'done',
                    'quiz_id'   => $quiz->id,
                    'questions' => $quiz->questions->map(fn ($q) => (new QuizQuestionResource($q))->resolve())->values()->all(),
                ])
                ->send();

        } catch (\Exception $e) {
            Log::error('GenerateQuizJob failed', ['job_record_id' => $this->jobRecordId, 'error' => $e->getMessage()]);
            $jobRecord->update([
                'status' => 'failed',
                'error' => $this->friendlyError($e->getMessage()),
            ]);

            Broadcast::on("private-quiz-job.{$this->jobRecordId}")
                ->as('QuizGenerationCompleted')
                ->with([
                    'status' => 'failed',
                    'error'  => $this->friendlyError($e->getMessage()),
                ])
                ->send();
        }
    }

    private function friendlyError(string $raw): string
    {
        return match (true) {
            str_contains($raw, 'API_KEY') => 'Khóa API Gemini không hợp lệ. Vui lòng kiểm tra cấu hình.',
            str_contains($raw, 'Rate Limit') => 'Hệ thống AI đang bận. Vui lòng thử lại sau vài giây.',
            str_contains($raw, 'quota') => 'Đã hết hạn mức sử dụng AI trong ngày. Thử lại vào ngày mai.',
            str_contains($raw, 'SAFETY') => 'Nội dung tài liệu bị từ chối bởi bộ lọc an toàn AI.',
            str_contains($raw, 'kết nối') => 'Không thể kết nối đến máy chủ AI. Kiểm tra kết nối mạng.',
            str_contains($raw, 'parse') => 'AI trả về kết quả không hợp lệ. Vui lòng thử lại.',
            default => 'Sinh câu hỏi thất bại. Vui lòng thử lại.',
        };
    }
}
```

- [ ] **Step 2: Verify style**

```bash
wsl.exe -d Ubuntu -- bash -c "./vendor/bin/pint Modules/Quiz/app/Jobs/GenerateQuizJob.php --test 2>&1" | cat
```
Expected: `PASS`

- [ ] **Step 3: Run the GenerateQuizJob broadcast tests**

```bash
wsl.exe -d Ubuntu -- bash -c "php artisan test tests/Feature/Jobs/BroadcastTest.php --filter=generate_quiz_job 2>&1" | cat
```
Expected: **2 PASSED**

- [ ] **Step 4: Commit**

```bash
git add e-learning-backend/Modules/Quiz/app/Jobs/GenerateQuizJob.php e-learning-backend/tests/Feature/Jobs/BroadcastTest.php
git commit -m "feat(backend): broadcast QuizGenerationCompleted event from GenerateQuizJob"
```

---

## Task 5: Add Broadcast to TranscodeToHlsJob

**Files:**
- Modify: `e-learning-backend/Modules/Upload/app/Jobs/TranscodeToHlsJob.php`

Three broadcast points: `processing` at the start of `handle()`, `done` after `transcode()` completes, `failed` in the `failed()` callback.

- [ ] **Step 1: Edit the file**

Replace the entire content of `Modules/Upload/app/Jobs/TranscodeToHlsJob.php` with:

```php
<?php

namespace Modules\Upload\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;
use Modules\Upload\Models\MediaFile;
use Modules\Upload\Services\HlsService;
use Throwable;

class TranscodeToHlsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 600; // 10 minutes — large videos need time

    public function __construct(public readonly int $mediaId) {}

    public function handle(HlsService $service): void
    {
        $media = MediaFile::findOrFail($this->mediaId);
        $media->update(['hls_status' => 'processing']);

        Broadcast::on("private-hls.{$this->mediaId}")
            ->as('HlsProgress')
            ->with(['status' => 'processing', 'percent' => 0])
            ->send();

        $service->transcode($media);

        Broadcast::on("private-hls.{$this->mediaId}")
            ->as('HlsProgress')
            ->with(['status' => 'done', 'percent' => 100])
            ->send();
    }

    public function failed(Throwable $exception): void
    {
        MediaFile::where('id', $this->mediaId)->update(['hls_status' => 'failed']);

        Broadcast::on("private-hls.{$this->mediaId}")
            ->as('HlsProgress')
            ->with(['status' => 'failed', 'percent' => 0])
            ->send();

        Log::error('TranscodeToHlsJob failed', [
            'media_id' => $this->mediaId,
            'error' => $exception->getMessage(),
        ]);
    }
}
```

- [ ] **Step 2: Verify style**

```bash
wsl.exe -d Ubuntu -- bash -c "./vendor/bin/pint Modules/Upload/app/Jobs/TranscodeToHlsJob.php --test 2>&1" | cat
```
Expected: `PASS`

- [ ] **Step 3: Run the TranscodeToHlsJob broadcast tests**

```bash
wsl.exe -d Ubuntu -- bash -c "php artisan test tests/Feature/Jobs/BroadcastTest.php --filter=transcode_to_hls 2>&1" | cat
```
Expected: **2 PASSED**

- [ ] **Step 4: Run all 4 broadcast tests**

```bash
wsl.exe -d Ubuntu -- bash -c "php artisan test tests/Feature/Jobs/BroadcastTest.php 2>&1" | cat
```
Expected: **4 PASSED**

- [ ] **Step 5: Run full test suite**

```bash
wsl.exe -d Ubuntu -- bash -c "php artisan test 2>&1" | cat
```
Expected: All existing tests pass plus the 4 new ones.

- [ ] **Step 6: Commit**

```bash
git add e-learning-backend/Modules/Upload/app/Jobs/TranscodeToHlsJob.php
git commit -m "feat(backend): broadcast HlsProgress event from TranscodeToHlsJob"
```

---

## Task 6: Create useQuizJobChannel Composable

**Files:**
- Create: `e-learning-frontend/src/composables/useQuizJobChannel.ts`

`waitForJob` subscribes to `private-quiz-job.{jobId}`, resolves on `done`, rejects on `failed` or after 3-minute timeout. Uses the shared Echo instance from `src/plugins/echo.ts` (created by `useNotifications`) or creates one if not yet initialised.

- [ ] **Step 1: Create the file**

```typescript
import { createEcho, getEcho } from '@/plugins/echo'

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

      setTimeout(() => {
        echo.leave(`quiz-job.${jobId}`)
        reject(new Error('Hết thời gian chờ. Vui lòng thử lại.'))
      }, 180_000)
    })
  }

  return { waitForJob }
}
```

- [ ] **Step 2: Verify TypeScript compiles**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning/e-learning-frontend && npx tsc --noEmit 2>&1" | cat
```
Expected: Exit code 0, no errors.

- [ ] **Step 3: Commit**

```bash
git add e-learning-frontend/src/composables/useQuizJobChannel.ts
git commit -m "feat(frontend): add useQuizJobChannel composable for WebSocket quiz progress"
```

---

## Task 7: Create useHlsChannel Composable

**Files:**
- Create: `e-learning-frontend/src/composables/useHlsChannel.ts`

`subscribeHls` immediately sets `hlsStatus = 'processing'` and subscribes to the channel. `unsubscribeHls` leaves the channel and resets status to `'idle'` — called on modal close and `onUnmounted`.

- [ ] **Step 1: Create the file**

```typescript
import { ref } from 'vue'
import { createEcho, getEcho } from '@/plugins/echo'

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

- [ ] **Step 2: Verify TypeScript compiles**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning/e-learning-frontend && npx tsc --noEmit 2>&1" | cat
```
Expected: No errors.

- [ ] **Step 3: Commit**

```bash
git add e-learning-frontend/src/composables/useHlsChannel.ts
git commit -m "feat(frontend): add useHlsChannel composable for real-time HLS progress"
```

---

## Task 8: Replace Polling in LessonQuizManager.vue

**Files:**
- Modify: `e-learning-frontend/src/components/forms/LessonQuizManager.vue`

Remove the `pollJobStatus` function and its two constants (`MAX_POLLS`, `INTERVAL_MS`). Add `useQuizJobChannel` import, call `waitForJob`, then update state and show toast on the resolved result.

- [ ] **Step 1: Add import**

In `LessonQuizManager.vue`, in the `<script setup>` block, add after `import type { QuizQuestion, ChapterPdf } from '@/services/quiz.service'`:

```ts
import { useQuizJobChannel } from '@/composables/useQuizJobChannel'
```

- [ ] **Step 2: Initialise composable**

Add after `const toast = useToast()`:

```ts
const { waitForJob } = useQuizJobChannel()
```

- [ ] **Step 3: Replace the `doGenerate` success logic**

In `doGenerate`, replace `await pollJobStatus(jobId)` (line 347) with:

```ts
    const result = await waitForJob(jobId)
    questions.value = result.questions as QuizQuestion[]
    showGeneratePanel.value = false
    toast.success(`Đã sinh ${result.questions?.length ?? 0} câu hỏi thành công!`)
```

The full updated `doGenerate` function becomes:

```ts
async function doGenerate() {
  generating.value = true
  generatingStep.value = 'Đang gửi yêu cầu...'
  try {
    const formData = new FormData()
    formData.append('source', genSource.value)
    formData.append('count', String(genCount.value))
    formData.append('max_attempts', String(maxAttempts.value))
    if (customPrompt.value) formData.append('custom_prompt', customPrompt.value)
    if (genSource.value === 'upload' && uploadedFile.value) {
      formData.append('file', uploadedFile.value)
    }
    if (genSource.value === 'chapter') {
      selectedPdfIds.value.forEach((id) => formData.append('pdf_ids[]', String(id)))
    }

    const res = await quizService.lessonQuizGenerate(props.lessonId, formData)
    const jobId = res.data.data.job_id
    generatingStep.value = 'AI đang sinh câu hỏi...'
    const result = await waitForJob(jobId)
    questions.value = result.questions as QuizQuestion[]
    showGeneratePanel.value = false
    toast.success(`Đã sinh ${result.questions?.length ?? 0} câu hỏi thành công!`)
  } catch (err) {
    const e = err as {
      response?: { data?: { message?: string; errors?: Record<string, string[]> } }
      message?: string
    }
    const data = e.response?.data
    if (data?.errors) {
      const firstError = Object.values(data.errors)[0] as string[]
      toast.error(firstError[0] || data.message || 'Sinh câu hỏi thất bại')
    } else {
      toast.error(e.message || data?.message || 'Sinh câu hỏi thất bại')
    }
  } finally {
    generating.value = false
    generatingStep.value = ''
  }
}
```

- [ ] **Step 4: Delete the `pollJobStatus` function**

Remove the entire function from line 366 to 388 (inclusive):

```ts
// DELETE this entire block:
async function pollJobStatus(jobId: number): Promise<void> {
  const MAX_POLLS = 60
  const INTERVAL_MS = 2000

  for (let i = 0; i < MAX_POLLS; i++) {
    await new Promise((r) => setTimeout(r, INTERVAL_MS))

    const res = await quizService.lessonQuizJobStatus(jobId)
    const payload = res.data.data

    if (payload.status === 'done') {
      questions.value = payload.questions
      showGeneratePanel.value = false
      toast.success(`Đã sinh ${payload.questions.length} câu hỏi thành công!`)
      return
    }

    if (payload.status === 'failed') {
      throw new Error(res.data.message || 'Sinh câu hỏi thất bại')
    }
  }
  throw new Error('Hết thời gian chờ. Vui lòng thử lại.')
}
```

- [ ] **Step 5: Verify TypeScript compiles**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning/e-learning-frontend && npx tsc --noEmit 2>&1" | cat
```
Expected: No errors.

- [ ] **Step 6: Commit**

```bash
git add e-learning-frontend/src/components/forms/LessonQuizManager.vue
git commit -m "feat(frontend): replace quiz job polling with WebSocket in LessonQuizManager"
```

---

## Task 9: Replace Polling in TeacherLessonQuizManager.vue

**Files:**
- Modify: `e-learning-frontend/src/components/forms/TeacherLessonQuizManager.vue`

Identical changes to Task 8 except: uses `quizService.teacherLessonQuizGenerate` (already in place) instead of `lessonQuizGenerate`.

- [ ] **Step 1: Add import**

In the `<script setup>` block, add after the quiz service import:

```ts
import { useQuizJobChannel } from '@/composables/useQuizJobChannel'
```

- [ ] **Step 2: Initialise composable**

Add after `const toast = useToast()`:

```ts
const { waitForJob } = useQuizJobChannel()
```

- [ ] **Step 3: Replace `doGenerate` success logic**

In `doGenerate`, replace `await pollJobStatus(jobId)` (line 347) with:

```ts
    const result = await waitForJob(jobId)
    questions.value = result.questions as QuizQuestion[]
    showGeneratePanel.value = false
    toast.success(`Đã sinh ${result.questions?.length ?? 0} câu hỏi thành công!`)
```

Full updated `doGenerate`:

```ts
async function doGenerate() {
  generating.value = true
  generatingStep.value = 'Đang gửi yêu cầu...'
  try {
    const formData = new FormData()
    formData.append('source', genSource.value)
    formData.append('count', String(genCount.value))
    formData.append('max_attempts', String(maxAttempts.value))
    if (customPrompt.value) formData.append('custom_prompt', customPrompt.value)
    if (genSource.value === 'upload' && uploadedFile.value) {
      formData.append('file', uploadedFile.value)
    }
    if (genSource.value === 'chapter') {
      selectedPdfIds.value.forEach((id) => formData.append('pdf_ids[]', String(id)))
    }

    const res = await quizService.teacherLessonQuizGenerate(props.lessonId, formData)
    const jobId = res.data.data.job_id
    generatingStep.value = 'AI đang sinh câu hỏi...'
    const result = await waitForJob(jobId)
    questions.value = result.questions as QuizQuestion[]
    showGeneratePanel.value = false
    toast.success(`Đã sinh ${result.questions?.length ?? 0} câu hỏi thành công!`)
  } catch (err) {
    const e = err as {
      response?: { data?: { message?: string; errors?: Record<string, string[]> } }
      message?: string
    }
    const data = e.response?.data
    if (data?.errors) {
      const firstError = Object.values(data.errors)[0] as string[]
      toast.error(firstError[0] || data.message || 'Sinh câu hỏi thất bại')
    } else {
      toast.error(e.message || data?.message || 'Sinh câu hỏi thất bại')
    }
  } finally {
    generating.value = false
    generatingStep.value = ''
  }
}
```

- [ ] **Step 4: Delete the `pollJobStatus` function**

Remove the entire function from line 366 to 388 (inclusive):

```ts
// DELETE this entire block:
async function pollJobStatus(jobId: number): Promise<void> {
  const MAX_POLLS = 60
  const INTERVAL_MS = 2000

  for (let i = 0; i < MAX_POLLS; i++) {
    await new Promise((r) => setTimeout(r, INTERVAL_MS))

    const res = await quizService.teacherLessonQuizJobStatus(jobId)
    const payload = res.data.data

    if (payload.status === 'done') {
      questions.value = payload.questions
      showGeneratePanel.value = false
      toast.success(`Đã sinh ${payload.questions.length} câu hỏi thành công!`)
      return
    }

    if (payload.status === 'failed') {
      throw new Error(res.data.message || 'Sinh câu hỏi thất bại')
    }
  }
  throw new Error('Hết thời gian chờ. Vui lòng thử lại.')
}
```

- [ ] **Step 5: Verify TypeScript compiles**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning/e-learning-frontend && npx tsc --noEmit 2>&1" | cat
```
Expected: No errors.

- [ ] **Step 6: Commit**

```bash
git add e-learning-frontend/src/components/forms/TeacherLessonQuizManager.vue
git commit -m "feat(frontend): replace quiz job polling with WebSocket in TeacherLessonQuizManager"
```

---

## Task 10: Add HLS Status Badge in LessonFormModal.vue

**Files:**
- Modify: `e-learning-frontend/src/components/forms/LessonFormModal.vue`

After video upload succeeds, subscribe to the HLS channel for that media ID. Show a reactive badge (`Đang xử lý...` / `✓ Video đã sẵn sàng phát` / `✕ Lỗi xử lý video`). Unsubscribe when the modal closes or the component unmounts.

- [ ] **Step 1: Update the Vue import (line 234)**

Change:
```ts
import { ref, watch } from 'vue'
```
To:
```ts
import { ref, watch, onUnmounted } from 'vue'
```

- [ ] **Step 2: Add composable import**

Add after `import { uploadService } from '@/services/upload.service'`:

```ts
import { useHlsChannel } from '@/composables/useHlsChannel'
```

- [ ] **Step 3: Initialise composable and tracking ref**

Add after `const toast = useToast()`:

```ts
const { hlsStatus, subscribeHls, unsubscribeHls } = useHlsChannel()
const subscribedMediaId = ref<number | null>(null)
```

- [ ] **Step 4: Update the `watch` block to unsubscribe on close**

Replace the current watch:
```ts
watch(
  () => props.show,
  (isShown) => {
    if (isShown) {
      localForm.value = { ...props.form }
      lUploadError.value = ''
      lUploadProgress.value = 0
    }
  },
)
```
With:
```ts
watch(
  () => props.show,
  (isShown) => {
    if (isShown) {
      localForm.value = { ...props.form }
      lUploadError.value = ''
      lUploadProgress.value = 0
    } else {
      if (subscribedMediaId.value !== null) {
        unsubscribeHls(subscribedMediaId.value)
        subscribedMediaId.value = null
      }
    }
  },
)

onUnmounted(() => {
  if (subscribedMediaId.value !== null) {
    unsubscribeHls(subscribedMediaId.value)
  }
})
```

- [ ] **Step 5: Subscribe to HLS channel after video upload**

In `uploadLessonFile`, after `localForm.value.media_id = res.data.data.id` (currently ~line 340), add:

```ts
    localForm.value.media_id = res.data.data.id
    if (localForm.value.type === 'video') {
      subscribedMediaId.value = res.data.data.id
      subscribeHls(res.data.data.id)
    }
```

Also fix the duplicate toast on lines 351–352 — remove the second one so only one `toast.success('Tải lên thành công')` remains.

- [ ] **Step 6: Add HLS status badge in the template**

In the `<template>` section, inside the `v-if="localForm.type !== 'quiz'"` div, locate:

```html
<p v-if="errors.document_id" class="error-msg mt-1">{{ errors.document_id }}</p>
```

Add the badge immediately after that paragraph (still inside the same div):

```html
<div
  v-if="localForm.type === 'video' && hlsStatus !== 'idle'"
  class="mt-2 flex items-center gap-1.5 text-xs"
>
  <span
    v-if="hlsStatus === 'processing'"
    class="inline-flex items-center gap-1 text-blue-600 dark:text-blue-400"
  >
    <svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24">
      <circle
        class="opacity-25"
        cx="12"
        cy="12"
        r="10"
        stroke="currentColor"
        stroke-width="4"
      />
      <path
        class="opacity-75"
        fill="currentColor"
        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
      />
    </svg>
    Đang xử lý video HLS...
  </span>
  <span v-else-if="hlsStatus === 'done'" class="text-green-600 dark:text-green-400">
    ✓ Video đã sẵn sàng phát
  </span>
  <span v-else-if="hlsStatus === 'failed'" class="text-red-500">
    ✕ Lỗi xử lý video, vẫn có thể phát file gốc
  </span>
</div>
```

- [ ] **Step 7: Verify TypeScript compiles**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning/e-learning-frontend && npx tsc --noEmit 2>&1" | cat
```
Expected: No errors.

- [ ] **Step 8: Run frontend lint**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning/e-learning-frontend && npm run lint -- src/components/forms/LessonFormModal.vue 2>&1" | cat
```
Expected: No errors.

- [ ] **Step 9: Commit**

```bash
git add e-learning-frontend/src/components/forms/LessonFormModal.vue
git commit -m "feat(frontend): add real-time HLS transcode status badge in LessonFormModal"
```

---

## Task 11: Final Verification

- [ ] **Step 1: Run full backend test suite**

```bash
wsl.exe -d Ubuntu -- bash -c "php artisan test 2>&1" | cat
```
Expected: All tests pass (4 new broadcast tests included).

- [ ] **Step 2: Run full frontend lint**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning/e-learning-frontend && npm run lint 2>&1" | cat
```
Expected: No errors.

- [ ] **Step 3: Build frontend**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning/e-learning-frontend && npm run build 2>&1" | cat
```
Expected: Build succeeds with no TypeScript or Vite errors.
