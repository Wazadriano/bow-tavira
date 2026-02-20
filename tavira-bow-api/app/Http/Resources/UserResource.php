<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\User */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'full_name' => $this->full_name,
            'role' => $this->role,
            'is_active' => $this->is_active,
            'department' => $this->primary_department,
            'has_2fa' => $this->two_factor_confirmed_at !== null,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            // Relations
            'department_permissions' => UserDepartmentPermissionResource::collection(
                $this->whenLoaded('departmentPermissions')
            ),
            'risk_theme_permissions' => RiskThemePermissionResource::collection(
                $this->whenLoaded('riskThemePermissions')
            ),
        ];
    }
}
