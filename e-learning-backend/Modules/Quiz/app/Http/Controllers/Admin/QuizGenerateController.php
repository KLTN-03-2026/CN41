<?php

namespace Modules\Quiz\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Lessons\Models\Lesson;
use Modules\Quiz\Http\Resources\QuizQuestionResource;
use Modules\Quiz\Http\Resources\QuizResource;
use Modules\Quiz\Jobs\GenerateQuizJob;
use Modules\Quiz\Models\Quiz;
use Modules\Quiz\Models\QuizGenerationJob;
use Modules\Quiz\Models\QuizQuestion;
use Modules\Quiz\Services\AIQuizService;

class QuizGenerateController extends Controller
{
    use ApiResponse;

    public function __construct(private AIQuizService $aiService) {}

    /**
     * Lấy thông tin quiz của một lesson (tạo mới nếu chưa có).
     * GET /admin/lessons/{lessonId}/quiz
     */
    public function show(int $lessonId): JsonResponse
    {
        $lesson = Lesson::findOrFail($lessonId);
        $quiz = Quiz::where('lesson_id', $lessonId)->with('questions')->first();

        if (! $quiz) {
            return $this->success(null, 'Chưa có quiz cho bài học này.');
        }

        return $this->success([
            'quiz' => new QuizResource($quiz),
            'questions' => $quiz->questions->map(fn ($q) => new QuizQuestionResource($q)),
        ], 'Chi tiết quiz');
    }

    /**
     * Sinh câu hỏi quiz từ PDF upload hoặc PDF trong chương (async via queue).
     * POST /admin/lesson-quiz/{lessonId}/generate
     */
    public function generate(Request $request, int $lessonId): JsonResponse
    {
        $request->validate([
            'source' => 'required|in:upload,chapter',
            'count' => 'nullable|integer|min:1|max:20',
            'file' => 'required_if:source,upload|nullable|file|mimes:pdf|max:20480',
            'custom_prompt' => 'nullable|string|max:500',
            'max_attempts' => 'nullable|integer|min:1|max:10',
            'time_limit' => 'nullable|integer|min:1',
        ]);

        Lesson::findOrFail($lessonId);

        $tempPath = null;
        if ($request->input('source') === 'upload' && $request->hasFile('file')) {
            $tempPath = $request->file('file')->store('quiz-tmp', 'local');
        }

        $jobRecord = QuizGenerationJob::create([
            'lesson_id' => $lessonId,
            'status' => 'pending',
            'payload' => [
                'lesson_id' => $lessonId,
                'source' => $request->input('source'),
                'count' => min((int) $request->get('count', 5), 20),
                'custom_prompt' => $request->input('custom_prompt'),
                'max_attempts' => $request->get('max_attempts', 3),
                'time_limit' => $request->get('time_limit'),
                'temp_path' => $tempPath,
            ],
        ]);

        GenerateQuizJob::dispatch($jobRecord->id)->onQueue('ai');

        return $this->success(['job_id' => $jobRecord->id], 'Yêu cầu đã được nhận. Đang xử lý...', 202);
    }

    /**
     * Kiểm tra trạng thái job sinh câu hỏi AI.
     * GET /admin/lesson-quiz/jobs/{jobId}
     */
    public function jobStatus(int $jobId): JsonResponse
    {
        $job = QuizGenerationJob::findOrFail($jobId);

        if ($job->status === 'done') {
            $quiz = Quiz::where('lesson_id', $job->lesson_id)->with('questions')->first();

            return $this->success([
                'status' => 'done',
                'quiz' => new QuizResource($quiz),
                'questions' => $quiz->questions->map(fn ($q) => new QuizQuestionResource($q)),
            ], 'Sinh câu hỏi thành công');
        }

        if ($job->status === 'failed') {
            return $this->error($job->error ?? 'Sinh câu hỏi thất bại.', 422);
        }

        return $this->success(['status' => $job->status], 'Đang xử lý...');
    }

    /**
     * Cập nhật một câu hỏi (admin sửa sau khi AI sinh).
     * PATCH /admin/quiz-questions/{questionId}
     */
    public function updateQuestion(Request $request, int $questionId): JsonResponse
    {
        $request->validate([
            'question' => 'sometimes|string',
            'option_a' => 'sometimes|string',
            'option_b' => 'sometimes|string',
            'option_c' => 'sometimes|string',
            'option_d' => 'sometimes|string',
            'correct_option' => 'sometimes|in:A,B,C,D',
        ]);

        $question = QuizQuestion::findOrFail($questionId);
        $question->update($request->only(['question', 'option_a', 'option_b', 'option_c', 'option_d', 'correct_option']));

        return $this->success(new QuizQuestionResource($question), 'Cập nhật câu hỏi thành công');
    }

    /**
     * Xóa một câu hỏi.
     * DELETE /admin/quiz-questions/{questionId}
     */
    public function deleteQuestion(int $questionId): JsonResponse
    {
        QuizQuestion::findOrFail($questionId)->delete();

        return $this->success(null, 'Đã xóa câu hỏi');
    }

    /**
     * Lấy danh sách PDF trong chương để admin xem trước.
     * GET /admin/lessons/{lessonId}/chapter-pdfs
     */
    public function chapterPdfs(int $lessonId): JsonResponse
    {
        $lesson = Lesson::with(['section.lessons.document'])->findOrFail($lessonId);
        $pdfs = $this->aiService->getChapterDocuments($lesson);

        return $this->success($pdfs, 'Danh sách PDF trong chương');
    }
}
