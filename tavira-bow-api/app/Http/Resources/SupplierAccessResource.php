<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierAccessResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'can_view' => $this->can_view,
            'can_edit' => $this->can_edit,
            'can_create' => $this->can_create,
            'can_delete' => $this->can_delete,
            'user' => new UserResource($this->whenLoaded('user')),
        ];
    }
}
