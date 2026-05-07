<?php

namespace Modules\Quiz\Models;

use Illuminate\Database\Eloquent\Model;

class QuizGenerationJob extends Model
{
    protected $table = 'quiz_generation_jobs';

    protected $fillable = ['lesson_id', 'status', 'payload', 'result', 'error'];

    protected $casts = [
        'payload' => 'array',
        'result' => 'array',
    ];
}
