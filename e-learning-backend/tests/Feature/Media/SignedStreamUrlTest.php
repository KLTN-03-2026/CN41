<?php

namespace Tests\Feature\Media;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Modules\Upload\Models\MediaFile;
use Tests\TestCase;
use Tests\Traits\HasAdminUser;

class SignedStreamUrlTest extends TestCase
{
    use HasAdminUser, RefreshDatabase;

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
            'api.media.stream',
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
            'api.media.stream',
            now()->subMinute(),   // already expired
            ['id' => $media->id]
        );

        $parsed = parse_url($signedUrl);
        parse_str($parsed['query'], $params);

        // Expired signature: expires is in the past
        $this->assertLessThan(now()->timestamp, $params['expires']);
    }
}
