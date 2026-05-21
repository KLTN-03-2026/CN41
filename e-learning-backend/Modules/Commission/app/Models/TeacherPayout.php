<?php

namespace Modules\Commission\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Teachers\Models\Teachers;

class TeacherPayout extends Model
{
    protected $table = 'teacher_payouts';

    protected $fillable = ['teacher_id', 'amount', 'status', 'teacher_note', 'admin_note', 'processed_at'];

    protected $casts = ['amount' => 'decimal:2', 'processed_at' => 'datetime'];

    public function teacher()
    {
        return $this->belongsTo(Teachers::class, 'teacher_id');
    }
}
