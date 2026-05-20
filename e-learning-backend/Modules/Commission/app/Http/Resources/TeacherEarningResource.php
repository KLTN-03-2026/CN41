<?php

namespace Modules\Commission\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TeacherEarningResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'              => $this->id,
            'type'            => $this->type,
            'amount'          => $this->amount,
            'commission_rate' => $this->commission_rate,
            'description'     => $this->description,
            'created_at'      => $this->created_at->toDateTimeString(),
        ];
    }
}
