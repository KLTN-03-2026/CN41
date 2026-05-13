<?php

namespace Modules\Lessons\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Upload\Resources\MediaFileResource;

class LessonResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'course_id' => $this->course_id,
            'section_id' => $this->section_id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'type' => $this->type,
            'content' => $this->content,
            'order' => $this->order,
            'is_preview' => $this->is_preview,
            'duration' => $this->duration,
            'status' => $this->status,
            'video' => new MediaFileResource($this->whenLoaded('video')),
            'document' => new MediaFileResource($this->whenLoaded('document')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
