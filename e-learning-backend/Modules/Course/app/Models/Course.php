<?php

namespace Modules\Course\Models;

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
    use HasFactory, SoftDeletes;

    /**
     * Bảng tương ứng trong database.
     */
    protected $table = 'courses';

    /**
     * Các cột được phép mass-assign.
     */
    protected $fillable = [
        'teacher_id',
        'category_id',
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
        'category_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // ── Cascade delete ──

    /**
     * Đăng ký model events để cascade soft/force delete xuống sections & lessons.
     */
    protected static function booted(): void
    {
        // Soft delete Course → soft delete tất cả Sections + Lessons thuộc course
        static::deleting(function (Course $course) {
            if (! $course->isForceDeleting()) {
                // Soft delete sections
                $course->sections()->each(fn (Section $section) => $section->delete());

                // Soft delete lessons (qua section_id, bao gồm cả lesson chưa có section)
                Lesson::where('course_id', $course->id)->each(fn (Lesson $lesson) => $lesson->delete());
            }
        });

        // Force delete Course → force delete vĩnh viễn tất cả Sections + Lessons (kể cả đã soft-deleted)
        static::forceDeleting(function (Course $course) {
            // Force delete lessons trước (FK section_id)
            Lesson::withTrashed()->where('course_id', $course->id)->each(fn (Lesson $lesson) => $lesson->forceDelete());

            // Force delete sections
            Section::withTrashed()->where('course_id', $course->id)->each(fn (Section $section) => $section->forceDelete());
        });

        // Restore Course → restore tất cả Sections + Lessons đã bị soft-delete cùng thời điểm
        static::restoring(function (Course $course) {
            Section::withTrashed()->where('course_id', $course->id)->each(fn (Section $section) => $section->restore());
            Lesson::withTrashed()->where('course_id', $course->id)->each(fn (Lesson $lesson) => $lesson->restore());
        });
    }

    // ── Scopes ──

    /**
     * Scope: chỉ lấy courses đã published (status = 1).
     */
    public function scopePublished($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Scope: alias cho published.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    // ── Relationships ──

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

    /**
     * Course có nhiều Lessons (phẳng) qua section. Tạm thời có thể giữ lại nếu logic cũ cần.
     */
    public function lessons()
    {
        return $this->hasManyThrough(Lesson::class, Section::class, 'course_id', 'section_id');
    }
}
