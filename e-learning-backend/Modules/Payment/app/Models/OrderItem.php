<?php

namespace Modules\Payment\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Course\Models\Course;

class OrderItem extends Model
{
    protected $table = 'order_items';

    protected $fillable = [
        'order_id',
        'course_id',
        'price',
        'sale_price',
        'final_price',
    ];

    protected $casts = [
        'order_id' => 'integer',
        'course_id' => 'integer',
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'final_price' => 'decimal:2',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }
}
