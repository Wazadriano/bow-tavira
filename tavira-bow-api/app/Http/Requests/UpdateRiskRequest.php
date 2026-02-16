<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRiskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => 'sometimes|exists:risk_categories,id',
            'ref_no' => [
                'sometimes',
                'string',
                'max:50',
                Rule::unique('risks', 'ref_no')->ignore($this->route('risk')),
            ],
            'name' => 'sometimes|string|max:200',
            'description' => 'nullable|string',
            'tier' => 'nullable|string',
            'owner_id' => 'nullable|exists:users,id',
            'responsible_party_id' => 'nullable|exists:users,id',
            'financial_impact' => 'nullable|integer|min:1|max:5',
            'regulatory_impact' => 'nullable|integer|min:1|max:5',
            'reputational_impact' => 'nullable|integer|min:1|max:5',
            'inherent_probability' => 'nullable|integer|min:1|max:5',
            'monthly_update' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ];
    }
}
