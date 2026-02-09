<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RiskThemePermissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'theme_id' => $this->theme_id,
            'can_view' => $this->can_view,
            'can_edit' => $this->can_edit,
            'can_create' => $this->can_create,
            'can_delete' => $this->can_delete,
        ];
    }
}
