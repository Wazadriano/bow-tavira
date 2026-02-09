<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RiskCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'theme_id' => $this->theme_id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'order' => $this->order,
            'is_active' => $this->is_active,
            'risks_count' => $this->risks_count ?? $this->risks->count(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            // Relations
            'theme' => new RiskThemeResource($this->whenLoaded('theme')),
            'risks' => RiskResource::collection($this->whenLoaded('risks')),
        ];
    }
}
