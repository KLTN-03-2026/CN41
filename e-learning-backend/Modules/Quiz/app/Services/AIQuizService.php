<?php

namespace Modules\Quiz\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Lessons\Models\Lesson;
use Modules\Upload\Models\MediaFile;

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

        $truncated = mb_substr($pdfText, 0, 20000);
        $subject = $lessonContext ?: 'môn học này';

        // Request 40% extra to absorb questions that get filtered
        $requestCount = min(20, (int) ceil($count * 1.4));

        $prompt = <<<PROMPT
Bạn là giáo sư đang soạn đề thi cuối kỳ cho môn: {$subject}
Sinh viên sẽ làm bài thi KHÔNG có tài liệu, sách vở hay thiết bị gì.

════════════════════════════════════════
LUẬT BẤT DI BẤT DỊCH (đọc trước khi làm gì khác)
════════════════════════════════════════
Đề thi kiểm tra SỰ HIỂU BIẾT — không kiểm tra xem sinh viên có ĐỌC tài liệu không.

CẤM tuyệt đối các từ sau trong câu hỏi và đáp án:
  tài liệu · văn bản · bài học · bài giảng · chương · đề cập · nêu trên · như trên · như đã học · trong bài · theo bài · theo văn bản · gợi ý rằng · nhắc đến

CẤM các dạng câu hỏi:
  ✗ "... được đề cập trong ...?"
  ✗ "... tài liệu gợi ý ...?"
  ✗ "Nội dung chính của ... là gì?"
  ✗ "Điều nào sau đây được nhắc đến?"
  ✗ Bất kỳ câu hỏi nào cần đọc tài liệu gốc mới trả lời được

════════════════════════════════════════
PHONG CÁCH ĐỀ THI ĐÚNG
════════════════════════════════════════
Câu hỏi hỏi thẳng vào kiến thức — không nhắc nguồn gốc:

  ▸ Lập trình:
    "Đoạn code sau in ra gì?  nums=[1,2,3]; print(sum(nums)*len(nums))"
    "Độ phức tạp O() của Binary Search trong trường hợp trung bình là?"
    "Khi nào nên dùng HashMap thay vì ArrayList?"

  ▸ Thiết kế / Editing:
    "Kỹ thuật J-cut trong dựng phim đạt hiệu quả gì?"
    "Màu bổ túc (complementary) của màu đỏ trên color wheel là?"

  ▸ Kinh tế / Kinh doanh:
    "Khi lãi suất tăng từ 5% lên 8%, đầu tư tư nhân sẽ?"
    "Công ty có doanh thu 800tr, chi phí biến đổi 500tr, cố định 200tr — lợi nhuận là?"

  ▸ Ngoại ngữ / Kỹ năng:
    "Câu nào dùng Past Perfect đúng ngữ pháp?"
    "Kỹ thuật paraphrasing trong active listening dùng để làm gì?"

════════════════════════════════════════
NHIỆM VỤ
════════════════════════════════════════
Viết {$requestCount} câu hỏi trắc nghiệm. Phân bổ:
  • ~30% định nghĩa / khái niệm cơ bản
  • ~40% phân tích / so sánh / quan hệ nhân-quả
  • ~30% tình huống có số liệu hoặc code snippet cụ thể

Tiêu chuẩn:
  ✓ Đáp án đúng: rõ ràng, không tranh cãi
  ✓ 3 đáp án sai: hợp lý, liên quan, đủ để người chưa kỹ nhầm
  ✓ Thuật ngữ kỹ thuật (tên hàm, tool, framework): giữ nguyên tiếng Anh
  ✓ Phần còn lại: tiếng Việt học thuật

KIỂM TRA TRƯỚC KHI OUTPUT:
Đọc lại từng câu — nếu vi phạm luật trên → xóa và thay câu mới.

════════════════════════════════════════
[CHỈ ĐỂ THAM KHẢO NỘI BỘ — KHÔNG NHẮC ĐẾN TRONG CÂU HỎI]
════════════════════════════════════════
{$truncated}
════════════════════════════════════════

OUTPUT: Chỉ JSON array thuần, không markdown, không text khác.
[{"question":"...","option_a":"...","option_b":"...","option_c":"...","option_d":"...","correct_option":"A"}]
PROMPT;

        $all = $this->filterBannedPhrases($this->callAndParse($prompt));

        return array_slice($all, 0, $count);
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

    public function extractPdfTextByIds(array $pdfIds): string
    {
        $mediaFiles = MediaFile::whereIn('id', $pdfIds)->get();

        $allText = '';
        foreach ($mediaFiles as $file) {
            if (empty($file->path)) {
                continue;
            }

            $fullPath = Storage::disk($file->disk ?? 'public')->path($file->path);
            if (! file_exists($fullPath)) {
                continue;
            }

            $text = $this->extractPdfTextFromPath($fullPath);
            if ($text) {
                $name = basename($file->path);
                $allText .= "\n\n--- Document: {$name} ---\n".$text;
            }
        }

        return $allText;
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
[VAI TRÒ]
Bạn là giáo sư đại học đang soạn đề thi cuối kỳ cho môn: {$context}
Sinh viên làm bài thi không có tài liệu trước mặt.

[VIẾT {$count} CÂU HỎI TRẮC NGHIỆM]
Phân bổ: ~30% định nghĩa/khái niệm | ~40% phân tích/nhân-quả | ~30% tình huống số liệu

[LUẬT CỨNG]
CẤM từ: "tài liệu" | "bài học" | "chương" | "đề cập" | "nêu trên" | "theo bài"
CẤM hỏi về cấu trúc, bố cục, hay nguồn gốc kiến thức.
Hỏi thẳng vào kiến thức: "X là gì?" không phải "Theo bài học, X là gì?"

[TIÊU CHUẨN]
✓ Câu hỏi tự đứng được, không cần tài liệu gốc
✓ 3 đáp án sai đủ hợp lý, liên quan chủ đề
✓ Câu tình huống bắt buộc có số liệu cụ thể
✓ 100% tiếng Việt, học thuật

[OUTPUT — CHỈ JSON ARRAY, KHÔNG MARKDOWN]
[{"question":"...","option_a":"...","option_b":"...","option_c":"...","option_d":"...","correct_option":"A"}]
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

    private function filterBannedPhrases(array $questions): array
    {
        $banned = [
            'tài liệu', 'văn bản', 'bài học', 'bài giảng',
            'đề cập', 'nêu trên', 'như trên', 'như đã học',
            'trong bài', 'theo bài', 'theo văn bản', 'gợi ý rằng', 'nhắc đến',
        ];

        return array_values(array_filter($questions, function ($q) use ($banned) {
            $text = mb_strtolower($q['question']);
            foreach ($banned as $word) {
                if (str_contains($text, $word)) {
                    Log::warning('Filtered quiz question with banned phrase', [
                        'phrase' => $word,
                        'question' => $q['question'],
                    ]);

                    return false;
                }
            }

            return true;
        }));
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
