<?php

namespace Modules\Lessons\Models;

use App\Traits\ScopesToTeacher;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Course\Models\Course;
use Modules\Upload\Models\MediaFile;

class Lesson extends Model
{
    use HasFactory, ScopesToTeacher, SoftDeletes;

    /**
     * Bảng tương ứng trong database.
     */
    protected $table = 'lessons';

    /**
     * Các cột được phép mass-assign.
     */
    protected $fillable = [
        'course_id',
        'section_id',
        'title',
        'slug',
        'description',
        'type',
        'content',
        'video_id',
        'document_id',
        'order',
        'is_preview',
        'duration',
        'status',
    ];

    /**
     * Các cột cần cast kiểu dữ liệu.
     */
    protected $casts = [
        'is_preview' => 'boolean',
        'status' => 'integer',
        'order' => 'integer',
        'duration' => 'integer',
        'course_id' => 'integer',
        'section_id' => 'integer',
        'video_id' => 'integer',
        'document_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function scopePublished($query)
    {
        return $query->where('status', 1);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order', 'asc');
    }

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function section()
    {
        return $this->belongsTo(Section::class, 'section_id');
    }

    public function progresses()
    {
        return $this->hasMany(LessonProgress::class, 'lesson_id');
    }

    public function video()
    {
        return $this->belongsTo(MediaFile::class, 'video_id');
    }

    public function document()
    {
        return $this->belongsTo(MediaFile::class, 'document_id');
    }
}
