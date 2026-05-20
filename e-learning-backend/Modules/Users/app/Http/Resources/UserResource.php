<?php

namespace Modules\Users\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'avatar' => $this->avatar,
            'status' => $this->status,
            'email_verified_at' => $this->email_verified_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'deleted_at' => $this->deleted_at?->toISOString(),
            'roles' => $this->whenLoaded('roles', fn () => $this->roles->map(fn ($r) => ['id' => $r->id, 'name' => $r->name])),
            'permissions' => $this->whenLoaded('permissions', fn () => $this->permissions->pluck('name')),
        ];
    }
}
