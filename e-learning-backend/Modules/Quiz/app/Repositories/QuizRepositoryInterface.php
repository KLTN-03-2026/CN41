<?php

namespace Modules\Quiz\Repositories;

use App\Repositories\RepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Quiz\Models\Quiz;
use Modules\Quiz\Models\QuizAttempt;

interface QuizRepositoryInterface extends RepositoryInterface
{
    public function getFiltered(array $filters, int $perPage): LengthAwarePaginator;

    public function findPublishedByLesson(int $lessonId): Quiz;

    public function findPublishedWithQuestions(int $id): Quiz;

    public function countStudentAttempts(int $quizId, int $studentId): int;

    public function createAttempt(array $data): QuizAttempt;

    public function getStudentAttempts(int $quizId, int $studentId): Collection;
}
