<?php

namespace Modules\Quiz\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuizAttemptResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $correctAnswers = null;
        $questions = null;

        if ($this->relationLoaded('quiz') && $this->quiz->relationLoaded('questions')) {
            $correctAnswers = $this->quiz->questions->pluck('correct_option', 'id');
            $questions = $this->quiz->questions->map(fn ($q) => [
                'id' => $q->id,
                'question' => $q->question,
                'option_a' => $q->option_a,
                'option_b' => $q->option_b,
                'option_c' => $q->option_c,
                'option_d' => $q->option_d,
                'order' => $q->order,
            ])->values();
        }

        // Cast về object để JSON luôn trả về {"questionId": "A"} thay vì array []
        // PHP decode JSON integer-key thành int array, array_map giữ key dạng int
        // → JSON encode lại thành [] mất key nếu không ép object
        $answersObj = $this->answers ? (object) array_combine(
            array_map('strval', array_keys((array) $this->answers)),
            array_values((array) $this->answers)
        ) : null;

        return [
            'id' => $this->id,
            'quiz_id' => $this->quiz_id,
            'student_id' => $this->student_id,
            'score' => $this->score,
            'total_questions' => $this->total_questions,
            'percentage' => round(($this->score / $this->total_questions) * 100),
            'answers' => $answersObj,
            'correct_answers' => $correctAnswers,
            'questions' => $questions,
            'completed_at' => $this->completed_at,
            'created_at' => $this->created_at,
        ];
    }
}
