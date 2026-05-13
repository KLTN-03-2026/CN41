<?php

namespace Modules\Categories\Models;

use App\Traits\HasActivityLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kalnoy\Nestedset\NodeTrait;
use Modules\Course\Models\Course;

class Category extends Model
{
    use HasActivityLog, HasFactory, NodeTrait, SoftDeletes;

    protected $table = 'categories';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'status',
        'order',
        'parent_id',
    ];

    protected $casts = [
        'status' => 'integer',
        'order' => 'integer',
        'parent_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $hidden = [
        '_lft',
        '_rgt',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function getIsRootAttribute(): bool
    {
        return is_null($this->parent_id);
    }

    public function courses()
    {
        return $this->belongsToMany(
            Course::class,
            'categories_courses',
            'category_id',
            'course_id'
        );
    }
}
