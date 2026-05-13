<?php

namespace Modules\Lessons\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Course\Models\Course;
use Modules\Students\Models\Student;

class LessonProgress extends Model
{
    use HasFactory;

    /**
     * Bảng tương ứng trong database.
     */
    protected $table = 'lesson_progress';

    /**
     * Các cột được phép mass-assign.
     */
    protected $fillable = [
        'student_id',
        'lesson_id',
        'course_id',
        'is_completed',
        'watched_seconds',
        'completed_at',
    ];

    /**
     * Các cột cần cast kiểu dữ liệu.
     */
    protected $casts = [
        'is_completed' => 'boolean',
        'watched_seconds' => 'integer',
        'completed_at' => 'datetime',
        'student_id' => 'integer',
        'lesson_id' => 'integer',
        'course_id' => 'integer',
    ];

    public function lesson()
    {
        return $this->belongsTo(Lesson::class, 'lesson_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }
}
