<?php

namespace Modules\Quiz\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuizAttemptResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'quiz_id' => $this->quiz_id,
            'student_id' => $this->student_id,
            'score' => $this->score,
            'total_questions' => $this->total_questions,
            'percentage' => round(($this->score / $this->total_questions) * 100),
            'answers' => $this->answers,
            'completed_at' => $this->completed_at,
            'created_at' => $this->created_at,
        ];
    }
}
