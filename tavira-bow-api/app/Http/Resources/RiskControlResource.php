<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\RiskControl */
class RiskControlResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'risk_id' => $this->risk_id,
            'control_id' => $this->control_id,
            'implementation_status' => $this->implementation_status?->value,
            'effectiveness_score' => $this->effectiveness_score,
            'notes' => $this->notes,
            'last_tested_date' => $this->last_tested_date?->toDateString(),
            'next_test_date' => $this->next_test_date?->toDateString(),
            'is_effective' => $this->is_effective,
            'test_overdue' => $this->test_overdue,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            // Relations
            'control' => new ControlLibraryResource($this->whenLoaded('control')),
            'risk' => new RiskResource($this->whenLoaded('risk')),
        ];
    }
}
