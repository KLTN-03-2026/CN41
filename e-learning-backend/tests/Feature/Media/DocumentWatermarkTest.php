<?php

namespace Tests\Feature\Media;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Upload\Services\DocumentWatermarkService;
use Tests\TestCase;

class DocumentWatermarkTest extends TestCase
{
    use RefreshDatabase;

    private function makeMinimalPdf(): string
    {
        $fpdf = new \FPDF();
        $fpdf->AddPage();
        $fpdf->SetFont('Arial', '', 14);
        $fpdf->Cell(0, 10, 'Test Document');
        return $fpdf->Output('S');
    }

    public function test_watermark_returns_pdf_bytes(): void
    {
        $pdf = $this->makeMinimalPdf();

        $service = app(DocumentWatermarkService::class);
        $result = $service->applyWatermark($pdf, 'student@example.com');

        $this->assertStringStartsWith('%PDF', $result);
        $this->assertGreaterThan(100, strlen($result));
    }

    public function test_watermark_accepts_empty_email(): void
    {
        $pdf = $this->makeMinimalPdf();

        $service = app(DocumentWatermarkService::class);
        $result = $service->applyWatermark($pdf, '');

        $this->assertStringStartsWith('%PDF', $result);
    }
}
