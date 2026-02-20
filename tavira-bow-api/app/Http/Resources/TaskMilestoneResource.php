<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\TaskMilestone */
class TaskMilestoneResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'work_item_id' => $this->work_item_id,
            'title' => $this->title,
            'description' => $this->description,
            'due_date' => $this->due_date,
            'completion_date' => $this->completion_date,
            'rag_status' => $this->rag_status,
            'order' => $this->order,
            'is_completed' => $this->is_completed,
            'is_overdue' => $this->is_overdue,
            'assignments' => MilestoneAssignmentResource::collection($this->whenLoaded('assignments')),
        ];
    }
}
