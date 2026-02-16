<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSupplierRequest extends FormRequest
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
                Rule::unique('suppliers', 'ref_no')->ignore($this->route('supplier')),
            ],
            'name' => 'sometimes|string|max:200',
            'sage_category_id' => 'nullable|exists:sage_categories,id',
            'responsible_party_id' => 'nullable|exists:users,id',
            'location' => 'nullable|string',
            'is_common_provider' => 'sometimes|boolean',
            'status' => 'nullable|string',
            'notes' => 'nullable|string',
            'entities' => 'nullable|array',
            'entities.*' => 'string',
        ];
    }
}
