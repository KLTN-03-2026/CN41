<?php

namespace Modules\Commission\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Payment\Models\OrderItem;
use Modules\Teachers\Models\Teachers;

class TeacherEarning extends Model
{
    protected $table = 'teacher_earnings';

    protected $fillable = ['teacher_id', 'order_item_id', 'type', 'amount', 'commission_rate', 'description'];

    protected $casts = ['amount' => 'decimal:2', 'commission_rate' => 'decimal:2'];

    public function teacher()
    {
        return $this->belongsTo(Teachers::class, 'teacher_id');
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }
}
