<?php

namespace Modules\Quiz\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Quiz\Http\Requests\StoreQuizRequest;
use Modules\Quiz\Http\Requests\UpdateQuizRequest;
use Modules\Quiz\Http\Resources\QuizQuestionResource;
use Modules\Quiz\Http\Resources\QuizResource;
use Modules\Quiz\Models\Quiz;
use Modules\Quiz\Repositories\QuizRepositoryInterface;
use Modules\Quiz\Services\GeminiQuizService;

class AdminQuizController extends Controller
{
    use ApiResponse;

    public function __construct(
        private QuizRepositoryInterface $repository,
        private GeminiQuizService $geminiService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min($request->get('per_page', 15), 100));
        $lessonId = $request->get('lesson_id');

        $query = Quiz::query();
        if ($lessonId) {
            $query->where('lesson_id', $lessonId);
        }

        $quizzes = $query->paginate($perPage);

        return $this->paginated(
            QuizResource::collection($quizzes),
            'Danh sách quiz'
        );
    }

    public function store(StoreQuizRequest $request): JsonResponse
    {
        $quiz = $this->repository->create($request->validated());

        return $this->success(
            new QuizResource($quiz),
            'Tạo quiz thành công',
            201
        );
    }

    public function show(int $id): JsonResponse
    {
        $quiz = $this->repository->findOrFail($id);

        return $this->success(
            new QuizResource($quiz),
            'Chi tiết quiz'
        );
    }

    public function update(UpdateQuizRequest $request, int $id): JsonResponse
    {
        $quiz = $this->repository->update($id, $request->validated());

        return $this->success(
            new QuizResource($quiz),
            'Cập nhật quiz thành công'
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $this->repository->delete($id);

        return $this->success(null, 'Xóa quiz thành công');
    }

    public function generate(Request $request, int $id): JsonResponse
    {
        $quiz = $this->repository->findOrFail($id);
        $quiz->load('lesson');

        $context = $quiz->lesson->title;
        if ($quiz->lesson->description) {
            $context .= '. '.$quiz->lesson->description;
        }

        if ($request->filled('custom_prompt')) {
            $context .= ' '.$request->input('custom_prompt');
        }

        $count = min((int) $request->get('count', 5), 10);
        $questions = $this->geminiService->generateQuestions($context, $count);

        DB::transaction(function () use ($quiz, $questions) {
            $quiz->questions()->delete();
            foreach ($questions as $index => $q) {
                $q['order'] = $index;
                $quiz->questions()->create($q);
            }
        });

        return $this->success(
            QuizQuestionResource::collection($quiz->fresh()->questions),
            'Đã sinh câu hỏi thành công'
        );
    }

    public function toggleStatus(int $id): JsonResponse
    {
        $quiz = Quiz::findOrFail($id);
        $quiz->update(['status' => ! $quiz->status]);

        return $this->success(
            new QuizResource($quiz),
            'Cập nhật trạng thái thành công'
        );
    }
}
