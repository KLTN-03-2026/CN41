<?php

namespace Tests\Feature\Media;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Modules\Upload\Models\MediaFile;
use Modules\Upload\Services\DocumentWatermarkService;
use Modules\Users\Models\User;
use Tests\TestCase;
use Tests\Traits\HasAdminUser;

class DocumentStreamTest extends TestCase
{
    use HasAdminUser, RefreshDatabase;

    private function makePdfMedia(int $uploadedBy): MediaFile
    {
        $fpdf = new \FPDF;
        $fpdf->AddPage();
        $fpdf->SetFont('Arial', '', 14);
        $fpdf->Cell(0, 10, 'Test');
        $pdfBytes = $fpdf->Output('S');

        $path = 'documents/test.pdf';
        Storage::disk('local')->put($path, $pdfBytes);

        return MediaFile::create([
            'disk' => 'local',
            'type' => 'document',
            'original_name' => 'test.pdf',
            'path' => $path,
            'url' => '',
            'mime_type' => 'application/pdf',
            'size' => strlen($pdfBytes),
            'status' => 'ready',
            'uploaded_by' => $uploadedBy,
        ]);
    }

    public function test_admin_can_stream_document_with_watermark(): void
    {
        $admin = $this->setupAdmin();
        $media = $this->makePdfMedia($admin->id);

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
        // Create user record for FK, but do NOT call actingAs — request stays unauthenticated
        $user = User::forceCreate([
            'name' => 'No Auth User',
            'email' => 'noauth@test.com',
            'password' => 'password',
        ]);

        $media = MediaFile::create([
            'disk' => 'local',
            'type' => 'document',
            'original_name' => 'test.pdf',
            'path' => 'documents/test_unauth.pdf',
            'url' => '',
            'mime_type' => 'application/pdf',
            'size' => 100,
            'status' => 'ready',
            'uploaded_by' => $user->id,
        ]);

        $response = $this->get("/api/v1/media/{$media->id}/document");

        $response->assertStatus(401);
    }

    public function test_returns_400_for_non_document_media(): void
    {
        $admin = $this->setupAdmin();

        $media = MediaFile::create([
            'disk' => 'local',
            'type' => 'video',
            'original_name' => 'test.mp4',
            'path' => 'videos/test.mp4',
            'url' => '',
            'mime_type' => 'video/mp4',
            'size' => 1024,
            'status' => 'ready',
            'uploaded_by' => $admin->id,
        ]);

        $response = $this->get("/api/v1/media/{$media->id}/document");

        $response->assertStatus(400);
    }
}
