<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\WorkItem */
class WorkItemResource extends JsonResource
{
    private const BAU_REVERSE = [
        'BAU' => 'bau',
        'Non BAU' => 'transformative',
    ];

    private function normalizeEnum(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return strtolower(str_replace(' ', '_', $value));
    }

    public function toArray(Request $request): array
    {
        $bauValue = $this->bau_or_transformative?->value;

        return [
            'id' => $this->id,
            'ref_no' => $this->ref_no,
            'type' => $this->type,
            'activity' => $this->activity,
            'department' => $this->department,
            'description' => $this->description,
            'bau_or_transformative' => isset(self::BAU_REVERSE[$bauValue]) ? self::BAU_REVERSE[$bauValue] : $this->normalizeEnum($bauValue),
            'impact_level' => $this->normalizeEnum($this->impact_level?->value),
            'current_status' => $this->normalizeEnum($this->current_status?->value),
            'rag_status' => $this->normalizeEnum($this->rag_status?->value),
            'deadline' => $this->deadline?->toDateString(),
            'completion_date' => $this->completion_date?->toDateString(),
            'monthly_update' => $this->monthly_update,
            'update_frequency' => $this->update_frequency?->value,
            'responsible_party_id' => $this->responsible_party_id,
            'tags' => $this->tags ?? [],
            'priority_item' => $this->priority_item,
            'file_path' => $this->file_path,
            'is_overdue' => $this->is_overdue,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            // Relations
            'responsible_party' => new UserResource($this->whenLoaded('responsibleParty')),
            'assignments' => TaskAssignmentResource::collection($this->whenLoaded('assignments')),
            'milestones' => TaskMilestoneResource::collection($this->whenLoaded('milestones')),
            'dependencies' => TaskDependencyResource::collection($this->whenLoaded('dependencies')),
        ];
    }
}
