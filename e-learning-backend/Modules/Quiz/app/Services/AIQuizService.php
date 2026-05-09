<?php

namespace Modules\Quiz\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Lessons\Models\Lesson;

class AIQuizService
{
    private string $apiKey;

    private string $apiUrl;

    private string $fallbackUrl;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.key', '');
        $this->apiUrl = config('services.gemini.url');
        $this->fallbackUrl = config('services.gemini.fallback_url');
    }

    /**
     * Sinh câu hỏi từ context text (tên/mô tả bài học).
     */
    public function generateQuestions(string $context, int $count = 5): array
    {
        $prompt = $this->buildContextPrompt($context, $count);

        return $this->callAndParse($prompt);
    }

    /**
     * Sinh câu hỏi từ nội dung PDF đã extract thành text.
     */
    public function generateFromPdfText(string $pdfText, int $count = 5, string $lessonContext = ''): array
    {
        if (! $this->apiKey) {
            throw new \Exception('GEMINI_API_KEY chưa được cấu hình.');
        }

        // ~20,000 chars — gemini-2.0-flash supports large context
        $truncated = mb_substr($pdfText, 0, 20000);

        $contextHint = $lessonContext
            ? "Bài học này thuộc chủ đề: {$lessonContext}. Hãy tập trung vào các khái niệm quan trọng nhất của chủ đề này.\n\n"
            : '';

        $prompt = <<<PROMPT
Bạn là một chuyên gia biên soạn câu hỏi trắc nghiệm cho hệ thống e-learning. Nhiệm vụ của bạn là tạo ra {$count} câu hỏi trắc nghiệm chất lượng cao dựa trên nội dung tài liệu bên dưới.

{$contextHint}Nội dung tài liệu:
---
{$truncated}
---

Yêu cầu bắt buộc:
1. Tất cả câu hỏi và đáp án PHẢI bằng tiếng Việt
2. Mỗi câu hỏi có đúng 4 lựa chọn (A, B, C, D), chỉ 1 đáp án đúng
3. Câu hỏi phải bám sát nội dung tài liệu, KHÔNG được bịa đặt thông tin ngoài tài liệu
4. Phân bổ cấp độ tư duy đa dạng:
   - Nhận biết (20%): hỏi về định nghĩa, khái niệm, sự kiện cụ thể trong tài liệu
   - Thông hiểu (50%): hỏi về ý nghĩa, giải thích, so sánh các khái niệm
   - Vận dụng (30%): hỏi về áp dụng kiến thức vào tình huống thực tế
5. Các đáp án sai (nhiễu) phải hợp lý, liên quan đến chủ đề, KHÔNG được quá dễ loại trừ
6. Câu hỏi phải rõ ràng, không mơ hồ, không có 2 đáp án đều đúng

Chỉ trả về JSON array hợp lệ (không có markdown, không có giải thích), theo đúng định dạng sau:
[
  {
    "question": "...",
    "option_a": "...",
    "option_b": "...",
    "option_c": "...",
    "option_d": "...",
    "correct_option": "A"
  }
]
PROMPT;

        return $this->callAndParse($prompt);
    }

    // ── PDF helpers ───────────────────────────────────────────────────────────

    public function extractPdfTextFromPath(string $filePath): string
    {
        try {
            if (shell_exec('which pdftotext 2>/dev/null')) {
                $escaped = escapeshellarg($filePath);

                return shell_exec("pdftotext {$escaped} - 2>/dev/null") ?? '';
            }

            return $this->extractPdfTextRaw($filePath);
        } catch (\Throwable) {
            return '';
        }
    }

    public function extractChapterPdfText(Lesson $lesson): string
    {
        $documents = $this->getChapterDocuments($lesson);
        if (empty($documents)) {
            return '';
        }

        $allText = '';
        foreach ($documents as $doc) {
            if (empty($doc['path'])) {
                continue;
            }

            $fullPath = Storage::disk($doc['disk'] ?? 'public')->path($doc['path']);
            if (! file_exists($fullPath)) {
                continue;
            }

            $text = $this->extractPdfTextFromPath($fullPath);
            if ($text) {
                $allText .= "\n\n--- Document: {$doc['name']} ---\n".$text;
            }
        }

        return $allText;
    }

    public function getChapterDocuments(Lesson $lesson): array
    {
        $query = Lesson::where('course_id', $lesson->course_id)
            ->where('type', 'document')
            ->with('document')
            ->whereNotNull('document_id');

        if ($lesson->section_id) {
            $query->where('section_id', $lesson->section_id);
        }

        return $query->get()
            ->filter(fn ($l) => $l->document && str_contains($l->document->mime_type ?? '', 'pdf'))
            ->map(fn ($l) => [
                'id' => $l->document->id,
                'name' => $l->title,
                'path' => $l->document->path,
                'disk' => $l->document->disk,
                'url' => $l->document->url,
            ])
            ->values()
            ->toArray();
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function buildContextPrompt(string $context, int $count): string
    {
        return <<<PROMPT
Bạn là một chuyên gia biên soạn câu hỏi trắc nghiệm cho hệ thống e-learning. Tạo ra {$count} câu hỏi trắc nghiệm về chủ đề sau:

Chủ đề: {$context}

Yêu cầu bắt buộc:
1. Tất cả câu hỏi và đáp án PHẢI bằng tiếng Việt
2. Mỗi câu hỏi có đúng 4 lựa chọn (A, B, C, D), chỉ 1 đáp án đúng
3. Phân bổ cấp độ tư duy đa dạng:
   - Nhận biết: hỏi về định nghĩa, khái niệm cơ bản
   - Thông hiểu: hỏi về ý nghĩa, giải thích nguyên lý
   - Vận dụng: hỏi về áp dụng vào tình huống thực tế
4. Các đáp án sai phải hợp lý, liên quan đến chủ đề, không quá dễ loại trừ
5. Câu hỏi phải cụ thể, rõ ràng, kiến thức đúng về chuyên môn

Chỉ trả về JSON array hợp lệ (không có markdown, không có giải thích), theo đúng định dạng sau:
[
  {
    "question": "...",
    "option_a": "...",
    "option_b": "...",
    "option_c": "...",
    "option_d": "...",
    "correct_option": "A"
  }
]
PROMPT;
    }

    private function callAndParse(string $prompt, bool $isFallback = false): array
    {
        if (! $this->apiKey) {
            throw new \Exception('GEMINI_API_KEY chưa được cấu hình.');
        }

        $url = $isFallback ? $this->fallbackUrl : $this->apiUrl;

        try {
            $response = Http::timeout(90)
                ->post("{$url}?key={$this->apiKey}", [
                    'contents' => [
                        ['parts' => [['text' => $prompt]]],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.4,
                        'maxOutputTokens' => 8192,
                    ],
                ]);
        } catch (ConnectionException $e) {
            throw new \Exception('Không thể kết nối đến máy chủ AI. Vui lòng kiểm tra mạng và thử lại.');
        }

        $status = $response->status();

        if ($status === 401 || $status === 403) {
            throw new \Exception('GEMINI_API_KEY không hợp lệ hoặc không có quyền truy cập.');
        }

        if ($status === 429 || $status === 503) {
            if (! $isFallback) {
                Log::warning("AI system overloaded (HTTP {$status}). Falling back to gemini-flash-lite-latest.");

                return $this->callAndParse($prompt, true);
            }
            throw new \Exception('Hệ thống AI đang bận (Rate Limit). Vui lòng thử lại sau vài giây.');
        }

        if ($response->failed()) {
            $errorMsg = $response->json('error.message', 'Không rõ nguyên nhân');
            Log::error('Gemini API error', ['status' => $status, 'message' => $errorMsg]);
            throw new \Exception("Lỗi từ hệ thống AI (HTTP {$status}): {$errorMsg}");
        }

        $text = $response->json('candidates.0.content.parts.0.text', '');

        if (empty($text)) {
            throw new \Exception('Hệ thống AI không trả về kết quả. Vui lòng thử lại.');
        }

        return $this->parseQuestions($text);
    }

    private function parseQuestions(string $response): array
    {
        $text = trim($response);

        // Strategy 1: extract JSON array directly via regex (most robust)
        if (preg_match('/\[[\s\S]*\]/u', $text, $matches)) {
            $json = $matches[0];
            $questions = json_decode($json, true);
            if (is_array($questions) && count($questions) > 0) {
                return $this->normalizeQuestions($questions);
            }
        }

        // Strategy 2: strip markdown code block manually as fallback
        $text = preg_replace('/^```(?:json)?\s*/i', '', $text);
        $text = preg_replace('/\s*```\s*$/', '', $text);
        $text = trim($text);

        $questions = json_decode($text, true);

        if (! is_array($questions) || count($questions) === 0) {
            Log::error('AI parse failed', ['raw' => substr($response, 0, 500)]);
            throw new \Exception('Không thể xử lý kết quả từ AI. Vui lòng thử lại.');
        }

        return $this->normalizeQuestions($questions);
    }

    private function normalizeQuestions(array $questions): array
    {
        return array_values(array_map(fn ($q, $i) => [
            'question' => $q['question'] ?? '',
            'option_a' => $q['option_a'] ?? '',
            'option_b' => $q['option_b'] ?? '',
            'option_c' => $q['option_c'] ?? '',
            'option_d' => $q['option_d'] ?? '',
            'correct_option' => strtoupper($q['correct_option'] ?? 'A'),
            'order' => $i,
        ], $questions, array_keys($questions)));
    }

    private function extractPdfTextRaw(string $filePath): string
    {
        $content = file_get_contents($filePath);
        if (! $content) {
            return '';
        }

        preg_match_all('/stream(.*?)endstream/si', $content, $matches);
        $text = '';
        foreach ($matches[1] as $stream) {
            $decompressed = @gzuncompress(ltrim($stream));
            if ($decompressed !== false) {
                $readable = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', ' ', $decompressed);
                $text .= ' '.$readable;
            }
        }

        return preg_replace('/\s+/', ' ', $text) ?? '';
    }
}
