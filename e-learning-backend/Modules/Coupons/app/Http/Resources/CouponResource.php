<?php

namespace Modules\Coupons\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CouponResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'code'            => $this->code,
            'type'            => $this->type,
            'value'           => $this->value,
            'min_order_value' => $this->min_order_value,
            'max_discount'    => $this->max_discount,
            'usage_limit'     => $this->usage_limit,
            'used_count'      => $this->used_count,
            'remaining'       => $this->usage_limit !== null
                                    ? max(0, $this->usage_limit - $this->used_count)
                                    : null,
            'start_date'      => $this->start_date?->toISOString(),
            'end_date'        => $this->end_date?->toISOString(),
            'status'          => $this->status,
            'is_expired'      => $this->end_date && $this->end_date->isPast(),
            'is_valid'        => $this->isValid(),
            'description'     => $this->description,
            'created_at'      => $this->created_at->toISOString(),
            'updated_at'      => $this->updated_at->toISOString(),
        ];
    }
}
