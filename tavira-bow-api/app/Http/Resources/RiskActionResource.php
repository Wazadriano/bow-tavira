<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RiskActionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'risk_id' => $this->risk_id,
            'title' => $this->title,
            'description' => $this->description,
            'owner_id' => $this->owner_id,
            'status' => $this->status?->value,
            'priority' => $this->priority?->value,
            'due_date' => $this->due_date?->toDateString(),
            'completion_date' => $this->completion_date?->toDateString(),
            'notes' => $this->notes,
            'is_overdue' => $this->is_overdue,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            // Relations
            'owner' => new UserResource($this->whenLoaded('owner')),
            'risk' => new RiskResource($this->whenLoaded('risk')),
        ];
    }
}
