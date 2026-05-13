<?php

namespace Modules\Course\Models;

use App\Traits\HasActivityLog;
use App\Traits\ScopesToTeacher;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Categories\Models\Category;
use Modules\Lessons\Models\Lesson;
use Modules\Lessons\Models\Section;
use Modules\Students\Models\Student;
use Modules\Teachers\Models\Teachers;

class Course extends Model
{
    use HasActivityLog, HasFactory, ScopesToTeacher, SoftDeletes;

    /**
     * Bảng tương ứng trong database.
     */
    protected $table = 'courses';

    /**
     * Các cột được phép mass-assign.
     */
    protected $fillable = [
        'teacher_id',
        'name',
        'slug',
        'description',
        'thumbnail',
        'price',
        'sale_price',
        'level',
        'total_lessons',
        'total_students',
        'rating',
        'status',
    ];

    /**
     * Các cột cần cast kiểu dữ liệu.
     */
    protected $casts = [
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'rating' => 'float',
        'total_lessons' => 'integer',
        'total_students' => 'integer',
        'status' => 'integer',
        'teacher_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::deleting(function (Course $course) {
            if (! $course->isForceDeleting()) {
                $course->sections()->each(fn (Section $section) => $section->delete());
                Lesson::where('course_id', $course->id)->each(fn (Lesson $lesson) => $lesson->delete());
            }
        });

        static::forceDeleting(function (Course $course) {
            $course->categories()->detach();
            Lesson::withTrashed()->where('course_id', $course->id)->each(fn (Lesson $lesson) => $lesson->forceDelete());
            Section::withTrashed()->where('course_id', $course->id)->each(fn (Section $section) => $section->forceDelete());
        });

        static::restoring(function (Course $course) {
            Section::withTrashed()->where('course_id', $course->id)->each(fn (Section $section) => $section->restore());
            Lesson::withTrashed()->where('course_id', $course->id)->each(fn (Lesson $lesson) => $lesson->restore());
        });
    }

    public function scopePublished($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Course thuộc về một Teacher.
     */
    public function teacher()
    {
        return $this->belongsTo(Teachers::class, 'teacher_id');
    }

    /**
     * Course thuộc nhiều Categories (many-to-many qua categories_courses).
     */
    public function categories()
    {
        return $this->belongsToMany(
            Category::class,
            'categories_courses',
            'course_id',
            'category_id'
        );
    }

    /**
     * Course có nhiều Students đã enroll (many-to-many qua students_course).
     */
    public function students()
    {
        return $this->belongsToMany(
            Student::class,
            'students_course',
            'course_id',
            'student_id'
        )->withPivot('enrolled_at')->withTimestamps();
    }

    /**
     * Course có nhiều Sections chứa Lessons
     */
    public function sections()
    {
        return $this->hasMany(Section::class, 'course_id')->ordered();
    }

    public function lessons()
    {
        return $this->hasManyThrough(Lesson::class, Section::class, 'course_id', 'section_id');
    }
}
