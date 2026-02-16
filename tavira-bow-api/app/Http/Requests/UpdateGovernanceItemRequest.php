<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGovernanceItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ref_no' => [
                'sometimes',
                'string',
                'max:50',
                Rule::unique('governance_items', 'ref_no')->ignore($this->route('item')),
            ],
            'activity' => 'nullable|string|max:100',
            'description' => 'sometimes|string',
            'frequency' => 'nullable|string',
            'location' => 'nullable|string',
            'department' => 'sometimes|string|max:100',
            'responsible_party_id' => 'nullable|exists:users,id',
            'current_status' => 'nullable|string',
            'rag_status' => 'nullable|string',
            'deadline' => 'nullable|date',
            'completion_date' => 'nullable|date',
            'monthly_update' => 'nullable|string',
            'tags' => 'nullable|array',
        ];
    }
}
