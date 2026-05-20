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
            'thumbnail_url' => $this->thumbnail ? asset('storage/'.$this->thumbnail) : null,
            'author' => $this->whenLoaded('author', fn () => [
                'id' => $this->author->id,
                'name' => $this->author->name,
            ]),
            'category' => new PostCategoryResource($this->whenLoaded('category')),
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'comments' => PostCommentResource::collection($this->whenLoaded('comments')),
            'is_published' => $this->is_published,
            'approval_status' => $this->approval_status,
            'rejection_reason' => $this->rejection_reason,
            'published_at' => $this->published_at?->toISOString(),
            'views' => $this->views,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'deleted_at' => $this->deleted_at?->toISOString(),
        ];
    }
}
