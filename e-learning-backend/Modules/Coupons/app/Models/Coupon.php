<?php

namespace Modules\Coupons\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Coupon extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'coupons';

    protected $fillable = [
        'code',
        'type',
        'value',
        'min_order_value',
        'max_discount',
        'usage_limit',
        'used_count',
        'start_date',
        'end_date',
        'status',
        'description',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'min_order_value' => 'decimal:2',
        'max_discount' => 'decimal:2',
        'usage_limit' => 'integer',
        'used_count' => 'integer',
        'status' => 'integer',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function scopeValid($query)
    {
        return $query->active()
            ->where(function ($q) {
                $q->whereNull('start_date')->orWhere('start_date', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', now());
            })
            ->where(function ($q) {
                $q->whereNull('usage_limit')->orWhereColumn('used_count', '<', 'usage_limit');
            });
    }

    public function isValid(): bool
    {
        if ($this->status !== 1) {
            return false;
        }
        if ($this->start_date && $this->start_date->isFuture()) {
            return false;
        }
        if ($this->end_date && $this->end_date->isPast()) {
            return false;
        }
        if ($this->usage_limit !== null && $this->used_count >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    public function calculateDiscount(float $subtotal): float
    {
        if (! $this->isValid()) {
            return 0;
        }

        // Kiểm tra giá trị đơn hàng tối thiểu
        if ($this->min_order_value && $subtotal < (float) $this->min_order_value) {
            return 0;
        }

        if ($this->type === 'fixed') {
            $discount = (float) $this->value;
        } else {
            // percentage
            $discount = $subtotal * (float) $this->value / 100;

            // Áp dụng max_discount nếu có
            if ($this->max_discount && $discount > (float) $this->max_discount) {
                $discount = (float) $this->max_discount;
            }
        }

        // Không giảm quá subtotal
        return min($discount, $subtotal);
    }
}
