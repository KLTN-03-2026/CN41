<?php

namespace Modules\Students\Models;

use App\Traits\HasActivityLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Modules\Auth\Notifications\StudentResetPasswordNotification;
use Modules\Course\Models\Course;
use Modules\Payment\Models\Order;

class Student extends Authenticatable
{
    use HasActivityLog, HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $table = 'students';

    protected $guard_name = 'api';

    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'date_of_birth',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'date_of_birth' => 'date',
        'password' => 'hashed',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Gửi notification đặt lại mật khẩu.
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new StudentResetPasswordNotification($token));
    }

    /**
     * Các khóa học đã đăng ký (qua bảng pivot students_course).
     */
    public function enrolledCourses()
    {
        return $this->belongsToMany(
            Course::class,
            'students_course',
            'student_id',
            'course_id'
        )->withPivot('enrolled_at')->withTimestamps();
    }

    /**
     * Các đơn hàng của học viên.
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'student_id');
    }
}
