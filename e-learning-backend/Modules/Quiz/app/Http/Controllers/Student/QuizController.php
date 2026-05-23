<?php

namespace Modules\Quiz\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Modules\Course\Repositories\CourseRepositoryInterface;
use Modules\Quiz\Http\Requests\SubmitQuizRequest;
use Modules\Quiz\Http\Resources\QuizAttemptResource;
use Modules\Quiz\Http\Resources\QuizQuestionResource;
use Modules\Quiz\Http\Resources\QuizResource;
use Modules\Quiz\Repositories\QuizRepositoryInterface;

class QuizController extends Controller
{
    use ApiResponse;

    public function __construct(
        private QuizRepositoryInterface $repository,
        private CourseRepositoryInterface $courseRepository
    ) {}

    private function checkAccess($quiz): void
    {
        $quiz->loadMissing('lesson');
        $lesson = $quiz->lesson;

        if (! $lesson || ! $lesson->is_preview) {
            $student = auth('api')->user();
            if (! $student) {
                abort(401, 'Vui lòng đăng nhập để xem nội dung này.');
            }
            if (! $this->courseRepository->isEnrolled($lesson->course_id, $student->id)) {
                abort(403, 'Bạn phải đăng ký khóa học này để làm bài kiểm tra.');
            }
        }
    }

    public function show(int $lessonId): JsonResponse
    {
        $quiz = $this->repository->findPublishedByLesson($lessonId);
        $this->checkAccess($quiz);

        return $this->success([
            'quiz' => new QuizResource($quiz),
            'questions' => $quiz->questions->map(fn ($q) => new QuizQuestionResource($q, true)),
        ], 'Chi tiết quiz');
    }

    public function submit(SubmitQuizRequest $request, int $id): JsonResponse
    {
        $quiz = $this->repository->findPublishedWithQuestions($id);
        $this->checkAccess($quiz);
        $student = auth('api')->user();

        $attemptCount = $this->repository->countStudentAttempts($id, $student->id);
        if ($attemptCount >= $quiz->max_attempts) {
            return $this->error('Bạn đã hết lượt làm bài.', 403);
        }

        $answers = $request->input('answers');
        $score = 0;
        foreach ($answers as $questionId => $answer) {
            $question = $quiz->questions->firstWhere('id', $questionId);
            if ($question && strtoupper($answer) === $question->correct_option) {
                $score++;
            }
        }

        $attempt = DB::transaction(fn () => $this->repository->createAttempt([
            'quiz_id' => $quiz->id,
            'student_id' => $student->id,
            'score' => $score,
            'total_questions' => $quiz->questions->count(),
            'answers' => array_map(fn ($a) => strtoupper($a), $answers),
            'completed_at' => now(),
        ]));

        $attempt->load(['quiz.questions']);

        return $this->success(
            new QuizAttemptResource($attempt),
            'Nộp bài thành công',
            201
        );
    }

    public function attempts(int $id): JsonResponse
    {
        $quiz = $this->repository->findOrFail($id);
        $this->checkAccess($quiz);
        $student = auth('api')->user();

        return $this->success(
            QuizAttemptResource::collection(
                $this->repository->getStudentAttempts($id, $student->id)
            ),
            'Lịch sử làm bài'
        );
    }
}
