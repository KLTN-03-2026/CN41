<?php

namespace Modules\Teachers\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeacherResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->whenLoaded('user', fn () => $this->user->email),
            'slug' => $this->slug,
            'description' => $this->description,
            'exp' => $this->exp,
            'image' => $this->image
                                ? (str_starts_with($this->image, 'http') || str_starts_with($this->image, '/storage')
                                    ? $this->image
                                    : '/storage/'.$this->image)
                                : null,
            'status' => $this->status,
            'courses' => $this->whenLoaded('courses', function () {
                return $this->courses->map(fn ($course) => [
                    'id' => $course->id,
                    'name' => $course->name,
                    'slug' => $course->slug,
                    'thumbnail' => $course->thumbnail
                        ? (str_starts_with($course->thumbnail, 'http') || str_starts_with($course->thumbnail, '/storage')
                            ? $course->thumbnail
                            : asset('storage/'.$course->thumbnail))
                        : null,
                    'price' => $course->price,
                    'sale_price' => $course->sale_price,
                ]);
            }),
            'courses_count' => $this->whenCounted('courses'),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
