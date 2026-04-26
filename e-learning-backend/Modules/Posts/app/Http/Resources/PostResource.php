<?php

namespace Modules\Posts\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'content' => $this->content,
            'thumbnail' => $this->thumbnail,
            'thumbnail_url' => $this->thumbnail ? asset('storage/' . $this->thumbnail) : null, // Giả sử dùng storage local
            'author' => [
                'id' => $this->author->id,
                'name' => $this->author->name,
            ],
            'category' => new PostCategoryResource($this->whenLoaded('category')),
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'is_published' => $this->is_published,
            'published_at' => $this->published_at,
            'views' => $this->views,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
