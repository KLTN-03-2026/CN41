<?php

namespace Modules\Categories\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Bảng tương ứng trong database.
     */
    protected $table = 'categories';

    /**
     * Các cột được phép mass-assign.
     * TODO: Thêm các cột cần thiết.
     */
    protected $fillable = [
        //
    ];

    /**
     * Các cột cần cast kiểu dữ liệu.
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

// ── Relationships ──

}
