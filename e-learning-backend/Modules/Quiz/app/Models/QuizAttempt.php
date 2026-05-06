<?php

namespace Modules\Quiz\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Students\Models\Student;

class QuizAttempt extends Model
{
    protected $fillable = ['quiz_id', 'student_id', 'score', 'total_questions', 'answers', 'completed_at'];

    protected $casts = [
        'quiz_id' => 'integer',
        'student_id' => 'integer',
        'score' => 'integer',
        'total_questions' => 'integer',
        'answers' => 'array',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
