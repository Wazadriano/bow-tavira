<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskAssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'work_item_id' => $this->work_item_id,
            'user_id' => $this->user_id,
            'assignment_type' => $this->assignment_type?->value,
            'user' => new UserResource($this->whenLoaded('user')),
        ];
    }
}
