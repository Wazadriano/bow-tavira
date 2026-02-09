<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GovernanceItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ref_no' => $this->ref_no,
            'activity' => $this->activity,
            'description' => $this->description,
            'frequency' => $this->frequency?->value,
            'location' => $this->location?->value,
            'department' => $this->department,
            'responsible_party_id' => $this->responsible_party_id,
            'current_status' => $this->current_status?->value,
            'rag_status' => $this->rag_status?->value,
            'deadline' => $this->deadline?->toDateString(),
            'completion_date' => $this->completion_date?->toDateString(),
            'monthly_update' => $this->monthly_update,
            'tags' => $this->tags ?? [],
            'file_path' => $this->file_path,
            'is_overdue' => $this->is_overdue,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            // Relations
            'responsible_party' => new UserResource($this->whenLoaded('responsibleParty')),
            'milestones' => GovernanceMilestoneResource::collection($this->whenLoaded('milestones')),
            'attachments' => GovernanceAttachmentResource::collection($this->whenLoaded('attachments')),
            'access' => GovernanceAccessResource::collection($this->whenLoaded('access')),
        ];
    }
}
