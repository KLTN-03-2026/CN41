<?php

namespace Modules\Users\Models;

use App\Traits\HasActivityLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Modules\Teachers\Models\Teachers;
use Modules\Users\Database\Factories\UserFactory;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasActivityLog, HasApiTokens, HasFactory, HasRoles, Notifiable, SoftDeletes;

    protected static function newFactory()
    {
        return UserFactory::new();
    }

    /**
     * Bảng tương ứng trong database.
     */
    protected $table = 'users';

    /**
     * Các cột được phép mass-assign.
     * TODO: Thêm các cột cần thiết.
     */

    /**
     * Guard name
     */
    protected $guard_name = 'admin';

    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'status',
    ];

    // Các cột cần ẩn
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Các cột cần cast kiểu dữ liệu.
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * User có một hồ sơ Teacher (nếu có role teacher).
     */
    public function teacher()
    {
        return $this->hasOne(Teachers::class, 'user_id');
    }

    // ── Relationships ──

}
