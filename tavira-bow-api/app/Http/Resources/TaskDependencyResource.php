<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskDependencyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'work_item_id' => $this->work_item_id,
            'depends_on_id' => $this->depends_on_id,
            'dependency_type' => $this->dependency_type,
            'depends_on' => new WorkItemResource($this->whenLoaded('dependsOn')),
        ];
    }
}
