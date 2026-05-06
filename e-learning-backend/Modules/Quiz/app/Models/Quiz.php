<?php

namespace Modules\Quiz\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Lessons\Models\Lesson;

class Quiz extends Model
{
    use SoftDeletes;

    protected $fillable = ['lesson_id', 'title', 'description', 'max_attempts', 'time_limit', 'status'];

    protected $casts = [
        'lesson_id' => 'integer',
        'max_attempts' => 'integer',
        'time_limit' => 'integer',
        'status' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(QuizQuestion::class)->orderBy('order');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function scopePublished($query)
    {
        return $query->where('status', 1);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
