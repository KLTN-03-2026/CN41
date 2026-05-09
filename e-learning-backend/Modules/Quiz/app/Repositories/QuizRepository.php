<?php

namespace Modules\Quiz\Repositories;

use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Quiz\Models\Quiz;
use Modules\Quiz\Models\QuizAttempt;

class QuizRepository extends BaseRepository implements QuizRepositoryInterface
{
    public function __construct(Quiz $model)
    {
        parent::__construct($model);
    }

    public function getFiltered(array $filters, int $perPage): LengthAwarePaginator
    {
        $perPage = max(1, min($perPage, self::MAX_PER_PAGE));

        $query = $this->model->withCount('questions');

        if (! empty($filters['lesson_id'])) {
            $query->where('lesson_id', $filters['lesson_id']);
        }

        return $query->paginate($perPage);
    }

    public function findPublishedByLesson(int $lessonId): Quiz
    {
        return Quiz::where('lesson_id', $lessonId)
            ->published()
            ->with('questions')
            ->firstOrFail();
    }

    public function findPublishedWithQuestions(int $id): Quiz
    {
        return Quiz::where('status', 1)
            ->with('questions')
            ->findOrFail($id);
    }

    public function countStudentAttempts(int $quizId, int $studentId): int
    {
        return QuizAttempt::where('quiz_id', $quizId)
            ->where('student_id', $studentId)
            ->count();
    }

    public function createAttempt(array $data): QuizAttempt
    {
        return QuizAttempt::create($data);
    }

    public function getStudentAttempts(int $quizId, int $studentId): Collection
    {
        return QuizAttempt::with(['quiz.questions'])
            ->where('quiz_id', $quizId)
            ->where('student_id', $studentId)
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
