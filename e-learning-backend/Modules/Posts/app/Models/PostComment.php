<?php

namespace Modules\Posts\Models;

use Modules\Users\Models\User;
use Illuminate\Database\Eloquent\Model;
use Modules\Students\Models\Student;

class PostComment extends Model
{
    protected $fillable = [
        'post_id',
        'user_id',
        'user_type',
        'content',
        'parent_id',
        'is_approved',
    ];

    protected $casts = [
        'is_approved' => 'boolean',
    ];

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * Lấy user (admin) nếu user_type = 'admin'
     */
    public function adminUser()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Lấy student nếu user_type = 'student'
     */
    public function student()
    {
        return $this->belongsTo(Student::class, 'user_id');
    }

    /**
     * Accessor: trả về commenter dù là admin hay student
     */
    public function getCommenterAttribute()
    {
        if ($this->user_type === 'student') {
            return $this->student;
        }

        return $this->adminUser;
    }

    public function parent()
    {
        return $this->belongsTo(PostComment::class, 'parent_id');
    }

    public function replies()
    {
        return $this->hasMany(PostComment::class, 'parent_id');
    }
}
