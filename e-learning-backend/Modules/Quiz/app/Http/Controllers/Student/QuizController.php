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
use Modules\Quiz\Models\Quiz;
use Modules\Quiz\Models\QuizAttempt;

class QuizController extends Controller
{
    use ApiResponse;

    public function show(int $lessonId): JsonResponse
    {
        $quiz = Quiz::where('lesson_id', $lessonId)->published()->firstOrFail();
        $quiz->load('questions');

        $data = [
            'quiz' => new QuizResource($quiz),
            'questions' => QuizQuestionResource::collection($quiz->questions)->map(fn ($q) => new QuizQuestionResource($q->resource, true)),
        ];

        return $this->success($data, 'Chi tiết quiz');
    }

    public function submit(SubmitQuizRequest $request, int $id): JsonResponse
    {
        $quiz = Quiz::findOrFail($id);
        $student = auth('api')->user();

        // Check max attempts
        $attemptCount = QuizAttempt::where('quiz_id', $id)
            ->where('student_id', $student->id)
            ->count();

        if ($attemptCount >= $quiz->max_attempts) {
            return $this->error('Bạn đã hết lượt làm bài.', 403);
        }

        $quiz->load('questions');
        $answers = $request->input('answers');

        // Calculate score
        $score = 0;
        foreach ($answers as $questionId => $answer) {
            $question = $quiz->questions->firstWhere('id', $questionId);
            if ($question && strtoupper($answer) === $question->correct_option) {
                $score++;
            }
        }

        // Save attempt
        $attempt = DB::transaction(function () use ($quiz, $student, $score, $answers) {
            return QuizAttempt::create([
                'quiz_id' => $quiz->id,
                'student_id' => $student->id,
                'score' => $score,
                'total_questions' => $quiz->questions->count(),
                'answers' => array_map(fn ($a) => strtoupper($a), $answers),
                'completed_at' => now(),
            ]);
        });

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

        $quiz = Quiz::with('questions')->findOrFail($id);

        $attempts = QuizAttempt::with(['quiz.questions'])
            ->where('quiz_id', $id)
            ->where('student_id', $student->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->success(
            QuizAttemptResource::collection($attempts),
            'Lịch sử làm bài'
        );
    }
}
