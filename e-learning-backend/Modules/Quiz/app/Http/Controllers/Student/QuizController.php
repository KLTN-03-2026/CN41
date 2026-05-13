<?php

namespace Modules\Quiz\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Modules\Quiz\Http\Requests\SubmitQuizRequest;
use Modules\Quiz\Http\Resources\QuizAttemptResource;
use Modules\Quiz\Http\Resources\QuizQuestionResource;
use Modules\Quiz\Http\Resources\QuizResource;
use Modules\Quiz\Repositories\QuizRepositoryInterface;

class QuizController extends Controller
{
    use ApiResponse;

    public function __construct(private QuizRepositoryInterface $repository) {}

    public function show(int $lessonId): JsonResponse
    {
        $quiz = $this->repository->findPublishedByLesson($lessonId);

        return $this->success([
            'quiz' => new QuizResource($quiz),
            'questions' => $quiz->questions->map(fn ($q) => new QuizQuestionResource($q, true)),
        ], 'Chi tiết quiz');
    }

    public function submit(SubmitQuizRequest $request, int $id): JsonResponse
    {
        $quiz = $this->repository->findPublishedWithQuestions($id);
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
        $student = auth('api')->user();

        $this->repository->findOrFail($id);

        return $this->success(
            QuizAttemptResource::collection(
                $this->repository->getStudentAttempts($id, $student->id)
            ),
            'Lịch sử làm bài'
        );
    }
}
