<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\UserDepartmentPermission */
class UserDepartmentPermissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'department' => $this->department,
            'can_view' => $this->can_view,
            'can_edit_status' => $this->can_edit_status,
            'can_create_tasks' => $this->can_create_tasks,
            'can_edit_all' => $this->can_edit_all,
        ];
    }
}
