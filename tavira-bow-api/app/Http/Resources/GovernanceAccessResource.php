<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GovernanceAccessResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'can_view' => $this->can_view,
            'can_edit' => $this->can_edit,
            'user' => new UserResource($this->whenLoaded('user')),
        ];
    }
}
