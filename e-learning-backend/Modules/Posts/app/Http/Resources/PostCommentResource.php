<?php

namespace Modules\Posts\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostCommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'content' => $this->content,
            'is_approved' => $this->is_approved,
            'commenter' => [
                'id' => $this->commenter->id,
                'name' => $this->commenter->name,
                'avatar' => $this->commenter->avatar_url ?? ($this->commenter->avatar ?? null),
                'type' => $this->user_type,
            ],
            'post' => [
                'id' => $this->post->id,
                'title' => $this->post->title,
            ],
            'parent_id' => $this->parent_id,
            'replies' => PostCommentResource::collection($this->whenLoaded('replies')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
