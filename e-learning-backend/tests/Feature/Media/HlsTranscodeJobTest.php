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
    use HasAdminUser, RefreshDatabase;

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

        try {
            TranscodeToHlsJob::dispatch($media->id); // sync in tests
        } catch (\RuntimeException $e) {
            // Expected
        }

        $this->assertDatabaseHas('media_files', [
            'id' => $media->id,
            'hls_status' => 'failed',
        ]);
    }
}
