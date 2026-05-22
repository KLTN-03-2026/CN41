<?php

namespace Modules\Upload\Services;

class WatermarkPdf extends \setasign\Fpdi\Fpdi
{
    /** @var array<string, array{ca: float, CA: float, n?: int}> */
    private array $extGStates = [];

    public function setAlphaValue(float $alpha): void
    {
        $gsId = 'GS' . (count($this->extGStates) + 1);
        $this->extGStates[$gsId] = ['ca' => $alpha, 'CA' => $alpha];
        $this->_out("/{$gsId} gs");
    }

    /**
     * Rotate text output by writing raw PDF transform stream commands.
     * This emits a "q ... Q" save/restore block with a rotation matrix,
     * then places a text cell at the rotated position.
     *
     * @param  float  $angle  Rotation angle in degrees (counter-clockwise)
     * @param  float  $cx     Center X in mm (page coordinates)
     * @param  float  $cy     Center Y in mm (page coordinates)
     * @param  string $text   Text to render
     */
    public function rotatedText(float $angle, float $cx, float $cy, string $text): void
    {
        $rad = deg2rad($angle);
        $cosA = cos($rad);
        $sinA = sin($rad);

        // Convert mm to PDF user units (points at 72dpi, k = points/mm)
        $k = $this->k;
        $h = $this->h;

        // PDF coordinate system: y increases downward from bottom
        $x = $cx * $k;
        $y = ($h - $cy) * $k;

        // Save graphics state, apply transformation matrix, draw text, restore
        $this->_out('q');
        $this->_out(sprintf(
            '%.5F %.5F %.5F %.5F %.5F %.5F cm',
            $cosA,
            $sinA,
            -$sinA,
            $cosA,
            $x,
            $y
        ));

        // Draw the text cell at local origin (0,0) after transform
        $savedX = $this->x;
        $savedY = $this->y;
        $this->SetXY(0, 0);
        // Use internal _out to place text without moving the cursor permanently
        $txt = $this->_escape($text);
        $cellW = $this->GetStringWidth($text) + 2;
        $this->_out(sprintf(
            'BT /F%d %.2F Tf %.5F %.5F Td (%s) Tj ET',
            $this->CurrentFont['i'],
            $this->FontSizePt,
            -$cellW / 2 * $k,
            -$this->FontSize / 2 * $k,
            $txt
        ));

        $this->_out('Q');

        // Restore cursor
        $this->x = $savedX;
        $this->y = $savedY;
    }

    protected function _putresources(): void
    {
        // Write ExtGState objects first so we get their object numbers
        foreach ($this->extGStates as $id => &$state) {
            $this->_newobj();
            $state['n'] = $this->n;
            $this->_out('<</Type /ExtGState /ca ' . $state['ca'] . ' /CA ' . $state['CA'] . '>>');
            $this->_out('endobj');
        }
        unset($state);

        // Now call parent which writes fonts, images, then the resource dict
        parent::_putresources();
    }

    protected function _putresourcedict(): void
    {
        parent::_putresourcedict();
        if (! empty($this->extGStates)) {
            $this->_out('/ExtGState <<');
            foreach ($this->extGStates as $id => $state) {
                $this->_out("/{$id} {$state['n']} 0 R");
            }
            $this->_out('>>');
        }
    }
}

class DocumentWatermarkService
{
    public function applyWatermark(string $pdfContent, string $email): string
    {
        $tmpIn = tempnam(sys_get_temp_dir(), 'wm_in_') . '.pdf';
        file_put_contents($tmpIn, $pdfContent);

        try {
            $pdf = new WatermarkPdf();
            $pageCount = $pdf->setSourceFile($tmpIn);

            for ($i = 1; $i <= $pageCount; $i++) {
                $tpl = $pdf->importPage($i);
                $size = $pdf->getTemplateSize($tpl);

                $orientation = ($size['width'] > $size['height']) ? 'L' : 'P';
                $pdf->AddPage($orientation, [$size['width'], $size['height']]);
                $pdf->useTemplate($tpl, 0, 0, $size['width'], $size['height']);

                // Logo top-right (PNG only — fpdf does not support SVG)
                $logoPng = public_path('images/logo/logo.png');
                if (file_exists($logoPng)) {
                    $logoW = 28;
                    $pdf->Image($logoPng, $size['width'] - $logoW - 8, 6, $logoW, 0, 'PNG');
                }

                // Email diagonal watermark
                if ($email !== '') {
                    $pdf->SetFont('Helvetica', '', 11);
                    $pdf->SetTextColor(180, 180, 180);
                    $pdf->setAlphaValue(0.25);

                    $cx = $size['width'] / 2;
                    $cy = $size['height'] / 2;

                    $pdf->rotatedText(45, $cx, $cy, $email);

                    $pdf->setAlphaValue(1.0);
                }
            }

            return $pdf->Output('S');
        } finally {
            @unlink($tmpIn);
        }
    }
}
