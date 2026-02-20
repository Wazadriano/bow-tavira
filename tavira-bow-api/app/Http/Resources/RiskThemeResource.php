<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\RiskTheme */
class RiskThemeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'board_appetite' => $this->board_appetite,
            'order' => $this->order,
            'is_active' => $this->is_active,
            'categories_count' => $this->categories_count ?? $this->categories->count(),
            'risk_count' => $this->when(isset($this->risk_count), $this->risk_count),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            // Relations
            'categories' => RiskCategoryResource::collection($this->whenLoaded('categories')),
            'permissions' => RiskThemePermissionResource::collection($this->whenLoaded('permissions')),
        ];
    }
}
