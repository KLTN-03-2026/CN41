<?php

namespace Modules\Quiz\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuizAttemptResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $correctAnswers = null;
        if ($this->relationLoaded('quiz') && $this->quiz->relationLoaded('questions')) {
            $correctAnswers = $this->quiz->questions->pluck('correct_option', 'id');
        }

        return [
            'id' => $this->id,
            'quiz_id' => $this->quiz_id,
            'student_id' => $this->student_id,
            'score' => $this->score,
            'total_questions' => $this->total_questions,
            'percentage' => round(($this->score / $this->total_questions) * 100),
            'answers' => $this->answers,
            'correct_answers' => $correctAnswers,
            'completed_at' => $this->completed_at,
            'created_at' => $this->created_at,
        ];
    }
}
