<?php

namespace Modules\Quiz\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuizResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'lesson_id' => $this->lesson_id,
            'title' => $this->title,
            'description' => $this->description,
            'max_attempts' => $this->max_attempts,
            'time_limit' => $this->time_limit,
            'status' => $this->status,
            'questions_count' => $this->questions_count ?? $this->questions->count(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
