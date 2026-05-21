<?php

namespace Modules\Teachers\Models;

use App\Traits\HasActivityLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Course\Models\Course;
use Modules\Users\Models\User;

class Teachers extends Model
{
    use HasActivityLog, HasFactory, SoftDeletes;

    /**
     * Bảng tương ứng trong database.
     */
    protected $table = 'teachers';

    /**
     * Các cột được phép mass-assign.
     */
    protected $fillable = [
        'user_id',
        'name',
        'date_of_birth',
        'slug',
        'description',
        'exp',
        'image',
        'status',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
    ];

    /**
     * Các cột cần cast kiểu dữ liệu.
     */
    protected $casts = [
        'user_id' => 'integer',
        'exp' => 'float',
        'status' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
    // ── Scopes ──

    /**
     * Scope: chỉ lấy teachers đang active.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
    // ── Relationships ──

    /**
     * Teacher liên kết với một tài khoản User.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Teacher có nhiều Course.
     */
    public function courses()
    {
        return $this->hasMany(Course::class, 'teacher_id');
    }
}
