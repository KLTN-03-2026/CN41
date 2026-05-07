<?php

namespace Modules\Quiz\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
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
                $pdfText = $this->extractPdfText($fullPath);
            } else {
                $pdfText = $this->extractChapterPdfText($lesson);
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

        } catch (\Exception $e) {
            Log::error('GenerateQuizJob failed', ['job_record_id' => $this->jobRecordId, 'error' => $e->getMessage()]);
            $jobRecord->update([
                'status' => 'failed',
                'error' => $this->friendlyError($e->getMessage()),
            ]);
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

    // ── PDF helpers (copy từ QuizGenerateController) ──────────────────────────

    private function extractPdfText(string $filePath): string
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

    private function extractChapterPdfText(Lesson $lesson): string
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

            $text = $this->extractPdfText($fullPath);
            if ($text) {
                $allText .= "\n\n--- Document: {$doc['name']} ---\n".$text;
            }
        }

        return $allText;
    }

    private function getChapterDocuments(Lesson $lesson): array
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
}
