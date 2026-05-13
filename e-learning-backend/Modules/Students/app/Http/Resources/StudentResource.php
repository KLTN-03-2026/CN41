<?php

namespace Modules\Students\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'avatar' => $this->avatar,
            'date_of_birth' => $this->date_of_birth?->format('Y-m-d'),
            'email_verified_at' => $this->email_verified_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),

            // Chỉ trả về khi được eager load (show detail)
            'enrolled_courses' => $this->whenLoaded('enrolledCourses', function () {
                return $this->enrolledCourses->map(fn ($c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                    'slug' => $c->slug,
                    'thumbnail' => $c->thumbnail,
                    'price' => $c->price,
                    'sale_price' => $c->sale_price,
                    'enrolled_at' => $c->pivot->enrolled_at,
                ]);
            }),
            'orders_count' => $this->whenLoaded('orders', fn () => $this->orders->count()),
            'total_spent' => $this->whenLoaded('orders', fn () => (float) $this->orders->where('status', 'paid')->sum('total_amount')
            ),
        ];
    }
}
