<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGovernanceItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ref_no' => 'required|string|max:50|unique:governance_items,ref_no',
            'activity' => 'nullable|string|max:100',
            'description' => 'required|string',
            'frequency' => 'nullable|string',
            'location' => 'nullable|string',
            'department' => 'required|string|max:100',
            'responsible_party_id' => 'nullable|exists:users,id',
            'current_status' => 'nullable|string',
            'rag_status' => 'nullable|string',
            'deadline' => 'nullable|date',
            'tags' => 'nullable|array',
        ];
    }
}
