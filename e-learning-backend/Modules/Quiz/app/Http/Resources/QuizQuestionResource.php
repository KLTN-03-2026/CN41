<?php

namespace Modules\Quiz\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuizQuestionResource extends JsonResource
{
    private bool $hideCorrectOption = false;

    public function __construct($resource, bool $hideCorrectOption = false)
    {
        parent::__construct($resource);
        $this->hideCorrectOption = $hideCorrectOption;
    }

    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'quiz_id' => $this->quiz_id,
            'question' => $this->question,
            'option_a' => $this->option_a,
            'option_b' => $this->option_b,
            'option_c' => $this->option_c,
            'option_d' => $this->option_d,
            'order' => $this->order,
        ];

        if (! $this->hideCorrectOption) {
            $data['correct_option'] = $this->correct_option;
        }

        return $data;
    }
}
