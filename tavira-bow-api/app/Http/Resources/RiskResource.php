<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Risk */
class RiskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category_id' => $this->category_id,
            'ref_no' => $this->ref_no,
            'name' => $this->name,
            'description' => $this->description,
            'tier' => $this->tier?->value,
            'owner_id' => $this->owner_id,
            'responsible_party_id' => $this->responsible_party_id,

            // Impact scores
            'financial_impact' => $this->financial_impact,
            'regulatory_impact' => $this->regulatory_impact,
            'reputational_impact' => $this->reputational_impact,
            'inherent_probability' => $this->inherent_probability,

            // Calculated scores
            'inherent_impact' => $this->inherent_impact,
            'inherent_risk_score' => $this->inherent_risk_score,
            'inherent_rag' => $this->inherent_rag?->value,
            'residual_risk_score' => $this->residual_risk_score,
            'residual_rag' => $this->residual_rag?->value,
            'appetite_status' => $this->appetite_status?->value,

            'monthly_update' => $this->monthly_update,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            // Relations
            'category' => new RiskCategoryResource($this->whenLoaded('category')),
            'owner' => new UserResource($this->whenLoaded('owner')),
            'responsible_party' => new UserResource($this->whenLoaded('responsibleParty')),
            'controls' => RiskControlResource::collection($this->whenLoaded('controls')),
            'actions' => RiskActionResource::collection($this->whenLoaded('actions')),
            'attachments' => RiskAttachmentResource::collection($this->whenLoaded('attachments')),
            'work_items' => WorkItemResource::collection($this->whenLoaded('workItems')),
            'governance_items' => GovernanceItemResource::collection($this->whenLoaded('governanceItems')),
        ];
    }
}
