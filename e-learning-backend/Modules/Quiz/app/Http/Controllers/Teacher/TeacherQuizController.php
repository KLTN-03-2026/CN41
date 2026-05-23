<?php

namespace Modules\Quiz\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Modules\Lessons\Models\Lesson;
use Modules\Quiz\Http\Requests\GenerateQuizRequest;
use Modules\Quiz\Http\Requests\UpdateQuizQuestionRequest;
use Modules\Quiz\Http\Resources\QuizQuestionResource;
use Modules\Quiz\Http\Resources\QuizResource;
use Modules\Quiz\Jobs\GenerateQuizJob;
use Modules\Quiz\Models\Quiz;
use Modules\Quiz\Models\QuizGenerationJob;
use Modules\Quiz\Models\QuizQuestion;
use Modules\Quiz\Services\AIQuizService;

class TeacherQuizController extends Controller
{
    use ApiResponse;

    public function __construct(private AIQuizService $aiService) {}

    /**
     * Lấy thông tin quiz của một lesson thuộc giảng viên (tự động scoped).
     * GET /teacher/lesson-quiz/{lessonId}
     */
    public function show(int $lessonId): JsonResponse
    {
        // ScopesToTeacher trên Lesson tự động chặn nếu không phải bài học của mình
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
     * Sinh câu hỏi quiz từ tài liệu của giảng viên (tự động scoped).
     * POST /teacher/lesson-quiz/{lessonId}/generate
     */
    public function generate(GenerateQuizRequest $request, int $lessonId): JsonResponse
    {
        // ScopesToTeacher check ownership
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
                'pdf_ids' => $request->input('pdf_ids', []),
            ],
        ]);

        GenerateQuizJob::dispatch($jobRecord->id)->onQueue('ai');

        return $this->success(['job_id' => $jobRecord->id], 'Yêu cầu đã được nhận. Đang xử lý...', 202);
    }

    /**
     * Kiểm tra trạng thái job sinh câu hỏi AI cho giảng viên.
     * GET /teacher/lesson-quiz/jobs/{jobId}
     */
    public function jobStatus(int $jobId): JsonResponse
    {
        $job = QuizGenerationJob::findOrFail($jobId);

        // Bảo mật: check xem bài học có thuộc quyền sở hữu của giảng viên hay không
        Lesson::findOrFail($job->lesson_id);

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
     * Giảng viên cập nhật một câu hỏi trong quiz của mình.
     * PATCH /teacher/quiz-questions/{questionId}
     */
    public function updateQuestion(UpdateQuizQuestionRequest $request, int $questionId): JsonResponse
    {
        $question = QuizQuestion::findOrFail($questionId);

        // Bảo mật: check ownership qua Quiz -> Lesson
        $quiz = Quiz::findOrFail($question->quiz_id);
        Lesson::findOrFail($quiz->lesson_id);

        $question->update($request->only(['question', 'option_a', 'option_b', 'option_c', 'option_d', 'correct_option']));

        return $this->success(new QuizQuestionResource($question), 'Cập nhật câu hỏi thành công');
    }

    /**
     * Giảng viên xóa câu hỏi trong quiz của mình.
     * DELETE /teacher/quiz-questions/{questionId}
     */
    public function deleteQuestion(int $questionId): JsonResponse
    {
        $question = QuizQuestion::findOrFail($questionId);

        // Bảo mật: check ownership qua Quiz -> Lesson
        $quiz = Quiz::findOrFail($question->quiz_id);
        Lesson::findOrFail($quiz->lesson_id);

        $question->delete();

        return $this->success(null, 'Đã xóa câu hỏi');
    }

    /**
     * Lấy danh sách PDF trong chương thuộc giảng viên.
     * GET /teacher/lesson-quiz/{lessonId}/chapter-pdfs
     */
    public function chapterPdfs(int $lessonId): JsonResponse
    {
        // ScopesToTeacher check ownership
        $lesson = Lesson::with(['section.lessons.document'])->findOrFail($lessonId);
        $pdfs = $this->aiService->getChapterDocuments($lesson);

        return $this->success($pdfs, 'Danh sách PDF trong chương');
    }
}
