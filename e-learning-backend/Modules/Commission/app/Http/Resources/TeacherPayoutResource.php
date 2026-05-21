<?php

namespace Modules\Commission\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TeacherPayoutResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'teacher_id' => $this->teacher_id,
            'teacher_name' => $this->whenLoaded('teacher', fn () => $this->teacher->name),
            'bank_name' => $this->whenLoaded('teacher', fn () => $this->teacher->bank_name),
            'bank_account_number' => $this->whenLoaded('teacher', fn () => $this->teacher->bank_account_number),
            'bank_account_name' => $this->whenLoaded('teacher', fn () => $this->teacher->bank_account_name),
            'amount' => $this->amount,
            'status' => $this->status,
            'teacher_note' => $this->teacher_note,
            'admin_note' => $this->admin_note,
            'processed_at' => $this->processed_at?->toDateTimeString(),
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}
