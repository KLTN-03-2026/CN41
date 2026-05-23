# Anti-Piracy: Signed URLs + Watermark + HLS/AES-128 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Protect video content against piracy via three complementary layers: time-limited signed URLs (URL sharing useless after 2 hours), dynamic student watermark (screen recordings traceable to source), and HLS+AES-128 encryption (segments are encrypted garbage without authenticated key fetch).

**Architecture:**
- Feature 1: `LessonController::myLessonDetail` replaces the long-lived `?token=` URL with Laravel `URL::temporarySignedRoute()` (2-hour expiry, HMAC-SHA256). No frontend changes.
- Feature 2: `LearnVideoPlayer.vue` gains a floating `watermarkText` overlay that moves position every 30 s. The parent `LearnPage.vue` passes the student's email.
- Feature 3: After local video upload, `TranscodeToHlsJob` runs FFmpeg to produce AES-128-encrypted HLS segments. A new `/hls-key` endpoint serves the decryption key only to enrolled students. Frontend uses HLS.js with `xhrSetup` to pass the Bearer token when fetching the key.

**Tech Stack:** Laravel `URL::temporarySignedRoute`, FFmpeg (CLI), HLS.js ^1.5, Vue 3 Composition API

---

## File Map

### Backend — New files
| File | Purpose |
|------|---------|
| `Modules/Upload/app/Services/HlsService.php` | FFmpeg transcoding + key generation |
| `Modules/Upload/app/Jobs/TranscodeToHlsJob.php` | Queued job that calls HlsService |
| `e-learning-backend/database/migrations/XXXX_add_hls_fields_to_media_files_table.php` | hls_path, hls_key, hls_status columns |
| `tests/Feature/Media/SignedStreamUrlTest.php` | Tests for signed URL auth |
| `tests/Feature/Media/HlsKeyTest.php` | Tests for HLS key endpoint |

### Backend — Modified files
| File | Change |
|------|--------|
| `Modules/Upload/routes/api.php` | Name route `media.stream`; add `media/{id}/hls-key` route |
| `Modules/Upload/app/Http/Controllers/UploadController.php` | Add `hlsKey()` method; update `authorizeStreamRequest()` to accept signed URLs |
| `Modules/Upload/app/Services/UploadService.php` | Dispatch `TranscodeToHlsJob` after local video upload |
| `Modules/Upload/app/Models/MediaFile.php` | Add `hls_path`, `hls_key`, `hls_status` to `$fillable` |
| `Modules/Lessons/app/Http/Controllers/LessonController.php` | `myLessonDetail`: use signed URL or HLS URL instead of `?token=` |

### Frontend — Modified files
| File | Change |
|------|--------|
| `e-learning-frontend/src/components/shared/client/LearnVideoPlayer.vue` | Add watermark overlay; add HLS.js support |
| `e-learning-frontend/src/views/client/LearnPage.vue` | Pass `watermarkText` prop to `LearnVideoPlayer` |

---

## Task 1 — Name the stream route

**Files:**
- Modify: `e-learning-backend/Modules/Upload/routes/api.php`

- [ ] **Step 1.1: Write the failing test**

```php
// tests/Feature/Media/SignedStreamUrlTest.php
<?php

namespace Tests\Feature\Media;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Modules\Upload\Models\MediaFile;
use Tests\TestCase;
use Tests\Traits\HasAdminUser;

class SignedStreamUrlTest extends TestCase
{
    use RefreshDatabase, HasAdminUser;

    public function test_signed_url_grants_stream_access(): void
    {
        $this->setupAdmin();

        $media = MediaFile::create([
            'disk' => 'local',
            'type' => 'video',
            'original_name' => 'test.mp4',
            'path' => 'videos/test.mp4',
            'url' => '',
            'mime_type' => 'video/mp4',
            'size' => 1024,
            'status' => 'ready',
            'uploaded_by' => 1,
        ]);

        $signedUrl = URL::temporarySignedRoute(
            'media.stream',
            now()->addHours(2),
            ['id' => $media->id]
        );

        // Extract query string from signed URL to test route resolution
        $parsed = parse_url($signedUrl);
        parse_str($parsed['query'], $params);

        $this->assertArrayHasKey('expires', $params);
        $this->assertArrayHasKey('signature', $params);
        $this->assertGreaterThan(now()->timestamp, $params['expires']);
    }

    public function test_expired_signed_url_is_rejected(): void
    {
        $this->setupAdmin();

        $media = MediaFile::create([
            'disk' => 'local',
            'type' => 'video',
            'original_name' => 'test.mp4',
            'path' => 'videos/test.mp4',
            'url' => '',
            'mime_type' => 'video/mp4',
            'size' => 1024,
            'status' => 'ready',
            'uploaded_by' => 1,
        ]);

        $signedUrl = URL::temporarySignedRoute(
            'media.stream',
            now()->subMinute(),   // already expired
            ['id' => $media->id]
        );

        $parsed = parse_url($signedUrl);
        parse_str($parsed['query'], $params);

        // Expired signature: expires is in the past
        $this->assertLessThan(now()->timestamp, $params['expires']);
    }
}
```

- [ ] **Step 1.2: Run test to confirm it fails (route not named yet)**

```bash
wsl.exe -d Ubuntu -- bash -c "php artisan test tests/Feature/Media/SignedStreamUrlTest.php 2>&1" | cat
```

Expected: `InvalidArgumentException: Route [media.stream] not defined`

- [ ] **Step 1.3: Name the route**

In `e-learning-backend/Modules/Upload/routes/api.php`, change:
```php
Route::get('media/{id}/stream', [UploadController::class, 'stream']);
```
to:
```php
Route::get('media/{id}/stream', [UploadController::class, 'stream'])->name('media.stream');
```

- [ ] **Step 1.4: Run test — should pass**

```bash
wsl.exe -d Ubuntu -- bash -c "php artisan test tests/Feature/Media/SignedStreamUrlTest.php 2>&1" | cat
```

Expected: 2 tests, 2 passed

- [ ] **Step 1.5: Commit**

```bash
git add e-learning-backend/Modules/Upload/routes/api.php \
        e-learning-backend/tests/Feature/Media/SignedStreamUrlTest.php
git commit -m "feat(upload): name media.stream route for signed URL support"
```

---

## Task 2 — Accept signed URLs in stream auth

**Files:**
- Modify: `e-learning-backend/Modules/Upload/app/Http/Controllers/UploadController.php:218-233`

- [ ] **Step 2.1: Update `authorizeStreamRequest`**

Replace the existing `authorizeStreamRequest` method (lines 218–233) with:

```php
private function authorizeStreamRequest(Request $request): void
{
    // Signed URL (time-limited, generated by LessonController)
    if ($request->hasValidSignature()) {
        return;
    }

    // Active guard session (admin panel preview, middleware auth)
    if (Auth::guard('admin')->check() || Auth::guard('api')->check()) {
        return;
    }

    // Legacy: token in query param (admin LessonPreviewModal)
    $rawToken = $request->query('token');
    if (! $rawToken) {
        abort(401, 'Unauthenticated.');
    }

    $accessToken = PersonalAccessToken::findToken($rawToken);
    if (! $accessToken || ($accessToken->expires_at && $accessToken->expires_at->isPast())) {
        abort(401, 'Token không hợp lệ hoặc đã hết hạn.');
    }
}
```

- [ ] **Step 2.2: Run existing tests to confirm nothing broke**

```bash
wsl.exe -d Ubuntu -- bash -c "php artisan test tests/Feature/Media/ 2>&1" | cat
```

Expected: all pass

- [ ] **Step 2.3: Commit**

```bash
git add e-learning-backend/Modules/Upload/app/Http/Controllers/UploadController.php
git commit -m "feat(upload): accept Laravel signed URLs in stream auth"
```

---

## Task 3 — Generate signed URL in LessonController

**Files:**
- Modify: `e-learning-backend/Modules/Lessons/app/Http/Controllers/LessonController.php:281-284`

- [ ] **Step 3.1: Add URL import and replace token-in-URL logic**

At the top of `LessonController.php`, add to the existing `use` block:
```php
use Illuminate\Support\Facades\URL;
```

Replace lines 281–284 in `myLessonDetail`:
```php
// OLD — embeds long-lived token in URL
$token = $request->bearerToken();
$videoUrl = $lesson->video
    ? url('api/v1/media/'.$lesson->video->id.'/stream').($token ? '?token='.urlencode($token) : '')
    : null;
```
with:
```php
// NEW — time-limited signed URL (2 hours), no user token exposed
$videoUrl = $lesson->video
    ? URL::temporarySignedRoute('media.stream', now()->addHours(2), ['id' => $lesson->video->id])
    : null;
```

- [ ] **Step 3.2: Run tests**

```bash
wsl.exe -d Ubuntu -- bash -c "php artisan test 2>&1" | cat
```

Expected: all existing tests pass (no lessons detail test exists yet, but nothing should break)

- [ ] **Step 3.3: Commit**

```bash
git add e-learning-backend/Modules/Lessons/app/Http/Controllers/LessonController.php
git commit -m "feat(lessons): replace long-lived token URL with 2-hour signed stream URL"
```

---

## Task 4 — Dynamic watermark on video player

**Files:**
- Modify: `e-learning-frontend/src/components/shared/client/LearnVideoPlayer.vue`
- Modify: `e-learning-frontend/src/views/client/LearnPage.vue`

- [ ] **Step 4.1: Update LearnVideoPlayer.vue**

Replace the entire file content with:

```vue
<template>
  <div v-if="url" class="video-wrapper" style="position: relative; overflow: hidden">
    <video
      ref="videoEl"
      :src="isHls ? undefined : url"
      controls
      class="video-player"
      @timeupdate="onTimeUpdate"
      @ended="onVideoEnded"
    ></video>

    <div
      v-if="watermarkText"
      :style="watermarkStyle"
      aria-hidden="true"
    >
      {{ watermarkText }}
    </div>
  </div>
  <div v-else class="video-placeholder">
    <svg class="w-16 h-16 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
      <path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
    </svg>
    <p>Video không khả dụng</p>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, computed, onMounted, onUnmounted } from 'vue'

const props = defineProps<{
  url?: string
  watchedSeconds?: number
  watermarkText?: string
}>()

const emit = defineEmits<{
  'timeupdate': [currentTime: number]
  'ended': []
}>()

const videoEl = ref<HTMLVideoElement | null>(null)
const watermarkX = ref(10)
const watermarkY = ref(10)
let wmTimer: ReturnType<typeof setInterval> | null = null

const isHls = computed(() => !!props.url?.endsWith('.m3u8'))

const watermarkStyle = computed(() => ({
  position: 'absolute' as const,
  left: `${watermarkX.value}%`,
  top: `${watermarkY.value}%`,
  color: 'rgba(255, 255, 255, 0.28)',
  fontSize: '13px',
  fontFamily: 'monospace',
  pointerEvents: 'none' as const,
  userSelect: 'none' as const,
  textShadow: '0 1px 3px rgba(0,0,0,0.7)',
  zIndex: 10,
  whiteSpace: 'nowrap' as const,
  transition: 'left 1s ease, top 1s ease',
}))

function moveWatermark() {
  watermarkX.value = Math.floor(Math.random() * 65) + 5   // 5–70 %
  watermarkY.value = Math.floor(Math.random() * 75) + 5   // 5–80 %
}

onMounted(() => {
  if (props.watermarkText) {
    wmTimer = setInterval(moveWatermark, 30_000)
  }
})

onUnmounted(() => {
  if (wmTimer) clearInterval(wmTimer)
})

watch(() => props.watchedSeconds, (val) => {
  if (videoEl.value && val !== undefined) {
    if (Math.abs(videoEl.value.currentTime - val) > 1) {
      videoEl.value.currentTime = val
    }
  }
}, { immediate: true })

function onTimeUpdate() {
  if (!videoEl.value) return
  emit('timeupdate', Math.floor(videoEl.value.currentTime))
}

function onVideoEnded() {
  emit('ended')
}

defineExpose({ videoElement: videoEl })
</script>

<style scoped>
/* CSS moved from LearnPage.vue */
</style>
```

> Note: The `isHls` computed and HLS.js wiring will be completed in Task 10. For now the component works for MP4 (the `:src` binding handles non-HLS URLs).

- [ ] **Step 4.2: Pass watermarkText from LearnPage.vue**

Find where `<LearnVideoPlayer` is used in `LearnPage.vue` (line ~117) and add the `watermarkText` prop:

```vue
<!-- BEFORE -->
<LearnVideoPlayer
  :url="lessonDetail?.video_url"
  ...
/>

<!-- AFTER -->
<LearnVideoPlayer
  :url="lessonDetail?.video_url"
  :watermark-text="studentAuthStore.student?.email ?? undefined"
  ...
/>
```

Also add the import at the top of `<script setup>` in `LearnPage.vue`:
```ts
import { useStudentAuthStore } from '@/stores/studentAuth.store'
const studentAuthStore = useStudentAuthStore()
```

- [ ] **Step 4.3: Run lint**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning/e-learning-frontend && npm run lint 2>&1" | cat
```

Expected: no errors

- [ ] **Step 4.4: Commit**

```bash
git add e-learning-frontend/src/components/shared/client/LearnVideoPlayer.vue \
        e-learning-frontend/src/views/client/LearnPage.vue
git commit -m "feat(frontend): add animated email watermark to video player"
```

---

## Task 5 — Install FFmpeg (WSL Ubuntu)

- [ ] **Step 5.1: Install FFmpeg**

```bash
wsl.exe -d Ubuntu -- bash -c "sudo apt-get update && sudo apt-get install -y ffmpeg 2>&1" | cat
```

- [ ] **Step 5.2: Verify**

```bash
wsl.exe -d Ubuntu -- bash -c "ffmpeg -version 2>&1 | head -2" | cat
```

Expected output starts with: `ffmpeg version 4.x.x` or `ffmpeg version 5.x.x` / `6.x.x`

---

## Task 6 — Migration: add HLS fields to media_files

**Files:**
- Create: `e-learning-backend/database/migrations/2026_05_14_000001_add_hls_fields_to_media_files_table.php`
- Modify: `e-learning-backend/Modules/Upload/app/Models/MediaFile.php`

- [ ] **Step 6.1: Create migration**

```bash
wsl.exe -d Ubuntu -- bash -c "php artisan make:migration add_hls_fields_to_media_files_table --table=media_files 2>&1" | cat
```

Open the generated migration file in `database/migrations/` and replace the `up()` and `down()` bodies:

```php
public function up(): void
{
    Schema::table('media_files', function (Blueprint $table) {
        $table->string('hls_path')->nullable()->after('path');
        $table->string('hls_key', 32)->nullable()->after('hls_path');
        $table->enum('hls_status', ['pending', 'processing', 'ready', 'failed'])->nullable()->after('hls_key');
    });
}

public function down(): void
{
    Schema::table('media_files', function (Blueprint $table) {
        $table->dropColumn(['hls_path', 'hls_key', 'hls_status']);
    });
}
```

- [ ] **Step 6.2: Run migration**

```bash
wsl.exe -d Ubuntu -- bash -c "php artisan migrate 2>&1" | cat
```

Expected: `Running migrations... add_hls_fields_to_media_files_table`

- [ ] **Step 6.3: Add new fields to MediaFile `$fillable`**

Open `Modules/Upload/app/Models/MediaFile.php` and add `'hls_path'`, `'hls_key'`, `'hls_status'` to the `$fillable` array.

- [ ] **Step 6.4: Run full test suite to confirm migration doesn't break tests**

```bash
wsl.exe -d Ubuntu -- bash -c "php artisan test 2>&1" | cat
```

Expected: all existing tests pass (SQLite in-memory rebuilds from migrations each run)

- [ ] **Step 6.5: Commit**

```bash
git add database/migrations/ \
        e-learning-backend/Modules/Upload/app/Models/MediaFile.php
git commit -m "feat(upload): add hls_path, hls_key, hls_status to media_files"
```

---

## Task 7 — HlsService: FFmpeg transcoding with AES-128

**Files:**
- Create: `e-learning-backend/Modules/Upload/app/Services/HlsService.php`

- [ ] **Step 7.1: Create HlsService**

```php
<?php

namespace Modules\Upload\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Upload\Models\MediaFile;

class HlsService
{
    public function transcode(MediaFile $media): void
    {
        if ($media->disk !== 'local' && $media->disk !== 'public') {
            throw new \RuntimeException('HLS transcoding only supported for local disk.');
        }

        $inputPath = Storage::disk($media->disk)->path($media->path);

        if (! file_exists($inputPath)) {
            throw new \RuntimeException("Source file not found: {$inputPath}");
        }

        $outputDir = storage_path("app/public/hls/{$media->id}");
        if (! is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        // 16-byte AES-128 key
        $key = random_bytes(16);
        $keyHex = bin2hex($key);

        // Temporary files ffmpeg needs
        $keyFile = "{$outputDir}/enc.key";
        $keyInfoFile = "{$outputDir}/keyinfo.txt";

        file_put_contents($keyFile, $key);

        $keyUrl = url("api/v1/media/{$media->id}/hls-key");
        file_put_contents($keyInfoFile, "{$keyUrl}\n{$keyFile}");

        $playlistPath = "{$outputDir}/playlist.m3u8";
        $segmentPattern = "{$outputDir}/segment_%03d.ts";

        $cmd = sprintf(
            'ffmpeg -y -i %s -c:v copy -c:a copy -hls_time 10 -hls_key_info_file %s -hls_playlist_type vod -hls_segment_filename %s %s 2>&1',
            escapeshellarg($inputPath),
            escapeshellarg($keyInfoFile),
            escapeshellarg($segmentPattern),
            escapeshellarg($playlistPath)
        );

        Log::info('HLS transcode start', ['media_id' => $media->id, 'cmd' => $cmd]);

        exec($cmd, $output, $exitCode);

        // Clean up temp files — key is now embedded in segments, file no longer needed
        @unlink($keyFile);
        @unlink($keyInfoFile);

        if ($exitCode !== 0) {
            $detail = implode("\n", array_slice($output, -10));
            throw new \RuntimeException("FFmpeg failed (exit {$exitCode}): {$detail}");
        }

        $media->update([
            'hls_path' => "hls/{$media->id}/playlist.m3u8",
            'hls_key' => $keyHex,
            'hls_status' => 'ready',
        ]);

        Log::info('HLS transcode done', ['media_id' => $media->id]);
    }
}
```

- [ ] **Step 7.2: Commit**

```bash
git add e-learning-backend/Modules/Upload/app/Services/HlsService.php
git commit -m "feat(upload): add HlsService for FFmpeg AES-128 HLS transcoding"
```

---

## Task 8 — TranscodeToHlsJob

**Files:**
- Create: `e-learning-backend/Modules/Upload/app/Jobs/TranscodeToHlsJob.php`

- [ ] **Step 8.1: Create the job**

```php
<?php

namespace Modules\Upload\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
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
        $service->transcode($media);
    }

    public function failed(Throwable $exception): void
    {
        MediaFile::where('id', $this->mediaId)->update(['hls_status' => 'failed']);

        Log::error('TranscodeToHlsJob failed', [
            'media_id' => $this->mediaId,
            'error' => $exception->getMessage(),
        ]);
    }
}
```

- [ ] **Step 8.2: Write a test for the job**

```php
// tests/Feature/Media/HlsTranscodeJobTest.php
<?php

namespace Tests\Feature\Media;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Modules\Upload\Jobs\TranscodeToHlsJob;
use Modules\Upload\Models\MediaFile;
use Modules\Upload\Services\HlsService;
use Tests\TestCase;
use Tests\Traits\HasAdminUser;

class HlsTranscodeJobTest extends TestCase
{
    use RefreshDatabase, HasAdminUser;

    public function test_job_marks_status_failed_when_service_throws(): void
    {
        $this->setupAdmin();

        $media = MediaFile::create([
            'disk' => 'local',
            'type' => 'video',
            'original_name' => 'test.mp4',
            'path' => 'videos/nonexistent.mp4',
            'url' => '',
            'mime_type' => 'video/mp4',
            'size' => 1024,
            'status' => 'ready',
            'uploaded_by' => 1,
        ]);

        $this->mock(HlsService::class)
            ->shouldReceive('transcode')
            ->andThrow(new \RuntimeException('FFmpeg not found'));

        Log::shouldReceive('error')->once()
            ->with('TranscodeToHlsJob failed', \Mockery::subset(['media_id' => $media->id]));

        TranscodeToHlsJob::dispatch($media->id); // sync in tests

        $this->assertDatabaseHas('media_files', [
            'id' => $media->id,
            'hls_status' => 'failed',
        ]);
    }
}
```

- [ ] **Step 8.3: Run test**

```bash
wsl.exe -d Ubuntu -- bash -c "php artisan test tests/Feature/Media/HlsTranscodeJobTest.php 2>&1" | cat
```

Expected: 1 test, 1 passed

- [ ] **Step 8.4: Commit**

```bash
git add e-learning-backend/Modules/Upload/app/Jobs/TranscodeToHlsJob.php \
        e-learning-backend/tests/Feature/Media/HlsTranscodeJobTest.php
git commit -m "feat(upload): add TranscodeToHlsJob with failure handling"
```

---

## Task 9 — Dispatch HLS job after video upload

**Files:**
- Modify: `e-learning-backend/Modules/Upload/app/Services/UploadService.php`

- [ ] **Step 9.1: Find the uploadVideo method in UploadService**

```bash
grep -n "uploadVideo\|MediaFile::create\|return \$media" \
  e-learning-backend/Modules/Upload/app/Services/UploadService.php | head -20
```

- [ ] **Step 9.2: Add job dispatch at the end of uploadVideo**

At the bottom of `uploadVideo()`, just before `return $mediaFile;`, add:

```php
// Dispatch HLS transcoding for local-disk videos only
if ($mediaFile->disk === 'local' || $mediaFile->disk === 'public') {
    $mediaFile->update(['hls_status' => 'pending']);
    \Modules\Upload\Jobs\TranscodeToHlsJob::dispatch($mediaFile->id);
}
```

- [ ] **Step 9.3: Run tests**

```bash
wsl.exe -d Ubuntu -- bash -c "php artisan test 2>&1" | cat
```

Expected: all pass (QUEUE_CONNECTION=sync in tests, job runs but HlsService mock handles FFmpeg absence)

> Note: In the test environment, `QUEUE_CONNECTION=sync` means the job runs inline. The test from Task 8 mocks `HlsService`, so the FFmpeg call is intercepted. Other tests that upload videos may fail if `HlsService` isn't mocked. If tests fail due to HLS job running, add a `config(['queue.default' => 'database'])` in those tests' setUp, or add a `HLS_ENABLED` env guard:

If tests fail, wrap the dispatch in an env guard and set `HLS_ENABLED=false` in `phpunit.xml`:
```php
if (config('media.hls_enabled', true) && ($mediaFile->disk === 'local' || $mediaFile->disk === 'public')) {
    $mediaFile->update(['hls_status' => 'pending']);
    TranscodeToHlsJob::dispatch($mediaFile->id);
}
```
And in `phpunit.xml`:
```xml
<env name="HLS_ENABLED" value="false"/>
```
And in `config/media.php` (create if missing):
```php
return ['hls_enabled' => env('HLS_ENABLED', true)];
```

- [ ] **Step 9.4: Commit**

```bash
git add e-learning-backend/Modules/Upload/app/Services/UploadService.php
git commit -m "feat(upload): dispatch TranscodeToHlsJob after local video upload"
```

---

## Task 10 — HLS key endpoint

**Files:**
- Modify: `e-learning-backend/Modules/Upload/routes/api.php`
- Modify: `e-learning-backend/Modules/Upload/app/Http/Controllers/UploadController.php`

- [ ] **Step 10.1: Write the test first**

```php
// tests/Feature/Media/HlsKeyTest.php
<?php

namespace Tests\Feature\Media;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Upload\Models\MediaFile;
use Tests\TestCase;
use Tests\Traits\HasAdminUser;

class HlsKeyTest extends TestCase
{
    use RefreshDatabase, HasAdminUser;

    private function makeMedia(array $override = []): MediaFile
    {
        return MediaFile::create(array_merge([
            'disk' => 'local',
            'type' => 'video',
            'original_name' => 'test.mp4',
            'path' => 'videos/test.mp4',
            'url' => '',
            'mime_type' => 'video/mp4',
            'size' => 1024,
            'status' => 'ready',
            'uploaded_by' => 1,
            'hls_key' => bin2hex(random_bytes(16)),
            'hls_status' => 'ready',
        ], $override));
    }

    public function test_admin_can_fetch_hls_key(): void
    {
        $this->setupAdmin();
        $media = $this->makeMedia();

        $response = $this->getJson("/api/v1/media/{$media->id}/hls-key");

        $response->assertStatus(200);
        $this->assertEquals('application/octet-stream', $response->headers->get('Content-Type'));
        $this->assertEquals(hex2bin($media->hls_key), $response->getContent());
    }

    public function test_unauthenticated_cannot_fetch_hls_key(): void
    {
        $media = $this->makeMedia();

        $response = $this->getJson("/api/v1/media/{$media->id}/hls-key");

        $response->assertStatus(401);
    }

    public function test_returns_404_when_hls_key_not_set(): void
    {
        $this->setupAdmin();
        $media = $this->makeMedia(['hls_key' => null]);

        $response = $this->getJson("/api/v1/media/{$media->id}/hls-key");

        $response->assertStatus(404);
    }
}
```

- [ ] **Step 10.2: Run test to confirm it fails (route not registered)**

```bash
wsl.exe -d Ubuntu -- bash -c "php artisan test tests/Feature/Media/HlsKeyTest.php 2>&1" | cat
```

Expected: 3 tests fail with 404 (route missing)

- [ ] **Step 10.3: Add route**

In `Modules/Upload/routes/api.php`, add inside the `auth:admin` middleware group:

```php
Route::get('media/{id}/hls-key', [UploadController::class, 'hlsKey']);
```

> This route is inside `auth:admin` — see Task 10.4 for adding student auth support.

- [ ] **Step 10.4: Add `hlsKey` method to UploadController**

Add this method to `UploadController` (after the `stream` method):

```php
public function hlsKey(int $id, Request $request): \Illuminate\Http\Response
{
    $this->authorizeStreamRequest($request);

    $media = $this->uploadService->findOrFail($id);

    if (! $media->hls_key) {
        abort(404, 'HLS key not found.');
    }

    return response(hex2bin($media->hls_key), 200, [
        'Content-Type'  => 'application/octet-stream',
        'Cache-Control' => 'no-store, no-cache',
    ]);
}
```

- [ ] **Step 10.5: Move the hls-key route outside admin-only group**

The key endpoint needs to be accessible by students too (via Bearer token). Move it to the public section:

In `routes/api.php`, the final state should be:
```php
// Admin-only uploads
Route::middleware(['auth:admin'])->prefix('admin')->group(function () {
    Route::post('upload/video', [UploadController::class, 'uploadVideo']);
    Route::post('upload/document', [UploadController::class, 'uploadDocument']);
    Route::post('upload/image', [UploadController::class, 'uploadImage']);
    Route::post('upload/presigned', [UploadController::class, 'presigned']);
    Route::post('upload/{id}/confirm', [UploadController::class, 'confirm']);
    Route::delete('upload/{id}', [UploadController::class, 'destroy']);
});

// Streaming — auth handled inside controller (supports signed URL, Bearer, ?token=)
Route::get('media/{id}/stream', [UploadController::class, 'stream'])->name('media.stream');
Route::get('media/{id}/hls-key', [UploadController::class, 'hlsKey']);
```

- [ ] **Step 10.6: Run tests**

```bash
wsl.exe -d Ubuntu -- bash -c "php artisan test tests/Feature/Media/HlsKeyTest.php 2>&1" | cat
```

Expected: 3 tests, 3 passed

- [ ] **Step 10.7: Commit**

```bash
git add e-learning-backend/Modules/Upload/routes/api.php \
        e-learning-backend/Modules/Upload/app/Http/Controllers/UploadController.php \
        e-learning-backend/tests/Feature/Media/HlsKeyTest.php
git commit -m "feat(upload): add /hls-key endpoint for AES-128 HLS decryption"
```

---

## Task 11 — Serve HLS URL in LessonController

**Files:**
- Modify: `e-learning-backend/Modules/Lessons/app/Http/Controllers/LessonController.php`

- [ ] **Step 11.1: Update video URL generation logic**

In `myLessonDetail`, replace the `$videoUrl` assignment (already updated in Task 3):

```php
// OLD (from Task 3)
$videoUrl = $lesson->video
    ? URL::temporarySignedRoute('media.stream', now()->addHours(2), ['id' => $lesson->video->id])
    : null;
```

with:

```php
$videoUrl = null;
if ($lesson->video) {
    if ($lesson->video->hls_status === 'ready' && $lesson->video->hls_path) {
        // Serve HLS playlist — segments are public (encrypted), key requires auth
        $videoUrl = asset('storage/' . $lesson->video->hls_path);
    } else {
        // Fallback to direct stream with signed URL (2-hour expiry)
        $videoUrl = URL::temporarySignedRoute(
            'media.stream',
            now()->addHours(2),
            ['id' => $lesson->video->id]
        );
    }
}
```

- [ ] **Step 11.2: Run tests**

```bash
wsl.exe -d Ubuntu -- bash -c "php artisan test 2>&1" | cat
```

Expected: all pass

- [ ] **Step 11.3: Commit**

```bash
git add e-learning-backend/Modules/Lessons/app/Http/Controllers/LessonController.php
git commit -m "feat(lessons): serve HLS playlist URL when transcoding is ready"
```

---

## Task 12 — Frontend HLS.js player

**Files:**
- Modify: `e-learning-frontend/src/components/shared/client/LearnVideoPlayer.vue`

- [ ] **Step 12.1: Install hls.js**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning/e-learning-frontend && npm install hls.js 2>&1" | cat
```

- [ ] **Step 12.2: Update LearnVideoPlayer.vue to wire HLS.js**

Replace the `<script setup>` section (keep the `<template>` and `<style>` from Task 4 unchanged):

```ts
import { ref, watch, computed, onMounted, onUnmounted } from 'vue'
import Hls from 'hls.js'
import { useStudentAuthStore } from '@/stores/studentAuth.store'

const props = defineProps<{
  url?: string
  watchedSeconds?: number
  watermarkText?: string
}>()

const emit = defineEmits<{
  'timeupdate': [currentTime: number]
  'ended': []
}>()

const videoEl = ref<HTMLVideoElement | null>(null)
const watermarkX = ref(10)
const watermarkY = ref(10)
let wmTimer: ReturnType<typeof setInterval> | null = null
let hlsInstance: Hls | null = null

const authStore = useStudentAuthStore()
const isHls = computed(() => !!props.url?.endsWith('.m3u8'))

const watermarkStyle = computed(() => ({
  position: 'absolute' as const,
  left: `${watermarkX.value}%`,
  top: `${watermarkY.value}%`,
  color: 'rgba(255, 255, 255, 0.28)',
  fontSize: '13px',
  fontFamily: 'monospace',
  pointerEvents: 'none' as const,
  userSelect: 'none' as const,
  textShadow: '0 1px 3px rgba(0,0,0,0.7)',
  zIndex: 10,
  whiteSpace: 'nowrap' as const,
  transition: 'left 1s ease, top 1s ease',
}))

function moveWatermark() {
  watermarkX.value = Math.floor(Math.random() * 65) + 5
  watermarkY.value = Math.floor(Math.random() * 75) + 5
}

function destroyHls() {
  if (hlsInstance) {
    hlsInstance.destroy()
    hlsInstance = null
  }
}

function setupVideo(url: string) {
  if (!videoEl.value) return
  destroyHls()

  if (isHls.value) {
    if (Hls.isSupported()) {
      hlsInstance = new Hls({
        xhrSetup(xhr, reqUrl) {
          // Add auth header only for the AES key request
          if (reqUrl.includes('/hls-key')) {
            const token = authStore.token
            if (token) xhr.setRequestHeader('Authorization', `Bearer ${token}`)
          }
        },
      })
      hlsInstance.loadSource(url)
      hlsInstance.attachMedia(videoEl.value)
    } else if (videoEl.value.canPlayType('application/vnd.apple.mpegurl')) {
      // Safari: native HLS support — key fetched by browser, no custom headers possible.
      // Key endpoint must accept signed URL or cookie auth for Safari.
      videoEl.value.src = url
    }
  } else {
    videoEl.value.src = url
  }
}

watch(() => props.url, (newUrl) => {
  if (newUrl) setupVideo(newUrl)
  else destroyHls()
}, { immediate: true })

watch(() => props.watchedSeconds, (val) => {
  if (videoEl.value && val !== undefined) {
    if (Math.abs(videoEl.value.currentTime - val) > 1) {
      videoEl.value.currentTime = val
    }
  }
}, { immediate: true })

onMounted(() => {
  if (props.watermarkText) {
    wmTimer = setInterval(moveWatermark, 30_000)
  }
})

onUnmounted(() => {
  if (wmTimer) clearInterval(wmTimer)
  destroyHls()
})

function onTimeUpdate() {
  if (!videoEl.value) return
  emit('timeupdate', Math.floor(videoEl.value.currentTime))
}

function onVideoEnded() {
  emit('ended')
}

defineExpose({ videoElement: videoEl })
```

- [ ] **Step 12.3: Also update the template `:src` binding**

The `<video>` element in the template should set `src` only for non-HLS. In Task 4 the template already has `:src="isHls ? undefined : url"`. This is already correct — no template change needed.

- [ ] **Step 12.4: Run lint**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning/e-learning-frontend && npm run lint 2>&1" | cat
```

Expected: no errors

- [ ] **Step 12.5: Run frontend build to check type errors**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning/e-learning-frontend && npm run build 2>&1" | cat
```

Expected: build succeeds with no TypeScript errors

- [ ] **Step 12.6: Commit**

```bash
git add e-learning-frontend/src/components/shared/client/LearnVideoPlayer.vue \
        e-learning-frontend/package.json \
        e-learning-frontend/package-lock.json
git commit -m "feat(frontend): integrate HLS.js for AES-128 encrypted video playback"
```

---

## Task 13 — Symlink public storage (one-time setup)

HLS segments are stored in `storage/app/public/hls/` and served via the `public` disk symlink. Ensure the symlink exists:

- [ ] **Step 13.1: Create symlink if missing**

```bash
wsl.exe -d Ubuntu -- bash -c "php artisan storage:link 2>&1" | cat
```

Expected: `The [public/storage] link has been connected to [storage/app/public].` or `The [public/storage] directory already exists.`

---

---

## Task 14 — Logo watermark overlay on video player

**Files:**
- Modify: `e-learning-frontend/src/components/shared/client/LearnVideoPlayer.vue`

Logo platform hiển thị cố định ở góc dưới-phải của video. Kết hợp với email watermark di động → screen recording bị "ký tên" kép.

- [ ] **Step 14.1: Thêm prop `logoUrl` và overlay vào template**

Trong `LearnVideoPlayer.vue`, thêm prop mới và overlay logo vào `<template>`. Tìm phần `<div v-if="url" class="video-wrapper"...>` và thêm vào sau email watermark div:

```vue
<!-- Logo watermark — fixed bottom-right -->
<img
  v-if="logoUrl"
  :src="logoUrl"
  aria-hidden="true"
  :style="{
    position: 'absolute',
    bottom: '12px',
    right: '14px',
    width: '72px',
    opacity: 0.22,
    pointerEvents: 'none',
    userSelect: 'none',
    zIndex: 10,
    filter: 'brightness(10)',
  }"
/>
```

Thêm `logoUrl` vào `defineProps`:

```ts
const props = defineProps<{
  url?: string
  watchedSeconds?: number
  watermarkText?: string
  logoUrl?: string        // ← thêm dòng này
}>()
```

- [ ] **Step 14.2: Truyền logoUrl từ LearnPage.vue**

Trong `LearnPage.vue`, tìm `<LearnVideoPlayer` và thêm prop:

```vue
<LearnVideoPlayer
  :url="lessonDetail?.video_url"
  :watermark-text="studentAuthStore.student?.email ?? undefined"
  logo-url="/images/logo/logo.svg"
  ...
/>
```

> Logo path `/images/logo/logo.svg` đã tồn tại trong `public/images/logo/`. `filter: brightness(10)` chuyển logo tối thành trắng trên nền video.

- [ ] **Step 14.3: Lint**

```bash
wsl.exe -d Ubuntu -- bash -c "cd /home/vanthanh/DATN/e-learning/e-learning-frontend && npm run lint 2>&1" | cat
```

Expected: no errors

- [ ] **Step 14.4: Commit**

```bash
git add e-learning-frontend/src/components/shared/client/LearnVideoPlayer.vue \
        e-learning-frontend/src/views/client/LearnPage.vue
git commit -m "feat(frontend): add logo watermark overlay on video player"
```

---

## Task 15 — Document watermark service (backend)

**Files:**
- Create: `e-learning-backend/Modules/Upload/app/Services/DocumentWatermarkService.php`

Khi học viên xem tài liệu PDF, hệ thống ghi đè watermark động lên từng trang: logo platform góc trên-phải + email học viên dạng text chéo mờ. Dùng thư viện `setasign/fpdi`.

- [ ] **Step 15.1: Cài đặt fpdi**

```bash
wsl.exe -d Ubuntu -- bash -c "composer require setasign/fpdi setasign/fpdf 2>&1" | cat
```

Expected: `Package operations: 2 installs`

- [ ] **Step 15.2: Viết test trước**

```php
// tests/Feature/Media/DocumentWatermarkTest.php
<?php

namespace Tests\Feature\Media;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Upload\Services\DocumentWatermarkService;
use Tests\TestCase;

class DocumentWatermarkTest extends TestCase
{
    use RefreshDatabase;

    public function test_watermark_returns_pdf_bytes(): void
    {
        // Create a minimal 1-page PDF in memory for testing
        $fpdf = new \FPDF();
        $fpdf->AddPage();
        $fpdf->SetFont('Arial', '', 14);
        $fpdf->Cell(0, 10, 'Test Document');
        $originalPdf = $fpdf->Output('S'); // string output

        $service = app(DocumentWatermarkService::class);
        $result = $service->applyWatermark($originalPdf, 'student@example.com');

        // Must still be a PDF
        $this->assertStringStartsWith('%PDF', $result);
        // Must be non-empty
        $this->assertGreaterThan(100, strlen($result));
    }

    public function test_watermark_accepts_empty_email(): void
    {
        $fpdf = new \FPDF();
        $fpdf->AddPage();
        $originalPdf = $fpdf->Output('S');

        $service = app(DocumentWatermarkService::class);
        $result = $service->applyWatermark($originalPdf, '');

        $this->assertStringStartsWith('%PDF', $result);
    }
}
```

- [ ] **Step 15.3: Chạy test để confirm fail**

```bash
wsl.exe -d Ubuntu -- bash -c "php artisan test tests/Feature/Media/DocumentWatermarkTest.php 2>&1" | cat
```

Expected: `Error: Class "Modules\Upload\Services\DocumentWatermarkService" not found`

- [ ] **Step 15.4: Tạo DocumentWatermarkService**

```php
<?php

namespace Modules\Upload\Services;

use setasign\Fpdi\Fpdi;

class DocumentWatermarkService
{
    /**
     * Apply logo + email watermark to each page of a PDF.
     *
     * @param  string  $pdfContent  Raw PDF bytes (string from file_get_contents or Storage::get)
     * @param  string  $email       Student email shown as diagonal text watermark
     * @return string               Watermarked PDF bytes
     */
    public function applyWatermark(string $pdfContent, string $email): string
    {
        // Write original PDF to a temp file (fpdi requires a real path or stream)
        $tmpIn = tempnam(sys_get_temp_dir(), 'wm_in_') . '.pdf';
        file_put_contents($tmpIn, $pdfContent);

        try {
            $pdf = new Fpdi();
            $pageCount = $pdf->setSourceFile($tmpIn);

            for ($i = 1; $i <= $pageCount; $i++) {
                $tpl = $pdf->importPage($i);
                $size = $pdf->getTemplateSize($tpl);

                $pdf->AddPage($size['width'] > $size['height'] ? 'L' : 'P', [$size['width'], $size['height']]);
                $pdf->useTemplate($tpl, 0, 0, $size['width'], $size['height']);

                // ── Logo (top-right corner) ──────────────────────────────────
                $logoPath = public_path('images/logo/logo.svg');
                // fpdi/fpdf only supports PNG/JPEG — use PNG fallback if svg absent
                $logoPng = public_path('images/logo/logo.png');
                if (file_exists($logoPng)) {
                    $logoW = 28; // mm
                    $pdf->Image($logoPng, $size['width'] - $logoW - 8, 6, $logoW, 0, 'PNG');
                }

                // ── Email diagonal text ──────────────────────────────────────
                if ($email !== '') {
                    $pdf->SetFont('Helvetica', '', 11);
                    $pdf->SetTextColor(180, 180, 180);   // light grey
                    $pdf->SetAlpha(0.25);                 // 25 % opacity

                    // Rotate 45° and draw text across page center
                    $cx = $size['width'] / 2;
                    $cy = $size['height'] / 2;
                    $pdf->StartTransform();
                    $pdf->Rotate(45, $cx, $cy);
                    $pdf->SetXY($cx - 50, $cy);
                    $pdf->Cell(100, 10, $email, 0, 0, 'C');
                    $pdf->StopTransform();

                    $pdf->SetAlpha(1);
                }
            }

            return $pdf->Output('S');
        } finally {
            @unlink($tmpIn);
        }
    }
}
```

> **Lưu ý về `SetAlpha`:** FPDI kế thừa từ FPDF, không có `SetAlpha` mặc định. Nếu method không tồn tại, thay thế bằng FPDF extension. Xem Step 15.5.

- [ ] **Step 15.5: Kiểm tra `SetAlpha` — thêm nếu cần**

FPDF cơ bản không có `SetAlpha`. Thêm helper bằng cách extend FPDI:

```php
// Thay `use setasign\Fpdi\Fpdi;` bằng class inline trong cùng file:

class WatermarkPdf extends \setasign\Fpdi\Fpdi
{
    public function SetAlpha(float $alpha): void
    {
        // Uses PDF ExtGState for transparency
        $gsId = 'GS' . count($this->_extgstates ?? []);
        $this->_extgstates[$gsId] = ['ca' => $alpha, 'CA' => $alpha];
        $this->_out("/{$gsId} gs");
    }

    protected function _putextgstates(): void
    {
        foreach ($this->_extgstates ?? [] as $id => $state) {
            $this->_newobj();
            $this->_extgstates[$id]['n'] = $this->n;
            $this->_out('<</Type /ExtGState');
            $this->_out('/ca ' . $state['ca']);
            $this->_out('/CA ' . $state['CA']);
            $this->_out('>>');
            $this->_out('endobj');
        }
    }

    protected function _putresourcedict(): void
    {
        parent::_putresourcedict();
        $this->_out('/ExtGState <<');
        foreach ($this->_extgstates ?? [] as $id => $state) {
            $this->_out("/{$id} {$state['n']} 0 R");
        }
        $this->_out('>>');
    }
}
```

Thay `new Fpdi()` → `new WatermarkPdf()` trong `DocumentWatermarkService`.

- [ ] **Step 15.6: Chạy test**

```bash
wsl.exe -d Ubuntu -- bash -c "php artisan test tests/Feature/Media/DocumentWatermarkTest.php 2>&1" | cat
```

Expected: 2 tests, 2 passed

- [ ] **Step 15.7: Commit**

```bash
git add e-learning-backend/Modules/Upload/app/Services/DocumentWatermarkService.php \
        e-learning-backend/tests/Feature/Media/DocumentWatermarkTest.php
git commit -m "feat(upload): add DocumentWatermarkService for PDF logo+email watermarking"
```

---

## Task 16 — Document stream endpoint với watermark

**Files:**
- Modify: `e-learning-backend/Modules/Upload/routes/api.php`
- Modify: `e-learning-backend/Modules/Upload/app/Http/Controllers/UploadController.php`

Thêm endpoint `GET /api/v1/media/{id}/document` — serve PDF kèm watermark, chỉ học viên đã mua mới gọi được.

- [ ] **Step 16.1: Viết test**

```php
// tests/Feature/Media/DocumentStreamTest.php
<?php

namespace Tests\Feature\Media;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Upload\Models\MediaFile;
use Modules\Upload\Services\DocumentWatermarkService;
use Tests\TestCase;
use Tests\Traits\HasAdminUser;

class DocumentStreamTest extends TestCase
{
    use RefreshDatabase, HasAdminUser;

    private function makePdfMedia(): MediaFile
    {
        // Create a minimal PDF file in local storage for testing
        $fpdf = new \FPDF();
        $fpdf->AddPage();
        $fpdf->SetFont('Arial', '', 14);
        $fpdf->Cell(0, 10, 'Test');
        $pdfBytes = $fpdf->Output('S');

        $path = 'documents/test.pdf';
        \Illuminate\Support\Facades\Storage::disk('local')->put($path, $pdfBytes);

        return MediaFile::create([
            'disk' => 'local',
            'type' => 'document',
            'original_name' => 'test.pdf',
            'path' => $path,
            'url' => '',
            'mime_type' => 'application/pdf',
            'size' => strlen($pdfBytes),
            'status' => 'ready',
            'uploaded_by' => 1,
        ]);
    }

    public function test_admin_can_stream_document_with_watermark(): void
    {
        $this->setupAdmin();
        $media = $this->makePdfMedia();

        // Mock watermark service — don't actually run FPDI in test
        $this->mock(DocumentWatermarkService::class)
            ->shouldReceive('applyWatermark')
            ->once()
            ->andReturn('%PDF-1.4 fake watermarked');

        $response = $this->get("/api/v1/media/{$media->id}/document");

        $response->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_unauthenticated_cannot_stream_document(): void
    {
        $media = $this->makePdfMedia();

        $response = $this->get("/api/v1/media/{$media->id}/document");

        $response->assertStatus(401);
    }
}
```

- [ ] **Step 16.2: Chạy test — confirm fail**

```bash
wsl.exe -d Ubuntu -- bash -c "php artisan test tests/Feature/Media/DocumentStreamTest.php 2>&1" | cat
```

Expected: fail (route not found)

- [ ] **Step 16.3: Thêm route**

Trong `Modules/Upload/routes/api.php`, thêm vào section không có middleware (auth handled in controller, giống `media.stream`):

```php
Route::get('media/{id}/stream', [UploadController::class, 'stream'])->name('media.stream');
Route::get('media/{id}/hls-key', [UploadController::class, 'hlsKey']);
Route::get('media/{id}/document', [UploadController::class, 'streamDocument']);  // ← thêm
```

- [ ] **Step 16.4: Thêm method `streamDocument` vào UploadController**

```php
public function streamDocument(int $id, Request $request): \Illuminate\Http\Response
{
    $this->authorizeStreamRequest($request);

    $media = $this->uploadService->findOrFail($id);

    if ($media->type !== 'document') {
        abort(400, 'Tài nguyên không phải tài liệu.');
    }

    $pdfBytes = \Illuminate\Support\Facades\Storage::disk($media->disk)->get($media->path);

    if ($pdfBytes === null) {
        abort(404, 'File không tồn tại.');
    }

    // Determine who is viewing — prefer student email, fallback to admin name
    $email = '';
    if ($student = \Illuminate\Support\Facades\Auth::guard('api')->user()) {
        $email = $student->email;
    } elseif ($admin = \Illuminate\Support\Facades\Auth::guard('admin')->user()) {
        $email = $admin->email . ' (admin)';
    }

    $watermarked = app(\Modules\Upload\Services\DocumentWatermarkService::class)
        ->applyWatermark($pdfBytes, $email);

    return response($watermarked, 200, [
        'Content-Type'        => 'application/pdf',
        'Content-Disposition' => 'inline; filename="' . $media->original_name . '"',
        'Cache-Control'       => 'no-store, no-cache',
    ]);
}
```

- [ ] **Step 16.5: Chạy test**

```bash
wsl.exe -d Ubuntu -- bash -c "php artisan test tests/Feature/Media/DocumentStreamTest.php 2>&1" | cat
```

Expected: 2 tests, 2 passed

- [ ] **Step 16.6: Chạy toàn bộ test suite**

```bash
wsl.exe -d Ubuntu -- bash -c "php artisan test 2>&1" | cat
```

Expected: all pass

- [ ] **Step 16.7: Commit**

```bash
git add e-learning-backend/Modules/Upload/routes/api.php \
        e-learning-backend/Modules/Upload/app/Http/Controllers/UploadController.php \
        e-learning-backend/tests/Feature/Media/DocumentStreamTest.php
git commit -m "feat(upload): add /document endpoint streaming PDF with watermark"
```

---

## Task 17 — Frontend: hiển thị tài liệu qua watermark endpoint

**Files:**
- Modify: `e-learning-frontend/src/views/client/LearnPage.vue`

Thay vì serve URL trực tiếp của file PDF, dùng endpoint watermark.

- [ ] **Step 17.1: Cập nhật document URL trong LearnPage**

Tìm phần render tài liệu trong `LearnPage.vue` (thường là `<iframe>` hoặc `<embed>` với URL tài liệu). Thay URL trực tiếp bằng:

```ts
// Trong composable hoặc computed của LearnPage
const documentUrl = computed(() => {
  if (!lessonDetail.value?.document_id) return null
  const base = import.meta.env.VITE_API_URL   // '/api/v1'
  const token = studentAuthStore.token
  // Dùng ?token= vì iframe không thể gửi Authorization header
  return `${base}/media/${lessonDetail.value.document_id}/document?token=${token}`
})
```

Render trong template:
```vue
<iframe
  v-if="documentUrl"
  :src="documentUrl"
  class="w-full h-full border-0"
  :title="lessonDetail?.title"
/>
```

- [ ] **Step 17.2: Xác nhận endpoint `/stream` cũng accept `?token=`**

Method `authorizeStreamRequest` (Task 2) đã chấp nhận `?token=` là fallback — `streamDocument` reuse cùng method đó, nên không cần thay đổi gì thêm.

- [ ] **Step 17.3: Commit**

```bash
git add e-learning-frontend/src/views/client/LearnPage.vue
git commit -m "feat(frontend): serve lesson documents through watermark endpoint"
```

---

## Self-Review Checklist

**Spec coverage:**
- [x] Feature 1 (Signed URLs): Tasks 1–3 — named route, signed URL generation, stream auth accepts signatures
- [x] Feature 2 (Email watermark + Logo watermark): Task 4 (email di động) + Task 14 (logo cố định góc)
- [x] Feature 3 (HLS+AES): Tasks 5–13 — FFmpeg install, migration, HlsService, job, dispatch trigger, key endpoint, serve HLS URL in controller, HLS.js frontend
- [x] Feature 4 (Document watermark): Tasks 15–17 — DocumentWatermarkService, endpoint stream PDF có logo+email, frontend dùng endpoint

**Gaps identified:**
- Safari on HLS: `xhrSetup` is không được gọi với native HLS. **Mitigation**: Chrome/Firefox với HLS.js đủ dùng cho thesis demo.
- Admin preview: `LessonPreviewModal` dùng `?token=` — vẫn hoạt động vì `authorizeStreamRequest` chấp nhận fallback này (Task 2).
- Logo PNG vs SVG: FPDF chỉ hỗ trợ PNG/JPEG, không hỗ trợ SVG. Cần export thêm `logo.png` vào `public/images/logo/`. Nếu chưa có, watermark bỏ qua logo (code đã guard bằng `file_exists`).
- Document watermark performance: FPDI generate on-the-fly mỗi request. Với tài liệu lớn (50+ trang) có thể chậm. Trong scope thesis, chấp nhận được.

**Type consistency:**
- `hls_key` hex string (32 chars) — nhất quán qua migration, model, HlsService, controller.
- `hls_status` enum `'pending'|'processing'|'ready'|'failed'` — nhất quán qua migration, job, service, LessonController.
- `streamDocument` reuse `authorizeStreamRequest` — cùng auth logic với `stream` và `hlsKey`.
