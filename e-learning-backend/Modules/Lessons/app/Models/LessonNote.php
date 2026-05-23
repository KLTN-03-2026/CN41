<?php

namespace Modules\Lessons\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Students\Models\Student;

class LessonNote extends Model
{
    protected $table = 'lesson_notes';

    protected $fillable = [
        'student_id',
        'lesson_id',
        'content',
        'timestamp_seconds',
    ];

    protected $casts = [
        'student_id' => 'integer',
        'lesson_id' => 'integer',
        'timestamp_seconds' => 'integer',
    ];

    public function lesson()
    {
        return $this->belongsTo(Lesson::class, 'lesson_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
}
